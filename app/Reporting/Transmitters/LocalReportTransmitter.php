<?php

namespace App\Reporting\Transmitters;

use App\Reporting\Contracts\Data\ReportIngestionResultData;
use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Contracts\ReportTransmitter;
use App\Reporting\Services\ReportIngestionService;

final class LocalReportTransmitter implements ReportTransmitter
{
    public function __construct(private readonly ReportIngestionService $service) {}

    public function send(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData
    {
        return $this->service->ingest($systemSlug, $payload, $idempotencyKey);
    }
}
