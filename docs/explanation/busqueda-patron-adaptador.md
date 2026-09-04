# Explicación: Arquitectura de Búsqueda y Patrón Adaptador

> **Tipo**: Explicación / Arquitectura (Diátaxis)  
> **Objetivo**: Fundamentar el diseño arquitectónico desacoplado para el motor de búsqueda, justificando la implementación del Patrón Adaptador sobre Typesense y la geolocalización por radio de proximidad.

---

## 1. Motivación y Diagnóstico del Problema

En aplicaciones que utilizan motores de búsqueda externos (como Typesense, Meilisearch o Elasticsearch), es muy común que el código de los controladores HTTP quede fuertemente acoplado a la API concreta del motor o a los métodos mágicos del ORM (`Offer::search()`).

### Problemas del Acoplamiento Directo:
1. **Controladores con conocimiento de infraestructura**: Si el controlador llama directamente a métodos de Scout (`Offer::search()->where(...)`), cualquier cambio de sintaxis en el motor obliga a refactorizar capas de presentación.
2. **Dificultad para Testing**: Probar endpoints HTTP requiere obligatoriamente tener una instancia real de Typesense corriendo, lo cual ralentiza las suites de pruebas continuas (CI).
3. **Imposibilidad de intercambiar motores**: Migrar en el futuro a Meilisearch o Algolia implicaría reescribir decenas de controladores y modelos.

---

## 2. Solución de Diseño: El Patrón Adaptador (*Adapter Pattern*)

Para resolver el acoplamiento y adherir al **Principio de Inversión de Dependencias (DIP)** de SOLID, se introduce un contrato abstracto intermedio:

```mermaid
flowchart TD
    subgraph Presentation["Capa de Presentación"]
        Controller["OfferCustomerController"]
    end

    subgraph Application["Capa de Aplicación / Dominio"]
        Action["SearchOffersAction"]
        Contract["SearchServiceInterface (Interface)"]
        DTO["SearchQueryDTO"]
    end

    subgraph Infrastructure["Capa de Infraestructura"]
        TypesenseAdapter["TypesenseSearchAdapter"]
        MeiliAdapter["MeilisearchSearchAdapter (Futuro)"]
        NullAdapter["NullSearchAdapter (Testing)"]
        ScoutDriver["Laravel Scout / Typesense Server"]
    end

    Controller --> Action
    Action --> DTO
    Action --> Contract
    Contract <|.. TypesenseAdapter
    Contract <|.. MeiliAdapter
    Contract <|.. NullAdapter
    TypesenseAdapter --> ScoutDriver
```

### Componentes Clave:
* **`SearchServiceInterface`**: Contrato abstracto que define qué operaciones de búsqueda necesita Tatelestai (`searchOffers`, `indexOffer`, `removeOfferFromIndex`), sin mencionar nunca a Typesense.
* **`SearchQueryDTO`**: Objeto inmutable fuertemente tipado que encapsula los parámetros de búsqueda (término de texto, coordenadas geográficas, radio en kilómetros, filtros por estado).
* **`TypesenseSearchAdapter`**: Implementación concreta que traduce el DTO hacia las llamadas nativas de Typesense y Laravel Scout.
* **`NullSearchAdapter`**: Implementación nula (*dummy*) que permite ejecutar tests unitarios ultrarrápidos en memoria sin requerir conexión de red.

---

## 3. Geobúsqueda y Filtrado por Radio de Proximidad

Uno de los requerimientos centrales de Tatelestai es que los compradores puedan encontrar ofertas de comida cercanas a su ubicación física.

### Modelado Geoespacial:
Typesense cuenta con soporte nativo para campos de tipo `geopoint` (`[latitud, longitud]`).

1. **Esquema de Colección (`config/scout.php`)**:
   El modelo `Offer` indexa un campo especial de coordenadas:
   ```php
   [
       'name' => '_geoloc',
       'type' => 'geopoint',
       'optional' => true,
   ]
   ```

2. **Indexación en el Modelo (`Offer.php`)**:
   Al enviar el documento al motor, las coordenadas se extraen del establecimiento gastronómico asociado:
   ```php
   public function toSearchableArray(): array
   {
       return [
           'id' => (string) $this->id,
           'title' => $this->title,
           'price' => (float) $this->price,
           'state' => $this->state->value,
           '_geoloc' => [
               (float) $this->foodEstablishment->latitude,
               (float) $this->foodEstablishment->longitude,
           ],
       ];
   }
   ```

3. **Filtrado y Ordenamiento por Distancia**:
   El adaptador traduce la solicitud a un filtro de rango geográfico en Typesense:
   ```text
   filter_by: _geoloc:(-34.6037, -58.3816, 5 km) && state:=active
   sort_by: _geoloc(-34.6037, -58.3816):asc
   ```
   * Las ofertas fuera del radio estipulado quedan automáticamente excluidas.
   * Los resultados se ordenan en orden ascendente de proximidad (las ofertas más cercanas aparecen primero).

---

## 4. Estrategia de Testing (TDD)

El desacoplamiento del adaptador permite probar la lógica de búsqueda en dos niveles bien diferenciados:

1. **Pruebas Unitarias del Adaptador (`tests/Unit/Search/TypesenseSearchAdapterTest.php`)**:
   * Valida que una oferta dentro del radio (ej. Obelisco CABA) sea incluida, y una oferta lejana (ej. La Plata, a 55 km) sea excluida.
   * Valida que la lista devuelta respete el orden estricto de distancia en kilómetros.
2. **Pruebas de Feature (`tests/Feature/OfferCustomerControllerTest.php`)**:
   * Simula la petición HTTP completa `GET /api/offers?search=Pizza&lat=-34.6037&lng=-58.3816&radius=5`.
   * Verifica la respuesta `200 OK`, el formato del payload y la presencia de metadatos geográficos.

---

## 5. Beneficios Académicos y de Producción

* **Principio Abierto/Cerrado (OCP)**: Añadir un nuevo motor de búsqueda solo requiere crear una nueva clase que implemente `SearchServiceInterface`, sin tocar una sola línea de los controladores existentes.
* **Resiliencia**: Si el servicio de búsqueda externo sufre una degradación o falla temporal, el contenedor de servicios puede conmutar dinámicamente a una consulta fallback en PostgreSQL.
