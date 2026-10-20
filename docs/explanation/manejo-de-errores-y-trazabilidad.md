# Manejo de Errores, Doble Perspectiva y Trazabilidad de Peticiones

> **Tipo**: Explicación (Diátaxis)  
> **Objetivo**: Comprender la arquitectura de dos flujos para el tratamiento de fallos en Tatelestai: cómo se separan el reporting técnico interno del renderizado JSON seguro al cliente, y cómo el identificador único `request_id` permite diagnosticar problemas en producción sin exponer información confidencial.

---

## 1. La Doble Perspectiva de una Excepción

En una arquitectura web moderna con clientes desacoplados (Single Page Application en Vue 3 y potenciales aplicaciones móviles en Android/iOS), una excepción no puede tratarse de manera uniforme hacia ambos lados:

```text
                               ┌────────────────────────────────────────────────────────┐
                               │                 EXCEPCIÓN EN EL BACKEND                 │
                               └──────────────────────────┬─────────────────────────────┘
                                                          │
                             ┌────────────────────────────┴───────────────────────────┐
                             ▼                                                        ▼
             [ PERSPECTIVA INTERNA / BACKEND ]                        [ PERSPECTIVA CLIENTE / FRONTEND ]
             - Stack trace completo                                   - Mensaje amigable y legible
             - Archivo, línea y sentencia SQL                         - Código semántico (error_code)
             - Variables de sesión y contexto técnico                 - Datos públicos necesarios (context)
             - Registro en storage/logs/laravel.log                   - Identificador de correlación (request_id)
             - Sin censura (para debugging interno)                   - Blindaje total (cero SQL o trazas en 500)
```

Tratar ambas perspectivas de la misma manera conduce a dos extremos perniciosos:
1. **Volcar la excepción cruda al cliente**: Expone consultas SQL (`QueryException`), nombres de tablas, variables de entorno o rutas de archivos en el servidor (`APP_DEBUG=true` o `catch (Exception $e) { return response()->json($e->getMessage(), 500); }`).
2. **Ocultar todo sin registrar evidencia**: Atrapar la excepción con un mensaje genérico sin asociar un identificador ni escribir en el log, lo que convierte la depuración en producción en una búsqueda a ciegas.

En Tatelestai, ambos flujos se coordinan mediante un identificador común: el **`request_id`**.

---

## 2. Ciclo de Vida del `request_id`

Cada petición HTTP que ingresa a la API de Laravel atraviesa el middleware global [`AssignRequestId`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Http/Middleware/AssignRequestId.php):

```mermaid
sequenceDiagram
    autonumber
    actor Cliente as Cliente (Vue 3 / Axios)
    participant MW as Middleware AssignRequestId
    participant LogCtx as Monolog (Log::withContext)
    participant Core as Controladores / Actions
    participant Handler as ApiErrorRenderer (bootstrap/app.php)

    Cliente->>MW: HTTP GET / POST (opcional header X-Request-ID)
    Note over MW: Si viene cabecera válida la propaga;<br/>si no, genera 'req_' + 12 caracteres hex.
    MW->>LogCtx: Log::withContext(['request_id' => $requestId])
    MW->>Core: Procesa la petición
    alt Operación Exitosa (2xx)
        Core-->>MW: JsonResponse
        MW-->>Cliente: HTTP 200 OK (Header X-Request-ID: req_abc123)
    else Ocurre Excepción (4xx / 5xx)
        Core-->>Handler: Lanza Throwable
        Note over Handler: ApiErrorRenderer formatea JSON estándar<br/>e inyecta context.request_id
        Handler-->>MW: JsonResponse con X-Request-ID
        MW-->>Cliente: HTTP 4xx / 5xx (JSON con request_id)
    end
```

### Propagación a los Logs de Laravel
Al invocar `Log::withContext(['request_id' => $requestId])` en el middleware, cualquier invocación subsiguiente a `Log::info()`, `Log::warning()` o `Log::error()` incluirá automáticamente el campo `request_id` en las líneas de registro:

```text
[2026-10-20 02:16:38] local.ERROR: SQLSTATE[42P01]: Undefined table... {"request_id":"req_7fa921c3b8e1","user_id":4,"route":"api/test","method":"GET"}
```

Si un usuario se comunica con soporte indicando:
> *"No pude pagar mi reserva, me dio error con código `req_7fa921c3b8e1`"*

El desarrollador solo debe ejecutar:
```bash
grep "req_7fa921c3b8e1" storage/logs/laravel.log
```
para acceder inmediatamente al stack trace y contexto técnico del incidente.

---

## 3. Separación de Flujos: Reporting vs. Renderizado

Laravel 12 centraliza el manejo de excepciones en `bootstrap/app.php`. En Tatelestai se configuran dos canales independientes dentro del closure `withExceptions`:

### 3.1. Canal de Reporting (Logs y Monitoreo)
Determina **qué se escribe en disco o servicios de monitoreo**.
- **Excepciones Silenciadas (`ShouldntReport`)**:
  - Toda excepción de negocio que extiende [`ApiException`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Exceptions/ApiException.php) implementa la interfaz `Illuminate\Contracts\Debug\ShouldntReport`.
  - Excepciones estándar de HTTP como `ValidationException` (`422`), `AuthenticationException` (`401`), `AuthorizationException` (`403`) y `NotFoundHttpException` (`404`) no saturan los archivos de logs.
- **Excepciones Reportadas (`5xx` y fallos inesperados)**:
  - Excepciones del motor de base de datos (`QueryException`), errores de tiempo de ejecución (`RuntimeException`), fallos de comunicación con servicios externos (Mercado Pago, Google Places) o cualquier error no controlado invocan el reporte nativo de Laravel.
  - El closure `$exceptions->context(...)` enriquece estos reportes con `request_id`, `user_id`, `route` y `method`.

### 3.2. Canal de Renderizado (Respuesta al Cliente)
Determina **qué viaja por la red hacia el navegador o app**.
- La clase [`ApiErrorRenderer`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Exceptions/ApiErrorRenderer.php) garantiza que **toda** respuesta de error (`4xx` y `5xx`) de la API cumpla una estructura predecible:
  ```json
  {
    "message": "Mensaje legible para el usuario",
    "error": "Mensaje legible para el usuario (alias retrocompatible)",
    "error_code": "CODIGO_SEMANTICO_EN_MAYUSCULAS",
    "errors": null,
    "context": {
      "request_id": "req_..."
    }
  }
  ```
- En errores de validación (`422`), el campo `errors` contiene el mapa de campos y sus mensajes de validación.
- En errores inesperados (`500`), el mensaje devuelto al cliente siempre es:
  > *"Ocurrió un error interno en el servidor."* con `error_code: "INTERNAL_ERROR"`.  
  **Nunca** se expone la traza de PHP ni la consulta SQL.

---

## 4. Contexto Público (`publicContext`) vs. Contexto Técnico (`context`)

En las excepciones que extienden `ApiException`, se establece una distinción fundamental:

1. **`publicContext(): array`**:
   - Información segura de negocio que el cliente **necesita** para renderizar la interfaz o tomar decisiones lógicas.
   - *Ejemplo*: En `OfferQuantityExceededException`, incluye `offer_id`, `requested_quantity`, `available_quantity` y `already_in_cart`.
   - Se serializa dentro del bloque `context` de la respuesta JSON junto con el `request_id`.
2. **`context(): array`**:
   - Método nativo de Laravel para el logger.
   - Contiene información interna o sensible que **nunca** debe enviarse al navegador (por ejemplo, identificadores de nodos internos, tiempos de espera de bloqueos pesimistas, IDs de auditoría privada).

---

## 5. Integración con el Frontend (Vue 3 / Axios)

El frontend en `Frontend/vue-project/` consume esta arquitectura de forma transparente a través de dos mecanismos:

1. **Interceptor de Respuestas (`src/lib/axios.js`)**:
   - Intercepta cualquier respuesta rechazada y le inyecta propiedades convenientes:
     ```javascript
     error.userMessage // Mensaje ya resuelto (prioriza validaciones o message/error)
     error.errorCode   // Código semántico devuelto por la API
     error.requestId   // ID de correlación
     ```
   - **Control de Redirecciones Disruptivas**:
     - Respuestas `403` correspondientes a reglas de negocio (ej. `CANCELLATION_NOT_ALLOWED` o `REVIEW_NOT_ALLOWED`) **no** expulsan al usuario a la pantalla de login.
     - Respuestas `500` en operaciones de mutación (`POST`, `PUT`, `PATCH`, `DELETE`) **no** redirigen a la página global `/server-error`, permitiendo que el formulario o modal preserve los datos ingresados por el usuario y muestre el mensaje junto al `request_id`.
2. **Módulo de Utilidades (`src/utils/apiError.js`)**:
   - Provee funciones puras (`getApiErrorMessage`, `getApiErrorCode`, `getApiRequestId`, `getValidationErrors`) para que los componentes puedan extraer datos sin encadenar operadores opcionales complejos (`error?.response?.data?.message || ...`).
