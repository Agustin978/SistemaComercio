<?php

namespace Database\Seeders;

use App\Catalog\Data\CategoryData;
use App\Catalog\Data\ProductData;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Services\CategoryService;
use App\Catalog\Services\ProductService;
use App\Inventory\Enums\StockMovementType;
use App\Inventory\Services\InventoryService;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public const DEMO_SKU = 'SKU-DEMO-001';

    /**
     * Catálogo de demostración. El stock inicial entra por InventoryService para que quede su movimiento.
     */
    public function run(CategoryService $categories, ProductService $products, InventoryService $inventory): void
    {
        if (Product::query()->withTrashed()->where('sku', self::DEMO_SKU)->exists()) {
            return;
        }

        $tree = [
            'Bebidas' => ['Gaseosas', 'Aguas'],
            'Almacén' => ['Fideos', 'Arroz y legumbres'],
            'Limpieza' => ['Hogar'],
        ];

        $leaves = [];

        foreach ($tree as $rootName => $children) {
            $root = $this->category($categories, $rootName);

            foreach ($children as $childName) {
                $leaves[$childName] = $this->category($categories, $childName, $root);
            }
        }

        $items = [
            [self::DEMO_SKU, 'Gaseosa cola 1.5 L', 'Gaseosas', '1850.00', '1100.00', 36],
            ['SKU-DEMO-002', 'Gaseosa lima-limón 1.5 L', 'Gaseosas', '1790.00', '1050.00', 24],
            ['SKU-DEMO-003', 'Agua mineral 2 L', 'Aguas', '990.00', '520.00', 48],
            ['SKU-DEMO-004', 'Agua saborizada 1.5 L', 'Aguas', '1250.00', '700.00', 0],
            ['SKU-DEMO-005', 'Fideos tirabuzón 500 g', 'Fideos', '1420.00', '800.00', 60],
            ['SKU-DEMO-006', 'Fideos spaghetti 500 g', 'Fideos', '1390.00', '790.00', 55],
            ['SKU-DEMO-007', 'Arroz largo fino 1 kg', 'Arroz y legumbres', '2100.00', '1300.00', 30],
            ['SKU-DEMO-008', 'Lentejas 500 g', 'Arroz y legumbres', '1750.00', '1000.00', 18],
            ['SKU-DEMO-009', 'Lavandina 1 L', 'Hogar', '1150.00', '600.00', 40],
            ['SKU-DEMO-010', 'Detergente 750 ml', 'Hogar', '2350.00', '1400.00', 22],
        ];

        foreach ($items as [$sku, $name, $leaf, $price, $cost, $stock]) {
            $product = $products->create(new ProductData(
                sku: $sku,
                slug: str($name)->slug()->toString(),
                name: $name,
                price: $price,
                cost: $cost,
                categoryId: $leaves[$leaf]->id,
            ));

            if ($stock > 0) {
                $inventory->adjust($product, $stock, StockMovementType::Purchase, 'Carga inicial');
            }
        }
    }

    private function category(CategoryService $service, string $name, ?Category $parent = null): Category
    {
        return $service->create(new CategoryData(
            name: $name,
            slug: str($name)->slug()->toString(),
            parentId: $parent?->id,
        ));
    }
}
