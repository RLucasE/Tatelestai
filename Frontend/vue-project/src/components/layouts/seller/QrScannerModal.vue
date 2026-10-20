<script setup>
import { onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { useQrScanner } from '@/composables/useQrScanner';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'scanned']);

const {
  isScanning,
  isInitializing,
  cameraError,
  availableCameras,
  selectedCameraId,
  startScanner,
  stopScanner,
  switchCamera,
} = useQrScanner();

const containerId = 'tatelestai-qr-reader';

const handleScanSuccess = async (decodedText) => {
  await stopScanner();
  emit('scanned', decodedText);
  emit('close');
};

const initScanner = async () => {
  await nextTick();
  startScanner(containerId, handleScanSuccess);
};

const handleCameraChange = async (event) => {
  const newId = event.target.value;
  if (newId) {
    await switchCamera(newId, containerId, handleScanSuccess);
  }
};

const handleClose = async () => {
  await stopScanner();
  emit('close');
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && props.show) {
    handleClose();
  }
};

watch(
  () => props.show,
  async (newVal) => {
    if (newVal) {
      await initScanner();
    } else {
      await stopScanner();
    }
  }
);

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
  if (props.show) {
    initScanner();
  }
});

onBeforeUnmount(async () => {
  window.removeEventListener('keydown', handleKeyDown);
  await stopScanner();
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all"
      @click.self="handleClose"
      aria-modal="true"
      role="dialog"
    >
      <div
        class="relative w-full max-w-sm sm:max-w-md bg-[#2D2438] border border-white/[0.1] rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4 text-[#E8EAF6] animate-fadeIn"
      >
        <!-- Encabezado Modal -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#7C3AED]/20 border border-[#7C3AED]/35 text-[#C4B5FD] flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-white tracking-tight">
                Escanear Código QR
              </h3>
              <p class="text-[11px] text-[#A5A8C2]">
                Apuntá la cámara al QR presentado por el cliente.
              </p>
            </div>
          </div>

          <button
            type="button"
            class="text-[#A5A8C2] hover:text-white p-1 rounded-lg hover:bg-white/[0.08] transition-colors cursor-pointer"
            @click="handleClose"
            aria-label="Cerrar escáner"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Visor de la Cámara -->
        <div class="relative w-full aspect-square bg-[#1A1625] rounded-2xl overflow-hidden border border-white/[0.1] flex items-center justify-center">
          
          <!-- Elemento donde html5-qrcode inyecta el video -->
          <div :id="containerId" class="w-full h-full"></div>

          <!-- Estado de Inicialización / Carga -->
          <div
            v-if="isInitializing && !cameraError"
            class="absolute inset-0 bg-[#1A1625]/90 flex flex-col items-center justify-center gap-2 p-4 text-center"
          >
            <div class="w-8 h-8 border-3 border-[#7C3AED] border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs text-[#A5A8C2]">Iniciando cámara...</p>
          </div>

          <!-- Mensaje de Error de Cámara -->
          <div
            v-else-if="cameraError"
            class="absolute inset-0 bg-[#1A1625] flex flex-col items-center justify-center gap-3 p-6 text-center"
          >
            <div class="w-12 h-12 rounded-2xl bg-[#EF4444]/15 text-[#EF4444] border border-[#EF4444]/30 flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="space-y-1">
              <h4 class="text-xs font-bold text-white">No se pudo acceder a la cámara</h4>
              <p class="text-[11px] text-[#A5A8C2] leading-relaxed">{{ cameraError }}</p>
            </div>
            <button
              type="button"
              class="px-3.5 py-1.5 rounded-xl bg-white/[0.08] hover:bg-white/[0.14] text-xs font-semibold text-white transition-colors cursor-pointer"
              @click="initScanner"
            >
              Reintentar
            </button>
          </div>

          <!-- Guía de Encuadre Visual -->
          <div
            v-if="isScanning && !cameraError"
            class="absolute inset-0 pointer-events-none flex items-center justify-center p-8"
          >
            <div class="relative w-48 h-48 border-2 border-[#7C3AED]/70 rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]">
              <!-- Esquinas reforzadas -->
              <span class="absolute -top-1 -left-1 w-4 h-4 border-t-3 border-l-3 border-[#10B981] rounded-tl"></span>
              <span class="absolute -top-1 -right-1 w-4 h-4 border-t-3 border-r-3 border-[#10B981] rounded-tr"></span>
              <span class="absolute -bottom-1 -left-1 w-4 h-4 border-b-3 border-l-3 border-[#10B981] rounded-bl"></span>
              <span class="absolute -bottom-1 -right-1 w-4 h-4 border-b-3 border-r-3 border-[#10B981] rounded-br"></span>
              
              <!-- Línea de escaneo animada -->
              <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#10B981] to-transparent animate-scan"></div>
            </div>
          </div>
        </div>

        <!-- Selector de Cámara (si hay más de 1) -->
        <div v-if="availableCameras.length > 1" class="flex items-center justify-between gap-2 text-xs">
          <label for="camera-select" class="text-[#A5A8C2] font-medium shrink-0">Cámara:</label>
          <select
            id="camera-select"
            v-model="selectedCameraId"
            class="w-full max-w-[220px] px-2.5 py-1.5 rounded-xl bg-[#1A1625] border border-white/[0.1] text-xs text-white focus:outline-none focus:border-[#7C3AED]"
            @change="handleCameraChange"
          >
            <option v-for="cam in availableCameras" :key="cam.id" :value="cam.id">
              {{ cam.label || `Cámara ${cam.id}` }}
            </option>
          </select>
        </div>

        <!-- Pie y Acción de Cierre -->
        <div class="pt-1 flex items-center justify-between gap-2">
          <span class="text-[11px] text-[#A5A8C2]">
            ¿Problemas de lectura? Podés ingresar el código por teclado.
          </span>
          <button
            type="button"
            class="px-4 py-2 rounded-xl bg-white/[0.08] hover:bg-white/[0.12] text-xs font-bold text-white transition-colors cursor-pointer shrink-0"
            @click="handleClose"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes scanLine {
  0% {
    transform: translateY(0);
    opacity: 0.4;
  }
  50% {
    transform: translateY(180px);
    opacity: 1;
  }
  100% {
    transform: translateY(0);
    opacity: 0.4;
  }
}

.animate-scan {
  animation: scanLine 2.5s ease-in-out infinite;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

:deep(#tatelestai-qr-reader) {
  width: 100% !important;
  height: 100% !important;
  border: none !important;
}

:deep(#tatelestai-qr-reader video) {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  border-radius: 1rem;
}
</style>
