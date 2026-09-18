<?php

namespace App\Catalog\Exceptions;

use App\Catalog\Models\Category;
use App\Shared\Exceptions\DomainException;

final class CategoryCycleException extends DomainException
{
    public static function selfOrDescendant(Category $category): self
    {
        return new self("La categoría «{$category->name}» no puede colgar de sí misma ni de una de sus subcategorías.");
    }

    public static function depthExceeded(Category $category, int $limit): self
    {
        return new self("La jerarquía de «{$category->name}» supera los {$limit} niveles permitidos; revisá los datos de categorías.");
    }

    public static function corruptHierarchy(Category $category): self
    {
        return new self("La jerarquía de «{$category->name}» está corrupta: una subcategoría vuelve a apuntar a ella. Revisá los datos de categorías.");
    }
}
