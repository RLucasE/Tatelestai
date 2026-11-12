<script setup>
import { ref, computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  pickup: {
    type: Object,
    required: true,
  },
  expanded: {
    type: Boolean,
    default: false,
  },
  verifiedCode: {
    type: String,
    default: '',
  },
  confirming: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['redeem', 'cancelled', 'collapse', 'confirm-delivery']);

const { formatCurrency, formatTime } = useFormatters();

const isCancelling = ref(false);
const showCancelConfirm = ref(false);
const cancelErrorMessage = ref('');

const isPickedUp = computed(
  () => Boolean(props.pickup.is_picked_up) || props.pickup.status === 'picked_up'
);
const isCancelled = computed(() => props.pickup.status === 'cancelled');
const isRefunded = computed(() => props.pickup.payment_status === 'refunded');
const isPartiallyRefunded = computed(() => props.pickup.payment_status === 'partially_refunded');

const isExpired = computed(() => {
  if (isPickedUp.value || isCancelled.value) return false;
  if (props.pickup.status === 'expired') return true;
  if (!props.pickup.max_pickup_datetime) return false;
  return new Date() > new Date(props.pickup.max_pickup_datetime);
});

const statusLabel = computed(() => {
  if (isCancelled.value) return 'Cancelado / Reembolsado';
  if (isRefunded.value) return 'Reembolsado por reclamo';
  if (isPickedUp.value) {
    return isPartiallyRefunded.value ? 'Retirado (Reembolso parcial)' : 'Retirado';
  }
  if (isExpired.value) {
    return isPartiallyRefunded.value ? 'Expirado (Reembolso parcial)' : 'Expirado (No-Show)';
  }
  if (props.pickup.status === 'ready') return 'Listo para retirar';
  if (props.pickup.status === 'confirmed') return 'Confirmado';
  return 'Pendiente de retiro';
});

const statusPillClass = computed(() => {
  if (isCancelled.value || isExpired.value || isRefunded.value) {
    return 'bg-danger/15 text-danger';
  }
  if (isPartiallyRefunded.value) {
    return 'bg-warning/15 text-brand';
  }
  if (isPickedUp.value) {
    return 'bg-success/15 text-success';
  }
  return 'bg-brand-light/15 text-brand';
});

const canRedeem = computed(() => {
  return (
    ['ready', 'confirmed', 'pending'].includes(props.pickup.status) &&
    !isPickedUp.value &&
    !isExpired.value &&
    !isCancelled.value &&
    !isRefunded.value &&
    !isPartiallyRefunded.value
  );
});

const grossAmount = computed(() => {
  return props.pickup.gross_amount ?? props.pickup.total_price ?? 0;
});

const packsSummaryText = computed(() => {
  const packs = props.pickup.packs || [];
  if (packs.length === 0) return 'Bolsa Sorpresa';
  return packs
    .map((p) => `${p.quantity}× ${p.pack_name || p.name || 'Bolsa Sorpresa'}`)
    .join(' • ');
});

const packsDescriptionText = computed(() => {
  const packs = props.pickup.packs || [];
  const firstDesc = packs.find((p) => p.description)?.description;
  return (
    firstDesc ||
    'Selección variada de excedentes gastronómicos frescos elaborados en la jornada.'
  );
});

const displayCode = computed(() => {
  return props.verifiedCode || props.pickup.pickup_code || `PED-${props.pickup.id}`;
});

const handleCancelAndRefund = async () => {
  isCancelling.value = true;
  cancelErrorMessage.value = '';

  try {
    await axiosInstance.post(`/seller/sells/${props.pickup.id}/cancel`, {
      reason: 'Comercio sin merma suficiente',
      restore_stock: false,
    });
    showCancelConfirm.value = false;
    emit('cancelled', props.pickup.id);
  } catch (error) {
    console.error('Error al cancelar pedido:', error);
    cancelErrorMessage.value =
      error.response?.data?.error || 'No se pudo cancelar el pedido.';
  } finally {
    isCancelling.value = false;
  }
};
</script>

<template>
  <!-- EXPANDED STATE (6005:2455 — QR / Código Verificado) -->
  <div
    v-if="expanded"
    class="rounded-[18px] bg-surface border-2 border-brand p-6 shadow-card flex flex-col gap-[22px] transition-all"
  >
    <!-- Header Row -->
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div class="flex flex-col gap-0.5 min-w-0">
        <h4 class="text-[17px] font-extrabold text-content leading-snug truncate">
          {{ pickup.customer_name || 'Cliente' }}
        </h4>
        <p class="text-[12.5px] font-medium text-content-secondary">
          Pedido #{{ pickup.id }}
          <template v-if="pickup.customer_email"> • {{ pickup.customer_email }}</template>
          <template v-if="pickup.pickup_start_datetime || pickup.max_pickup_datetime">
            • Retiro hoy {{ formatTime(pickup.pickup_start_datetime) }} a
            {{ formatTime(pickup.max_pickup_datetime) }} hs
          </template>
        </p>
      </div>

      <span
        class="inline-flex items-center px-3 py-1 rounded-full text-[11.5px] font-bold bg-brand/15 text-brand shrink-0"
      >
        ✓ Código {{ displayCode }} Verificado
      </span>
    </div>

    <!-- Content to deliver -->
    <div class="flex flex-col gap-2">
      <span class="text-[10.5px] font-bold uppercase tracking-wider text-content-secondary">
        CONTENIDO A ENTREGAR AL CLIENTE
      </span>
      <div class="flex items-center justify-between gap-4">
        <span class="text-[14.5px] font-semibold text-content">
          {{ packsSummaryText }}
        </span>
        <span class="text-[16px] font-extrabold text-content tabular-nums shrink-0">
          ${{ formatCurrency(grossAmount) }}
        </span>
      </div>
      <p class="text-[12.5px] text-content-secondary leading-relaxed">
        {{ packsDescriptionText }}
      </p>
    </div>

    <!-- Actions Row -->
    <div class="flex items-center justify-between gap-3 pt-0.5 flex-wrap">
      <div>
        <button
          v-if="canRedeem && !showCancelConfirm"
          type="button"
          class="text-[12.5px] font-semibold text-danger hover:underline cursor-pointer"
          @click="showCancelConfirm = true"
        >
          Sin merma / Reembolsar
        </button>

        <div v-else-if="showCancelConfirm" class="flex items-center gap-2">
          <span class="text-xs text-danger font-semibold">¿Reembolsar 100%?</span>
          <button
            type="button"
            class="px-2.5 py-1 rounded-lg bg-danger hover:bg-danger/90 text-surface font-bold text-xs cursor-pointer disabled:opacity-50"
            :disabled="isCancelling"
            @click="handleCancelAndRefund"
          >
            {{ isCancelling ? 'Reembolsando...' : 'Sí, reembolsar' }}
          </button>
          <button
            type="button"
            class="px-2.5 py-1 rounded-lg bg-canvas text-content font-semibold text-xs cursor-pointer"
            @click="showCancelConfirm = false"
          >
            No
          </button>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <button
          type="button"
          class="text-[13px] font-semibold text-content-secondary hover:text-content cursor-pointer"
          @click="emit('collapse')"
        >
          Cerrar ▴
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13px] font-bold transition-all cursor-pointer disabled:opacity-50"
          :disabled="confirming"
          @click="emit('confirm-delivery', pickup)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ confirming ? 'Confirmando...' : 'Confirmar Entrega' }}</span>
        </button>
      </div>
    </div>

    <p v-if="cancelErrorMessage" class="text-xs text-danger">{{ cancelErrorMessage }}</p>
  </div>

  <!-- DEFAULT COLLAPSED STATE (6002:1851 — SellerPickupRowCard) -->
  <div
    v-else
    class="rounded-[18px] bg-surface border border-border-subtle px-6 py-[22px] shadow-card flex flex-col gap-4 transition-all hover:border-brand/40"
    :class="{ 'opacity-75': isPickedUp }"
  >
    <!-- PickupHeaderRow -->
    <div class="flex items-start justify-between gap-3">
      <div class="flex flex-col gap-0.5 min-w-0">
        <h4 class="text-[15px] font-bold text-content truncate">
          {{ pickup.customer_name || 'Cliente' }}
        </h4>
        <span class="text-[12.5px] font-medium text-content-secondary">
          Pedido #{{ pickup.id }}
          <template v-if="pickup.pickup_start_datetime || pickup.max_pickup_datetime">
            • Retiro hoy de {{ formatTime(pickup.pickup_start_datetime) }} a
            {{ formatTime(pickup.max_pickup_datetime) }} hs
          </template>
        </span>
      </div>

      <span
        class="inline-flex items-center px-3 py-[5px] rounded-full text-[11.5px] font-bold shrink-0"
        :class="statusPillClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- PickupItemsAndTotalRow -->
    <div class="flex items-center justify-between gap-4">
      <span class="text-[13.5px] font-semibold text-content truncate">
        {{ packsSummaryText }}
      </span>
      <span class="text-[15px] font-extrabold text-content tabular-nums shrink-0">
        ${{ formatCurrency(grossAmount) }}
      </span>
    </div>

    <!-- PickupActionsRow -->
    <div class="flex items-center justify-between gap-3 pt-0.5">
      <div v-if="canRedeem && !showCancelConfirm">
        <button
          type="button"
          class="text-[12.5px] font-semibold text-danger hover:underline transition-colors cursor-pointer"
          @click="showCancelConfirm = true"
        >
          Sin merma / Reembolsar
        </button>
      </div>

      <div v-else-if="showCancelConfirm" class="flex items-center gap-2">
        <span class="text-xs text-danger font-semibold">¿Reembolsar 100%?</span>
        <button
          type="button"
          class="px-2.5 py-1 rounded-lg bg-danger hover:bg-danger/90 text-surface font-bold text-xs cursor-pointer disabled:opacity-50"
          :disabled="isCancelling"
          @click="handleCancelAndRefund"
        >
          {{ isCancelling ? 'Reembolsando...' : 'Sí, reembolsar' }}
        </button>
        <button
          type="button"
          class="px-2.5 py-1 rounded-lg bg-canvas text-content font-semibold text-xs cursor-pointer"
          @click="showCancelConfirm = false"
        >
          No
        </button>
      </div>

      <div v-else>
        <span
          v-if="pickup.payment_status === 'refunded' || isCancelled"
          class="text-xs text-danger font-medium"
        >
          Reembolsado al comprador
        </span>
        <span v-else-if="isPickedUp" class="text-xs text-success font-semibold">
          Entregado en mostrador
        </span>
        <span v-else class="text-xs text-content-secondary">
          Ventana finalizada
        </span>
      </div>

      <button
        v-if="canRedeem"
        type="button"
        class="text-[13px] font-bold text-brand hover:text-brand-hover transition-colors cursor-pointer"
        @click="emit('redeem', pickup)"
      >
        Validar y entregar ▾
      </button>
    </div>

    <p v-if="cancelErrorMessage" class="text-xs text-danger">{{ cancelErrorMessage }}</p>
  </div>
</template>
