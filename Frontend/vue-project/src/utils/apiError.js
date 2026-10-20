/**
 * Utilidades centralizadas para extraer mensajes, códigos de error,
 * identificadores de petición (request_id) y errores de validación de la API.
 */

/**
 * Extrae un mensaje legible por humanos desde un error de Axios.
 *
 * @param {unknown} error
 * @param {string} [fallbackMessage='Ocurrió un error inesperado.']
 * @returns {string}
 */
export function getApiErrorMessage(error, fallbackMessage = "Ocurrió un error inesperado.") {
  if (!error) {
    return fallbackMessage;
  }

  const responseData = error.response?.data;

  if (responseData) {
    // Si existen errores de validación por campo (422), priorizar el primer mensaje específico
    if (responseData.errors && typeof responseData.errors === "object") {
      const firstFieldErrors = Object.values(responseData.errors)[0];
      if (Array.isArray(firstFieldErrors) && typeof firstFieldErrors[0] === "string" && firstFieldErrors[0].trim()) {
        return firstFieldErrors[0];
      }
    }

    if (typeof responseData.message === "string" && responseData.message.trim()) {
      return responseData.message;
    }

    if (typeof responseData.error === "string" && responseData.error.trim()) {
      return responseData.error;
    }
  }

  // Si hubo request pero no hubo respuesta (error de red / servidor inaccesible)
  if (error.request && !error.response) {
    return "Error de conexión. Verifica tu conexión a internet e intenta nuevamente.";
  }

  if (typeof error.message === "string" && error.message.trim() && !error.message.startsWith("Request failed with status code")) {
    return error.message;
  }

  return fallbackMessage;
}

/**
 * Obtiene el código de error semántico (error_code) devuelto por el backend.
 *
 * @param {unknown} error
 * @returns {string|null}
 */
export function getApiErrorCode(error) {
  return error?.response?.data?.error_code ?? null;
}

/**
 * Obtiene el identificador único de petición (request_id) para correlación con logs y soporte.
 *
 * @param {unknown} error
 * @returns {string|null}
 */
export function getApiRequestId(error) {
  return (
    error?.response?.data?.context?.request_id ??
    error?.response?.headers?.["x-request-id"] ??
    null
  );
}

/**
 * Obtiene el diccionario de errores de validación por campo (422).
 *
 * @param {unknown} error
 * @returns {Record<string, string[]>}
 */
export function getValidationErrors(error) {
  const errors = error?.response?.data?.errors;
  return errors && typeof errors === "object" ? errors : {};
}
