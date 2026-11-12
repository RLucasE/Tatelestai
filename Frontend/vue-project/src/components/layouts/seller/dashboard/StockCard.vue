<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['edit']);

const { formatCurrency, formatTime } = useFormatters();

const stockRemaining = computed(() => {
  return props.offer.stock_remaining ?? props.offer.quantity ?? 0;
});

const stockSoldToday = computed(() => {
  return props.offer.stock_sold_today ?? props.offer.sold_quantity ?? 0;
});

const isSoldOut = computed(() => Boolean(props.offer.is_sold_out || stockRemaining.value <= 0));

const isScheduled = computed(() => {
  if (isSoldOut.value) return false;
  const start = props.offer.pickup_start_datetime || props.offer.start_datetime;
  if (!start) return false;
  return new Date(start) > new Date();
});

const statusLabel = computed(() => {
  if (isSoldOut.value) return 'Agotado';
  if (isScheduled.value) return 'Programada';
  return 'Disponible';
});

const statusBadgeClass = computed(() => {
  if (isSoldOut.value) {
    return 'bg-danger/15 text-danger';
  }
  if (isScheduled.value) {
    return 'bg-content-muted/15 text-content';
  }
  return 'bg-brand-light/15 text-brand';
});

const offerTitle = computed(() => {
  return props.offer.title || props.offer.name || 'Bolsa Sorpresa';
});

const offerPrice = computed(() => {
  if (props.offer.price !== undefined) return props.offer.price;
  if (props.offer.packs && props.offer.packs.length > 0) {
    return props.offer.packs[0].price ?? 0;
  }
  return 0;
});

const pickupStart = computed(() => {
  return props.offer.pickup_start_datetime || props.offer.start_datetime;
});

const pickupEnd = computed(() => {
  return props.offer.expiration_datetime || props.offer.end_datetime;
});
</script>

<template>
  <div
    class="rounded-[18px] bg-surface border border-border-subtle px-6 py-[22px] shadow-card flex items-center justify-between gap-4 transition-all hover:border-brand/40"
    :class="{ 'opacity-75': isSoldOut }"
  >
    <!-- StockOfferDetails -->
    <div class="min-w-0 flex-1 flex flex-col gap-2">
      <!-- TitleAndPriceRow -->
      <div class="flex items-center gap-2.5 flex-wrap">
        <h4 class="text-[15px] font-bold text-content truncate max-w-xs sm:max-w-md">
          {{ offerTitle }}
        </h4>

        <span class="text-[14px] font-bold text-brand tabular-nums shrink-0">
          ${{ formatCurrency(offerPrice) }}
        </span>

        <span
          class="inline-flex items-center px-2.5 py-[3px] rounded-full text-[11px] font-bold shrink-0"
          :class="statusBadgeClass"
        >
          {{ statusLabel }}
        </span>
      </div>

      <!-- StockMetricsLine -->
      <p class="text-[12.5px] font-medium text-content-secondary">
        {{ stockRemaining }} {{ stockRemaining === 1 ? 'disponible' : 'disponibles' }}
        • {{ stockSoldToday }} {{ stockSoldToday === 1 ? 'vendido hoy' : 'vendidos hoy' }}
        <template v-if="pickupStart || pickupEnd">
          • Retiro {{ formatTime(pickupStart) }} a {{ formatTime(pickupEnd) }} hs
        </template>
      </p>
    </div>

    <!-- Btn_AjustarOferta -->
    <button
      type="button"
      class="inline-flex items-center justify-center px-4 py-2.5 rounded-[10px] bg-canvas hover:bg-surface-sunken text-content text-[13px] font-semibold transition-colors cursor-pointer shrink-0"
      title="Ajustar oferta"
      @click="emit('edit', offer.offer_id ?? offer.id, offer)"
    >
      Ajustar
    </button>
  </div>
</template>
