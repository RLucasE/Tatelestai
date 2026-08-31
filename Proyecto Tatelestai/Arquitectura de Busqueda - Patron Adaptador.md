# Arquitectura de Búsqueda — Patrón Adaptador

> **Objetivo**: Desacoplar la lógica de búsqueda del motor concreto (Typesense) para permitir cambiar o añadir motores de búsqueda en el futuro sin modificar la lógica de negocio ni los controladores.

---

## 1. Estado Actual — Diagnóstico

### Cómo funciona hoy

La integración con Typesense está construida sobre **Laravel Scout**, que actúa como capa de abstracción del framework. Sin embargo, el acoplamiento existe en varios niveles:

```
HTTP Request
    └─► OfferCustomerController::index()
            └─► Offer::search($query)          ← Scout llama directamente a Typesense
                    └─► ->where(\'state\', ...)
                    └─► ->get()
                    └─► ->filter(...)
                    └─► ->forPage(...)
```

### Archivos involucrados

| Archivo | Rol | Problema de acoplamiento |
|---------|-----|--------------------------|
| `config/scout.php` | Configuración del driver Scout + esquema Typesense | El esquema de colección de Typesense (`model-settings`) está **hardcodeado** con el modelo `Offer` |
| `app/Models/Offer.php` | Modelo Eloquent | Usa el trait `Searchable` + define `toSearchableArray()` acoplando formato de datos al motor |
| `app/Http/Controllers/OfferCustomerController.php` | Controlador HTTP | **Llama `Offer::search()` directamente**, mezcla lógica de búsqueda con construcción de respuesta HTTP |
| `app/Http/Controllers/ProductController.php` | Controlador HTTP | Llama `$offer->searchable()` para re-indexar directamente desde el controller |
| `.env` | Variables de entorno | `SCOUT_DRIVER=typesense` — el driver está acoplado a nivel de aplicación |

### Problemas concretos

**1. El Controller conoce al motor de búsqueda**

El filtro `->where()` en Scout tiene una sintaxis específica de cada driver. Con Typesense funciona así; con Meilisearch o Algolia la API puede variar.

**2. La re-indexación ocurre en el Controller**

`ProductController.php` llama a `$offer->searchable()` dentro del loop de actualización de productos. Esto es un efecto secundario de infraestructura (indexación) viviendo en la capa HTTP.

**3. El esquema de Typesense está en `config/scout.php`**

El bloque `model-settings` contiene la definición de la colección de Typesense (campos, tipos, ordenamiento), lo cual es un detalle de infraestructura viviendo en configuración general de la app.

---

## 2. Arquitectura Propuesta — Patrón Adaptador

### Concepto

Se introduce una **interfaz de contrato** (`SearchServiceInterface`) que define qué operaciones de búsqueda necesita la aplicación, sin importar qué motor las implementa.

```
Capa HTTP (Controller)
    └─► SearchServiceInterface  ← contrato abstracto
            ├─► TypesenseSearchAdapter   ← implementación actual
            ├─► MeilisearchSearchAdapter ← futura implementación
            └─► NullSearchAdapter        ← para tests / modo sin buscador
```

### Diagrama de capas

```
┌─────────────────────────────────┐
│  HTTP Request                   │
│  OfferCustomerController        │
└────────────┬────────────────────┘
             │ depende de ↓
┌────────────▼────────────────────┐
│  SearchServiceInterface         │  ← Contrato abstracto
│  (App\Contracts\Search)         │
└────────────┬────────────────────┘
             │ implementado por ↓
    ┌─────────┬─────────┬─────────┐
    ▼         ▼         ▼         
┌───────┐ ┌───────┐ ┌───────┐
│Typese-│ │Meili- │ │ Null  │
│nse    │ │search │ │(tests)│
│Adapter│ │Adapter│ │Adapter│
└───┬───┘ └───┬───┘ └───────┘
    │         │
    ▼         ▼
Laravel Scout / Driver correspondiente
```

---

## 3. Estructura de Archivos a Crear

```
app/
├── Contracts/
│   └── Search/
│       └── SearchServiceInterface.php        ← [NUEVO] Contrato de búsqueda
│
├── Search/
│   ├── DTOs/
│   │   └── SearchQueryDTO.php                ← [NUEVO] Input de búsqueda tipado
│   │
│   └── Adapters/
│       ├── TypesenseSearchAdapter.php        ← [NUEVO] Implementación con Scout/Typesense
│       └── NullSearchAdapter.php             ← [NUEVO] Adaptador nulo para tests
│
├── Actions/
│   └── Offers/
│       └── SearchOffersAction.php            ← [NUEVO] Encapsula la lógica de búsqueda
│
└── Providers/
    └── AppServiceProvider.php                ← [MODIFICAR] Registrar el binding
```

---

## 4. Código de Implementación

### 4.1 — Contrato: SearchServiceInterface

```php
<?php
// app/Contracts/Search/SearchServiceInterface.php

namespace App\Contracts\Search;

use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

interface SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection;
    public function indexOffer(int $offerId): void;
    public function removeOfferFromIndex(int $offerId): void;
}
```

### 4.2 — DTO de entrada: SearchQueryDTO

```php
<?php
// app/Search/DTOs/SearchQueryDTO.php

namespace App\Search\DTOs;

use App\Enums\OfferState;

final class SearchQueryDTO
{
    public function __construct(
        public readonly string $query,
        public readonly string $state = OfferState::ACTIVE->value,
        public readonly int    $page = 1,
        public readonly int    $perPage = 20,
    ) {}
}
```

### 4.3 — Adaptador Typesense: TypesenseSearchAdapter

```php
<?php
// app/Search/Adapters/TypesenseSearchAdapter.php

namespace App\Search\Adapters;

use App\Contracts\Search\SearchServiceInterface;
use App\Models\Offer;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class TypesenseSearchAdapter implements SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection
    {
        return Offer::search($query->query)
            ->where(\'state\', $query->state)
            ->get()
            ->filter(fn($offer) => $offer->expiration_datetime >= now())
            ->forPage($query->page, $query->perPage)
            ->load([
                \'fullProducts\',
                \'foodEstablishment\' => fn($q) => $q->select(\'id\', \'name\', \'address\'),
            ]);
    }

    public function indexOffer(int $offerId): void
    {
        Offer::findOrFail($offerId)->searchable();
    }

    public function removeOfferFromIndex(int $offerId): void
    {
        Offer::findOrFail($offerId)->unsearchable();
    }
}
```

### 4.4 — Adaptador Nulo: NullSearchAdapter

```php
<?php
// app/Search/Adapters/NullSearchAdapter.php

namespace App\Search\Adapters;

use App\Contracts\Search\SearchServiceInterface;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class NullSearchAdapter implements SearchServiceInterface
{
    public function searchOffers(SearchQueryDTO $query): Collection { return collect(); }
    public function indexOffer(int $offerId): void {}
    public function removeOfferFromIndex(int $offerId): void {}
}
```

### 4.5 — Action: SearchOffersAction

```php
<?php
// app/Actions/Offers/SearchOffersAction.php

namespace App\Actions\Offers;

use App\Contracts\Search\SearchServiceInterface;
use App\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Collection;

class SearchOffersAction
{
    public function __construct(
        private readonly SearchServiceInterface $searchService
    ) {}

    public function execute(SearchQueryDTO $query): Collection
    {
        return $this->searchService->searchOffers($query);
    }
}
```

### 4.6 — Registro del binding en AppServiceProvider

```php
use App\Contracts\Search\SearchServiceInterface;
use App\Search\Adapters\TypesenseSearchAdapter;
use App\Search\Adapters\NullSearchAdapter;

public function register(): void
{
    $searchDriver = config(\'scout.driver\', \'typesense\');

    $this->app->bind(SearchServiceInterface::class, match($searchDriver) {
        \'typesense\' => TypesenseSearchAdapter::class,
        \'null\'      => NullSearchAdapter::class,
        default     => TypesenseSearchAdapter::class,
    });
}
```

---

## 5. Cómo Cambiar de Motor en el Futuro

Para migrar de Typesense a Meilisearch, solo se necesita:

1. Crear `app/Search/Adapters/MeilisearchSearchAdapter.php` implementando `SearchServiceInterface`
2. Cambiar el driver en `.env`: `SCOUT_DRIVER=meilisearch`
3. Registrar el nuevo caso en el `match()` del `AppServiceProvider`

El controller, la action, el modelo y toda la lógica de negocio permanecen **sin cambios**.

---

## 6. Comparación: Antes vs Después

| Aspecto | Estado Actual | Con Adaptador |
|---------|---------------|---------------|
| **Cambiar motor** | Modificar controller, model, config | Solo crear nuevo adaptador + cambiar `.env` |
| **Testear búsqueda** | Requiere Typesense corriendo | Inyectar `NullSearchAdapter` o mock de la interfaz |
| **Responsabilidad del Controller** | Orquesta la búsqueda completa | Solo construye el DTO y llama a la Action |
| **Dónde vive la lógica de búsqueda** | Controller + Model mezclados | `TypesenseSearchAdapter` + `SearchOffersAction` |
| **Acoplamiento a Scout** | Toda la app | Solo los adaptadores concretos |
| **Añadir nuevo motor** | Refactorizar múltiples archivos | Crear 1 archivo + actualizar 2 líneas |

---

## 7. Plan de Migración — Paso a Paso

```
[ ] Paso 1 — Crear la interfaz SearchServiceInterface
             app/Contracts/Search/SearchServiceInterface.php

[ ] Paso 2 — Crear el DTO de entrada
             app/Search/DTOs/SearchQueryDTO.php

[ ] Paso 3 — Crear TypesenseSearchAdapter
             app/Search/Adapters/TypesenseSearchAdapter.php

[ ] Paso 4 — Crear NullSearchAdapter
             app/Search/Adapters/NullSearchAdapter.php

[ ] Paso 5 — Crear SearchOffersAction
             app/Actions/Offers/SearchOffersAction.php

[ ] Paso 6 — Registrar el binding en AppServiceProvider

[ ] Paso 7 — Refactorizar OfferCustomerController

[ ] Paso 8 — Refactorizar ProductController

[ ] Paso 9 — Ejecutar los tests: php artisan test --filter=OfferCustomerControllerTest

[ ] Paso 10 — Documentar SEARCH_DRIVER en .env.example
```

---

## 8. Relación con el Plan de Mejoras de Arquitectura

Este cambio complementa la **Fase 3 — Infraestructura y Servicios** del Plan de Mejoras de Arquitectura:

> **3.2 Crear Interfaces para Servicios Externos**: seguir el mismo patrón que para `GooglePlacesService`, manteniendo consistencia en toda la capa de infraestructura.

---

*Documento generado: agosto 2026 — Tatelestai Backend (Laravel 12 + Scout + Typesense)*
