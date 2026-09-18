<?php

use App\Catalog\Models\Product;
use App\Inventory\Data\StockAdjustmentData;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Exceptions\InsufficientStockException;
use App\Inventory\Exceptions\InvalidStockMovementException;
use App\Inventory\Exceptions\ProductUnavailableException;
use App\Inventory\Models\StockMovement;
use App\Inventory\Services\InventoryService;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InventoryService::class);
});

it('suma stock y registra el movimiento con el actor', function () {
    $product = Product::factory()->create();
    $actor = User::factory()->create();

    $movement = $this->service->adjust($product, 10, StockMovementType::Purchase, 'Reposición', $actor);

    expect($movement->quantity)->toBe(10)
        ->and($movement->stock_after)->toBe(10)
        ->and($movement->type)->toBe(StockMovementType::Purchase)
        ->and($movement->user_id)->toBe($actor->id)
        ->and($movement->reason)->toBe('Reposición')
        ->and($product->stock_on_hand)->toBe(10)
        ->and($product->fresh()->stock_on_hand)->toBe(10);
});

it('rechaza dejar el stock negativo sin escribir nada', function () {
    $product = Product::factory()->create();
    $this->service->adjust($product, 3, StockMovementType::Purchase);

    expect(fn () => $this->service->adjust($product, -5, StockMovementType::Adjustment))
        ->toThrow(InsufficientStockException::class, 'hay 3 y se quieren descontar 5')
        ->and($product->fresh()->stock_on_hand)->toBe(3)
        ->and(StockMovement::query()->count())->toBe(1);
});

it('rechaza cantidad cero y compras negativas', function () {
    $product = Product::factory()->create();

    expect(fn () => $this->service->adjust($product, 0, StockMovementType::Adjustment))
        ->toThrow(InvalidStockMovementException::class)
        ->and(fn () => $this->service->adjust($product, -1, StockMovementType::Purchase))
        ->toThrow(InvalidStockMovementException::class)
        ->and(StockMovement::query()->count())->toBe(0);
});

it('avisa en términos del producto cuando fue borrado', function () {
    $product = Product::factory()->create(['name' => 'Lavandina', 'sku' => 'LIM-9']);
    $product->delete();

    expect(fn () => $this->service->adjust($product, 1, StockMovementType::Purchase))
        ->toThrow(ProductUnavailableException::class, 'Lavandina» (LIM-9)');
});

it('ajusta varios productos en una sola operación, en el orden recibido', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();
    $this->service->adjust($second, 4, StockMovementType::Purchase);

    $movements = $this->service->adjustMany([
        new StockAdjustmentData($second->id, -1, StockMovementType::Adjustment),
        new StockAdjustmentData($first->id, 7, StockMovementType::Purchase),
        new StockAdjustmentData($second->id, -2, StockMovementType::Adjustment),
    ]);

    expect($movements->pluck('product_id')->all())->toBe([$second->id, $first->id, $second->id])
        ->and($movements->pluck('stock_after')->all())->toBe([3, 7, 1])
        ->and($first->fresh()->stock_on_hand)->toBe(7)
        ->and($second->fresh()->stock_on_hand)->toBe(1);
});

it('no escribe ningún movimiento si uno de los ajustes deja stock negativo', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    expect(fn () => $this->service->adjustMany([
        new StockAdjustmentData($first->id, 5, StockMovementType::Purchase),
        new StockAdjustmentData($second->id, -1, StockMovementType::Adjustment),
    ]))->toThrow(InsufficientStockException::class)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($first->fresh()->stock_on_hand)->toBe(0);
});

it('falla nombrando los ids faltantes antes de procesar nada', function () {
    $product = Product::factory()->create();
    $trashed = Product::factory()->create();
    $trashed->delete();

    expect(fn () => $this->service->adjustMany([
        new StockAdjustmentData($product->id, 5, StockMovementType::Purchase),
        new StockAdjustmentData($trashed->id, 5, StockMovementType::Purchase),
        new StockAdjustmentData(999999, 5, StockMovementType::Purchase),
    ]))->toThrow(fn (ModelNotFoundException $exception) => expect($exception->getIds())->toBe([$trashed->id, 999999]))
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($product->fresh()->stock_on_hand)->toBe(0);
});

it('la base rechaza un stock negativo aunque se salteé el Service', function () {
    $product = Product::factory()->create();

    expect(fn () => DB::transaction(fn () => DB::table('products')->where('id', $product->id)->update(['stock_on_hand' => -1])))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23514'));
});

it('reconcilia el stock con la secuencia completa de movimientos', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();

    $this->service->adjust($product, 10, StockMovementType::Purchase);
    $this->service->adjustMany([
        new StockAdjustmentData($product->id, 5, StockMovementType::Purchase),
        new StockAdjustmentData($other->id, 7, StockMovementType::Purchase),
        new StockAdjustmentData($product->id, -3, StockMovementType::Adjustment),
    ]);
    $this->service->adjust($product, -4, StockMovementType::Adjustment);

    $movements = StockMovement::query()->where('product_id', $product->id)->orderBy('id')->get();
    $running = 0;

    foreach ($movements as $movement) {
        $running += $movement->quantity;
        expect($movement->stock_after)->toBe($running);
    }

    $last = StockMovement::query()->where('product_id', $product->id)->orderByDesc('id')->firstOrFail();

    expect($movements)->toHaveCount(4)
        ->and($product->fresh()->stock_on_hand)->toBe(8)
        ->and($last->stock_after)->toBe(8)
        ->and((int) $movements->sum('quantity'))->toBe(8);
});
