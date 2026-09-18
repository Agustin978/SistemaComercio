<?php

namespace App\Inventory\Policies;

use App\Shared\Models\User;

final class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('merchant_admin');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
}
