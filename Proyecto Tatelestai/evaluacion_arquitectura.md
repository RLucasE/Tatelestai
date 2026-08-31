# Cómo Evaluar si una Arquitectura es Buena

> No hay una métrica única, pero hay un conjunto de señales concretas que distinguen una arquitectura sólida de una frágil.

---

## 1. Facilidad de cambio (Evolvability)

La señal más importante. Una buena arquitectura **absorbe cambios sin propagarlos**. Pregúntate:

- ¿Puedo cambiar la base de datos sin tocar la lógica de negocio?
- ¿Puedo reemplazar un servicio externo (API de pagos, proveedor de email) con cambios localizados?
- ¿Un nuevo requerimiento de negocio requiere tocar 2 archivos o 20?

> Si un cambio "pequeño" requiere modificaciones en cascada a través de múltiples capas, la arquitectura tiene alto **acoplamiento**.

---

## 2. Principios estructurales

| Principio                  | Señal positiva                                                | Señal negativa                                             |
| -------------------------- | ------------------------------------------------------------- | ---------------------------------------------------------- |
| **Cohesión alta**          | Cada módulo tiene una responsabilidad clara                   | Clases/módulos "God object" que hacen de todo              |
| **Acoplamiento bajo**      | Los módulos se comunican por interfaces/contratos             | Dependencias directas entre implementaciones concretas     |
| **Dependency Inversion**   | El core del negocio no depende de frameworks ni de I/O        | `import database` dentro de la lógica de dominio           |
| **Separación de concerns** | Capas bien definidas (transporte, aplicación, dominio, infra) | Lógica de negocio mezclada con HTTP handlers o queries SQL |

---

## 3. Testeabilidad

Una arquitectura bien diseñada es **inherentemente testeable**:

- ¿Puedo testear la lógica de negocio **sin** levantar una base de datos, un servidor web o servicios externos?
- ¿Puedo escribir tests unitarios rápidos para el core?
- ¿Los tests son frágiles (se rompen con refactors internos) o robustos (solo se rompen cuando cambia el comportamiento)?

> Si necesitas mocks complejos y setup de 50 líneas para un test unitario, la arquitectura tiene un problema de diseño.

---

## 4. Comprensibilidad

- ¿Un desarrollador nuevo puede entender **dónde poner código nuevo** en menos de un día?
- ¿La estructura de carpetas/módulos refleja el **dominio del negocio** (no solo capas técnicas)?
- ¿Los nombres de los componentes comunican **intención**, no implementación?

---

## 5. Proporcionalidad al problema

Esto se suele ignorar, pero es clave:

- ¿La complejidad de la arquitectura es **proporcional** a la complejidad del problema?
- ¿Estás usando microservicios para un CRUD de 3 entidades? -> Sobreingeniería.
- ¿Estás metiendo todo en un monolito con 15 equipos trabajando en paralelo? -> Subingeniería.

> *"La mejor arquitectura es la más simple que resuelve el problema actual y permite evolucionar hacia el futuro probable."*

---

## 6. Red flags concretas

- **Dependencias circulares** entre módulos/paquetes
- **Shotgun surgery**: un cambio de negocio toca muchos módulos distintos
- **Divergent change**: un módulo cambia por razones muy diferentes
- **Leaky abstractions**: los detalles de implementación se escapan a los consumidores
- **Shared mutable state** sin control explícito de concurrencia

---

## Cómo evaluarlo en la práctica

1. **Haz el ejercicio mental del cambio**: "¿Qué pasaría si mañana cambio X?" (base de datos, proveedor, protocolo de transporte). Traza las dependencias.
2. **Revisa el grafo de dependencias**: herramientas como `madge` (JS), `pydeps` (Python), o `go mod graph` (Go) te muestran la realidad.
3. **Mide la cobertura de tests unitarios rápidos**: si la mayoría de tus tests son de integración lentos, es síntoma de mal diseño.
4. **Cuenta los "motivos de cambio"** de cada módulo: idealmente uno solo (Single Responsibility).
