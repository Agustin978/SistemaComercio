<?php

namespace App\Inventory\Exceptions;

use App\Catalog\Models\Product;
use App\Shared\Exceptions\DomainException;

final class ProductUnavailableException extends DomainException
{
    public static function for(Product $product): self
    {
        return new self("El producto «{$product->name}» ({$product->sku}) ya no está disponible: fue borrado o no existe.");
    }
}
