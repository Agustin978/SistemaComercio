# Sistema Comercio

Monolito modular en Laravel 12 (TALL stack: Tailwind, Alpine, Livewire, Flux UI, Fortify, Spatie Permission) con PostgreSQL. Implementa un SaaS comercial con tres sistemas lógicos en un solo despliegue:

- **Storefront público** — catálogo, ficha de producto, reseñas y puntuación.
- **Panel del comerciante** — stock, precios, pedidos y estadísticas de su comercio.
- **Hub central** — exclusivo del super admin, ingesta reportes y logs agregados de todos los comercios asociados (sin leer sus tablas operativas directamente).

Ver [`CLAUDE.md`](CLAUDE.md) para arquitectura de módulos, reglas de diseño y fases del proyecto.

## Estado

En desarrollo — Fase 0 (cimientos: estructura, autenticación, roles, CI).
