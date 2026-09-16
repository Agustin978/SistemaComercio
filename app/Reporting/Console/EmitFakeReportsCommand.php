<?php

namespace App\Reporting\Console;

use App\Reporting\Contracts\Data\DailySalesReportData;
use App\Reporting\Contracts\Data\LogEventData;
use App\Reporting\Contracts\Data\ProductMetricData;
use App\Reporting\Contracts\LogLevel;
use App\Reporting\Contracts\ReportPayload;
use App\Reporting\Contracts\ReportTransmitter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class EmitFakeReportsCommand extends Command
{
    protected $signature = 'reporting:emit-fake
        {slug=comercio-demo : Slug del sistema emisor}
        {--divergent : Reenvía la clave del evento de log con un payload distinto}';

    protected $description = 'Emite un reporte falso de cada tipo a través del transmisor, para verificar la ingesta a mano';

    public function handle(ReportTransmitter $transmitter): int
    {
        $slug = $this->argument('slug');

        if (! is_string($slug)) {
            throw new InvalidArgumentException('El slug debe ser una cadena.');
        }

        $today = CarbonImmutable::today();
        $date = $today->toDateString();

        // Claves y fechas fijas por día: una segunda corrida el mismo día debe salir como duplicate.
        $payloads = [
            "fake-daily-sales-{$date}" => new DailySalesReportData(
                date: $date,
                ordersCount: 12,
                totalRevenue: '15300.50',
                currency: 'ARS',
            ),
            "fake-product-metric-{$date}" => new ProductMetricData(
                externalProductId: 'SKU-DEMO-001',
                date: $date,
                unitsSold: 3,
                revenue: '4200.00',
            ),
            "fake-log-event-{$date}" => $this->option('divergent')
                ? new LogEventData(
                    level: LogLevel::Error,
                    message: 'Payload alterado para provocar una divergencia',
                    occurredAt: $today->setTime(12, 0),
                    context: ['source' => 'reporting:emit-fake', 'divergent' => true],
                )
                : new LogEventData(
                    level: LogLevel::Info,
                    message: 'Evento de prueba emitido por reporting:emit-fake',
                    occurredAt: $today->setTime(12, 0),
                    context: ['source' => 'reporting:emit-fake'],
                ),
        ];

        foreach ($payloads as $key => $payload) {
            $this->emit($transmitter, $slug, $key, $payload);
        }

        return self::SUCCESS;
    }

    private function emit(ReportTransmitter $transmitter, string $slug, string $key, ReportPayload $payload): void
    {
        $result = $transmitter->send($slug, $payload, $key);

        $this->line(sprintf(
            '%-20s %-32s → %-9s (ref #%d)',
            $payload->reportType()->value,
            $key,
            $result->outcome->value,
            $result->referenceId,
        ));
    }
}
