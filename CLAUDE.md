# Contexto del proyecto

Sistema comercial SaaS con hub de administración central. Monolito modular Laravel 12 + TALL stack (Tailwind, Alpine, Livewire, Flux UI, Fortify, Spatie Permission), PostgreSQL.

Todo el código, comentarios y mensajes de commit en español. Nombres de clases, métodos y columnas de base de datos en inglés.

## Documentación

Las decisiones de arquitectura viven en `docs/adr/` (una por archivo, se agregan, no se editan). Los planes de cada fase, tal como quedaron aprobados, en `docs/planes/`. Antes de cambiar algo que un ADR justifica, leelo.

## Regla de oro

Antes de crear o modificar una migración, cambiar una relación entre modelos, alterar la estructura de un módulo existente o introducir una dependencia nueva: **presentá el plan y esperá aprobación explícita**. No asumas consentimiento por el hecho de que la tarea lo implique.

Antes de escribir código, exponé siempre: qué archivos vas a tocar, qué archivos vas a crear, y qué decisiones estás tomando por tu cuenta.

## Topología

Tres sistemas lógicos en un solo despliegue:

1. **Storefront público** — catálogo, ficha de producto, reseñas, puntuación.
2. **Panel del comerciante** — stock, precios, pedidos, estadísticas de su comercio.
3. **Hub central** — solo el super admin. Ingesta reportes y logs de todos los sistemas asociados.

Decisión arquitectónica central: el hub **no lee las tablas operativas del comercio**. El comercio publica agregados contra un contrato explícito; el hub los ingesta. Hoy el transporte es local (`LocalReportTransmitter`); mañana puede ser HTTP sin tocar el resto.

## Módulos y dirección de dependencias

```
Ordering  → Inventory → Catalog → Shared
Publishing → (Ordering, Catalog) + Reporting\Contracts
Reporting → Shared
```

`App\Reporting` no importa **nada** de `App\Catalog`, `App\Inventory` ni `App\Ordering`. Hay un test de arquitectura en Pest que lo verifica. Si necesitás romper esa regla, es señal de que el contrato está mal diseñado: pará y planteámelo.

```
app/
├── Shared/       Models/User, Data/BaseData, Casts, Exceptions
├── Catalog/      Models, Services, Repositories, Data, Policies, Livewire
├── Inventory/    Models/StockMovement, Services/InventoryService, Enums
├── Ordering/     Models, Enums/OrderStatus, Services, Repositories, Data, Livewire
├── Publishing/   Publishers, Jobs
├── Reporting/    Contracts, Transmitters, Models, Services, Enums, Console, Livewire/Hub
└── Providers/
```

## Reglas de diseño no negociables

- **Nada de lógica de negocio en controladores ni en componentes Livewire.** Los componentes orquestan: reciben input, llaman a un Service, exponen estado a la vista.
- **Los Services son la única puerta de escritura al dominio.** Un componente Livewire no llama a `Model::create()` directamente.
- **Toda operación multi-tabla va dentro de `DB::transaction()`.**
- **`order_items` congela `unit_price` y `unit_cost`.** Ninguna consulta de reportes vuelve a `products` a buscar precios históricos.
- **`products.stock_on_hand` está denormalizado a propósito.** Solo `InventoryService` lo escribe, siempre junto con un registro en `stock_movements`, dentro de la misma transacción y con `lockForUpdate()` sobre el producto.
- **Nunca N+1.** Usá `with()` explícito. Si una vista itera relaciones, cargalas antes.
- **Autorización vía Policies**, no con condicionales sueltos en las vistas.
- **Enums de PHP 8.4 respaldados por string** para estados; nada de strings mágicos.
- **Objetos de datos tipados** para cruzar fronteras de módulo, nunca arrays asociativos.

## Convenciones de base de datos

- Tablas en plural y snake_case. Claves foráneas: `{singular}_id`.
- Timestamps siempre. `softDeletes` en `products`.
- FK hacia datos históricos: `restrictOnDelete`. FK hacia datos derivados: `cascadeOnDelete`.
- `categories.parent_id`: `nullOnDelete`.
- Toda migración necesita un `down()` funcional.
- Índices explícitos en columnas de filtro y ordenamiento frecuente.

## Contrato de reporte

El hub identifica productos externos con `external_product_id` de tipo **string**, nunca una FK a `products.id`. Los payloads son agregados (`daily_sales_report`, `product_metric`, `log_event`), nunca filas transaccionales. Cada payload implementa `ReportPayload` y declara su propio `ReportType`; el transmisor nunca recibe el tipo por separado, así el desajuste tipo/payload es inexpresable.

Toda ingesta pasa por `report_ingestions` con `idempotency_key` única. Un reenvío del mismo payload no debe duplicar datos.

## Testing

Pest. Cada Service necesita test de feature cubriendo el camino feliz y al menos un caso de error. `InventoryService` necesita además un test de concurrencia. Los tests de arquitectura viven en `tests/Architecture/`.

## Fases del proyecto

- **Fase 0** — Cimientos: estructura, auth, roles, CI, tests de arquitectura.
- **Fase 1** — Hub: `reported_systems`, `system_logs`, `report_ingestions`, contrato, ingesta, visor de logs.
- **Fase 2** — Inventario: `categories`, `products`, `stock_movements`, `InventoryService`, CRUD del comerciante.
- **Fase 3** — Pedidos: `orders`, `order_items`, storefront, checkout sin pago, tableros de ventas.
- **Fase 4** — Reseñas, puntuación, métricas de interés.

No adelantes trabajo de fases posteriores. Si una tarea parece requerirlo, decilo en vez de implementarlo.

## Decisiones resueltas

- **Categorías**: anidadas (Fase 2). Jerarquía vía `categories.parent_id` autoreferenciado — ya reflejado en la convención `nullOnDelete` de arriba.
- **Reserva de stock** (Fase 3): se reserva al crear el pedido pendiente, con expiración. Implica un mecanismo (job/scheduler) que libere la reserva si el pedido no se confirma a tiempo — a definir en el diseño de Fase 3.
- **IVA** (Fase 3): precios desglosados, neto + IVA por separado (no precio final único). A definir en el diseño de Fase 3 si `order_items` necesita congelar también el desglose impositivo, en línea con el congelamiento de `unit_price`/`unit_cost`.

## Decisiones todavía abiertas

No las resuelvas por tu cuenta. Si una tarea depende de alguna, pará y preguntá.

1. Reseña restringida a compradores verificados (Fase 4).
2. Moderación de reseñas pre o post publicación (Fase 4).
