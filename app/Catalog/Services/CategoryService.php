<?php

namespace App\Catalog\Services;

use App\Catalog\Data\CategoryData;
use App\Catalog\Exceptions\CategoryCycleException;
use App\Catalog\Exceptions\CategoryInUseException;
use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    // Tope del recorrido de la jerarquía: sin él, un parent_id corrupto (ciclo en la base) colgaría el request.
    public const MAX_DEPTH = 64;

    public function create(CategoryData $data): Category
    {
        if ($data->parentId !== null) {
            Category::query()->findOrFail($data->parentId);
        }

        return Category::query()->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'parent_id' => $data->parentId,
        ]);
    }

    public function update(Category $category, CategoryData $data): Category
    {
        if ($data->parentId !== null) {
            $this->ensureNoCycle($category, Category::query()->findOrFail($data->parentId));
        }

        $category->fill([
            'name' => $data->name,
            'slug' => $data->slug,
            'parent_id' => $data->parentId,
        ])->save();

        return $category;
    }

    /**
     * Los productos activos bloquean el borrado: se reasignan editando cada uno. Los borrados también
     * bloquean (la FK es restrictOnDelete), pero se pueden mover en bloque a otra categoría o a "sin categoría".
     */
    public function delete(Category $category, ?Category $reassignTrashedTo = null, bool $reassignTrashedToNone = false): void
    {
        $active = Product::query()->where('category_id', $category->id)->count();
        $trashed = Product::onlyTrashed()->where('category_id', $category->id)->count();

        if ($active > 0) {
            throw CategoryInUseException::activeProducts($category, $active, $trashed);
        }

        if ($trashed > 0 && $reassignTrashedTo === null && ! $reassignTrashedToNone) {
            throw CategoryInUseException::trashedProductsOnly($category, $trashed);
        }

        if ($reassignTrashedTo !== null && ! $this->isValidReassignTarget($category, $reassignTrashedTo)) {
            throw CategoryInUseException::invalidReassignTarget($category, $reassignTrashedTo);
        }

        DB::transaction(function () use ($category, $trashed, $reassignTrashedTo): void {
            if ($trashed > 0) {
                Product::onlyTrashed()
                    ->where('category_id', $category->id)
                    ->update(['category_id' => $reassignTrashedTo?->id]);
            }

            $category->delete();
        });
    }

    /**
     * Ids de todas las subcategorías, a cualquier profundidad.
     *
     * @return list<int>
     */
    public function descendantIds(Category $category): array
    {
        $found = [];
        $frontier = [$category->id];

        for ($level = 0; $frontier !== []; $level++) {
            if ($level >= self::MAX_DEPTH) {
                throw CategoryCycleException::depthExceeded($category, self::MAX_DEPTH);
            }

            $frontier = Category::query()
                ->whereIn('parent_id', $frontier)
                ->whereNotIn('id', $found)
                ->get(['id'])
                ->map(fn (Category $child): int => $child->id)
                ->all();

            // La raíz solo puede reaparecer si parent_id forma un ciclo en la base.
            if (in_array($category->id, $frontier, true)) {
                throw CategoryCycleException::corruptHierarchy($category);
            }

            $found = array_merge($found, $frontier);
        }

        return $found;
    }

    public function isValidReassignTarget(Category $category, Category $target): bool
    {
        return ! $target->is($category) && ! in_array($target->id, $this->descendantIds($category), true);
    }

    private function ensureNoCycle(Category $category, Category $proposedParent): void
    {
        $current = $proposedParent;

        for ($level = 0; $level < self::MAX_DEPTH; $level++) {
            if ($current->is($category)) {
                throw CategoryCycleException::selfOrDescendant($category);
            }

            if ($current->parent_id === null) {
                return;
            }

            $current = Category::query()->findOrFail($current->parent_id);
        }

        throw CategoryCycleException::depthExceeded($category, self::MAX_DEPTH);
    }
}
