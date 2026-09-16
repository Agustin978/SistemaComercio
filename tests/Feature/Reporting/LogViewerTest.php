<?php

use App\Reporting\Contracts\LogLevel;
use App\Reporting\Enums\LogOrigin;
use App\Reporting\Livewire\Hub\LogViewer;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\SystemLog;
use App\Shared\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function systemLog(ReportedSystem $system, array $attributes = []): SystemLog
{
    return SystemLog::query()->create(array_merge([
        'report_ingestion_id' => null,
        'reported_system_id' => $system->id,
        'origin' => LogOrigin::Emitter,
        'level' => LogLevel::Info,
        'message' => 'Mensaje de prueba',
        'context' => null,
        'logged_at' => new DateTimeImmutable('2026-09-16T10:00:00Z'),
    ], $attributes));
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('muestra el visor al super_admin en /hub', function () {
    $system = ReportedSystem::factory()->create(['name' => 'Comercio Norte']);
    systemLog($system, ['message' => 'Stock actualizado']);

    $this->actingAs(userWithRole('super_admin'))
        ->get(route('hub.dashboard'))
        ->assertOk()
        ->assertSee('Stock actualizado')
        ->assertSee('Comercio Norte');
});

it('devuelve 403 a un merchant_admin aunque llegue a montar el componente', function () {
    Livewire::actingAs(userWithRole('merchant_admin'))
        ->test(LogViewer::class)
        ->assertForbidden();
});

it('filtra por origen y por nivel', function () {
    $system = ReportedSystem::factory()->create();
    systemLog($system, ['message' => 'Log del emisor', 'origin' => LogOrigin::Emitter, 'level' => LogLevel::Info]);
    systemLog($system, ['message' => 'Warning del hub', 'origin' => LogOrigin::Hub, 'level' => LogLevel::Warning]);

    Livewire::actingAs(userWithRole('super_admin'))
        ->test(LogViewer::class)
        ->assertSee('Log del emisor')
        ->assertSee('Warning del hub')
        ->set('origin', LogOrigin::Hub->value)
        ->assertDontSee('Log del emisor')
        ->assertSee('Warning del hub')
        ->set('origin', '')
        ->set('level', LogLevel::Info->value)
        ->assertSee('Log del emisor')
        ->assertDontSee('Warning del hub');
});

it('filtra por sistema y por rango de fechas', function () {
    $norte = ReportedSystem::factory()->create(['name' => 'Norte']);
    $sur = ReportedSystem::factory()->create(['name' => 'Sur']);
    systemLog($norte, ['message' => 'Evento de Norte', 'logged_at' => new DateTimeImmutable('2026-09-10T10:00:00Z')]);
    systemLog($sur, ['message' => 'Evento de Sur', 'logged_at' => new DateTimeImmutable('2026-09-16T10:00:00Z')]);

    Livewire::actingAs(userWithRole('super_admin'))
        ->test(LogViewer::class)
        ->set('reportedSystemId', $sur->id)
        ->assertSee('Evento de Sur')
        ->assertDontSee('Evento de Norte')
        ->set('reportedSystemId', null)
        ->set('from', '2026-09-15')
        ->assertSee('Evento de Sur')
        ->assertDontSee('Evento de Norte')
        ->set('from', null)
        ->set('to', '2026-09-12')
        ->assertSee('Evento de Norte')
        ->assertDontSee('Evento de Sur');
});
