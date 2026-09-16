<?php

namespace App\Reporting\Contracts\Data;

use App\Reporting\Contracts\LogLevel;
use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Contracts\ReportType;
use App\Shared\Data\BaseData;
use DateTimeImmutable;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

final class LogEventData extends BaseData implements ReportPayload
{
    /**
     * ISO-8601 con offset obligatorio. Se listan p, P y O porque `date_format` valida por
     * round-trip estricto y cada escritura (Z, +00:00, +0000) solo lo cumple con uno de ellos.
     */
    public const OCCURRED_AT_FORMATS = [
        'Y-m-d\TH:i:sp',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:sO',
        'Y-m-d\TH:i:s.vp',
        'Y-m-d\TH:i:s.vP',
        'Y-m-d\TH:i:s.vO',
        'Y-m-d\TH:i:s.up',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:s.uO',
    ];

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        #[WithCast(DateTimeInterfaceCast::class, format: self::OCCURRED_AT_FORMATS)]
        #[DateFormat(self::OCCURRED_AT_FORMATS)]
        public DateTimeImmutable $occurredAt,
        public ?array $context = null,
    ) {}

    public function reportType(): ReportType
    {
        return ReportType::LogEvent;
    }
}
