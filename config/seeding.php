<?php

/*
|--------------------------------------------------------------------------
| Datos de siembra para desarrollo
|--------------------------------------------------------------------------
|
| Credenciales de los usuarios que crean SuperAdminSeeder y MerchantAdminSeeder.
| Solo para entornos locales y CI; no representan usuarios reales de producción.
|
*/

return [

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

    'merchant_admin' => [
        'name' => env('MERCHANT_ADMIN_NAME', 'Comerciante'),
        'email' => env('MERCHANT_ADMIN_EMAIL', 'comercio@example.com'),
        'password' => env('MERCHANT_ADMIN_PASSWORD', 'password'),
    ],

];
