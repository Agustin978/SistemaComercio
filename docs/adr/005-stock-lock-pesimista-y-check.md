# ADR 005 — Stock: lock pesimista, orden de locks y CHECK como garantía final

**Estado**: aceptada (Fase 2).

## Contexto

`products.stock_on_hand` está denormalizado a propósito: el storefront y el panel lo leen sin sumar `stock_movements`. Eso obliga a que las dos escrituras (la columna y el movimiento) sean atómicas y a que dos operaciones concurrentes sobre el mismo producto no puedan leer un valor viejo y pisarse. En Fase 3 un pedido va a descontar varios productos a la vez.

## Decisión

1. **`InventoryService` es la única puerta de escritura del stock.** `stock_on_hand` no es fillable en ningún modelo, las factories y seeders nunca lo setean: el stock inicial entra como un movimiento `purchase` con motivo "Carga inicial", así la reconciliación `stock_on_hand == suma de quantity` vale desde el primer día.
2. **Lock pesimista.** Dentro de `DB::transaction()`, el Service relee los productos con `lockForUpdate()` y calcula el stock resultante sobre lo leído bajo lock, nunca sobre la instancia en memoria. Otra sesión que ya bloqueó el producto hace esperar; al liberarse se relee el valor actualizado (`InventoryServiceRaceTest`).
3. **Locks en orden determinista.** `adjustMany()` deduplica los ids y los bloquea con `whereIn(...)->orderBy('id')->lockForUpdate()`. Dos transacciones que toquen los mismos productos siempre los piden en el mismo orden, así que una espera a la otra en vez de producir deadlock (SQLSTATE 40P01). `adjust()` es un caso particular de `adjustMany()` con un solo producto. Inmediatamente después del `get()` se compara la cantidad de filas con la de ids: si falta alguno (inexistente o borrado suave) se lanza `ModelNotFoundException` nombrando los ids, antes de procesar nada.
4. **CHECK en la base como garantía final.** `products.stock_on_hand >= 0`, `stock_movements.stock_after >= 0`, `quantity <> 0`, y los rangos de `price`, `cost` y `tax_rate`. El Service produce la excepción legible (`InsufficientStockException`); la base impide el estado inválido aunque alguien escriba por otro camino. Misma lógica que el unique del ADR 003.

## Consecuencias

- Cada `StockAdjustmentData` genera su propio movimiento con `stock_after` acumulado, en el orden recibido; el historial de un producto es una serie que se puede recorrer y verificar fila por fila.
- Un ajuste que falla no deja rastro: la transacción revierte columna y movimientos juntos.
- Fase 3 descuenta un pedido con una sola llamada a `adjustMany()`; el tipo de movimiento (`sale`, `reservation`) se agrega al enum sin tocar el mecanismo de locks.
