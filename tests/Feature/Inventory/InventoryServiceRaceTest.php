<?php

use App\Catalog\Models\Product;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Exceptions\InsufficientStockException;
use App\Inventory\Models\StockMovement;
use App\Inventory\Services\InventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

// PHP es single-thread: la carrera se simula con locks reales desde una segunda conexión (pgsql_race).
uses(DatabaseTruncation::class);

afterEach(function () {
    StockMovement::flushEventListeners();

    try {
        DB::connection('pgsql_race')->rollBack();
    } catch (Throwable) {
        // Sin transacción abierta.
    }

    DB::statement('RESET lock_timeout');
    DB::purge('pgsql_race');
    $this->truncateDatabaseTables();
});

function productWithStock(int $stock): Product
{
    $product = Product::factory()->create();
    app(InventoryService::class)->adjust($product, $stock, StockMovementType::Purchase, 'Carga inicial');

    return $product;
}

it('mantiene bloqueado el producto mientras escribe el movimiento', function () {
    $product = productWithStock(10);
    $fired = false;
    $blocked = null;

    // Justo antes del INSERT del movimiento, otra sesión intenta tomar el mismo lock sin esperar.
    StockMovement::creating(function () use (&$fired, &$blocked, $product): void {
        if ($fired) {
            return;
        }

        $fired = true;

        try {
            DB::connection('pgsql_race')->select('select id from products where id = ? for update nowait', [$product->id]);
            $blocked = false;
        } catch (QueryException $exception) {
            $blocked = $exception->getCode() === '55P03';
        }
    });

    $movement = app(InventoryService::class)->adjust($product, -3, StockMovementType::Adjustment);

    expect($fired)->toBeTrue()
        ->and($blocked)->toBeTrue()
        ->and($movement->stock_after)->toBe(7)
        ->and($product->fresh()->stock_on_hand)->toBe(7)
        ->and(StockMovement::query()->count())->toBe(2);
});

it('espera el lock y relee el stock cuando otra sesión gana la carrera', function () {
    $product = productWithStock(10);
    $service = app(InventoryService::class);

    $race = DB::connection('pgsql_race');
    $race->beginTransaction();
    $race->select('select id from products where id = ? for update', [$product->id]);
    $race->update('update products set stock_on_hand = 2 where id = ?', [$product->id]);

    DB::statement("SET lock_timeout = '300ms'");

    // El Service espera el lock en vez de leer el valor viejo: con la otra sesión sin commitear, agota el timeout.
    expect(fn () => $service->adjust($product, -5, StockMovementType::Adjustment))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('55P03'));

    $race->commit();
    DB::statement('RESET lock_timeout');

    // Ya libre, relee 2 bajo lock (no los 10 de la instancia en memoria) y rechaza el descuento.
    expect(fn () => $service->adjust($product, -5, StockMovementType::Adjustment))
        ->toThrow(InsufficientStockException::class, 'hay 2')
        ->and($product->fresh()->stock_on_hand)->toBe(2)
        ->and(StockMovement::query()->count())->toBe(1);
});
