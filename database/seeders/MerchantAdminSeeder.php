<?php

namespace Database\Seeders;

use App\Shared\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class MerchantAdminSeeder extends Seeder
{
    /**
     * Crea el comerciante de desarrollo con las credenciales de config/seeding.php.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => Config::string('seeding.merchant_admin.email')],
            [
                'name' => Config::string('seeding.merchant_admin.name'),
                'password' => Hash::make(Config::string('seeding.merchant_admin.password')),
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('merchant_admin')) {
            $user->assignRole('merchant_admin');
        }
    }
}
