<?php

namespace App\Catalog\Exceptions;

use App\Catalog\Models\Category;
use App\Shared\Exceptions\DomainException;

final class CategoryInUseException extends DomainException
{
    public static function activeProducts(Category $category, int $active, int $trashed): self
    {
        $detail = $trashed > 0 ? " y {$trashed} borrado(s)" : '';

        return new self("No se puede borrar «{$category->name}»: tiene {$active} producto(s) activo(s){$detail}. Reasignalos desde cada producto antes de borrarla.");
    }

    public static function trashedProductsOnly(Category $category, int $trashed): self
    {
        return new self("«{$category->name}» solo tiene {$trashed} producto(s) borrado(s) que la referencian. Elegí a qué categoría reasignarlos (o dejarlos sin categoría) para poder borrarla.");
    }

    public static function invalidReassignTarget(Category $category, Category $target): self
    {
        return new self("No se pueden reasignar los productos de «{$category->name}» a «{$target->name}»: es la misma categoría o una de sus subcategorías.");
    }
}
