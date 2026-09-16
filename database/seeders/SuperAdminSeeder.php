<?php

namespace Database\Seeders;

use App\Shared\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea el super_admin de desarrollo con las credenciales de config/seeding.php.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => Config::string('seeding.super_admin.email')],
            [
                'name' => Config::string('seeding.super_admin.name'),
                'password' => Hash::make(Config::string('seeding.super_admin.password')),
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
    }
}
