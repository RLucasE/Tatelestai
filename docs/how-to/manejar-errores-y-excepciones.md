# Cómo Manejar Errores y Excepciones en Backend y Frontend

> **Tipo**: Guía How-To (Diátaxis)  
> **Objetivo**: Receta práctica para desarrolladores sobre cómo lanzar excepciones de negocio en Actions, evitar `try/catch` innecesarios en controladores, rastrear errores en producción mediante el `request_id` y consumir errores en componentes Vue 3.

---

## 1. Cómo Lanzar Excepciones de Negocio en Backend

### Opción A: Usar `BusinessRuleException` (Recomendada para reglas puntuales)

En clases de servicio o Actions (`app/Actions/`), no utilices `throw new \Exception(...)`. Utiliza la factoría estática de [`BusinessRuleException`](file:///home/lucas/Documentos/Tatelestai/Backend/example-app/app/Exceptions/BusinessRuleException.php):

```php
use App\Exceptions\BusinessRuleException;

// Error 422 - Regla de negocio violada
throw BusinessRuleException::unprocessable(
    message: 'El período de retiro ya finalizó (No-Show). No se admiten cancelaciones.',
    errorCode: 'CANCELLATION_WINDOW_EXPIRED'
);

// Error 400 - Parámetro o estado inválido
throw BusinessRuleException::badRequest(
    message: 'El código de retiro ingresado no es válido.',
    errorCode: 'INVALID_PICKUP_CODE'
);

// Error 404 - Entidad de dominio no encontrada
throw BusinessRuleException::notFound(
    message: 'No se encontró un carrito activo para este establecimiento.',
    errorCode: 'CART_NOT_FOUND'
);

// Error 403 - Autorización denegada por regla de dominio
throw BusinessRuleException::forbidden(
    message: 'No tienes autorización para gestionar las ofertas de este local.',
    errorCode: 'FORBIDDEN_ESTABLISHMENT_ACCESS'
);
```

### Opción B: Crear una Excepción de Dominio Dedicada (Para errores recurrentes con contexto público)

Si el error requiere devolver datos adicionales a la interfaz para que el frontend tome decisiones (por ejemplo, cantidades disponibles):

```php
namespace App\Exceptions\Cart;

use App\Exceptions\ApiException;

class OfferQuantityExceededException extends ApiException
{
    protected int $httpStatus = 400;
    protected string $errorCode = 'OFFER_QUANTITY_EXCEEDED';

    public function __construct(
        protected int $offerId,
        protected int $requested,
        protected int $available
    ) {
        parent::__construct('La cantidad solicitada excede la disponible.', 400);
    }

    // Datos que se enviarán al cliente en el campo 'context' del JSON
    public function publicContext(): array
    {
        return [
            'offer_id' => $this->offerId,
            'requested_quantity' => $this->requested,
            'available_quantity' => $this->available,
        ];
    }
}
```

> [!NOTE]
> Toda excepción que extienda `ApiException` no generará alertas de error en `storage/logs/laravel.log` (implementa `ShouldntReport`), manteniendo los logs limpios de falsos positivos.

---

## 2. Cómo Escribir Controladores Limpios (Regla Anti-Try/Catch)

Los controladores deben delegar la captura al renderizador global centralizado (`bootstrap/app.php`).

### ❌ Práctica Incorrecta (A Evitar)
```php
// MAL: Envolver validaciones y búsquedas en try-catch genérico
public function prepare(Request $request)
{
    try {
        $validated = $request->validate([...]); // Si falla, convierte un 422 en 500!
        $sell = Sell::findOrFail($id);           // Si falla, convierte un 404 en 500!
        $this->action->execute($validated);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500); // Fuga de SQL y mensajes internos!
    }
}
```

### ✅ Práctica Correcta (Estándar Tatelestai)
```php
public function prepare(PreparePurchaseRequest $request)
{
    // 1. La validación se ejecuta automáticamente vía FormRequest (responde 422 si falla).
    // 2. Si no usas FormRequest, valida antes de cualquier lógica:
    $validated = $request->validate([...]);

    // 3. Ejecuta la lógica. Si la Action lanza una ApiException, ApiErrorRenderer
    //    la formateará automáticamente con el código HTTP y formato estandarizado.
    $result = $this->action->execute($validated);

    return response()->json([
        'message' => 'Operación completada exitosamente',
        'data' => $result,
    ], 200);
}
```

---

## 3. Cómo Rastrear Incidentes con `request_id` en Logs y Docker

Cuando un usuario o un test reporte un error con un identificador de incidente (ej. `req_7fa921c3b8e1`):

### En Entorno Local
```bash
grep "req_7fa921c3b8e1" Backend/example-app/storage/logs/laravel.log
```

### En Contenedor Docker Activo
```bash
docker exec tatelestai-php-fpm grep "req_7fa921c3b8e1" storage/logs/laravel.log
```

### Inspección en Tiempo Real
Para ver las peticiones y sus IDs conforme ocurren:
```bash
docker exec -it tatelestai-php-fpm tail -f storage/logs/laravel.log
```

---

## 4. Cómo Consumir Errores en Componentes Vue 3

En la SPA (`Frontend/vue-project/`), el interceptor de Axios ya normaliza el error. Puedes consumirlo de tres formas:

### Opción 1: Propiedades Directas Inyectadas en el Objeto `error`
```vue
<script setup>
import { ref } from 'vue';
import axios from '@/lib/axios';

const errorMessage = ref('');
const requestId = ref('');

async function handleSubmit() {
  try {
    await axios.post('/customer/pack-reservations/confirm', { purchase_token: '...' });
  } catch (error) {
    // error.userMessage ya resolvió si venía en errors (422), message o error
    errorMessage.value = error.userMessage;
    // error.requestId contiene el id de soporte para mostrar al usuario
    requestId.value = error.requestId;
  }
}
</script>

<template>
  <div v-if="errorMessage" class="text-rose-400 bg-rose-950/40 p-4 rounded-xl border border-rose-800/50">
    <p>{{ errorMessage }}</p>
    <span v-if="requestId" class="text-xs text-neutral-400">Código de referencia: {{ requestId }}</span>
  </div>
</template>
```

### Opción 2: Usar Utilidades Puras de `@/utils/apiError`
Ideal para formularios con errores de validación por campo (`422`):

```vue
<script setup>
import { ref } from 'vue';
import axios from '@/lib/axios';
import { getValidationErrors, getApiErrorMessage } from '@/utils/apiError';

const formErrors = ref({});
const globalError = ref('');

async function savePack() {
  formErrors.value = {};
  globalError.value = '';

  try {
    await axios.post('/packs', payload);
  } catch (error) {
    if (error.response?.status === 422) {
      formErrors.value = getValidationErrors(error);
    } else {
      globalError.value = getApiErrorMessage(error);
    }
  }
}
</script>
```

### Opción 3: Desactivar Redirecciones Globales en Mutaciones
Si necesitas que un error `500` o `404` no redirija a `/server-error` o `/not-found`:

```javascript
await axios.post('/endpoint', data, {
  skipServerErrorRedirect: true,
  skipNotFoundRedirect: true,
});
```
