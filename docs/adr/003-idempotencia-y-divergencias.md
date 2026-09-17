# ADR 003 — Idempotencia y divergencias en la ingesta

**Estado**: aceptada (Fase 1).

## Contexto

Un emisor puede reenviar el mismo reporte (reintentos, cortes de red). El hub no debe duplicar datos, pero tampoco debe descartar en silencio un reenvío cuyo contenido cambió: eso sería un bug del emisor que quedaría invisible.

## Decisión

- `report_ingestions` tiene un unique compuesto `(reported_system_id, idempotency_key)`: la clave es por sistema emisor, no global, para que cada sistema maneje su propio espacio de claves.
- Cada ingesta guarda `payload_hash`: sha256 del JSON canónico del payload (claves ordenadas recursivamente, listas intactas, `JSON_THROW_ON_ERROR`). Ver `PayloadHasher`.
- Reenvío con el mismo hash → `duplicate`, sin escribir nada. Reenvío con hash distinto → se registra en `report_ingestion_divergences` (unique `(report_ingestion_id, payload_hash)`) y se escribe un `SystemLog` de origen `hub` y nivel `warning`, visible en el visor. La ingesta original no se toca.
- Sistema inactivo → `rejected`, con un `SystemLog` de origen `hub` que deja rastro del slug y la clave rechazada.

### Por qué el `catch` va afuera de la transacción

Dos ingestas concurrentes con la misma clave pueden pasar ambas la verificación de existencia; la segunda choca con el unique (SQLSTATE 23505). En Postgres, **cualquier error aborta la transacción entera**: toda consulta posterior falla con "current transaction is aborted". Por eso `ReportIngestionService::ingest()` no captura la excepción adentro de `DB::transaction()`, sino afuera, y reintenta la operación completa una sola vez en una transacción nueva, donde la fila de la otra ingesta ya es visible y el resultado es `duplicate` o `divergent`. `ReportIngestionRaceTest` reproduce la carrera insertando la fila desde una segunda conexión (`pgsql_race`) justo antes del INSERT.

## Consecuencias

- La garantía real de no duplicar la da el índice único; el chequeo previo solo evita la excepción en el caso común.
- Las divergencias quedan auditables (payload recibido completo) sin mezclarse con los reportes válidos.
- El throttling de sistemas inactivos se resolverá cuando exista transporte HTTP.
