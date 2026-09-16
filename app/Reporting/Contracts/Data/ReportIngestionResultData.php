<?php

namespace App\Reporting\Contracts\Data;

use App\Reporting\Contracts\IngestionOutcome;
use App\Shared\Data\BaseData;

/**
 * `referenceId` se interpreta según `outcome`: para created y duplicate es el id de report_ingestions,
 * para divergent el de report_ingestion_divergences, para rejected el de reported_systems.
 */
final class ReportIngestionResultData extends BaseData
{
    public function __construct(
        public int $referenceId,
        public IngestionOutcome $outcome,
    ) {}
}
