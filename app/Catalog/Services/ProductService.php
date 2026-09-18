<?php

namespace App\Catalog\Services;

use App\Catalog\Data\ProductData;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;

final class ProductService
{
    public function create(ProductData $data): Product
    {
        $this->ensureCategoryExists($data);

        // El stock inicial nunca entra por acá: se carga con InventoryService para que quede su movimiento.
        // refresh() trae los defaults de la base (stock_on_hand, is_active) en la misma instancia.
        return Product::query()->create($this->attributes($data))->refresh();
    }

    public function update(Product $product, ProductData $data): Product
    {
        $this->ensureCategoryExists($data);

        $product->fill($this->attributes($data))->save();

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    private function ensureCategoryExists(ProductData $data): void
    {
        if ($data->categoryId !== null) {
            Category::query()->findOrFail($data->categoryId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ProductData $data): array
    {
        return [
            'category_id' => $data->categoryId,
            'sku' => $data->sku,
            'slug' => $data->slug,
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'cost' => $data->cost,
            'tax_rate' => $data->taxRate,
            'is_active' => $data->isActive,
        ];
    }
}
