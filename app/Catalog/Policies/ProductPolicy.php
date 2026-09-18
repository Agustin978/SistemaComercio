<?php

namespace App\Catalog\Policies;

use App\Catalog\Models\Product;
use App\Shared\Models\User;

final class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('merchant_admin');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }
}
