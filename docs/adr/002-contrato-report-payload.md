# ADR 002 — Contrato `ReportPayload`: el desajuste tipo/payload es inexpresable

**Estado**: aceptada (Fase 1).

## Contexto

La primera versión del transmisor recibía el tipo de reporte y el payload por separado: `send(string $slug, ReportType $type, BaseData $payload, string $key)`. Nada impedía enviar un `ProductMetricData` declarando `ReportType::LogEvent`; el error solo se detectaría con validación en tiempo de ejecución.

## Decisión

Cada payload implementa la interfaz `ReportPayload`, que expone `reportType(): ReportType` y `toArray()`. El transmisor y el servicio de ingesta reciben solo el payload: `send(string $systemSlug, ReportPayload $payload, string $idempotencyKey)`. El tipo se deriva del objeto y no puede contradecirlo.

Los nombres del contrato (clases y valores de enum) están en inglés: `DailySalesReportData`, `ProductMetricData`, `LogEventData`; `daily_sales_report`, `product_metric`, `log_event`.

## Consecuencias

- Un payload nuevo obliga a crear una clase que declare su tipo; no hay strings mágicos ni parámetros redundantes.
- `ReportIngestionResultData` devuelve `referenceId` + `outcome`; `referenceId` se interpreta según el outcome (ingesta, divergencia o sistema), documentado en su docblock.
- La interfaz `ReportTransmitter` no expone ningún modelo Eloquent de `Reporting\Models`, así `Publishing` puede depender solo del contrato.
