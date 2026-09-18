<?php

use App\Catalog\Models\Product;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Livewire\StockAdjustment;
use App\Inventory\Models\StockMovement;
use App\Inventory\Services\InventoryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('registra un ajuste y muestra el historial', function () {
    $product = Product::factory()->create();
    $merchant = userWithRole('merchant_admin');

    $this->actingAs($merchant)->get(route('admin.stock.adjust', $product))->assertOk();

    Livewire::actingAs($merchant)
        ->test(StockAdjustment::class, ['product' => $product])
        ->set('type', 'purchase')
        ->set('quantity', '5')
        ->set('reason', 'Reposición')
        ->call('adjust')
        ->assertHasNoErrors()
        ->assertSet('quantity', '')
        ->assertSee('stock actual 5')
        ->assertSee('Reposición')
        ->assertSee($merchant->name);

    expect($product->fresh()->stock_on_hand)->toBe(5)
        ->and(StockMovement::query()->sole()->user_id)->toBe($merchant->id);
});

it('muestra el error de stock insuficiente sin escribir', function () {
    $product = Product::factory()->create();
    app(InventoryService::class)->adjust($product, 2, StockMovementType::Purchase);

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(StockAdjustment::class, ['product' => $product])
        ->set('type', 'adjustment')
        ->set('quantity', '-5')
        ->call('adjust')
        ->assertHasErrors('quantity')
        ->assertSee('hay 2');

    expect($product->fresh()->stock_on_hand)->toBe(2)
        ->and(StockMovement::query()->count())->toBe(1);
});

it('rechaza cantidad cero por validación', function () {
    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(StockAdjustment::class, ['product' => Product::factory()->create()])
        ->set('quantity', '0')
        ->call('adjust')
        ->assertHasErrors('quantity');
});

it('bloquea la pantalla a un cliente', function () {
    $product = Product::factory()->create();

    $this->actingAs(userWithRole('customer'))->get(route('admin.stock.adjust', $product))->assertForbidden();

    Livewire::actingAs(userWithRole('customer'))
        ->test(StockAdjustment::class, ['product' => $product])
        ->assertForbidden();
});
