<?php

use App\Catalog\Data\ProductData;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function productData(string $sku = 'SKU-1', ?int $categoryId = null): ProductData
{
    return new ProductData(
        sku: $sku,
        slug: str($sku)->slug()->toString(),
        name: "Producto {$sku}",
        price: '100.00',
        cost: '60.00',
        taxRate: '21.00',
        categoryId: $categoryId,
    );
}

beforeEach(function () {
    $this->service = app(ProductService::class);
});

it('crea el producto con uuid v7 generado y stock en cero', function () {
    $product = $this->service->create(productData());

    expect($product->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($product->stock_on_hand)->toBe(0)
        ->and($product->price)->toBe('100.00');
});

it('conserva el uuid al actualizar', function () {
    $product = $this->service->create(productData());
    $uuid = $product->uuid;

    $this->service->update($product, productData('SKU-2'));

    expect($product->fresh()->uuid)->toBe($uuid)
        ->and($product->fresh()->sku)->toBe('SKU-2');
});

it('no permite reutilizar el SKU de un producto borrado', function () {
    $this->service->create(productData('SKU-1'))->delete();

    $this->service->create(productData('SKU-1'));
})->throws(UniqueConstraintViolationException::class);

it('exige que la categoría exista', function () {
    $this->service->create(productData('SKU-1', 999999));
})->throws(ModelNotFoundException::class);

it('desglosa neto e IVA desde el precio final', function () {
    $product = $this->service->create(productData());

    expect($product->netPrice())->toBe('82.64')
        ->and($product->taxAmount())->toBe('17.36');
});

it('borra en forma suave', function () {
    $category = Category::factory()->create();
    $product = $this->service->create(productData('SKU-1', $category->id));

    $this->service->delete($product);

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse()
        ->and(Product::withTrashed()->whereKey($product->id)->exists())->toBeTrue();
});
