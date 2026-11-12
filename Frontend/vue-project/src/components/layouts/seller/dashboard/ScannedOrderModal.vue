<script setup>
import { ref, computed, watch } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  order: {
    type: Object,
    default: null,
  },
  pickup: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  confirming: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: '',
  },
});

const emit = defineEmits([
  'update:modelValue',
  'verify-code',
  'confirm-delivery',
  'close',
]);

const { formatCurrency, formatTime } = useFormatters();

const manualCode = ref('');

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      manualCode.value = props.order?.pickup_code || props.pickup?.pickup_code || '';
    }
  }
);

const activeData = computed(() => props.order || props.pickup || {});

const isVerified = computed(() => Boolean(props.order));

const customerName = computed(() => activeData.value.customer_name || 'Cliente');
const customerEmail = computed(() => activeData.value.customer_email || '');

const pickupWindowText = computed(() => {
  const start = activeData.value.pickup_start_datetime;
  const end = activeData.value.max_pickup_datetime;
  if (start || end) {
    return `Retiro hoy ${formatTime(start)} a ${formatTime(end)} hs`;
  }
  return 'Ventana de retiro sujeta a horario comercial';
});

const packsList = computed(() => {
  return activeData.value.packs || [];
});

const totalAmount = computed(() => {
  return activeData.value.gross_amount ?? activeData.value.total_price ?? 0;
});

const displayCode = computed(() => {
  return (
    props.order?.pickup_code ||
    props.pickup?.pickup_code ||
    manualCode.value ||
    `PED-${activeData.value.id || ''}`
  );
});

const handleClose = () => {
  emit('update:modelValue', false);
  emit('close');
};

const handleVerifySubmit = () => {
  if (!manualCode.value.trim()) return;
  emit('verify-code', manualCode.value.trim().toUpperCase());
};

const handleConfirm = () => {
  if (isVerified.value) {
    emit('confirm-delivery', props.order);
  } else if (manualCode.value.trim()) {
    emit('verify-code', manualCode.value.trim().toUpperCase());
  }
};
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-content/50 backdrop-blur-xs"
    @click.self="handleClose"
  >
    <div
      class="w-full max-w-[540px] rounded-[22px] bg-surface border border-border-subtle p-7 sm:p-8 shadow-card flex flex-col gap-6 text-content"
    >
      <!-- ScannedModalHeader -->
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="flex flex-col gap-1 min-w-0">
          <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
            {{ isVerified ? 'CÓDIGO QR VERIFICADO' : 'VALIDAR CÓDIGO DE RETIRO' }} • PEDIDO #{{ activeData.id }}
          </span>
          <h3 class="text-[22px] font-extrabold text-content leading-tight">
            {{ customerName }}
          </h3>
          <p class="text-[13px] text-content-secondary">
            <template v-if="customerEmail">{{ customerEmail }} • </template>
            {{ pickupWindowText }}
          </p>
        </div>

        <div
          v-if="isVerified || activeData.pickup_code"
          class="px-3 py-1.5 rounded-[10px] bg-canvas text-[12.5px] font-bold text-content font-mono shrink-0"
        >
          {{ displayCode }}
        </div>
      </div>

      <!-- Optional Code Input when opened from unverified pickup card -->
      <div v-if="!isVerified" class="flex flex-col gap-2">
        <label class="text-[12px] font-semibold text-content">
          Ingresá el código de retiro dictado por el cliente
        </label>
        <form class="flex items-center gap-2.5" @submit.prevent="handleVerifySubmit">
          <input
            v-model="manualCode"
            type="text"
            placeholder="Ej: TATE-8492-X7B"
            maxlength="20"
            class="flex-1 px-4 py-2.5 rounded-[10px] bg-canvas border border-border-subtle text-[13.5px] font-semibold font-mono uppercase text-content placeholder:text-content-secondary focus:outline-none focus:border-brand"
          />
          <button
            type="submit"
            class="px-4 py-2.5 rounded-[10px] bg-surface-darkest hover:bg-surface-darkest/90 text-surface text-[13px] font-bold cursor-pointer disabled:opacity-50"
            :disabled="loading || !manualCode.trim()"
          >
            {{ loading ? 'Verificando...' : 'Verificar' }}
          </button>
        </form>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="p-3 rounded-[12px] bg-danger/15 border border-danger/30 text-danger text-xs font-semibold"
      >
        {{ errorMessage }}
      </div>

      <!-- ScannedOrderItemsSection -->
      <div class="flex flex-col gap-2.5">
        <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary">
          BOLSAS A ENTREGAR EN MOSTRADOR
        </span>

        <div
          v-for="(pack, idx) in packsList"
          :key="idx"
          class="flex items-center justify-between gap-4 py-1"
        >
          <span class="text-[14.5px] font-semibold text-content">
            {{ pack.quantity }}× {{ pack.pack_name || pack.name || 'Bolsa Sorpresa' }}
          </span>
          <span class="text-[15px] font-bold text-content tabular-nums shrink-0">
            ${{ formatCurrency(pack.subtotal ?? (pack.unit_price || 0) * (pack.quantity || 1)) }}
          </span>
        </div>

        <div
          v-if="packsList.length > 1"
          class="flex items-center justify-between pt-2 border-t border-border-subtle text-sm font-extrabold text-content"
        >
          <span>Total del pedido</span>
          <span class="tabular-nums">${{ formatCurrency(totalAmount) }}</span>
        </div>
      </div>

      <!-- ScannedModalActionsRow -->
      <div class="flex items-center justify-between gap-3 pt-1">
        <button
          type="button"
          class="px-[18px] py-3 rounded-[10px] bg-canvas hover:bg-surface-sunken text-content text-[13.5px] font-semibold transition-colors cursor-pointer"
          @click="handleClose"
        >
          Cancelar
        </button>

        <button
          type="button"
          class="px-[22px] py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13.5px] font-bold transition-all cursor-pointer disabled:opacity-50"
          :disabled="confirming || loading || (!isVerified && !manualCode.trim())"
          @click="handleConfirm"
        >
          <span v-if="confirming">Confirmando Entrega...</span>
          <span v-else-if="isVerified">Confirmar Entrega Física</span>
          <span v-else>Verificar y Confirmar Entrega</span>
        </button>
      </div>
    </div>
  </div>
</template>
