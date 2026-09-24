# Deuda Técnica Backend: Estandarización de Detalles de Venta (`sell_details`)

> **Tipo**: Gestión de Proyecto / Calidad y Refactorización Backend  
> **Alcance**: Exclusivamente Backend (Laravel 12 / PostgreSQL 16)  
> **Estado**: ⏳ Pendiente de implementación  
> **Fecha de Registro**: 13 de Septiembre de 2026  
> **Contexto Arquitectónico**: Consecuencia de la transición al modelo de Bolsas Sorpresa ([ADR-0004](../explanation/adrs/0004-transicion-a-modelo-bolsas-sorpresa.md))  

---

## 1. Diagnóstico del Problema

Tras migrar del catálogo tradicional de productos al modelo de **Bolsas Sorpresa (*Surprise Bags*) con cupos diarios** ([ADR-0004](../explanation/adrs/0004-transicion-a-modelo-bolsas-sorpresa.md)), se desacoplaron las ofertas de la tabla `product_offers`. 

Sin embargo, la capa de persistencia y serialización de ventas (`sells` y `sell_details`) quedó en un estado intermedio con las siguientes inconsistencias en el backend:

1. **Nombres híbridos en la base de datos**: En la tabla `sell_details` convive la columna `offer_quantity` (prefijo `offer_`) junto con `pack_name`, `pack_description` y `pack_price` (prefijo `pack_`).
2. **Contratos dispares en la API**: Diferentes controladores serializan los detalles de venta bajo nombres y estructuras distintas (`'offers'` vs `'sell_details'`).
3. **Duplicidad de atributos en respuestas**: En algunos endpoints se retorna al mismo tiempo `offer_title` y `pack_name` para el mismo dato.
4. **Respuestas Eloquent sin transformar**: Algunos métodos de controladores devuelven colecciones Eloquent en crudo (`json($sells)`), salteándose la capa de API Resources.

---

## 2. Inventario Exhaustivo de Inconsistencias en Backend

### A. Capa de Base de Datos y Migración
**Archivo**: [`Backend/example-app/database/migrations/2025_08_01_142449_create_sell_details_table.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/database/migrations/2025_08_01_142449_create_sell_details_table.php)

```php
Schema::create('sell_details', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('offer_id');
    $table->unsignedBigInteger('sell_id');
    $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
    $table->foreign('sell_id')->references('id')->on('sells')->onDelete('cascade');
    $table->bigInteger('offer_quantity'); // ⚠️ Prefijo "offer_"
    $table->bigInteger('pack_price');     // ⚠️ Prefijo "pack_"
    $table->string('pack_name');          // ⚠️ Prefijo "pack_"
    $table->string('pack_description')->nullable(); // ⚠️ Prefijo "pack_"
    $table->timestamps();
});
```
* **Inconsistencia**: No existe un criterio uniforme. O bien todas las columnas representan el ítem con nombres directos (`quantity`, `unit_price`, `name`, `description`), o se usa un prefijo único.

---

### B. Actions de Dominio

1. **[`makeSellAction.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Actions/Sell/makeSellAction.php)**:
   Al registrar el detalle persistido durante la transacción ACID, mapea propiedades de `PrepareOfferDTO`:
   ```php
   SellDetail::create([
       'sell_id' => $sell->id,
       'offer_id' => $offerDTO->id,
       'offer_quantity' => $offerDTO->quantity,
       'pack_name' => $offerDTO->title,
       'pack_description' => $offerDTO->description,
       'pack_price' => $offerDTO->price,
   ]);
   ```

2. **[`getCustomerSellsAction.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Actions/Sell/getCustomerSellsAction.php)**:
   Mapea manualmente los detalles bajo la clave `'sell_details'`:
   ```php
   'sell_details' => $sell->sellDetails->map(function ($detail) {
       return [
           'id' => $detail->id,
           'offer_id' => $detail->offer_id,
           'offer_quantity' => $detail->offer_quantity,
           'pack_price' => $detail->pack_price,
           'pack_name' => $detail->pack_name,
           'pack_description' => $detail->pack_description,
       ];
   })->toArray(),
   ```

---

### C. API Resources y Controladores HTTP

1. **[`SellResource.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Resources/SellResource.php)**:
   * Serializa los detalles bajo la clave `'offers'` en vez de `'sell_details'` o `'items'`:
   ```php
   'offers' => $this->sellDetails?->map(function ($detail) {
       return [
           'offer_id' => $detail->offer_id,
           'offer_quantity' => $detail->offer_quantity,
           'pack_name' => $detail->pack_name,
           'pack_description' => $detail->pack_description,
           'pack_price' => $detail->pack_price,
       ];
   })->values()->toArray() ?? [],
   ```

2. **[`SellerSellController.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Controllers/SellerSellController.php)**:
   * En `sellerSells()`: Devuelve un array manual con clave `'sell_details'`, conteniendo `pack_*`, `offer_quantity` y un objeto anidado `'offer'`.
   * En `checkCustomerCode()`: Devuelve una clave `'offers'`, duplicando `offer_title` y `pack_name` en la misma carga útil:
     ```php
     'offers' => $sell->sellDetails->map(function ($detail) {
         return [
             'offer_id' => $detail->offer_id,
             'offer_title' => $detail->offer->title ?? 'N/A', // ⚠️ Redundante con pack_name
             'offer_quantity' => $detail->offer_quantity,
             'pack_name' => $detail->pack_name,
             'pack_description' => $detail->pack_description,
             'pack_price' => $detail->pack_price,
         ];
     })
     ```

3. **[`SellController.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Controllers/SellController.php)**:
   * En `adminSells()`: Mapea `'sell_details'` con `pack_name`, `pack_description`, `pack_price`, `offer_quantity`.
   * En `adminSellDetail()` y `adminCustomerSells()`: Hace `response()->json(['sells' => $sells])` directo de los modelos Eloquent, sin pasar por ningún API Resource.

---

### D. Plantilla de Notificación por Correo
**Archivo**: [`Backend/example-app/resources/views/emails/purchase_confirmation.blade.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/resources/views/emails/purchase_confirmation.blade.php)

La vista Blade de confirmación de compra accede a los atributos con la mezcla actual:
```blade
<h4>{{ $detail->pack_name }}</h4>
<p>{{ $detail->pack_description }}</p>
<span class="quantity">Cantidad: {{ $detail->offer_quantity }}</span>
<span class="price">Precio unitario: ${{ number_format($detail->pack_price, 2) }}</span>
```

---

### E. Factories y Seeders de Prueba

1. **[`SellDetailFactory.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/database/factories/SellDetailFactory.php)**:
   ```php
   return [
       'offer_id' => Offer::factory(),
       'sell_id' => Sell::factory(),
       'offer_quantity' => $this->faker->numberBetween(1, 5),
       'pack_price' => $this->faker->numberBetween(1500, 5000),
       'pack_name' => $this->faker->words(3, true),
       'pack_description' => $this->faker->sentence(),
   ];
   ```
2. **[`SellFactory.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/database/factories/SellFactory.php)**:
   En su método `withDetails()`, instancia registros con `offer_quantity`, `pack_price`, `pack_name`, `pack_description`.

---

## 3. Propuesta de Estandarización de Backend

### Estructura Canónica de Base de Datos (`sell_details`)
Eliminar prefijos redundantes y usar nombres limpios propios de una línea de orden/venta:

```php
Schema::create('sell_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sell_id')->constrained('sells')->cascadeOnDelete();
    $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
    $table->string('name');                      // Nombre/título de la oferta/pack al comprar
    $table->text('description')->nullable();      // Descripción congelada de la oferta
    $table->bigInteger('unit_price');            // Precio unitario congelado
    $table->integer('quantity');                 // Cantidad de bolsas adquiridas
    $table->timestamps();
});
```

### Contrato Único en `SellResource`
Unificar la salida JSON para todos los endpoints de ventas en [`SellResource`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Resources/SellResource.php):

```php
return [
    'id' => $this->id,
    'pickup_code' => $this->pickup_code,
    'is_picked_up' => $this->is_picked_up,
    'picked_up_at' => $this->picked_up_at,
    'max_pickup_datetime' => $this->max_pickup_datetime,
    'created_at' => $this->created_at,
    'establishment' => [
        'id' => $this->foodEstablishment?->id,
        'name' => $this->foodEstablishment?->name,
        'address' => $this->foodEstablishment?->address,
    ],
    'items' => $this->sellDetails?->map(function ($detail) {
        return [
            'id' => $detail->id,
            'offer_id' => $detail->offer_id,
            'name' => $detail->name,
            'description' => $detail->description,
            'unit_price' => (int) $detail->unit_price,
            'quantity' => (int) $detail->quantity,
            'subtotal' => (int) ($detail->unit_price * $detail->quantity),
        ];
    })->values()->toArray() ?? [],
    'total' => $this->sellDetails?->sum(fn ($d) => $d->unit_price * $d->quantity) ?? 0,
];
```

---

## 4. Checklist de Implementación (Solo Backend)

- [ ] **Migración de Base de Datos**:
  - [ ] Crear migración para estandarizar columnas en `sell_details`:
    - `offer_quantity` ➔ `quantity`
    - `pack_price` ➔ `unit_price`
    - `pack_name` ➔ `name`
    - `pack_description` ➔ `description`
- [ ] **Modelo Eloquent**:
  - [ ] Actualizar [`SellDetail.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Models/SellDetail.php) (casts para `unit_price`, `quantity`).
- [ ] **Actions de Negocio**:
  - [ ] Actualizar [`makeSellAction.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Actions/Sell/makeSellAction.php) para persistir los nuevos nombres de columna.
  - [ ] Actualizar [`getCustomerSellsAction.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Actions/Sell/getCustomerSellsAction.php) o delegar su transformación a `SellResource`.
- [ ] **API Resources y Controladores**:
  - [ ] Refactorizar [`SellResource.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Resources/SellResource.php) con la estructura canónica (`items`, `unit_price`, `quantity`, `subtotal`).
  - [ ] Aplicar `SellResource` de forma consistente en [`SellController.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Controllers/SellController.php) (`adminSells`, `adminSellDetail`, `adminCustomerSells`).
  - [ ] Limpiar [`SellerSellController.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Controllers/SellerSellController.php) (`sellerSells`, `checkCustomerCode`), eliminando la duplicación de `offer_title` y `pack_name`.
- [ ] **Mails y Vistas Blade**:
  - [ ] Actualizar [`purchase_confirmation.blade.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/resources/views/emails/purchase_confirmation.blade.php) con los atributos `$detail->name`, `$detail->unit_price`, `$detail->quantity`.
- [ ] **Factories y Seeders**:
  - [ ] Actualizar [`SellDetailFactory.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/database/factories/SellDetailFactory.php).
  - [ ] Actualizar [`SellFactory.php`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/database/factories/SellFactory.php).
- [ ] **Pruebas Automatizadas (Pest PHP)**:
  - [ ] Actualizar aserciones en `CustomerPurchasesTest.php`.
  - [ ] Actualizar aserciones en `CustomerSellControllerTest.php`.
  - [ ] Actualizar aserciones en `SellControllerTest.php`.
  - [ ] Actualizar aserciones en `SellerSellControllerTest.php`.
  - [ ] Actualizar aserciones en `SendPurchaseConfirmationEmailTest.php`.
