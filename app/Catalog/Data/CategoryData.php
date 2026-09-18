<?php

namespace App\Catalog\Data;

use App\Shared\Data\BaseData;

final class CategoryData extends BaseData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $parentId = null,
    ) {}
}
