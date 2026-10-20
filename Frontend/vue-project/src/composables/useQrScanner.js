import { shallowRef, ref, onBeforeUnmount } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

/**
 * Composable para gestionar el escaneo de códigos QR mediante la cámara web o móvil con html5-qrcode.
 */
export function useQrScanner() {
  const isScanning = shallowRef(false);
  const isInitializing = shallowRef(false);
  const cameraError = shallowRef('');
  const availableCameras = ref([]);
  const selectedCameraId = shallowRef('');
  const html5QrCode = shallowRef(null);

  /**
   * Obtiene las cámaras disponibles en el dispositivo.
   */
  const loadCameras = async () => {
    try {
      const devices = await Html5Qrcode.getCameras();
      availableCameras.value = devices || [];
      if (devices && devices.length > 0) {
        // Preferir cámara trasera (environment / back) en móviles
        const backCamera = devices.find((d) =>
          d.label?.toLowerCase().includes('back') ||
          d.label?.toLowerCase().includes('trasera') ||
          d.label?.toLowerCase().includes('posterior')
        );
        selectedCameraId.value = backCamera ? backCamera.id : devices[0].id;
      }
    } catch (err) {
      console.warn('No se pudieron listar las cámaras:', err);
    }
  };

  /**
   * Inicia el escaneo en el elemento DOM indicado por `elementId`.
   *
   * @param {string} elementId - ID del elemento HTML donde renderizar el video
   * @param {Function} onScanSuccess - Callback cuando se detecta un QR válido (decodedText, decodedResult)
   */
  const startScanner = async (elementId, onScanSuccess) => {
    if (isScanning.value) return;

    cameraError.value = '';
    isInitializing.value = true;

    try {
      if (!html5QrCode.value) {
        html5QrCode.value = new Html5Qrcode(elementId);
      }

      await loadCameras();

      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
      };

      // Si tenemos un cameraId específico, usarlo; sino solicitar cámara trasera
      const cameraConfig = selectedCameraId.value
        ? selectedCameraId.value
        : { facingMode: 'environment' };

      await html5QrCode.value.start(
        cameraConfig,
        config,
        (decodedText, decodedResult) => {
          if (typeof onScanSuccess === 'function') {
            onScanSuccess(decodedText, decodedResult);
          }
        },
        () => {
          // Ignorar fallos de frame individual para evitar ruido
        }
      );

      isScanning.value = true;
    } catch (err) {
      console.error('Error al iniciar escáner de cámara:', err);
      const errName = err.name || '';
      if (errName === 'NotAllowedError' || String(err).includes('Permission denied')) {
        cameraError.value = 'Permiso denegado para acceder a la cámara. Revisa la configuración de tu navegador.';
      } else if (errName === 'NotFoundError' || String(err).includes('Requested device not found')) {
        cameraError.value = 'No se encontró ninguna cámara disponible en este dispositivo.';
      } else {
        cameraError.value = 'No se pudo iniciar la cámara. Puedes ingresar el código manualmente.';
      }
      isScanning.value = false;
    } finally {
      isInitializing.value = false;
    }
  };

  /**
   * Detiene el stream de video de la cámara y limpia el elemento.
   */
  const stopScanner = async () => {
    if (html5QrCode.value && isScanning.value) {
      try {
        await html5QrCode.value.stop();
      } catch (err) {
        console.warn('Advertencia al detener escáner:', err);
      } finally {
        isScanning.value = false;
      }
    }
  };

  /**
   * Cambia la cámara activa.
   */
  const switchCamera = async (newCameraId, elementId, onScanSuccess) => {
    selectedCameraId.value = newCameraId;
    await stopScanner();
    await startScanner(elementId, onScanSuccess);
  };

  onBeforeUnmount(async () => {
    await stopScanner();
  });

  return {
    isScanning,
    isInitializing,
    cameraError,
    availableCameras,
    selectedCameraId,
    startScanner,
    stopScanner,
    switchCamera,
  };
}
