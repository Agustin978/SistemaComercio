<?php

namespace App\Inventory\Services;

use App\Catalog\Models\Product;
use App\Inventory\Data\StockAdjustmentData;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Exceptions\InsufficientStockException;
use App\Inventory\Exceptions\InvalidStockMovementException;
use App\Inventory\Exceptions\ProductUnavailableException;
use App\Inventory\Models\StockMovement;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Única puerta de escritura de products.stock_on_hand: cada cambio queda registrado en stock_movements,
 * en la misma transacción y con el producto bloqueado (lockForUpdate).
 */
final class InventoryService
{
    public function adjust(Product $product, int $quantity, StockMovementType $type, ?string $reason = null, ?User $actor = null): StockMovement
    {
        try {
            $movement = $this->adjustMany([new StockAdjustmentData($product->id, $quantity, $type, $reason)], $actor)->firstOrFail();
        } catch (ModelNotFoundException) {
            throw ProductUnavailableException::for($product);
        }

        $product->forceFill(['stock_on_hand' => $movement->stock_after])->syncOriginal();

        return $movement->setRelation('product', $product);
    }

    /**
     * Aplica varios ajustes en una sola transacción. Los locks se toman siempre en orden ascendente
     * de product_id para que dos operaciones sobre los mismos productos nunca se crucen (deadlock 40P01).
     *
     * @param  list<StockAdjustmentData>  $adjustments
     * @return Collection<int, StockMovement>
     */
    public function adjustMany(array $adjustments, ?User $actor = null): Collection
    {
        if ($adjustments === []) {
            return new Collection;
        }

        return DB::transaction(function () use ($adjustments, $actor): Collection {
            $ids = array_values(array_unique(array_map(fn (StockAdjustmentData $adjustment): int => $adjustment->productId, $adjustments)));
            sort($ids);

            $products = Product::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id')->all();

            if (count($products) !== count($ids)) {
                $missing = array_values(array_diff($ids, array_keys($products)));

                throw (new ModelNotFoundException)->setModel(Product::class, $missing);
            }

            $running = array_map(fn (Product $product): int => $product->stock_on_hand, $products);
            $movements = new Collection;

            foreach ($adjustments as $adjustment) {
                $product = $products[$adjustment->productId];

                if (! $adjustment->type->allows($adjustment->quantity)) {
                    throw InvalidStockMovementException::for($adjustment->type, $adjustment->quantity);
                }

                $after = $running[$product->id] + $adjustment->quantity;

                if ($after < 0) {
                    throw InsufficientStockException::for($product, $adjustment->quantity, $running[$product->id]);
                }

                $running[$product->id] = $after;

                $movements->push(StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $actor?->id,
                    'type' => $adjustment->type,
                    'quantity' => $adjustment->quantity,
                    'stock_after' => $after,
                    'reason' => $adjustment->reason,
                ]));
            }

            foreach ($products as $product) {
                $product->forceFill(['stock_on_hand' => $running[$product->id]])->save();
            }

            return $movements;
        });
    }
}
