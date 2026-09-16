<?php

namespace App\Reporting\Contracts\Data;

use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Contracts\ReportType;
use App\Shared\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Size;

final class DailySalesReportData extends BaseData implements ReportPayload
{
    public function __construct(
        #[DateFormat('Y-m-d')]
        public string $date,
        #[Min(0)]
        public int $ordersCount,
        #[Numeric]
        public string $totalRevenue,
        #[Size(3)]
        public string $currency,
    ) {}

    public function reportType(): ReportType
    {
        return ReportType::DailySalesReport;
    }
}
