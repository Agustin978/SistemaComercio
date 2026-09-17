# Registros de decisiones de arquitectura

Cada archivo documenta una decisión que condiciona el diseño del sistema: el contexto, lo decidido y sus consecuencias. Se agregan, no se editan; si una decisión cambia, se escribe una nueva que la reemplaza.

1. [Topología B: el hub no lee tablas operativas del comercio](001-topologia-hub-sin-acceso-a-tablas-operativas.md)
2. [Contrato `ReportPayload`: el desajuste tipo/payload es inexpresable](002-contrato-report-payload.md)
3. [Idempotencia y divergencias en la ingesta](003-idempotencia-y-divergencias.md)
4. [`logged_at` con offset explícito y sesión de Postgres en UTC](004-fechas-con-zona-horaria.md)
