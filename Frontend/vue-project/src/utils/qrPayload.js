/**
 * Utilidades para construir, parsear y formatear payloads de Códigos QR y Códigos de Retiro en Tatelestai.
 */

/**
 * Construye el payload estandarizado que codifica el código QR del cliente.
 * Formato: TAT:<pickup_code>:<sell_id>
 *
 * @param {string} pickupCode - Código alfanumérico de retiro (ej. 2X3Y-4Z5A-6B7C)
 * @param {number|string|null} [sellId] - ID de la venta/reserva asociada
 * @returns {string} Payload para codificar en el código QR
 */
export function buildPickupQrPayload(pickupCode, sellId = null) {
  if (!pickupCode) return '';
  const cleanCode = String(pickupCode).trim().toUpperCase();
  return sellId ? `TAT:${cleanCode}:${sellId}` : `TAT:${cleanCode}`;
}

/**
 * Parsea y extrae el código de pickup y sell_id a partir de cualquier texto leído por el escáner o teclado.
 * Soporta:
 *  - Formato QR estructurado: TAT:<pickup_code>:<sell_id> o TAT:<pickup_code>
 *  - Código plano directo: XXXX-XXXX-XXXX o caracteres alfanuméricos continuos
 *
 * @param {string} rawInput - Texto escaneado o ingresado
 * @returns {{ isValid: boolean, pickupCode: string, sellId: number|null }}
 */
export function parsePickupQrPayload(rawInput) {
  if (!rawInput || typeof rawInput !== 'string') {
    return { isValid: false, pickupCode: '', sellId: null };
  }

  const trimmed = rawInput.trim();

  // Caso 1: Payload oficial con prefijo TAT:
  if (trimmed.toUpperCase().startsWith('TAT:')) {
    const parts = trimmed.split(':');
    const rawCode = parts[1] ? parts[1].trim().toUpperCase() : '';
    const sellId = parts[2] ? Number(parts[2]) : null;

    const formattedCode = formatPickupCodeInput(rawCode);
    const isValid = formattedCode.length >= 6;

    return {
      isValid,
      pickupCode: formattedCode,
      sellId: Number.isInteger(sellId) ? sellId : null,
    };
  }

  // Caso 2: Código alfanumérico directo
  const formattedCode = formatPickupCodeInput(trimmed);
  const isValid = formattedCode.length >= 6;

  return {
    isValid,
    pickupCode: formattedCode,
    sellId: null,
  };
}

/**
 * Formatea una cadena alfanumérica agregando guiones cada 4 caracteres (XXXX-XXXX-XXXX).
 *
 * @param {string} input - Texto a formatear
 * @returns {string} Código formateado
 */
export function formatPickupCodeInput(input) {
  if (!input) return '';
  let value = String(input).replace(/[^A-Z0-9]/gi, '').toUpperCase();

  if (value.length > 4) {
    value = value.slice(0, 4) + '-' + value.slice(4);
  }
  if (value.length > 9) {
    value = value.slice(0, 9) + '-' + value.slice(9);
  }

  return value.slice(0, 14);
}
