<?php

use App\Reporting\Contracts\Data\DailySalesReportData;
use App\Reporting\Contracts\Data\LogEventData;
use App\Reporting\Contracts\IngestionOutcome;
use App\Reporting\Contracts\LogLevel;
use App\Reporting\Contracts\ReportType;
use App\Reporting\Enums\LogOrigin;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\ReportIngestion;
use App\Reporting\Models\ReportIngestionDivergence;
use App\Reporting\Models\SystemLog;
use App\Reporting\Services\ReportIngestionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function logEvent(string $message = 'Pedido confirmado'): LogEventData
{
    return new LogEventData(
        level: LogLevel::Info,
        message: $message,
        occurredAt: new DateTimeImmutable('2026-09-16T10:00:00-03:00'),
        context: ['order_id' => 42],
    );
}

function dailySales(): DailySalesReportData
{
    return new DailySalesReportData(date: '2026-09-16', ordersCount: 5, totalRevenue: '1200.00', currency: 'ARS');
}

beforeEach(function () {
    $this->system = ReportedSystem::factory()->create(['slug' => 'comercio-test']);
    $this->service = app(ReportIngestionService::class);
});

it('ingesta un log_event creando la ingesta y el system_log del emisor', function () {
    $result = $this->service->ingest('comercio-test', logEvent(), 'clave-1');

    expect($result->outcome)->toBe(IngestionOutcome::Created);

    $ingestion = ReportIngestion::query()->findOrFail($result->referenceId);
    expect($ingestion->report_type)->toBe(ReportType::LogEvent)
        ->and($ingestion->idempotency_key)->toBe('clave-1')
        ->and($ingestion->reported_system_id)->toBe($this->system->id)
        ->and($ingestion->payload_hash)->toMatch('/^[0-9a-f]{64}$/');

    $log = SystemLog::query()->sole();
    expect($log->report_ingestion_id)->toBe($ingestion->id)
        ->and($log->reported_system_id)->toBe($this->system->id)
        ->and($log->origin)->toBe(LogOrigin::Emitter)
        ->and($log->level)->toBe(LogLevel::Info)
        ->and($log->message)->toBe('Pedido confirmado')
        ->and($log->context)->toBe(['order_id' => 42])
        ->and($log->logged_at->getTimestamp())->toBe((new DateTimeImmutable('2026-09-16T10:00:00-03:00'))->getTimestamp());
});

it('ingesta un daily_sales_report sin generar system_log', function () {
    $result = $this->service->ingest('comercio-test', dailySales(), 'ventas-2026-09-16');

    expect($result->outcome)->toBe(IngestionOutcome::Created)
        ->and(ReportIngestion::query()->sole()->report_type)->toBe(ReportType::DailySalesReport)
        ->and(SystemLog::query()->count())->toBe(0);
});

it('devuelve duplicate y no escribe nada ante un reenvío idéntico', function () {
    $first = $this->service->ingest('comercio-test', logEvent(), 'clave-1');
    $second = $this->service->ingest('comercio-test', logEvent(), 'clave-1');

    expect($second->outcome)->toBe(IngestionOutcome::Duplicate)
        ->and($second->referenceId)->toBe($first->referenceId)
        ->and(ReportIngestion::query()->count())->toBe(1)
        ->and(SystemLog::query()->count())->toBe(1);
});

it('registra una divergencia y un warning del hub cuando cambia el payload de una clave ya ingerida', function () {
    $original = $this->service->ingest('comercio-test', logEvent(), 'clave-1');
    $result = $this->service->ingest('comercio-test', logEvent('Pedido cancelado'), 'clave-1');

    expect($result->outcome)->toBe(IngestionOutcome::Divergent)
        ->and(ReportIngestion::query()->count())->toBe(1);

    $divergence = ReportIngestionDivergence::query()->findOrFail($result->referenceId);
    expect($divergence->report_ingestion_id)->toBe($original->referenceId)
        ->and($divergence->payload['message'])->toBe('Pedido cancelado');

    $warning = SystemLog::query()->where('origin', LogOrigin::Hub->value)->sole();
    expect($warning->level)->toBe(LogLevel::Warning)
        ->and($warning->report_ingestion_id)->toBe($original->referenceId)
        ->and($warning->reported_system_id)->toBe($this->system->id)
        ->and($warning->message)->toContain('clave-1')
        ->and($warning->context['divergence_id'])->toBe($divergence->id)
        ->and($warning->context['expected_hash'])->not->toBe($warning->context['received_hash']);
});

it('no repite la divergencia ni el warning si vuelve a llegar el mismo payload divergente', function () {
    $this->service->ingest('comercio-test', logEvent(), 'clave-1');
    $first = $this->service->ingest('comercio-test', logEvent('Pedido cancelado'), 'clave-1');
    $second = $this->service->ingest('comercio-test', logEvent('Pedido cancelado'), 'clave-1');

    expect($second->outcome)->toBe(IngestionOutcome::Divergent)
        ->and($second->referenceId)->toBe($first->referenceId)
        ->and(ReportIngestionDivergence::query()->count())->toBe(1)
        ->and(SystemLog::query()->where('origin', LogOrigin::Hub->value)->count())->toBe(1);
});

it('rechaza la ingesta de un sistema inactivo dejando un warning del hub', function () {
    $inactive = ReportedSystem::factory()->inactive()->create(['slug' => 'comercio-inactivo']);

    $result = $this->service->ingest('comercio-inactivo', logEvent(), 'clave-1');

    expect($result->outcome)->toBe(IngestionOutcome::Rejected)
        ->and($result->referenceId)->toBe($inactive->id)
        ->and(ReportIngestion::query()->count())->toBe(0);

    $warning = SystemLog::query()->sole();
    expect($warning->origin)->toBe(LogOrigin::Hub)
        ->and($warning->level)->toBe(LogLevel::Warning)
        ->and($warning->report_ingestion_id)->toBeNull()
        ->and($warning->reported_system_id)->toBe($inactive->id)
        ->and($warning->message)->toContain('comercio-inactivo')
        ->and($warning->message)->toContain('clave-1')
        ->and($warning->context['idempotency_key'])->toBe('clave-1');
});

it('falla con ModelNotFoundException si el sistema no existe', function () {
    $this->service->ingest('inexistente', logEvent(), 'clave-1');
})->throws(ModelNotFoundException::class);
