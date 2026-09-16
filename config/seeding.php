<?php

/*
|--------------------------------------------------------------------------
| Datos de siembra para desarrollo
|--------------------------------------------------------------------------
|
| Credenciales del super_admin que crea SuperAdminSeeder. Solo para entornos
| locales y CI; no representan un usuario real de producción.
|
*/

return [

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

];
