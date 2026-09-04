# Geobúsqueda de Ofertas con Typesense y Tests Automatizados

Extender el sistema de búsqueda para soportar geolocalización (posición + radio) delegando el filtrado y ordenamiento nativo a Typesense. Se implementan primero las pruebas automatizadas (TDD) para validar que la búsqueda por coordenadas funcione y que únicamente se retornen las ofertas dentro del radio estipulado.

## Requisitos y Consideraciones Previas

> **Nota:** Los tests y el backend se ejecutan dentro del entorno de Docker del proyecto. Tras modificar el schema de Typesense en `scout.php`, se debe recrear la colección con `php artisan scout:flush` y `php artisan scout:import`.

---

## 1. Tests Automatizados (A escribir primero - TDD)

Se agregarán pruebas específicas tanto a nivel unitario (adaptador) como a nivel de integración/feature (endpoint HTTP).

### A. Test Unitario — `tests/Unit/Search/TypesenseSearchAdapterTest.php`

Se agregarán dos tests en `TypesenseSearchAdapterTest.php`:

1. **`it_searches_offers_within_a_given_geolocation_and_radius`**:
   - Crea un establecimiento cercano **A** (ej. Obelisco CABA: `-34.6037, -58.3816`) con oferta *"Pizza Napolitana"*.
   - Crea un establecimiento lejano **B** (ej. La Plata: `-34.9214, -57.9545`, a ~55 km) con oferta *"Pizza Fugazzeta"*.
   - Indexa ambas ofertas (`$offerA->searchable()`, `$offerB->searchable()`).
   - Ejecuta `$adapter->searchOffers()` con `latitude: -34.6037`, `longitude: -58.3816`, `radiusKm: 10.0` y `query: 'Pizza'`.
   - **Aserciones:**
     - El resultado contiene la oferta de A (dentro del radio).
     - El resultado **NO** contiene la oferta de B (fuera del radio).

2. **`it_orders_offers_by_distance_when_geofilter_is_applied`**:
   - Crea dos establecimientos dentro del radio: uno a 1 km y otro a 4 km del punto de consulta.
   - Ejecuta la búsqueda y comprueba que el primer elemento devuelto sea el más cercano (`sort_by` por distancia ascendente).

---

### B. Test de Feature (API Endpoint) — `tests/Feature/OfferCustomerControllerTest.php`

Se agregará un test en `OfferCustomerControllerTest.php`:

1. **`it_can_search_offers_with_geolocation_and_radius`**:
   - Configura un establecimiento cercano y uno lejano.
   - Envía solicitud `GET /api/offers?search=Pizza&lat=-34.6037&lng=-58.3816&radius=5`.
   - **Aserciones:**
     - Respuesta `200 OK`.
     - La oferta cercana está presente en `data`.
     - La oferta lejana **no** está presente en `data`.
     - La respuesta incluye los campos `establishment_latitude` y `establishment_longitude`.

---

## 2. Implementación de Cambios en Código

### 1. `config/scout.php`
Agregar el campo `_geoloc` de tipo `geopoint` en `fields` del modelo `Offer`:
```php
[
    'name' => '_geoloc',
    'type' => 'geopoint',
    'optional' => true,
],
```

### 2. `app/Search/DTOs/SearchQueryDTO.php`
Agregar parámetros geográficos opcionales y método de validación `hasGeoFilter()`:
```php
final class SearchQueryDTO
{
    public function __construct(
        public readonly string $query,
        public readonly string $state = OfferState::ACTIVE->value,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly float $radiusKm = 5.0,
    ) {}

    public function hasGeoFilter(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
```

### 3. `app/Models/Offer.php`
Incluir `_geoloc` en `toSearchableArray()` tomando coordenadas de la relación `foodEstablishment`:
```php
if ($this->foodEstablishment?->latitude !== null && $this->foodEstablishment?->longitude !== null) {
    $array['_geoloc'] = [
        (float) $this->foodEstablishment->latitude,
        (float) $this->foodEstablishment->longitude,
    ];
}
```

### 4. `app/Search/Adapters/TypesenseSearchAdapter.php`
Implementar la versión optimizada de `performSearch`:
```php
public function performSearch(SearchQueryDTO $query): Collection
{
    $now = now()->timestamp;
    $filterConditions = [
        "state:={$query->state}",
        "expiration_datetime:>={$now}",
    ];

    $options = [];

    if ($query->hasGeoFilter()) {
        $filterConditions[] = "_geoloc:({$query->latitude}, {$query->longitude}, {$query->radiusKm} km)";
        $options['sort_by'] = "_geoloc({$query->latitude}, {$query->longitude}):asc";
    }

    $options['filter_by'] = implode(' && ', $filterConditions);

    $paginator = Offer::search($query->query)
        ->options($options)
        ->paginate($query->perPage, 'page', $query->page);

    $paginator->load([
        'fullProducts',
        'foodEstablishment' => fn($q) => $q->select('id', 'name', 'address', 'latitude', 'longitude'),
    ]);

    return $paginator->getCollection();
}
```

### 5. `app/Http/Controllers/OfferCustomerController.php`
Recibir parámetros `lat`, `lng`, `radius` y mapear las coordenadas en `transformOfferToDTO`:
```php
$searchQueryDTO = new SearchQueryDTO(
    query: $searchQuery,
    page: (int) $page,
    perPage: $perPage,
    latitude: $request->filled('lat') ? (float) $request->get('lat') : null,
    longitude: $request->filled('lng') ? (float) $request->get('lng') : null,
    radiusKm: $request->filled('radius') ? (float) $request->get('radius') : 5.0,
);
```

### 6. `app/DTOs/OfferDTO.php`
Agregar `establishment_latitude` y `establishment_longitude` para retorno a clientes:
```php
public readonly ?float $establishment_latitude = null,
public readonly ?float $establishment_longitude = null,
```

---

## 3. Plan de Verificación

### Tests Automatizados
Ejecutar la suite de tests en el contenedor de desarrollo:
```bash
docker exec -it tatelestai-php-fpm php artisan test --filter=TypesenseSearchAdapterTest
docker exec -it tatelestai-php-fpm php artisan test --filter=OfferCustomerControllerTest
```

### Re-indexado en Typesense
```bash
docker exec -it tatelestai-php-fpm php artisan scout:flush "App\Models\Offer"
docker exec -it tatelestai-php-fpm php artisan scout:import "App\Models\Offer"
```

