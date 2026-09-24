# ADR 0004: Transición de Catálogo Tradicional a Bolsas Sorpresa con Cupos Diarios

* **Estado**: Aceptado
* **Fecha**: 2026-09-10
* **Decisores**: Equipo de Producto e Ingeniería Tatelestai
* **Documentos de Referencia**: 
  * [Explicación: Justificación del Cambio de Modelo](../justificacion-cambio-modelo-bolsas-sorpresa.md)
  * [Explicación: Lógica de Negocio de Packs Sorpresa](../logica-negocio-packs-sorpresa.md)

---

## Contexto y Planteamiento del Problema

La concepción inicial de Tatelestai implementó un catálogo global de productos donde los establecimientos gastronómicos debían registrar cada artículo individualmente (nombre, marca, foto, precio, descripción y stock unitario). 

En el ámbito de la reducción del desperdicio alimentario, esta estrategia evidenció tres fallas críticas de diseño:
1. **Fricción operativa extrema**: Los comercios no disponen de tiempo al cierre del turno para catalogar artículos remanentes heterogéneos y discontinuos.
2. **Inmanejabilidad de datos**: El catálogo global derivó en duplicidad masiva y necesidad de heurísticas complejas de normalización y búsqueda semántica de texto completo.
3. **Exclusión del comercio barrial y artesanal**: Negocios que generan alta merma (panaderías, rotiserías familiares) no poseen códigos EAN ni envasado estándar, quedando marginados del sistema.

---

## Criterios de Decisión

* **Mínima fricción para el comerciante**: El proceso de publicación diaria no debe superar los 60 segundos.
* **Inclusión universal**: Cualquier establecimiento gastronómico debe poder sumarse sin requerir digitalización de su inventario.
* **Simplicidad arquitectónica**: Eliminar la sobrecarga de mantenimiento de catálogos globales e indexadores de texto desmesurados.
* **Alineación con el valor nuclear**: Enfocarse exclusivamente en conectar excedentes con rescatistas por proximidad geográfica en ventanas de tiempo críticas.

---

## Opciones Consideradas

1. **Mantener Catálogo Global con motor de búsqueda avanzado (NLP / Elasticsearch / Typesense fuzzy)**: Rechazada. Demanda un esfuerzo técnico perpetuo de normalización de datos sin resolver la fricción del comerciante ni la exclusión del comercio artesanal.
2. **Catálogos locales aislados por comercio (sin catálogo global compartido)**: Rechazada. Reduce la duplicación global pero perpetúa la fricción operativa de carga de productos individuales para el comercio.
3. **Adopción del modelo de Bolsas Sorpresa (*Surprise Bags*) con Cupos Diarios (Estilo Too Good To Go)**: **Elegida**.

---

## Decisión Adoptada

Se adopta el modelo de **bolsas sorpresa con cupos diarios** como núcleo del producto y del dominio. Se abandona la carga obligatoria de productos individuales en el catálogo.

El nuevo esquema se articula en torno a:
* **Rubros comerciales fijos** (`bakery`, `restaurant`, `sushi`, `cafe`, etc.).
* **Plantillas de pack (`PackTemplate`)**: 1 o 2 configuraciones base por comercio con precio bonificado, valor mínimo garantizado, alérgenos declarados y franja horaria habitual.
* **Oferta diaria como cupo entero**: La columna `stock` en `offers` representa el número de bolsas disponibles ese día, protegido mediante la infraestructura existente de concurrencia pesimista (`lockForUpdate` en [ADR-0001](./0001-bloqueo-pesimista-para-control-de-stock.md)).
* **Búsqueda geográfica pura**: Indexación espacial en Typesense (`_geoloc`) priorizando distancia, franja de retiro y categoría.

---

### Consecuencias

* **Positivas**:
  * Publicación de ofertas en 1 clic: fomenta la retención diaria de los comerciantes.
  * Inclusión del 100% de la gastronomía de cercanía (rotiserías, panaderías, cafeterías).
  * Eliminación de complejidad accidental en base de datos y búsqueda de texto.
  * Menor exposición a contingencias legales y rotulados individuales de fecha/lote.
* **Negativas / Mitigaciones**:
  * *Incertidumbre del usuario*: El comprador no elige los ítems individuales.
    * *Mitigación*: Descuentos del 50-70%, compromiso vinculante de valor mínimo garantizado y filtros estrictos por restricciones dietarias/alérgenos.
  * *Refactorización requerida*: Desacoplar la relación mandatoria de ofertas con la tabla pivote `product_offers`.
    * *Mitigación*: Se planifica la migración de datos y soporte de discriminador `offer_type: 'pack' | 'standard'` en el backlog de la tesis.
