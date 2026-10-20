<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import QRCode from 'qrcode';
import { buildPickupQrPayload } from '@/utils/qrPayload';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  purchase: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close']);

const qrDataUrl = ref('');
const isCopied = ref(false);
const qrError = ref('');

const pickupCode = computed(() => {
  return props.purchase?.pickup_code || '';
});

const sellId = computed(() => {
  return props.purchase?.id || props.purchase?.sell_id || '';
});

const establishmentName = computed(() => {
  const est = props.purchase?.establishment || props.purchase?.food_establishment;
  return est?.name || `Comercio #${props.purchase?.sold_by || ''}`;
});

const maxPickupTime = computed(() => {
  if (!props.purchase?.max_pickup_datetime) return null;
  return new Date(props.purchase.max_pickup_datetime).toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });
});

const generateQr = async () => {
  if (!pickupCode.value) return;

  try {
    qrError.value = '';
    const payload = buildPickupQrPayload(pickupCode.value, sellId.value);

    // Generar DataURL de alta resolución con fondo blanco y márgenes adecuados
    qrDataUrl.value = await QRCode.toDataURL(payload, {
      width: 320,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF',
      },
      errorCorrectionLevel: 'M',
    });
  } catch (err) {
    console.error('Error al generar código QR:', err);
    qrError.value = 'No se pudo generar la imagen del código QR.';
  }
};

const copyCode = async () => {
  if (!pickupCode.value) return;
  try {
    await navigator.clipboard.writeText(pickupCode.value);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Error al copiar código:', err);
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close');
  }
};

watch(
  () => [props.show, pickupCode.value],
  ([newShow, newCode]) => {
    if (newShow && newCode) {
      generateQr();
    }
  },
  { immediate: true }
);

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all"
      @click.self="$emit('close')"
      aria-modal="true"
      role="dialog"
    >
      <div
        class="relative w-full max-w-sm sm:max-w-md bg-[#2D2438] border border-white/[0.1] rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5 text-[#E8EAF6] animate-fadeIn"
      >
        <!-- Encabezado Modal -->
        <div class="flex items-start justify-between gap-3">
          <div>
            <span class="text-[11px] font-bold text-[#A78BFA] uppercase tracking-wider block">
              Ticket de Retiro
            </span>
            <h3 class="text-lg font-black text-white mt-0.5">
              {{ establishmentName }}
            </h3>
            <span class="text-xs text-[#A5A8C2] block">
              Reserva #{{ sellId }}
            </span>
          </div>

          <button
            type="button"
            class="text-[#A5A8C2] hover:text-white p-1.5 rounded-xl hover:bg-white/[0.08] transition-colors cursor-pointer"
            @click="$emit('close')"
            aria-label="Cerrar modal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tarjeta QR en Alto Contraste (Fondo Blanco Puro) -->
        <div class="flex flex-col items-center justify-center">
          <div class="p-3.5 bg-white rounded-2xl shadow-xl border border-white/20 flex items-center justify-center">
            <img
              v-if="qrDataUrl"
              :src="qrDataUrl"
              alt="Código QR de retiro"
              class="w-56 h-56 sm:w-64 sm:h-64 object-contain rounded-lg"
            />
            <div
              v-else
              class="w-56 h-56 sm:w-64 sm:h-64 flex items-center justify-center text-xs text-gray-500"
            >
              <div class="w-8 h-8 border-3 border-[#7C3AED] border-t-transparent rounded-full animate-spin"></div>
            </div>
          </div>
          <p v-if="qrError" class="text-xs text-[#F87171] mt-2 font-medium">{{ qrError }}</p>
        </div>

        <!-- Código Alfanumérico con botón de copiar -->
        <div class="bg-[#1A1625] border border-white/[0.08] rounded-2xl p-3.5 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <span class="text-[10px] uppercase font-bold text-[#A5A8C2] tracking-wider block">
              Código Alfanumérico
            </span>
            <span class="font-mono text-lg sm:text-xl font-black text-[#10B981] tracking-widest select-all truncate block">
              {{ pickupCode }}
            </span>
          </div>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer shrink-0"
            :class="isCopied ? 'bg-[#10B981]/20 text-[#34D399] border border-[#10B981]/30' : 'bg-white/[0.08] hover:bg-white/[0.12] text-white'"
            @click="copyCode"
          >
            <svg v-if="!isCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <svg v-else class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ isCopied ? '¡Copiado!' : 'Copiar' }}</span>
          </button>
        </div>

        <!-- Indicaciones de Retiro -->
        <div class="space-y-1 text-center">
          <p class="text-xs text-[#E8EAF6] font-medium">
            Mostrá esta pantalla al personal gastronómico en mostrador.
          </p>
          <p v-if="maxPickupTime" class="text-[11px] text-[#A5A8C2]">
            Horario límite de retiro: <strong class="text-white">{{ maxPickupTime }} hs</strong>
          </p>
        </div>

        <!-- Botón de Cierre -->
        <button
          type="button"
          class="w-full py-3 rounded-2xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-bold text-xs tracking-wide transition-colors cursor-pointer shadow-lg shadow-[#7C3AED]/25"
          @click="$emit('close')"
        >
          Listo, cerrar
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
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
</style>
