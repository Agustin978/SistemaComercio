<?php

namespace App\Inventory\Exceptions;

use App\Inventory\Enums\StockMovementType;
use App\Shared\Exceptions\DomainException;

final class InvalidStockMovementException extends DomainException
{
    public static function for(StockMovementType $type, int $quantity): self
    {
        return match ($type) {
            StockMovementType::Purchase => new self("Una compra tiene que sumar stock: la cantidad {$quantity} no es válida."),
            StockMovementType::Adjustment => new self('Un ajuste tiene que cambiar el stock: la cantidad no puede ser cero.'),
        };
    }
}
