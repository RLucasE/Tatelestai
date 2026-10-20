# ADR 0005: Estandarización del Manejo de Errores, Doble Perspectiva y Trazabilidad con Request ID

* **Estado**: Aceptado
* **Fecha**: 2026-10-20
* **Decisores**: Equipo de Ingeniería Tatelestai

---

## Contexto y Planteamiento del Problema

A medida que Tatelestai evolucionó incorporando módulos complejos (pagos con Mercado Pago, calificaciones de doble media, reservas concurrentes de packs, reportes de moderación), el manejo de errores se fragmentó:

1. **Inconsistencia de contratos JSON**: Algunos controladores respondían con `{"error": "..."}`, otros con `{"message": "..."}`, otros con ambos, e incluso en ciertos casos (`AdmOfferController`) la clave `error` era un array contextual en vez de una cadena de texto, provocando fallos en clientes web o móviles.
2. **Atrapamiento accidental de excepciones de framework**: Varios controladores envolvían la llamada `$request->validate(...)` o llamadas `findOrFail()` dentro de bloques `try { ... } catch (Exception $e)` genéricos que devolvían HTTP `500` o `400`. Esto transformaba errores normales de validación (`422`) o recursos inexistentes (`404`) en fallos internos del servidor, disparando redirecciones disruptivas en el frontend hacia `/server-error`.
3. **Fuga de información y falta de correlación**: Respuestas `500` exponían mensajes crudos de excepciones y números de línea (`$e->getLine()`), con riesgo de fuga de sentencias SQL (`QueryException`). Al mismo tiempo, cuando un usuario experimentaba un fallo, el equipo de soporte no disponía de ningún identificador común para localizar el registro exacto en los logs del servidor.

---

## Criterios de Decisión

* **Doble perspectiva desacoplada**:
  * *Perspectiva Cliente (Vue / Android)*: Recibe un contrato JSON uniforme, seguro y legible por humanos, con un `error_code` semántico y un identificador único de incidente (`request_id`), sin trazas ni SQL.
  * *Perspectiva Backend (Laravel / Monolog)*: Conserva la excepción original, el stack trace, la clase causante y el contexto técnico de depuración vinculado al mismo `request_id`.
* **Cero contaminación de logs por errores esperados**: Errores `4xx` comunes (validación, sin stock, no autenticado) no deben generar alertas de error crítico en `laravel.log` o Sentry.
* **Limpieza de controladores**: Eliminar bloques `try/catch` manuales y repetitivos mediante un renderizador centralizado en `bootstrap/app.php`.
* **Retrocompatibilidad**: Mantener compatibilidad con clientes o tests que consuman `response.data.message` o `response.data.error`.

---

## Opciones Consideradas

1. **Mantener try/catch por controlador agregando helpers locales**: Rechazada. Propensa a errores humanos, duplica código y no garantiza que una nueva ruta o middleware cumpla el estándar.
2. **RFC 7807 (Problem Details)**: Rechazada temporalmente por ser invasiva con la estructura actual del frontend de Vue y requerir refactorizar decenas de vistas simultáneamente.
3. **Doble flujo desacoplado con Middleware de Request ID, Jerarquía `ApiException` y `ApiErrorRenderer` en `bootstrap/app.php`**: **Elegida**.

---

## Decisión Adoptada

Se implementó una solución full-stack basada en cuatro pilares:

1. **Middleware `AssignRequestId`**:
   - Genera o propaga un identificador único con prefijo `req_<hex>`.
   - Lo inyecta en `Log::withContext(['request_id' => $requestId])`.
   - Agrega la cabecera HTTP `X-Request-ID` a todas las respuestas salientes de la API.
2. **Jerarquía de Dominio `ApiException`**:
   - Extiende `DomainException` e implementa `ShouldntReport` de Laravel para evitar ruido en logs.
   - Separa `publicContext()` (seguro para el cliente) de `context()` (metadatos técnicos para logs).
   - Especialización mediante `BusinessRuleException` para reglas de negocio en `Actions` y `Policies`.
3. **Renderizador Centralizado `ApiErrorRenderer` en `bootstrap/app.php`**:
   - Transforma de manera consistente cualquier `Throwable` en un JSON uniforme:
     ```json
     {
       "message": "...",
       "error": "...",
       "error_code": "...",
       "errors": null,
       "context": {
         "request_id": "req_..."
       }
     }
     ```
   - Oculta completamente información técnica en errores `500` inesperados (`INTERNAL_ERROR`), mientras Monolog registra la excepción real con su stack trace y `request_id`.
4. **Capa Cliente en Frontend (`src/utils/apiError.js` y `src/lib/axios.js`)**:
   - Enriquecimiento automático de errores de Axios con `.userMessage`, `.errorCode` y `.requestId`.
   - Supresión de redirecciones agresivas a `/login` o `/server-error` en operaciones de mutación.

---

### Consecuencias

* **Positivas**:
  * Diagnóstico inmediato de incidentes en producción mediante el cruce de `request_id`.
  * Blindaje total contra fugas de información sensible (consultas SQL, rutas de servidor, líneas de código).
  * Controladores limpios de `try/catch` redundantes; las acciones lanzan excepciones de dominio que el framework renderiza automáticamente.
  * Los errores de validación (`422`) ya no se degradan erróneamente a `500`.
  * Total retrocompatibilidad con tests existentes y componentes de Vue.
* **Negativas / Mitigaciones**:
  * Nuevos desarrolladores deben evitar volver a colocar `$request->validate()` o llamadas a servicios dentro de bloques `catch (Exception $e)` genéricos.
  * *Mitigación*: Se creó la guía práctica `docs/how-to/manejar-errores-y-excepciones.md` y la especificación de referencia `docs/reference/estandar-respuestas-y-errores-api.md`.
