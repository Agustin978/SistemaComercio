<?php

use App\Reporting\Contracts\Data\LogEventData;
use App\Reporting\Contracts\IngestionOutcome;
use App\Reporting\Contracts\LogLevel;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\ReportIngestion;
use App\Reporting\Services\PayloadHasher;
use App\Reporting\Services\ReportIngestionService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;

// Necesita filas commiteadas visibles desde una segunda conexión, así que no puede correr dentro
// de la transacción de RefreshDatabase. Trunca también al terminar para no contaminar otros tests.
uses(DatabaseTruncation::class);

afterEach(function () {
    ReportIngestion::flushEventListeners();
    DB::purge('pgsql_race');
    $this->truncateDatabaseTables();
});

it('usa una segunda conexión física apuntando a la misma base de test', function () {
    foreach (['host', 'port', 'database'] as $key) {
        expect(config("database.connections.pgsql_race.{$key}"))
            ->toBe(config("database.connections.pgsql.{$key}"));
    }
});

it('reintenta una sola vez cuando otra ingesta gana la carrera por la misma clave', function () {
    $system = ReportedSystem::factory()->create(['slug' => 'comercio-carrera']);
    $payload = new LogEventData(
        level: LogLevel::Info,
        message: 'Carrera',
        occurredAt: new DateTimeImmutable('2026-09-16T10:00:00Z'),
    );
    $hash = app(PayloadHasher::class)->hash($payload->toArray());

    $fired = false;

    // Entre la verificación de existencia y el INSERT del primer intento, otra conexión inserta la misma clave y commitea.
    ReportIngestion::creating(function () use (&$fired, $system, $payload, $hash): void {
        if ($fired) {
            return;
        }

        $fired = true;

        DB::connection('pgsql_race')->table('report_ingestions')->insert([
            'reported_system_id' => $system->id,
            'report_type' => $payload->reportType()->value,
            'idempotency_key' => 'clave-carrera',
            'payload_hash' => $hash,
            'payload' => json_encode($payload->toArray(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $result = app(ReportIngestionService::class)->ingest('comercio-carrera', $payload, 'clave-carrera');

    expect($fired)->toBeTrue()
        ->and($result->outcome)->toBe(IngestionOutcome::Duplicate)
        ->and(ReportIngestion::query()->count())->toBe(1)
        ->and($result->referenceId)->toBe(ReportIngestion::query()->sole()->id);
});
