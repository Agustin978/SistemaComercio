# ADR 004 — `logged_at` con offset explícito y sesión de Postgres en UTC

**Estado**: aceptada (Fase 1, tras un CI en rojo).

## Contexto

`system_logs.logged_at` es `timestamp with time zone` y recibe el `occurredAt` del emisor, que trae offset explícito (por ejemplo `2026-09-16T10:00:00-03:00`). `ReportIngestionServiceTest` pasaba en la máquina de desarrollo (Argentina) y fallaba en CI con una diferencia exacta de 10800 s (3 h).

Causa: Eloquent formatea las fechas con `'Y-m-d H:i:s'`, **sin offset**. El `DateTimeImmutable` con `-03:00` llegaba a Postgres como `2026-09-16 10:00:00`, y Postgres lo interpretaba con la zona de la sesión: UTC en CI (instante incorrecto), horario argentino en local (instante correcto por casualidad). El verde local era falso.

## Decisión

1. `logged_at` usa el cast dedicado `App\Shared\Casts\UtcDateTime`: al escribir normaliza a UTC y formatea con offset explícito (`'Y-m-d H:i:sP'`); al leer devuelve un `CarbonImmutable` en UTC. No se cambia el `$dateFormat` del modelo entero.
2. Las conexiones `pgsql` y `pgsql_race` fijan `'timezone' => 'UTC'` en `config/database.php`, así cualquier valor que llegue sin offset (por ejemplo los bindings de `where('logged_at', '>=', $fecha)`, que Eloquent también formatea sin offset) se interpreta igual en todos los entornos.
3. `SystemLogTimezoneTest` fuerza una sesión con zona distinta de UTC y verifica que el instante guardado (`extract(epoch from logged_at)`) y el leído coinciden con el original.

## Consecuencias

- Toda columna `timestamptz` nueva debe usar `UtcDateTime`; los casts `datetime`/`immutable_datetime` de Laravel siguen siendo válidos solo para `timestamp` sin zona.
- Los tests no pueden confiar en la zona de la máquina: cualquier aserción sobre fechas debe comparar instantes (`getTimestamp()`), no cadenas.
