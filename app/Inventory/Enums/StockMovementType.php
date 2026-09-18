<?php

namespace App\Inventory\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';

    // El tipo dice por qué cambió el stock; el signo de la cantidad dice cómo.
    public function allows(int $quantity): bool
    {
        return match ($this) {
            self::Purchase => $quantity > 0,
            self::Adjustment => $quantity !== 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Compra / reposición',
            self::Adjustment => 'Ajuste',
        };
    }
}
