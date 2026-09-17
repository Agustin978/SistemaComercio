<?php

use App\Reporting\Contracts\LogLevel;
use App\Reporting\Enums\LogOrigin;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function storedEpoch(SystemLog $log): int
{
    return (int) DB::table('system_logs')
        ->where('id', $log->id)
        ->selectRaw('extract(epoch from logged_at) as epoch')
        ->value('epoch');
}

function logAt(DateTimeImmutable $occurredAt): SystemLog
{
    return SystemLog::query()->create([
        'reported_system_id' => ReportedSystem::factory()->create()->id,
        'origin' => LogOrigin::Emitter,
        'level' => LogLevel::Info,
        'message' => 'Evento con zona horaria',
        'logged_at' => $occurredAt,
    ]);
}

it('abre la conexión con la sesión de Postgres en UTC', function () {
    expect(DB::selectOne('show time zone')->TimeZone)->toBe('UTC');
});

it('guarda logged_at como el instante correcto aunque la sesión de Postgres use otra zona', function () {
    // Reproduce la máquina de desarrollo en Argentina, donde el bug quedaba oculto.
    DB::statement("set time zone 'America/Argentina/Buenos_Aires'");

    $occurredAt = new DateTimeImmutable('2026-09-16T10:00:00-03:00');
    $log = logAt($occurredAt);

    expect(storedEpoch($log))->toBe($occurredAt->getTimestamp())
        ->and($log->fresh()->logged_at->getTimestamp())->toBe($occurredAt->getTimestamp())
        ->and($log->fresh()->logged_at->getTimezone()->getName())->toBe('UTC');
});

it('conserva el instante con la sesión en UTC y con cualquier offset de entrada', function (string $input) {
    $occurredAt = new DateTimeImmutable($input);
    $log = logAt($occurredAt);

    expect(storedEpoch($log))->toBe($occurredAt->getTimestamp())
        ->and($log->fresh()->logged_at->getTimestamp())->toBe($occurredAt->getTimestamp());
})->with([
    'UTC' => '2026-09-16T13:00:00Z',
    'Argentina' => '2026-09-16T10:00:00-03:00',
    'Tokio' => '2026-09-16T22:00:00+09:00',
]);
