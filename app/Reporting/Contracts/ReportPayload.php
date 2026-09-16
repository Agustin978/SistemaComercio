<?php

namespace App\Reporting\Contracts;

interface ReportPayload
{
    public function reportType(): ReportType;

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
