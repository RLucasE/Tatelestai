# Explicación: Criterios de Evaluación y Calidad de la Arquitectura

> **Tipo**: Explicación / Arquitectura (Diátaxis)  
> **Objetivo**: Proporcionar un marco conceptual y heurístico para auditar la calidad, testeabilidad, desacoplamiento y evolutividad de la arquitectura de Tatelestai, sirviendo como guía de defensa técnica y análisis de deuda técnica.

---

## 1. Facilidad de Cambio (*Evolvability*)

La métrica principal de una arquitectura sólida es su capacidad de **absorber cambios sin propagarlos en cascada**.

En Tatelestai nos formulamos las siguientes preguntas de diseño:
* ¿Podemos cambiar el motor de búsqueda (Typesense a Meilisearch) sin tocar los controladores?  
  *(Sí, gracias al Patrón Adaptador en `app/Search/Adapters`)*.
* ¿Podemos cambiar la política de expiración de reservas sin modificar la tabla de la base de datos?  
  *(Sí, gracias a que la regla está aislada en `VerifyPurchaseDataFreshnessAction`)*.
* ¿Un nuevo requerimiento de negocio requiere tocar 2 archivos o 20?  
  *(Idealmente solo la Action correspondiente y su FormRequest de entrada)*.

> [!NOTE]
> Si una modificación pequeña en una regla de negocio obliga a alterar controladores, modelos, vistas y migraciones simultáneamente, el sistema sufre de **Shotgun Surgery** (síntoma directo de alto acoplamiento).

---

## 2. Principios Estructurales y su Aplicación en el Proyecto

| Principio | Señal Positiva en Tatelestai | Señal de Alerta / Anti-patrón |
|---|---|---|
| **Alta Cohesión** | Cada **Action** encapsula una única intención del negocio (ej. `CreateOfferAction`, `AddToCartAction`). | Controladores "God Object" con 500 líneas que validan, calculan, persisten y envían emails en el mismo método. |
| **Bajo Acoplamiento** | Las capas se comunican mediante **DTOs** inmutables y contratos abstractos (`SearchServiceInterface`). | Inyección directa de modelos Eloquent mutables a través de capas externas. |
| **Inversión de Dependencias (DIP)** | El dominio y las Actions no conocen detalles de SDKs de terceros ni de protocolos HTTP. | Llamar a `Http::post('api.google.com')` directamente dentro de un modelo o controlador. |
| **Separación de Responsabilidades (SoC)** | Separación estricta entre presentación (FormRequests/Controllers), dominio (Actions/Enums) e infraestructura (Repositories/Services). | Consultas SQL complejas escritas directamente en las rutas o en los controladores. |

---

## 3. Testeabilidad (*Testability*)

Una arquitectura bien diseñada es **inherentemente testeable**:

1. **Tests Unitarios Rápidos sin Dependencias**:  
   Las Actions y reglas de validación se prueban instanciando clases PHP puras, sin necesidad de levantar Nginx, PostgreSQL ni servicios de red externos.
2. **Robustez ante Refactorizaciones**:  
   Las pruebas escritas con Pest evalúan el **comportamiento observable** (entradas y salidas de las Actions) y no los detalles de implementación interna.
3. **Mocks y Fakes Mínimos**:  
   Gracias a que el sistema utiliza DTOs y adaptadores como `NullSearchAdapter`, no se requieren mocks complejos de 50 líneas para simular el motor de búsqueda en los tests.

---

## 4. Proporcionalidad de la Complejidad

En ingeniería de software es fundamental evitar tanto la **subingeniería** (código espagueti en controladores) como la **sobreingeniería** (crear microservicios distribuidos para un marketplace de alcance acotado).

> *"La mejor arquitectura es la más simple que resuelve con solvencia el problema actual y permite evolucionar con elegancia hacia el futuro previsible."*

En Tatelestai se optó por un **Monolito Modular en Laravel 12**:
* Despliegue simple y reproducible con Docker Compose.
* Transacciones ACID locales para garantizar consistencia estricta de stock (`pessimistic locking`).
* Separación interna por capas (Actions + DTOs) que permite, si fuera necesario en el futuro, extraer servicios independientes sin reescribir la lógica de dominio.

---

## 5. Señales de Alerta (*Red Flags*) Auditadas en el Código

Durante las revisiones de código de Tatelestai se vigilan activamente:
* **Dependencias Circulares**: Dos módulos o Actions que se requieren mutuamente.
* **Leaky Abstractions**: Exponer detalles de la base de datos (como ids autoincrementales internos o excepciones de PDO) directamente a las respuestas JSON del cliente.
* **Shared Mutable State**: Modificar variables globales o propiedades compartidas concurrentemente sin bloqueos transaccionales.
