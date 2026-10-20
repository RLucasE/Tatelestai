import { ref, shallowRef } from 'vue';
import axiosInstance from '@/lib/axios';
import { formatPickupCodeInput, parsePickupQrPayload } from '@/utils/qrPayload';

/**
 * Composable que encapsula la lógica operativa del canje en mostrador:
 * Paso 1: Verificación de código o lectura de QR (POST /check-customer-code).
 * Paso 2: Confirmación de entrega física (POST /complete-sell/{sellNumber}).
 */
export function usePickupRedemption() {
  const pickupCode = shallowRef('');
  const isVerifying = shallowRef(false);
  const verifyError = shallowRef('');
  const verifiedOrder = ref(null);
  const isConfirming = shallowRef(false);
  const successMessage = shallowRef('');

  /**
   * Actualiza el código de pickup asegurando formato XXXX-XXXX-XXXX.
   */
  const setAndFormatCode = (raw) => {
    pickupCode.value = formatPickupCodeInput(raw);
    verifyError.value = '';
  };

  /**
   * Paso 1: Verifica el código de pickup con el backend.
   *
   * @param {string} [codeOverride] - Código opcional a verificar en lugar del código en estado
   * @returns {Promise<boolean>} Indica si la verificación fue exitosa
   */
  const verifyCode = async (codeOverride = null) => {
    const targetCode = (codeOverride ?? pickupCode.value).trim();
    if (!targetCode || targetCode.length < 6) {
      verifyError.value = 'Por favor, ingresa un código de pickup válido';
      return false;
    }

    isVerifying.value = true;
    verifyError.value = '';
    successMessage.value = '';
    verifiedOrder.value = null;

    try {
      const response = await axiosInstance.post('/check-customer-code', {
        pickup_code: targetCode,
      });

      verifiedOrder.value = response.data?.data || null;
      return true;
    } catch (err) {
      console.error('Error al verificar código:', err);
      if (err.response) {
        if (err.response.status === 404) {
          verifyError.value = 'Código de pickup no encontrado';
        } else if (err.response.status === 403) {
          verifyError.value = 'Este código corresponde a otro establecimiento';
        } else if (err.response.status === 410) {
          verifyError.value = 'El plazo para retirar este pedido ha expirado';
        } else {
          verifyError.value = err.response.data?.error || 'Error al verificar el código';
        }
      } else {
        verifyError.value = 'Error de conexión. Por favor, intenta nuevamente';
      }
      return false;
    } finally {
      isVerifying.value = false;
    }
  };

  /**
   * Procesa un código o payload detectado por el escáner QR.
   * Extrae el código, actualiza el input y dispara la verificación automáticamente (Paso 1).
   *
   * @param {string} rawDecodedText - Texto decodificado por la cámara
   * @returns {Promise<boolean>}
   */
  const handleQrScanned = async (rawDecodedText) => {
    const { isValid, pickupCode: extractedCode } = parsePickupQrPayload(rawDecodedText);

    if (!isValid || !extractedCode) {
      verifyError.value = 'El código QR escaneado no es válido para Tatelestai';
      return false;
    }

    pickupCode.value = extractedCode;
    return await verifyCode(extractedCode);
  };

  /**
   * Paso 2: Confirma la entrega física de los packs al cliente.
   *
   * @param {Function} [onConfirmedCallback] - Callback a ejecutar tras confirmar
   * @returns {Promise<boolean>}
   */
  const confirmDelivery = async (onConfirmedCallback = null) => {
    if (!verifiedOrder.value || !verifiedOrder.value.sell_id) {
      verifyError.value = 'No hay datos del pedido para confirmar';
      return false;
    }

    const currentSellId = verifiedOrder.value.sell_id;
    const currentCode = verifiedOrder.value.pickup_code;

    isConfirming.value = true;
    verifyError.value = '';
    successMessage.value = '';

    try {
      await axiosInstance.post(`/complete-sell/${currentSellId}`, {
        pick_up_code: currentCode,
      });

      successMessage.value = `¡Entrega de la reserva #${currentSellId} confirmada con éxito!`;

      if (typeof onConfirmedCallback === 'function') {
        onConfirmedCallback(currentSellId);
      }

      // Limpiar automáticamente tras unos segundos
      setTimeout(() => {
        clearOrder();
      }, 2500);

      return true;
    } catch (err) {
      console.error('Error al confirmar entrega:', err);
      if (err.response) {
        if (err.response.status === 404) {
          verifyError.value = 'Venta no encontrada';
        } else if (err.response.status === 403) {
          verifyError.value = 'No tienes permiso para completar esta venta';
        } else {
          verifyError.value = err.response.data?.error || 'Error al confirmar la entrega';
        }
      } else {
        verifyError.value = 'Error de conexión. Por favor, intenta nuevamente';
      }
      return false;
    } finally {
      isConfirming.value = false;
    }
  };

  /**
   * Limpia el pedido verificado y el input.
   */
  const clearOrder = () => {
    verifiedOrder.value = null;
    pickupCode.value = '';
    verifyError.value = '';
    successMessage.value = '';
  };

  /**
   * Calcula el total del pedido verificado.
   */
  const calculateTotal = (order) => {
    if (!order) return 0;
    if (order.total_price != null) return Number(order.total_price);
    if (!order.offers) return 0;
    return order.offers.reduce(
      (sum, o) => sum + (Number(o.offer_quantity || 1) * Number(o.pack_price || 0)),
      0
    );
  };

  return {
    pickupCode,
    isVerifying,
    verifyError,
    verifiedOrder,
    isConfirming,
    successMessage,
    setAndFormatCode,
    verifyCode,
    handleQrScanned,
    confirmDelivery,
    clearOrder,
    calculateTotal,
  };
}
