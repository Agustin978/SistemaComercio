# Cierre de Fase 0 + Fase 1 (Hub)

Aprobado el 2026-09-16 con 9 correcciones (Parte A) + 3 confirmaciones + 6 correcciones + 5 ajustes + 3 ajustes finales (Parte B), todos incorporados abajo. Parte A y Parte B implementadas el 2026-09-16 (35 tests, Larastan 0, sin commitear). Desvíos respecto al plan: `newFactory()` en `User` y `ReportedSystem` + `$model` en `UserFactory` (el resolutor por defecto no funciona con el layout modular), `toArray()` declarado en `ReportPayload`, `originColor()`/`levelColor()` en `LogViewer` para los badges.

## Contexto

Fase 0 quedó con dos pendientes: CI y el test de arquitectura que `CLAUDE.md` da por existente. Fase 1 (Hub) implica migraciones nuevas y la primera estructura real de `App\Reporting` — la "regla de oro" exige plan y aprobación explícita.

## Parte A — Cerrar Fase 0 (HECHA, sin commitear)

- `.github/workflows/ci.yml`: jobs `pint`, `larastan`, `pest` sobre PHP 8.4; el de Pest con servicio `postgres:17`, `cp .env.testing.example .env.testing` y `php artisan migrate --force --env=testing` previo. Producción no está definida todavía; 17 iguala la máquina de desarrollo. Al elegir destino de deploy, alinear la versión mayor.
- `.env.testing.example` (commiteado, sin secretos) + `.env.testing` ignorado por git. Verificado por el usuario: `php artisan migrate --env=testing` aplicó las 4 migraciones base.
- Larastan `max` en 0 errores (migración de Spatie excluida; `config/seeding.php` + `Config::string()`; `LoginResponse` y `FortifyServiceProvider` tipados). Tests de arquitectura (grafo completo + presets `php`/`security`). `withoutVite()` en `TestCase`. Pint aplicado. `CLAUDE.md`: contrato en inglés.

## Hallazgo del ajuste 1 (formato de `occurredAt` en PHP 8.4)

Verificado con el binario PHP **8.4.20** que trae Herd (`~/.config/herd/bin/php84`), no en Docker (Docker Desktop no estaba corriendo). Resultado idéntico a 8.5:

- `createFromFormat` con `p`, `P` u `O` **parsea** `Z`, `+00:00`, `-03:00` y `+0000` por igual, y los tres **rechazan** un valor sin offset. `p` está soportado en 8.4 para parsear.
- Pero `format()` no es simétrico: `p` imprime `Z` para UTC, `P` imprime `+00:00`, `O` imprime `+0000`. Y Laravel valida `date_format` con round-trip estricto (`ValidatesAttributes::validateDateFormat`: `$date->format($format) == $value`). Con solo `p`, la validación rechazaría `+00:00`; con solo `P`, rechazaría `Z`.
- Decisión: no hace falta el fallback "`P` + chequeo de `Z`". Se define `LogEventData::OCCURRED_AT_FORMATS` con los tres especificadores (`p`, `P`, `O`) × tres precisiones (segundos, `.v` milisegundos, `.u` microsegundos) = 9 formatos, usados en `#[WithCast(DateTimeInterfaceCast::class, format: ...)]` y en `#[DateFormat(...)]`. Cada escritura válida de offset hace round-trip con exactamente uno; ninguna escritura sin offset pasa con ninguno.

## Parte B — Fase 1 (Hub), diseño aprobado

Solo el lado receptor (`App\Reporting`); `Publishing` no se construye todavía.

### Migraciones (orden: reported_systems → report_ingestions → system_logs → report_ingestion_divergences)

- `reported_systems`: `name`, `slug` unique, `is_active` boolean default true, timestamps. `is_active` es efectiva: sistema inactivo → ingesta rechazada.
- `report_ingestions`: `reported_system_id` FK restrictOnDelete, `report_type` string (enum), `idempotency_key` string, `payload_hash` string(64), `payload` jsonb, timestamps. Unique `(reported_system_id, idempotency_key)`.
- `system_logs`: **`report_ingestion_id` nullable** FK cascadeOnDelete (ajuste final 1: un log `hub` no siempre deriva de una ingesta), `reported_system_id` FK restrictOnDelete, `origin` string (enum `LogOrigin`: `emitter` | `hub`), `level` string (enum), `message` text, `context` jsonb nullable, `logged_at` timestampTz, timestamps. **Un solo índice compuesto `(reported_system_id, origin, level, logged_at)`** (sugerencia menor: un índice aparte sobre dos valores no aporta).
- `report_ingestion_divergences`: `report_ingestion_id` FK cascadeOnDelete, `payload_hash` string(64), `payload` jsonb, timestamps. Unique `(report_ingestion_id, payload_hash)`.

Ventas/métricas: solo JSON en `report_ingestions.payload`, sin tabla propia en Fase 1.

### Contrato `App\Reporting\Contracts`

- `ReportType` enum string: `DailySalesReport = 'daily_sales_report'`, `ProductMetric = 'product_metric'`, `LogEvent = 'log_event'`.
- `LogLevel` enum string, 8 niveles PSR-3.
- `IngestionOutcome` enum string: `created`, `duplicate`, `divergent`, `rejected`.
- Interfaz `ReportPayload { public function reportType(): ReportType; }`.
- `Contracts/Data/DailySalesReportData`, `ProductMetricData` (`externalProductId` string), `LogEventData` (`level: LogLevel`, `message`, `occurredAt: DateTimeImmutable` con los 9 formatos, `context: ?array`) — extienden `BaseData` e implementan `ReportPayload`.
- `Contracts/Data/ReportIngestionResultData`: `referenceId: int`, `outcome: IngestionOutcome`. Docblock: `referenceId` según `outcome` — `created`/`duplicate`: id de `report_ingestions`; `divergent`: id de `report_ingestion_divergences`; `rejected`: id de `reported_systems`.
- Interfaz `ReportTransmitter::send(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData`.

### `App\Reporting\Enums\LogOrigin`

Enum string `Emitter = 'emitter'`, `Hub = 'hub'`. En `Enums` (no en `Contracts`) porque es vocabulario interno del hub. Agrega `Enums` al árbol de Reporting en `CLAUDE.md`.

### `Services/PayloadHasher`

`canonicalize(array $payload): array` (ksort recursivo en asociativos, listas intactas) y `hash(array $payload): string` (`sha256` del `json_encode` con `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`). Recibe el array para que el test sea unitario puro; el Service le pasa `$payload->toArray()`.

### `Services/ReportIngestionService`

```php
public function ingest(string $systemSlug, ReportPayload $payload, string $idempotencyKey): ReportIngestionResultData
{
    try {
        return $this->attempt($systemSlug, $payload, $idempotencyKey);
    } catch (UniqueConstraintViolationException) {
        return $this->attempt($systemSlug, $payload, $idempotencyKey); // un solo reintento, ya sin transacción abierta
    }
}
```

`attempt()` abre `DB::transaction()` y adentro: `ReportedSystem::where('slug')->firstOrFail()` (inexistente → `ModelNotFoundException`); **si `! is_active` → escribe un `SystemLog` `hub`/`warning` con `report_ingestion_id = null`, mensaje "Ingesta rechazada: sistema {slug} inactivo (clave {key})" y `context` con `idempotency_key` y `report_type`, y devuelve `rejected` con `referenceId = system->id`** (ajuste final 2; el throttling queda para el transporte HTTP); `$hash = $this->hasher->hash($payload->toArray())`; busca `(system, key)`:
- No existe → crea `ReportIngestion`; si `$payload instanceof LogEventData` crea `SystemLog` `emitter` con `logged_at = occurredAt` → `created`.
- Existe con el mismo hash → `duplicate`, sin escribir nada.
- Existe con hash distinto → busca `ReportIngestionDivergence` por `(ingestion, hash)`; si no está, la crea y escribe un `SystemLog` `hub`/`warning` con `report_ingestion_id` = ingesta original, `logged_at = now()`, mensaje "Payload divergente para la clave {key}", `context` con `divergence_id`, `expected_hash`, `received_hash`, `report_type`. Si ya existía no se repite el warning. → `divergent` con `referenceId` = id de la divergencia.

El `catch` va afuera porque en Postgres un error de constraint aborta la transacción entera; `DB::transaction()` ya hizo rollback cuando la excepción llega, y el reintento abre una transacción nueva que encuentra la fila insertada por la ingesta concurrente.

### Transporte, Models, comando, visor

- `Transmitters/LocalReportTransmitter`: delega a `ReportIngestionService::ingest()`. Bind interfaz→impl en `AppServiceProvider::register()`.
- `Models/ReportedSystem`, `ReportIngestion` (casts `report_type`, `payload`), `SystemLog` (casts `origin` → `LogOrigin`, `level` → `LogLevel`, `logged_at` → `immutable_datetime`, `context` → array; `reportIngestion()` nullable), `ReportIngestionDivergence` (cast `payload`).
- `Console/EmitFakeReportsCommand` (`reporting:emit-fake {slug=comercio-demo} {--divergent}`), registrado en `bootstrap/app.php` con `->withCommands([...])`. Emite un payload de cada tipo vía `ReportTransmitter` e imprime el `outcome`; claves deterministas por fecha; `--divergent` reenvía la misma clave con payload alterado. `database/seeders/ReportedSystemSeeder.php` crea `comercio-demo`; `DatabaseSeeder` lo llama.
- `Livewire/Hub/LogViewer`: chequeo de rol `super_admin` en `mount()` además del middleware; filtros por sistema, origin, nivel y fecha; `SystemLog::with('reportedSystem')` paginado; vista `resources/views/reporting/hub/log-viewer.blade.php` con `flux:*`; reemplaza el placeholder de `resources/views/hub/dashboard.blade.php`. Sin Policy.

### Conexión `pgsql_race` y guard de base de test (ajuste final 3)

- `config/database.php`: conexión `pgsql_race` que lee **exactamente las mismas variables `DB_*`** que `pgsql` (segunda conexión física a la misma base; solo la usa `ReportIngestionRaceTest`). El test afirma además que `host`, `port` y `database` de `pgsql_race` coinciden con los de `pgsql`.
- **Decisión propia** (a reportar): `tests/TestCase.php::setUp()` lanza `RuntimeException` si `database.connections.pgsql.database` no termina en `_testing`. Motivo: `.env.testing` está ignorado por git; en un clon sin ese archivo, `APP_ENV=testing` cae al `.env` de desarrollo y `migrate:fresh`/`DatabaseTruncation` borrarían esa base.

### Tests

- `tests/Unit/Reporting/PayloadHasherTest.php` (puro): claves en distinto orden → mismo hash (también anidado); listas en distinto orden → distinto; valor distinto → distinto; UTF-8 inválido → `JsonException`.
- `tests/Feature/Reporting/LogEventDataTest.php` (sin DB): `validateAndCreate` rechaza `occurredAt` sin offset; acepta `Z`, `+00:00`, `-03:00`, `+0000` y `.000Z` conservando el offset.
- `tests/Feature/Reporting/ReportIngestionServiceTest.php` (`uses(RefreshDatabase::class)`): `log_event` → ingesta + system_log `emitter`; `daily_sales_report` → ingesta sin system_log; misma clave dos veces → `duplicate`, 1 fila; payload distinto → `divergent`, 1 divergencia + 1 warning `hub`, `referenceId` = id de la divergencia; reenvío del mismo divergente → sigue 1/1, mismo id; **sistema inactivo → `rejected`, 0 ingestas, 1 warning `hub` con `report_ingestion_id` null y el slug + clave en el mensaje**; slug inexistente → `ModelNotFoundException`.
- `tests/Feature/Reporting/ReportIngestionRaceTest.php` (`uses(DatabaseTruncation::class)`): listener `ReportIngestion::creating` con bandera `$fired` que lo desarma tras el primer disparo; inserta la fila conflictiva por `pgsql_race`. El INSERT real choca con el unique (23505), rollback, el `catch` externo reintenta y encuentra la fila → `duplicate`, 1 sola fila. `afterEach`: `ReportIngestion::flushEventListeners()`, `DB::purge('pgsql_race')` y `$this->truncateDatabaseTables()`.
- `tests/Feature/Reporting/LogViewerTest.php` (`uses(RefreshDatabase::class)`): `super_admin` ve los logs y filtra por origin y nivel; `merchant_admin` recibe 403.
- `RefreshDatabase` se declara por archivo (no en `tests/Pest.php`) para convivir con `DatabaseTruncation`.
- `database/factories/ReportedSystemFactory.php`.

### CLAUDE.md

- "Contrato de reporte": "Cada payload implementa `ReportPayload` y declara su propio `ReportType`; el transmisor nunca recibe el tipo por separado, así el desajuste tipo/payload es inexpresable."
- Árbol: `Reporting/ Contracts, Transmitters, Models, Services, Enums, Console, Livewire/Hub`.

## Archivos Parte B

Nuevos: 4 migraciones; `app/Reporting/Contracts/{ReportType,LogLevel,IngestionOutcome,ReportPayload,ReportTransmitter}.php`; `app/Reporting/Contracts/Data/{DailySalesReportData,ProductMetricData,LogEventData,ReportIngestionResultData}.php`; `app/Reporting/Enums/LogOrigin.php`; `app/Reporting/Transmitters/LocalReportTransmitter.php`; `app/Reporting/Services/{ReportIngestionService,PayloadHasher}.php`; `app/Reporting/Models/{ReportedSystem,ReportIngestion,SystemLog,ReportIngestionDivergence}.php`; `app/Reporting/Console/EmitFakeReportsCommand.php`; `app/Reporting/Livewire/Hub/LogViewer.php`; `resources/views/reporting/hub/log-viewer.blade.php`; `database/seeders/ReportedSystemSeeder.php`; `database/factories/ReportedSystemFactory.php`; `tests/Unit/Reporting/PayloadHasherTest.php`; `tests/Feature/Reporting/{LogEventDataTest,ReportIngestionServiceTest,ReportIngestionRaceTest,LogViewerTest}.php`.

Modificados: `resources/views/hub/dashboard.blade.php`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `database/seeders/DatabaseSeeder.php`, `config/database.php` (conexión `pgsql_race`), `tests/TestCase.php` (guard `_testing`), `CLAUDE.md`.

## Verificación Parte B

`pint --test`, `phpstan`, `pest` verdes (incluido el test de carrera); `php artisan migrate:fresh --seed --env=testing` limpio y `migrate:rollback --env=testing` funcional; `php artisan reporting:emit-fake --env=testing` dos veces (created → duplicate) y con `--divergent` (divergent + warning `hub`); `LogViewerTest` cubre el visor. Al terminar: resumen del hallazgo del ajuste 1 y de las decisiones propias (ubicación de `LogOrigin` en `Enums`, lista de 9 formatos, guard `_testing` en `TestCase`, índice compuesto único).
