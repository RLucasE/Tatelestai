# Tatelestai — Índice General del Proyecto

> **Objetivo**: Construir una aplicación que, si alguien la ve en una entrevista o en la defensa de tesis, demuestre solvencia técnica, arquitectura limpia y decisiones fundamentadas.

---

## 🎯 Plan de Trabajo

* **[Roadmap de Desarrollo y Aprendizaje](./ROADMAP.md)**: Plan estructurado por fases temáticas e hitos para aprender, auditar y aplicar cada concepto técnico junto con las nuevas features.

---

## Áreas de Auditoría y Desarrollo

| Área | Prioridad | Conceptos clave |
|------|-----------|-----------------|
| 1. Arquitectura | Crítico | Separation of Concerns, SRP, Cohesión/Acoplamiento, Capas, Inversión de Dependencias |
| 2. Backend Laravel | Crítico | Controllers delgados, DTOs, FormRequests, Actions puras, API Resources |
| 3. Base de Datos (PostgreSQL) | Crítico | Constraints, CHECK (`stock >= 0`), Foreign Keys, Índices, ACID, N+1 |
| 4. Concurrencia y Stock | Crítico | Transacciones, Race Conditions, Pessimistic / Optimistic Locking, Operaciones Atómicas |
| 5. API REST | Importante | Semántica HTTP, Códigos de estado, Naming consistente, Formato uniforme de errores |
| 6. Seguridad | Importante | OWASP básico, IDOR, Autorización (Policies/Gates), Mass Assignment, Rate Limiting |
| 7. Testing | Importante | Pirámide de testing, Tests de flujos críticos (compra, concurrencia, permisos), Pest |
| 8. Frontend Vue | Importante | Estructura modular, Stores (Pinia), Composables, Separación de lógica y UI |
| 9. Búsqueda y Geolocalización | Importante | Typesense (Scout), Filtros avanzados, Búsqueda geográfica por radio |
| 10. Docker y Despliegue | Importante | Docker Compose reproducible (Laravel, Postgres, Typesense, Redis, Node) |
| 11. UX y Frontend Profesional | Diferenciador | Loading states, Empty states, Manejo de errores claro, Responsive, Feedback al usuario |
| 12. Tesis y Justificación Académica | Importante | Mapeo de decisiones técnicas a fundamentos teóricos, preparación de defensa |

---

## Criterios de Prioridad

| Categoría | Significado |
|-----------|-------------|
| **Crítico** | Resolver primero. Impacta directamente en la integridad, consistencia y arquitectura base del software. |
| **Importante** | Hace que el proyecto sea profesional, mantenible, seguro y reproducible. |
| **Diferenciador** | Aporta un valor agregado visible y destacado sin comprometer el alcance principal. |

---

## Documentos del Proyecto

| Documento | Descripción |
|-----------|-------------|
| [ROADMAP.md](./ROADMAP.md) | Cronograma intensivo día por día con metas de estudio y aplicación |
| [Plan de Mejoras de Arquitectura.md](./Plan%20de%20Mejoras%20de%20Arquitectura.md) | Fases de refactorización técnica y desacoplamiento de capas |
| [Analisis Logica de Negocios.md](./Analisis%20Logica%20de%20Negocios.md) | Análisis exhaustivo de qué es regla de negocio vs detalle de infraestructura |
| [evaluacion_arquitectura.md](./evaluacion_arquitectura.md) | Criterios para evaluar la calidad, evolutividad y testeabilidad del diseño |
| [Funcionalidades/Funcionalidades.md](./Funcionalidades/Funcionalidades.md) | Lista de características y estado de implementación |
| [Funcionalidades/MoSCoW.md](./Funcionalidades/MoSCoW.md) | Priorización de requisitos según metodología MoSCoW |

---

## Metodología de Trabajo

1. **Avanzar paso a paso**: Crearemos carpetas y documentos específicos únicamente a medida que vayamos abordando cada área.
2. **Entender antes de aplicar**: Cada refactorización o feature se construye entendiendo el principio técnico que la respalda.
3. **Pequeñas victorias verificables**: Cada jornada debe dejar el código y la base de datos objetivamente mejor.
