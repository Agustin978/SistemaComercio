# ADR 006 — `order_items` congela `unit_price`, `unit_cost` y `tax_rate`

**Estado**: aceptada (decidida en Fase 2, se implementa en Fase 3).

## Contexto

`products.price` es el precio final con IVA incluido, `cost` es neto y `tax_rate` permite desglosar neto e IVA hacia atrás (ver la convención de dinero en `CLAUDE.md`). Los tres cambian con el tiempo: el comerciante actualiza precios y costos, y la alícuota de un producto puede cambiar por normativa. Un pedido, en cambio, es un hecho histórico: lo que se vendió, a qué precio, con qué costo y con qué impuesto en ese momento.

## Decisión

`order_items` guarda una copia de los tres valores al momento del pedido:

- `unit_price`: precio final unitario con IVA incluido, tal como se cobró.
- `unit_cost`: costo neto unitario, para margen y reportes.
- `tax_rate`: alícuota aplicada, para reconstruir el desglose neto/IVA del pedido.

Ninguna consulta de reportes, tableros ni documentos fiscales vuelve a `products` a buscar estos valores. El total del pedido es la suma exacta de `unit_price × quantity`; el desglose neto/IVA se calcula sobre ese total con la política de redondeo de `App\Shared\Support\Money` (una sola vez, al final), y cierra contra `total_amount` porque `tax = total − net`.

## Consecuencias

- Los tres campos viven juntos porque responden al mismo razonamiento; congelar solo el precio dejaría un desglose fiscal que cambia retroactivamente.
- Cambiar el precio o la alícuota de un producto no altera pedidos pasados ni los agregados que el hub ya recibió.
- El contrato del hub identifica productos por `products.uuid` (nunca por SKU, que es editable), así que la serie histórica de un producto tampoco se parte al renombrarlo.
