<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        collect(['super_admin', 'merchant_admin', 'customer'])
            ->each(fn (string $role) => Role::findOrCreate($role, 'web'));
    }
}
