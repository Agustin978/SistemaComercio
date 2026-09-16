<?php

namespace App\Reporting\Contracts;

use App\Reporting\Contracts\Data\ReportIngestionResultData;

interface ReportTransmitter
{
    public function send(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData;
}
