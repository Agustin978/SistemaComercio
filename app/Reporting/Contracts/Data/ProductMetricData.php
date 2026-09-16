<?php

namespace App\Reporting\Contracts\Data;

use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Contracts\ReportType;
use App\Shared\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;

final class ProductMetricData extends BaseData implements ReportPayload
{
    public function __construct(
        public string $externalProductId,
        #[DateFormat('Y-m-d')]
        public string $date,
        #[Min(0)]
        public int $unitsSold,
        #[Numeric]
        public string $revenue,
    ) {}

    public function reportType(): ReportType
    {
        return ReportType::ProductMetric;
    }
}
