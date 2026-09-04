# Documentación Técnica de Tatelestai

Bienvenido a la documentación oficial de **Tatelestai**. Este sistema de documentación sigue rigurosamente el **Framework Diátaxis**, un estándar de la industria que separa el contenido en cuatro cuadrantes según el objetivo del lector y la naturaleza de la información, complementado con una sección de gobernanza de proyecto.

---


### 1. [Tutoriales](./tutorials/) — *Orientado al Aprendizaje*
Pensados para quien recién clona el proyecto y necesita una experiencia guiada paso a paso para lograr un resultado tangible sin abrumarse con la teoría.
* [01. Onboarding y Primer Despliegue Local](./tutorials/01-onboarding-entorno-local.md)
* [02. Configuración Detallada de Variables de Entorno (.env)](./tutorials/configuracion-variables-entorno.md)

---

### 2. [Guías How-To](./how-to/) — *Orientado a Tareas*
Soluciones directas a problemas y operaciones habituales del ciclo de desarrollo.
* [Cómo ejecutar tests y control de calidad con Pest y Pint](./how-to/ejecutar-tests-y-calidad.md)
* [Cómo indexar y sincronizar datos en Typesense](./how-to/sincronizar-busqueda-typesense.md)

---

### 3. [Referencia](./reference/) — *Orientado a la Información Técnica*
Descripción técnica exacta, fría y exhaustiva de contratos, configuraciones y modelos. No enseña ni explica motivos, solo describe hechos.
* [Servicios, Contenedores y Puertos](./reference/servicios-y-puertos.md)
* [Variables de Entorno (.env)](./reference/variables-entorno.md)

---

### 4. [Explicación y Arquitectura](./explanation/) — *Orientado al Entendimiento*
El trasfondo teórico, análisis de diseño y justificación de por qué el sistema fue construido de esta manera.
* [Lógica de Dominio y Reglas de Negocio](./explanation/logica-de-negocio-y-reglas.md)
* [Control de Concurrencia y Stock (Pessimistic Locking)](./explanation/concurrencia-y-stock.md)
* [Máquinas de Estado del Dominio (Seller, Offer, Cart, Sell)](./explanation/maquinas-de-estado.md)
* [Motor de Búsqueda y Patrón Adaptador Typesense](./explanation/busqueda-patron-adaptador.md)
* [Evaluación y Criterios de Calidad Arquitectónica](./explanation/evaluacion-arquitectura.md)
* [Architecture Decision Records (ADRs)](./explanation/adrs/)

---

### 5. [Gestión y Alcance del Proyecto](./project/) — *Planificación, Backlog y Gobierno*
Planificación estratégica, priorización de requisitos funcionales y hojas de ruta de refactorización para la evolución ordenada del sistema.
* [Roadmap de Desarrollo y Fases de Implementación](./project/roadmap-de-desarrollo.md)
* [Alcance Funcional y Priorización MoSCoW](./project/alcance-y-requerimientos-moscow.md)
* [Plan de Refactorización y Mejoras de Arquitectura](./project/plan-mejoras-arquitectura.md)
* [Especificación Funcional: Módulo de Administración](./project/especificacion-funcional-admin.md)
