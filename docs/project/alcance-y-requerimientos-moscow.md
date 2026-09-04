# Alcance Funcional y Priorización de Requisitos (MoSCoW)

> **Tipo**: Gestión de Proyecto / Alcance y Requisitos  
> **Objetivo**: Delimitar formalmente el alcance de la plataforma Tatelestai clasificando cada requerimiento funcional bajo la metodología MoSCoW (Must, Should, Could, Won't Have), indicando su estado de desarrollo y criterios de factibilidad.

---

## 1. Metodología de Priorización

Para garantizar que el producto cumpla los objetivos de negocio y académicos sin sobreextender el cronograma, los requisitos se clasifican en:

* **Must Have (Debe tener)**: Requisitos imprescindibles. Sin ellos el marketplace no puede operar o carece de integridad técnica.
* **Should Have (Debería tener)**: Requisitos de alto valor que aumentan la competitividad y completitud del sistema, pero cuya ausencia temporal no bloquea el flujo básico.
* **Could Have (Podría tener)**: Mejoras deseables o características secundarias que se abordan si sobran recursos o tiempo.
* **Won't Have (No tendrá en esta versión)**: Funcionalidades expresamente descartadas para el ciclo actual para evitar dispersión (Machine Learning, sistemas de recomendación complejos).

**Leyenda de Estado de Implementación:**
* 🟢 **Completado**: Implementado, probado y funcionando en el repositorio.
* 🟡 **En Progreso**: Parcialmente implementado o en etapa de refactorización.
* 🔴 **Planificado**: Definido formalmente en el backlog para fases posteriores.

---

## 2. Requisitos "Must Have" (Imprescindibles)

### 2.1. Gestión de Usuarios y Autenticación
| Requerimiento | Estado | Complejidad (1-10) | Notas Técnicas |
|---|---|---|---|
| Registro e inicio de sesión de usuarios con correo | 🟢 | 3 | Laravel Fortify / Breeze con validación estricta y contraseñas hash (Bcrypt). |
| Asignación y cambio de roles (`Customer`, `Seller`, `Admin`) | 🟢 | 4 | Modelado mediante Spatie Laravel-Permission y Enums de dominio (`UserRole`). |

### 2.2. Panel de Administración
| Requerimiento | Estado | Complejidad (1-10) | Notas Técnicas |
|---|---|---|---|
| Habilitar o rechazar la solicitud de registro de un comercio | 🟢 | 4 | Transición de estados en `UserState` mediante `IsSellerActivableAction`. |
| Deshabilitar o reactivar un vendedor/comercio | 🟢 | 4 | `IsDeactivableSellerAction`. Desactiva en cascada todas las ofertas activas. |
| Moderar y deshabilitar ofertas que infrinjan políticas | 🟢 | 3 | `ChangeOfferStatusAction` (transiciona la oferta a estado `inactive`). |
| Visualizar métricas y listado de transacciones de compra/venta | 🟢 | 4 | Auditoría global para resolver disputas y verificar trazabilidad. |
| Crear y administrar tipos y categorías de establecimientos | 🟢 | 2 | Mantenedores de datos maestros para clasificación de locales. |

### 2.3. Compradores (Customers)
| Requerimiento | Estado | Complejidad (1-10) | Notas Técnicas |
|---|---|---|---|
| Explorar catálogo de ofertas activas no vencidas | 🟢 | 4 | Consultas optimizadas con eager loading y filtrado por estado `active`. |
| Gestión completa del carrito de compras | 🟢 | 6 | `AddToCartAction`, modificar cantidades, validación de stock y vaciado. |
| Restricción de carrito a un único establecimiento | 🟢 | 5 | Impide mezclar comidas de distintos locales en el mismo pedido de retiro. |
| Ejecutar compra atómica de las ofertas del carrito | 🟢 | 8 | `makeSellAction` con transacción ACID y bloqueo pesimista (`lockForUpdate`). |
| Código único de retiro para el paquete comprado | 🟢 | 4 | `GeneratePickupCodeAction` (código alfanumérico seguro). |
| Notificación de confirmación de compra por email | 🟢 | 4 | Integración con servicio SMTP / Gmail encolado. |
| Validación de expiración de oferta antes del pago | 🟢 | 5 | `ValidateOfferExpirationAction` y `VerifyPurchaseDataFreshnessAction`. |

### 2.4. Vendedores (Sellers)
| Requerimiento | Estado | Complejidad (1-10) | Notas Técnicas |
|---|---|---|---|
| Registro del establecimiento comercial y dirección física | 🟢 | 5 | Integración con geocodificación de direcciones (latitud/longitud). |
| Gestión de catálogo de productos individuales | 🟢 | 4 | Altas, modificaciones y bajas lógicas (*soft deletes*). |
| Creación y publicación de ofertas de excedentes | 🟢 | 6 | `CreateOfferAction`: vincula productos, define precios de descuento y stock. |
| Pausar o reactivar ofertas propias | 🟢 | 3 | Control del estado de publicación según disponibilidad diaria. |
| Visualizar historial de ventas y compras asignadas | 🟢 | 4 | Pantalla de control de retiros pendientes para el comerciante. |
| Confirmación y validación de retiro en mostrador | 🟡 | 5 | Verificación del código de retiro provisto por el comprador. |

---

## 3. Requisitos "Should Have" (Importantes)

| Requerimiento | Área | Estado | Complejidad (1-10) | Justificación |
|---|---|---|---|---|
| **Motor de Búsqueda Instantánea** | Core | 🟢 | 7 | Indexación en Typesense desacoplada mediante el Patrón Adaptador (`SearchServiceInterface`). |
| **Geobúsqueda por Radio** | Búsqueda | 🟢 | 8 | Filtrado de ofertas por proximidad geográfica (`_geoloc`) ordenadas por distancia ascendente. |
| **Sistema de Reclamos y Reportes** | Calidad | 🟢 | 6 | `ReportReason` y `CreateReportAction` para alertar fraudes o problemas bromatológicos. |
| **Ventana de Reserva (5 minutos)** | Compra | 🟢 | 6 | Token temporal para congelar datos y precio mientras el comprador confirma. |
| **Filtros por Categoría y Precio** | UX | 🟢 | 4 | Filtrado combinado en el catálogo de ofertas. |
| **Cancelación justificada con reembolso** | Ventas | 🔴 | 7 | Mecanismo para cancelar ventas cuando el comercio agota imprevistamente el producto. |

---

## 4. Requisitos "Could Have" (Deseables)

| Requerimiento | Área | Estado | Complejidad (1-10) | Notas |
|---|---|---|---|---|
| Ofertas compuestas tipo Pack | Catálogo | 🔴 | 6 | Paquetes cerrados de múltiples productos (ej. "Bolsa sorpresa de panadería"). |
| Contabilización de visualizaciones de ofertas | Analytics | 🔴 | 3 | Métrica de interés para el vendedor. |
| Mapa interactivo en Frontend con marcadores | UI / Maps | 🟡 | 7 | Integración con Leaflet / MapLibre en Vue 3 mostrando comercios cercanos. |
| Cacheo de solicitudes frecuentes | Infraestructura | 🔴 | 5 | Cacheo en Redis para reducir lecturas en catálogo estático. |

---

## 5. Requisitos "Won't Have" (Descartados para esta Versión)

* **Motor de recomendaciones con Machine Learning**: Descartado por requerir grandes volúmenes de telemetría y entrenamiento innecesarios en la etapa inicial.
* **Procesamiento de pagos con tarjeta de crédito en línea**: El modelo de negocio se basa en reserva digital y cobro en mostrador / billetera virtual en el punto de retiro para evitar costos financieros de pasarela a pequeños comercios.
