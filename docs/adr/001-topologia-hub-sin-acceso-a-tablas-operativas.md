# ADR 001 — Topología B: el hub no lee tablas operativas del comercio

**Estado**: aceptada (Fase 0).

## Contexto

El sistema tiene tres partes en un solo despliegue: storefront público, panel del comerciante y hub central del super admin. El hub necesita reportes y logs de los comercios. La opción obvia (Topología A) era que el hub consultara directamente `orders`, `products`, etc.

## Decisión

El hub **nunca lee las tablas operativas del comercio**. El comercio publica agregados (`daily_sales_report`, `product_metric`, `log_event`) contra un contrato explícito en `App\Reporting\Contracts`, y el hub los ingesta en sus propias tablas (`reported_systems`, `report_ingestions`, `system_logs`). Los productos externos se identifican con `external_product_id` de tipo string, nunca con una FK a `products.id`.

El transporte es un detalle detrás de la interfaz `ReportTransmitter`: hoy `LocalReportTransmitter` llama al servicio de ingesta en el mismo proceso; mañana puede ser HTTP sin tocar el contrato ni el hub.

## Consecuencias

- `App\Reporting` solo depende de `App\Shared`; `Publishing` solo conoce `Reporting\Contracts`. Un test de arquitectura (`tests/Architecture/ModuleBoundariesTest.php`) lo verifica.
- Los reportes son agregados, no filas transaccionales: el hub no puede reconstruir un pedido individual, y eso es intencional.
- Separar el comercio y el hub en despliegues distintos no requiere cambios de diseño, solo un transmisor nuevo.
