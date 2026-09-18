<?php

namespace App\Inventory\Data;

use App\Inventory\Enums\StockMovementType;
use App\Shared\Data\BaseData;

final class StockAdjustmentData extends BaseData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public StockMovementType $type,
        public ?string $reason = null,
    ) {}
}
