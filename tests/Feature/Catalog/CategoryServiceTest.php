<?php

use App\Catalog\Data\CategoryData;
use App\Catalog\Exceptions\CategoryCycleException;
use App\Catalog\Exceptions\CategoryInUseException;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function categoryData(string $name, ?int $parentId = null): CategoryData
{
    return new CategoryData(name: $name, slug: str($name)->slug()->toString(), parentId: $parentId);
}

beforeEach(function () {
    $this->service = app(CategoryService::class);
});

it('crea una raíz y una hija', function () {
    $root = $this->service->create(categoryData('Bebidas'));
    $child = $this->service->create(categoryData('Gaseosas', $root->id));

    expect($root->parent_id)->toBeNull()
        ->and($child->parent_id)->toBe($root->id)
        ->and($root->children()->count())->toBe(1);
});

it('rechaza que una categoría cuelgue de sí misma', function () {
    $category = $this->service->create(categoryData('Bebidas'));

    $this->service->update($category, categoryData('Bebidas', $category->id));
})->throws(CategoryCycleException::class);

it('rechaza que una categoría cuelgue de una de sus descendientes', function () {
    $root = $this->service->create(categoryData('Bebidas'));
    $child = $this->service->create(categoryData('Gaseosas', $root->id));
    $grandchild = $this->service->create(categoryData('Cola', $child->id));

    $this->service->update($root, categoryData('Bebidas', $grandchild->id));
})->throws(CategoryCycleException::class);

it('corta el recorrido si la jerarquía en la base está corrupta', function () {
    $a = $this->service->create(categoryData('A'));
    $b = $this->service->create(categoryData('B', $a->id));
    DB::table('categories')->where('id', $a->id)->update(['parent_id' => $b->id]);
    $other = $this->service->create(categoryData('Otra'));

    expect(fn () => $this->service->update($other, categoryData('Otra', $a->id)))
        ->toThrow(CategoryCycleException::class, (string) CategoryService::MAX_DEPTH)
        ->and(fn () => $this->service->descendantIds($a))
        ->toThrow(CategoryCycleException::class);
});

it('lista los ids de todas las subcategorías', function () {
    $root = $this->service->create(categoryData('Bebidas'));
    $child = $this->service->create(categoryData('Gaseosas', $root->id));
    $grandchild = $this->service->create(categoryData('Cola', $child->id));
    $this->service->create(categoryData('Almacén'));

    expect($this->service->descendantIds($root))->toBe([$child->id, $grandchild->id]);
});

it('no borra una categoría con productos activos', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);
    $trashed = Product::factory()->create(['category_id' => $category->id]);
    $trashed->delete();

    expect(fn () => $this->service->delete($category))
        ->toThrow(CategoryInUseException::class, '1 producto(s) activo(s) y 1 borrado(s)')
        ->and(Category::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('explica que solo bloquean productos borrados cuando no hay destino', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id])->delete();

    expect(fn () => $this->service->delete($category))
        ->toThrow(CategoryInUseException::class, 'borrado(s)');
});

it('reasigna los productos borrados a otra categoría y borra', function () {
    $category = Category::factory()->create();
    $target = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $product->delete();

    $this->service->delete($category, $target);

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Product::withTrashed()->findOrFail($product->id)->category_id)->toBe($target->id);
});

it('deja los productos borrados sin categoría cuando se pide explícitamente', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $product->delete();

    $this->service->delete($category, reassignTrashedToNone: true);

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Product::withTrashed()->findOrFail($product->id)->category_id)->toBeNull();
});

it('no acepta como destino la misma categoría ni una subcategoría', function () {
    $category = Category::factory()->create();
    $child = Category::factory()->childOf($category)->create();
    Product::factory()->create(['category_id' => $category->id])->delete();

    expect(fn () => $this->service->delete($category, $child))
        ->toThrow(CategoryInUseException::class, 'subcategorías')
        ->and(fn () => $this->service->delete($category, $category))
        ->toThrow(CategoryInUseException::class);
});

it('al borrar una categoría sus hijas pasan a ser raíz', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    $this->service->delete($root);

    expect($child->fresh()->parent_id)->toBeNull();
});
