<?php

namespace App\Catalog\Policies;

use App\Catalog\Models\Category;
use App\Shared\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('merchant_admin');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }
}
