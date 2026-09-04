# ADR 0001: Bloqueo Pesimista para el Control Concurrente de Stock

* **Estado**: Aceptado
* **Fecha**: 2026-09-02
* **Decisores**: Equipo de Ingeniería Tatelestai

---

## Contexto y Planteamiento del Problema

Tatelestai vende excedentes de comida de establecimientos gastronómicos. Dado que las ofertas representan productos físicos perecederos al final de la jornada, las cantidades en inventario son extremadamente reducidas (frecuentemente entre 1 y 5 unidades por comercio).

En horas pico o al lanzarse ofertas atractivas con descuento significativo, múltiples usuarios pueden intentar adquirir la última unidad disponible simultáneamente. Si no se aísla adecuadamente la operación, existe el riesgo de condición de carrera (*race condition*), resultando en sobreventa (stock negativo), clientes que pagan por comida inexistente y conflicto operativo con el establecimiento.

---

## Criterios de Decisión

* **Integridad estricta**: Cero tolerancia a stock negativo bajo cualquier nivel de concurrencia.
* **Simplicidad operativa**: No incorporar infraestructura distribuida compleja (como Redis distributed locks) si el motor relacional puede resolverlo de forma confiable.
* **Experiencia de usuario**: En compras disputadas, quien llega primero se asegura la orden; los siguientes deben recibir un rechazo inmediato por falta de stock sin estados inconsistentes.

---

## Opciones Consideradas

1. **Validación a nivel de aplicación (ORM Eloquent sin bloqueos)**: Rechazada. Es vulnerable a lecturas sucias y escrituras superpuestas.
2. **Bloqueo Optimista (Optimistic Locking vía columna de versión)**: Rechazada. Si 10 usuarios compiten por la última unidad, 9 transacciones fallarían por conflicto de versión, provocando reintentos forzados y alta latencia.
3. **Bloqueo Pesimista en Base de Datos (`SELECT ... FOR UPDATE`)**: **Elegida**.

---

## Decisión Adoptada

Se decidió utilizar **Bloqueo Pesimista (`lockForUpdate`)** en PostgreSQL encapsulado en una transacción de base de datos (`DB::transaction`) dentro de la Action de compra, complementado con una restricción a nivel de base de datos:

```sql
ALTER TABLE offers ADD CONSTRAINT check_stock_non_negative CHECK (stock >= 0);
```

### Consecuencias

* **Positivas**:
  * PostgreSQL serializa las peticiones competidoras sobre la misma fila de oferta.
  * La primera transacción reduce el stock; las subsecuentes leen el stock actualizado (o agotado) inmediatamente y lanzan `InsufficientStockException`.
  * Integridad de datos matemáticamente garantizada a nivel ACID.
* **Negativas / Mitigaciones**:
  * Peticiones concurrentes sobre la misma oferta experimentan una breve espera en cola.
  * *Mitigación*: La transacción se mantiene mínima (lectura con bloqueo, decremento, inserción de orden). Operaciones externas lentas (llamadas a pasarelas o notificaciones por email) se delegan a workers asíncronos (`queue`) fuera de la transacción.
