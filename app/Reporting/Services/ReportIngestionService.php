<?php

namespace App\Reporting\Services;

use App\Reporting\Contracts\Data\LogEventData;
use App\Reporting\Contracts\Data\ReportIngestionResultData;
use App\Reporting\Contracts\IngestionOutcome;
use App\Reporting\Contracts\LogLevel;
use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Enums\LogOrigin;
use App\Reporting\Models\ReportedSystem;
use App\Reporting\Models\ReportIngestion;
use App\Reporting\Models\ReportIngestionDivergence;
use App\Reporting\Models\SystemLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class ReportIngestionService
{
    public function __construct(private readonly PayloadHasher $hasher) {}

    public function ingest(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData
    {
        try {
            return $this->attempt($systemSlug, $payload, $idempotencyKey);
        } catch (UniqueConstraintViolationException) {
            // Carrera con otra ingesta de la misma clave: Postgres abortó la transacción entera,
            // así que se reintenta afuera de ella, ya con la fila de la otra ingesta visible.
            return $this->attempt($systemSlug, $payload, $idempotencyKey);
        }
    }

    private function attempt(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData
    {
        return DB::transaction(function () use ($systemSlug, $payload, $idempotencyKey): ReportIngestionResultData {
            $system = ReportedSystem::query()->where('slug', $systemSlug)->firstOrFail();

            if (! $system->is_active) {
                return $this->reject($system, $payload, $idempotencyKey);
            }

            $hash = $this->hasher->hash($payload->toArray());

            $existing = ReportIngestion::query()
                ->where('reported_system_id', $system->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing === null) {
                return $this->create($system, $payload, $idempotencyKey, $hash);
            }

            if ($existing->payload_hash === $hash) {
                return new ReportIngestionResultData($existing->id, IngestionOutcome::Duplicate);
            }

            return $this->diverge($system, $existing, $payload, $idempotencyKey, $hash);
        });
    }

    private function reject(ReportedSystem $system, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData
    {
        SystemLog::query()->create([
            'report_ingestion_id' => null,
            'reported_system_id' => $system->id,
            'origin' => LogOrigin::Hub,
            'level' => LogLevel::Warning,
            'message' => "Ingesta rechazada: sistema {$system->slug} inactivo (clave {$idempotencyKey})",
            'context' => [
                'idempotency_key' => $idempotencyKey,
                'report_type' => $payload->reportType()->value,
            ],
            'logged_at' => now(),
        ]);

        return new ReportIngestionResultData($system->id, IngestionOutcome::Rejected);
    }

    private function create(ReportedSystem $system, ReportPayload $payload, string $idempotencyKey, string $hash): ReportIngestionResultData
    {
        $ingestion = ReportIngestion::query()->create([
            'reported_system_id' => $system->id,
            'report_type' => $payload->reportType(),
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => $hash,
            'payload' => $payload->toArray(),
        ]);

        if ($payload instanceof LogEventData) {
            SystemLog::query()->create([
                'report_ingestion_id' => $ingestion->id,
                'reported_system_id' => $system->id,
                'origin' => LogOrigin::Emitter,
                'level' => $payload->level,
                'message' => $payload->message,
                'context' => $payload->context,
                'logged_at' => $payload->occurredAt,
            ]);
        }

        return new ReportIngestionResultData($ingestion->id, IngestionOutcome::Created);
    }

    private function diverge(ReportedSystem $system, ReportIngestion $existing, ReportPayload $payload, string $idempotencyKey, string $hash): ReportIngestionResultData
    {
        $divergence = ReportIngestionDivergence::query()
            ->where('report_ingestion_id', $existing->id)
            ->where('payload_hash', $hash)
            ->first();

        if ($divergence === null) {
            $divergence = ReportIngestionDivergence::query()->create([
                'report_ingestion_id' => $existing->id,
                'payload_hash' => $hash,
                'payload' => $payload->toArray(),
            ]);

            SystemLog::query()->create([
                'report_ingestion_id' => $existing->id,
                'reported_system_id' => $system->id,
                'origin' => LogOrigin::Hub,
                'level' => LogLevel::Warning,
                'message' => "Payload divergente para la clave {$idempotencyKey}",
                'context' => [
                    'divergence_id' => $divergence->id,
                    'expected_hash' => $existing->payload_hash,
                    'received_hash' => $hash,
                    'report_type' => $payload->reportType()->value,
                ],
                'logged_at' => now(),
            ]);
        }

        return new ReportIngestionResultData($divergence->id, IngestionOutcome::Divergent);
    }
}
