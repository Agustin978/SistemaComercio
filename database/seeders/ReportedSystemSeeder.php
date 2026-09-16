<?php

namespace Database\Seeders;

use App\Reporting\Models\ReportedSystem;
use Illuminate\Database\Seeder;

class ReportedSystemSeeder extends Seeder
{
    /**
     * Crea el sistema emisor de demostración que usa reporting:emit-fake.
     */
    public function run(): void
    {
        ReportedSystem::query()->firstOrCreate(
            ['slug' => 'comercio-demo'],
            ['name' => 'Comercio demo', 'is_active' => true],
        );
    }
}
