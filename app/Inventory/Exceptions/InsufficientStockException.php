<?php

namespace App\Inventory\Exceptions;

use App\Catalog\Models\Product;
use App\Shared\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public static function for(Product $product, int $quantity, int $available): self
    {
        $requested = abs($quantity);

        return new self("Stock insuficiente para «{$product->name}» ({$product->sku}): hay {$available} y se quieren descontar {$requested}.");
    }
}
