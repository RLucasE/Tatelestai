# Explicación: Control de Concurrencia y Stock

> **Tipo**: Explicación / Arquitectura (Diátaxis)  
> **Objetivo**: Fundamentar el problema de las condiciones de carrera (*race conditions*) en la venta de excedentes y justificar la elección de bloqueo pesimista (*Pessimistic Locking*) a nivel de base de datos.

---

## 1. El Problema: Condiciones de Carrera (Race Conditions)

En un marketplace de excedentes gastronómicos como Tatelestai, el stock de las ofertas es inherentemente **escaso y limitado** (por ejemplo, una panadería solo tiene 2 bolsas de medialunas sobrantes al final del día).

Si dos clientes intentan comprar la última unidad al mismo milisegundo:

```mermaid
sequenceDiagram
    autonumber
    actor Usuario A
    actor Usuario B
    participant App as Laravel API
    participant DB as PostgreSQL

    Usuario A->>App: Comprar 1 unidad de Oferta #5
    Usuario B->>App: Comprar 1 unidad de Oferta #5
    App->>DB: (Req A) SELECT stock FROM offers WHERE id=5; -> Retorna 1
    App->>DB: (Req B) SELECT stock FROM offers WHERE id=5; -> Retorna 1
    Note over App: Ambas peticiones evalúan: ¿stock >= 1? Sí.
    App->>DB: (Req A) UPDATE offers SET stock = 0 WHERE id=5;
    App->>DB: (Req B) UPDATE offers SET stock = -1 WHERE id=5;
    Note over DB: Sobreventa producida (Stock negativo o inconsistente)
```

Sin un mecanismo de control de concurrencia a nivel de base de datos, el stock pasaría a `-1`, vendiendo comida que no existe en el establecimiento y rompiendo la confianza del cliente.

---

## 2. Alternativas Evaluadas

### Opción A: Validación simple en la aplicación (Insuficiente)
* **Enfoque**: `if ($offer->stock >= $quantity) { ... }`
* **Falla**: Entre la lectura del registro y la escritura transcurren milisegundos donde otro hilo/proceso puede alterar el valor. No ofrece garantías ACID bajo tráfico concurrente.

### Opción B: Bloqueo Optimista (*Optimistic Locking*)
* **Enfoque**: Usar una columna de versión (`lock_version` o timestamp). Si la versión cambió al guardar, la transacción falla y se le pide al usuario reintentar.
* **Trade-off**: Excelente para escenarios con baja contención de escrituras. Sin embargo, en ofertas con alta demanda (drops de comida a precios regalados con stock de 1 o 2 unidades), genera una tasa alta de excepciones y frustración en el usuario por reintentos fallidos.

### Opción C: Bloqueo Pesimista (*Pessimistic Locking*) con `SELECT ... FOR UPDATE` (Seleccionada)
* **Enfoque**: Bloquear la fila en PostgreSQL desde el momento de la lectura hasta que la transacción finalice (`commit` o `rollback`).

---

## 3. La Solución Implementada en Tatelestai

Tatelestai adopta una estrategia de **defensa en profundidad** en tres niveles:

### Nivel 1: Bloqueo Pesimista en la Transacción
Al procesar la orden, la fila de la oferta se bloquea para lectura/escritura concurrente:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($offerId, $quantity, $userId) {
    // Bloqueo pesimista: cualquier otra petición esperará a que termine esta transacción
    $offer = Offer::where('id', $offerId)->lockForUpdate()->firstOrFail();

    if ($offer->stock < $quantity) {
        throw new InsufficientStockException("No hay stock suficiente.");
    }

    $offer->decrement('stock', $quantity);

    // Si el stock llega a 0, la oferta cambia de estado automáticamente
    if ($offer->stock === 0) {
        $offer->update(['state' => OfferState::PURCHASED]);
    }

    // Creación de la orden...
});
```

### Nivel 2: Restricción en Base de Datos (Constraint CHECK)
Para garantizar la integridad aún ante cualquier bug en el software o ejecución manual de queries:

```sql
ALTER TABLE offers ADD CONSTRAINT check_stock_non_negative CHECK (stock >= 0);
```

Si por algún motivo extraordinario una operación intentara llevar el stock a `< 0`, PostgreSQL rechaza la transacción a nivel de motor.

---

## 4. Consecuencias y Trade-offs

* **Ventajas**:
  * Consistencia estricta (cero sobreventa).
  * Lógica predecible y cumplimiento del principio ACID.
* **Trade-offs / Cuidados**:
  * Las transacciones deben ser lo más cortas posibles (no hacer llamadas HTTP externas a pasarelas de pago dentro del bloque `lockForUpdate` para no mantener bloqueos prolongados en la base de datos).
