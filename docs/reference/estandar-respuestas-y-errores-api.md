# Referencia: Estándar de Respuestas, Cabeceras y Errores de API

> **Tipo**: Referencia (Diátaxis)  
> **Objetivo**: Catálogo técnico exhaustivo del contrato JSON de error, cabeceras HTTP de trazabilidad, códigos semánticos (`error_code`) y utilidades cliente de Tatelestai.

---

## 1. Cabeceras HTTP de Trazabilidad

| Cabecera | Dirección | Descripción | Formato / Ejemplo |
|---|---|---|---|
| `X-Request-ID` | Petición (opcional) / Respuesta (obligatoria) | Identificador único de correlación por cada ciclo de petición HTTP. | `req_[a-f0-9]{12}` (ej. `req_7fa921c3b8e1`) |

* Si el cliente envía una cabecera `X-Request-ID` válida (`^[a-zA-Z0-9_\-]{4,64}$`), el backend la adopta y la propaga en los logs y en la cabecera de respuesta.
* Si el cliente no la envía, el middleware `AssignRequestId` genera una nueva con prefijo `req_`.

---

## 2. Contrato JSON Uniforme de Error

Toda respuesta con código de estado HTTP `4xx` o `5xx` devuelta por la API (`/api/*` o con cabecera `Accept: application/json`) cumple la siguiente estructura:

### 2.1. Definición del Esquema (JSON Schema Conceptual)
```json
{
  "message": "string (obligatorio)",
  "error": "string (obligatorio, alias retrocompatible de message)",
  "error_code": "string (obligatorio, SCREAMING_SNAKE_CASE)",
  "errors": "object<string, string[]> | null (opcional, solo en 422)",
  "context": {
    "request_id": "string (obligatorio)",
    "...": "any (metadatos públicos opcionales)"
  }
}
```

### 2.2. Ejemplos por Tipo de Error

#### Error de Autenticación (`401 Unauthorized`)
```json
{
  "message": "Unauthenticated.",
  "error": "Unauthenticated.",
  "error_code": "UNAUTHENTICATED",
  "errors": null,
  "context": {
    "request_id": "req_8a12bc44de01"
  }
}
```

#### Error de Autorización / Permisos (`403 Forbidden`)
```json
{
  "message": "No tienes permiso para acceder a este pack.",
  "error": "No tienes permiso para acceder a este pack.",
  "error_code": "PACK_OWNERSHIP_ERROR",
  "errors": null,
  "context": {
    "request_id": "req_10ef44bb55c1"
  }
}
```

#### Recurso no Encontrado (`404 Not Found`)
```json
{
  "message": "Recurso no encontrado.",
  "error": "Recurso no encontrado.",
  "error_code": "RESOURCE_NOT_FOUND",
  "errors": null,
  "context": {
    "request_id": "req_cc8811fa2200"
  }
}
```

#### Error de Validación de Formulario (`422 Unprocessable Content`)
```json
{
  "message": "Los datos proporcionados no son válidos.",
  "error": "Los datos proporcionados no son válidos.",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "food_establishment_id": ["El campo establecimiento gastronómico es obligatorio."],
    "offers": ["Debe incluir al menos una oferta para preparar la compra."]
  },
  "context": {
    "request_id": "req_99ee1144ffaa"
  }
}
```

#### Error de Regla de Negocio con Metadatos Públicos (`400 Bad Request` - Exceso de Stock)
```json
{
  "message": "La cantidad solicitada excede la disponible ",
  "error": "La cantidad solicitada excede la disponible ",
  "error_code": "OFFER_QUANTITY_EXCEEDED",
  "errors": null,
  "requested_quantity": 5,
  "available_quantity": 2,
  "already_in_cart": 1,
  "context": {
    "request_id": "req_33bb77aa1122",
    "offer_id": 42,
    "requested_quantity": 5,
    "available_quantity": 2,
    "already_in_cart": 1
  }
}
```

#### Error Inesperado del Servidor (`500 Internal Server Error`)
```json
{
  "message": "Ocurrió un error interno en el servidor.",
  "error": "Ocurrió un error interno en el servidor.",
  "error_code": "INTERNAL_ERROR",
  "errors": null,
  "context": {
    "request_id": "req_7fa921c3b8e1"
  }
}
```

---

## 3. Catálogo de Códigos de Error (`error_code`)

| Código de Error (`error_code`) | HTTP Status | Origen / Significado |
|---|---|---|
| `UNAUTHENTICATED` | 401 | Petición sin token de sesión válido o sesión expirada. |
| `FORBIDDEN` | 403 | El usuario autenticado carece del rol o permiso requerido. |
| `PACK_TEMPLATE_OWNERSHIP_ERROR` | 403 | El vendedor intentó usar una plantilla de otro establecimiento. |
| `PACK_OWNERSHIP_ERROR` | 403 | El vendedor intentó modificar o dar de baja una oferta ajena. |
| `RESOURCE_NOT_FOUND` | 404 | El ID solicitado en base de datos no existe (`ModelNotFoundException`). |
| `NOT_FOUND` | 404 | Ruta de API o endpoint inexistente (`NotFoundHttpException`). |
| `CART_NOT_FOUND` | 404 | El usuario no posee un carrito activo para la operación solicitada. |
| `CART_ITEM_NOT_FOUND` | 404 | El item a eliminar o modificar no existe en el carrito del usuario. |
| `REPORTABLE_NOT_FOUND` | 404 | La entidad reportada (oferta o comercio) no existe. |
| `ESTABLISHMENT_NOT_FOUND` | 404 | No se encontró el establecimiento asociado. |
| `OFFER_QUANTITY_EXCEEDED` | 400 | La cantidad solicitada supera el stock disponible en inventario. |
| `INVALID_PURCHASE_TOKEN` | 400 | El token temporal de preparación de compra es inexistente o nulo. |
| `PURCHASE_TOKEN_EXPIRED` | 400 | Se superó la ventana de 5 minutos para confirmar la reserva del pack. |
| `INVALID_PICKUP_CODE` | 400 | El código de retiro ingresado no es válido o está mal formateado. |
| `DUPLICATE_REPORT` | 409 | El usuario ya reportó previamente la misma entidad. |
| `VALIDATION_ERROR` | 422 | Fallo en las reglas de validación de un `FormRequest` o `$request->validate()`. |
| `CANCELLATION_NOT_ALLOWED` | 422 | Violación de la política de cancelación (ej. No-Show o fuera de franja). |
| `REVIEW_NOT_ALLOWED` | 422 | Violación de la política de calificación (pedido no retirado, duplicado o > 14 días). |
| `OFFER_STATUS_CHANGE_INVALID` | 422 | Transición de estado de oferta no permitida por la máquina de estados. |
| `PAYMENT_NOT_APPROVED` | 422 | El pago fue rechazado o cancelado en Mercado Pago. |
| `TOO_MANY_REQUESTS` | 429 | Límite de tasa (*rate limit*) excedido. |
| `INTERNAL_ERROR` | 500 | Fallo no controlado en backend (SQL, runtime, integración externa). |

---

## 4. API de Utilidades del Frontend (`src/utils/apiError.js`)

### `getApiErrorMessage(error, fallbackMessage?)`
* **Entrada**: Error capturado de Axios (`unknown`), mensaje por defecto (`string`, opcional).
* **Retorno**: `string`.
* **Prioridad de resolución**:
  1. Primer mensaje de validación si existe `response.data.errors` (`Record<string, string[]>`).
  2. `response.data.message` si es cadena de texto no vacía.
  3. `response.data.error` si es cadena de texto no vacía.
  4. Mensaje amigable de fallo de conexión si no hubo respuesta del servidor (`error.request && !error.response`).
  5. `fallbackMessage` (por defecto `"Ocurrió un error inesperado."`).

### `getApiErrorCode(error)`
* **Entrada**: Error capturado de Axios (`unknown`).
* **Retorno**: `string | null` con el valor de `response.data.error_code`.

### `getApiRequestId(error)`
* **Entrada**: Error capturado de Axios (`unknown`).
* **Retorno**: `string | null` buscando en `response.data.context.request_id` o en la cabecera HTTP `response.headers['x-request-id']`.

### `getValidationErrors(error)`
* **Entrada**: Error capturado de Axios (`unknown`).
* **Retorno**: `Record<string, string[]>` conteniendo los errores de validación por campo indexados.
