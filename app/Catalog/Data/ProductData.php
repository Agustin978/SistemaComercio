<?php

namespace App\Catalog\Data;

use App\Shared\Data\BaseData;

/**
 * price es el precio final con IVA incluido; cost es neto. Ambos como cadenas decimales, nunca float.
 */
final class ProductData extends BaseData
{
    public function __construct(
        public string $sku,
        public string $slug,
        public string $name,
        public string $price,
        public string $cost = '0.00',
        public string $taxRate = '21.00',
        public ?string $description = null,
        public ?int $categoryId = null,
        public bool $isActive = true,
    ) {}
}
