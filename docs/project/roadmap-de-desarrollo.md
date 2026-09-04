# Roadmap de Desarrollo y Aprendizaje — Tatelestai

> **Tipo**: Gestión de Proyecto / Planificación  
> **Objetivo**: Establecer las fases e hitos de evolución técnica, refactorización y nuevas características de Tatelestai, vinculando cada requerimiento a fundamentos teóricos de ingeniería de software para defensa técnica y tesis.

---

## Metodología de Trabajo

Cada fase sigue el ciclo: **Estudiar concepto ➔ Auditar el estado actual del repositorio ➔ Aplicar y refactorizar con tests**.  
El progreso se mide por cumplimiento de objetivos de calidad arquitectónica y cobertura, no por días calendario.

---

## Fase 1: Arquitectura Limpia y Desacoplamiento del Backend

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **1.1** | Separation of Concerns (SoC), Single Responsibility (SRP), Cohesión y Acoplamiento | Mapear archivos a sus capas (Presentación, Aplicación, Dominio, Infraestructura). Detectar violaciones de SRP en Actions y Controllers. |
| **1.2** | SOLID (foco en S, D, O, I), Patrón Adapter (Ports & Adapters) | Crear interfaces y contratos para servicios externos (`GmailService`, `GooglePlacesService`, Typesense). Registrar bindings en el Service Container. |
| **1.3** | Flujo Request ➔ DTO ➔ Action ➔ Resource | Implementar DTOs y FormRequests. Desacoplar `CreateOfferAction` y `AddToCartAction` (eliminar dependencias de `Request` y `Auth::id()`). |
| **1.4** | Value Objects y Primitive Obsession | Identificar conceptos de dominio con reglas propias (coordenadas geográficas, moneda, cantidades) y encapsularlos en objetos inmutables. |
| **1.5** | Transformación de Respuestas (API Resources) | Crear `JsonResource` para `Offer`, `Sell`, `FoodEstablishment`. Eliminar transformaciones manuales con `->map()`. |
| **1.6** | Manejo de Errores y Excepciones de Dominio | Crear excepciones específicas (`InsufficientStockException`, `OfferExpiredException`, `InvalidPurchaseTokenException`). Centralizar formato en handler. |
| **1.7** | Eventos de Dominio y Desacoplamiento Asíncrono | Implementar evento `PurchaseCompleted` y listener `SendConfirmationEmail` encolable (`ShouldQueue`). |
| **1.8** | Decisiones de Arquitectura (ADRs) | Documentar los ADRs justificando la estructura en capas y el uso de Actions de responsabilidad única. |

---

## Fase 2: Base de Datos, Transacciones y Control de Concurrencia

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **2.1** | Normalización, Foreign Keys, Constraints y ACID | Auditar migraciones: claves foráneas, reglas `onDelete`, tipos de datos e integridad referencial en PostgreSQL. |
| **2.2** | CHECK Constraints e Índices | Agregar `CHECK (stock >= 0)` en PostgreSQL e índices en columnas de búsqueda, ordenamiento y filtros frecuentes. |
| **2.3** | Transacciones y Niveles de Aislamiento | Revisar `DB::transaction()` asegurando que abarque la unidad de trabajo atómica completa. |
| **2.4** | Race Conditions y Bloqueo Pesimista (`lockForUpdate`) | Implementar lock pesimista en el proceso de reserva/compra para evitar sobreventas (*overselling*). |
| **2.5** | Bloqueo Optimista y Operaciones Atómicas | Aplicar decrementos atómicos garantizando consistencia absoluta en el inventario. |

---

## Fase 3: Modelo de Datos y Feature de Packs

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **3.1** | DDD Táctico: Entities, Value Objects y Ubiquitous Language | Alinear el modelo y vocabulario del código con el negocio real de rescate alimentario. |
| **3.2** | Modelado Relacional para Nuevos Casos de Uso | Diseñar migración para ofertas tipo pack (`offer_type`: standard / pack, items asociados opcionales). |
| **3.3** | Optimización de Consultas (N+1, Eager Loading) | Auditar queries con Telescope o `EXPLAIN`. Implementar eager loading en relaciones anidadas. |
| **3.4** | Backend Feature Packs | Implementar creación, validación y persistencia de ofertas tipo Pack (FormRequest, DTO, Action). |
| **3.5** | Flujo de Compra de Packs | Integrar packs en el flujo unificado de listado, reserva y compra asegurando compatibilidad con el control de concurrencia. |

---

## Fase 4: API REST y Geolocalización

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **4.1** | Estándares REST, Semántica HTTP e Idempotencia | Auditar rutas, verbos HTTP (`GET`, `POST`, `PUT/PATCH`, `DELETE`), convenciones de nomenclatura y códigos de respuesta. |
| **4.2** | Paginación, Filtros y Respuestas de Error Uniformes | Estandarizar estructura de errores y paginación en todos los endpoints públicos y privados. |
| **4.3** | Geolocalización y Búsqueda Espacial | Configurar latitud/longitud en establecimientos y habilitar búsqueda geográfica por radio mediante Typesense. |
| **4.4** | Mapas en Frontend (Leaflet / OpenStreetMap) | Integrar mapa interactivo en Vue con marcadores dinámicos y solicitud de permisos de ubicación. |
| **4.5** | Filtros de Búsqueda Avanzados | Implementar filtros combinados: radio de distancia, rango de precios, tipo de establecimiento y categoría. |

---

## Fase 5: Estrategia de Testing Integral

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **5.1** | Pirámide de Testing (Unit vs Feature vs Integration) | Definir la suite de pruebas y configurar Pest PHP con Factories y Database Refresh. |
| **5.2** | Tests Unitarios de Lógica de Negocio | Testear Actions puras: validaciones de stock, cálculo de totales, expiración y reglas de dominio. |
| **5.3** | Feature Tests de Flujos Críticos | Testear el ciclo completo de publicación, agregado al carrito y compra (happy path y edge cases). |
| **5.4** | Tests de Concurrencia y Autorización | Validar requests paralelos sobre el mismo stock y verificar que los roles/permisos no permitan acceso indebido. |
| **5.5** | Tests de Nuevas Features | Cubrir la creación y compra de packs, así como la precisión de los filtros y búsqueda geolocalizada. |

---

## Fase 6: Seguridad, Frontend y Observabilidad

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **6.1** | OWASP Top 10, IDOR y Mass Assignment | Auditar endpoints contra IDOR (evitar acceso a órdenes ajenas) y proteger asignación masiva de atributos. |
| **6.2** | Autorización Fina (Policies/Gates) y Rate Limiting | Proteger modelos con Policies y aplicar límites de tasa (throttling) en autenticación y checkout. |
| **6.3** | Observabilidad y Registro de Actividad | Estructurar logs en eventos críticos del negocio y configurar health checks. |
| **6.4** | Arquitectura Frontend en Vue 3 (Pinia + Composables) | Desacoplar llamadas a la API de los componentes hacia servicios/composables y centralizar estado con Pinia. |
| **6.5** | Experiencia de Usuario (UI/UX States) | Implementar loading skeletons, empty states, diálogos de confirmación y manejo intuitivo de errores en pantalla. |

---

## Fase 7: Infraestructura, Documentación y Tesis

| Hito | Conceptos a Estudiar | Auditar y Aplicar en Tatelestai |
|---|---|---|
| **7.1** | Contenedorización con Docker Compose | Configurar entorno reproducible: Laravel, PostgreSQL, Typesense y Node. |
| **7.2** | Validación de Instalación Limpia | Verificar que el proyecto inicie sin fricción con un solo comando en cualquier máquina. |
| **7.3** | Patrones de Diseño y Diagramado (Modelo C4) | Documentar patrones aplicados (Strategy, Adapter, Observer, Action) y generar diagramas de arquitectura. |
| **7.4** | Portal de Documentación (Diátaxis) | Elaborar guía completa de instalación, arquitectura, referencia de servicios y comandos de testing. |
| **7.5** | Preparación y Justificación Académica | Mapear cada decisión técnica con su fundamento teórico para la defensa del proyecto/tesis. |
