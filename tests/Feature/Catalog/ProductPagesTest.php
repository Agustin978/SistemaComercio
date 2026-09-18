<?php

use App\Catalog\Livewire\ProductForm;
use App\Catalog\Livewire\ProductIndex;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lista y filtra productos por nombre o SKU', function () {
    Product::factory()->create(['name' => 'Gaseosa cola', 'sku' => 'GAS-001']);
    Product::factory()->create(['name' => 'Detergente', 'sku' => 'LIM-001']);

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(ProductIndex::class)
        ->assertSee('Gaseosa cola')
        ->assertSee('Detergente')
        ->set('search', 'LIM')
        ->assertSee('Detergente')
        ->assertDontSee('Gaseosa cola');
});

it('bloquea la lista y el formulario a un cliente', function () {
    $this->actingAs(userWithRole('customer'))->get(route('admin.products.index'))->assertForbidden();

    Livewire::actingAs(userWithRole('customer'))->test(ProductForm::class)->assertForbidden();
});

it('crea un producto desde el formulario mostrando el desglose', function () {
    $category = Category::factory()->create();

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(ProductForm::class)
        ->set('sku', 'GAS-001')
        ->set('name', 'Gaseosa cola')
        ->assertSet('slug', 'gaseosa-cola')
        ->set('price', '1850')
        ->set('taxRate', '21.00')
        ->assertSee('neto $ 1528.93')
        ->set('categoryId', $category->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('sku', 'GAS-001')->firstOrFail();
    expect($product->price)->toBe('1850.00')
        ->and($product->category_id)->toBe($category->id)
        ->and($product->uuid)->not->toBeEmpty();
});

it('rechaza un SKU repetido y un precio inválido', function () {
    Product::factory()->create(['sku' => 'GAS-001']);

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(ProductForm::class)
        ->set('sku', 'GAS-001')
        ->set('name', 'Otro')
        ->set('price', 'abc')
        ->call('save')
        ->assertHasErrors(['sku', 'price']);
});

it('borra un producto desde la lista', function () {
    $product = Product::factory()->create();

    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(ProductIndex::class)
        ->call('delete', $product->id);

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse()
        ->and(Product::withTrashed()->findOrFail($product->id)->trashed())->toBeTrue();
});
