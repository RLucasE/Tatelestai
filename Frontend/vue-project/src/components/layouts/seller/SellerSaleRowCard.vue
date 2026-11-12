<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  sale: {
    type: Object,
    required: true,
  },
});

const { formatCurrency, formatTime } = useFormatters();

const customerName = computed(() => {
  return (
    props.sale.customer_name ||
    props.sale.bought_by?.name ||
    props.sale.bought_by?.email ||
    'Cliente'
  );
});

const formattedDateAndCode = computed(() => {
  const parts = [];
  if (props.sale.created_at) {
    const dt = new Date(props.sale.created_at);
    const now = new Date();
    const isToday =
      dt.getDate() === now.getDate() &&
      dt.getMonth() === now.getMonth() &&
      dt.getFullYear() === now.getFullYear();

    const timeStr = dt.toLocaleTimeString('es-AR', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    });

    if (isToday) {
      parts.push(`Hoy, ${timeStr} hs`);
    } else {
      const dateStr = dt.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
      });
      parts.push(`${dateStr}, ${timeStr} hs`);
    }
  }
  const code = props.sale.pickup_code || `TATE-${props.sale.id}`;
  parts.push(`Código ${code}`);
  return parts.join(' • ');
});

const packsSummary = computed(() => {
  const details = props.sale.sell_details || props.sale.packs || [];
  if (details.length === 0) return '1× Bolsa Sorpresa';
  return details
    .map((d) => {
      const qty = d.offer_quantity ?? d.pack_quantity ?? d.quantity ?? 1;
      const name = d.pack_name || d.offer?.title || d.pack?.name || 'Bolsa Sorpresa';
      return `${qty}× ${name}`;
    })
    .join(' • ');
});

const pickupWindowText = computed(() => {
  const details = props.sale.sell_details || props.sale.packs || [];
  const firstOffer = details[0]?.offer;
  const start = props.sale.pickup_start_datetime || firstOffer?.pickup_start_datetime;
  const end =
    props.sale.max_pickup_datetime ||
    props.sale.expiration_datetime ||
    firstOffer?.expiration_datetime;
  if (start && end) {
    return `Ventana de retiro: ${formatTime(start)} a ${formatTime(end)} hs`;
  }
  if (end) {
    return `Retiro hasta las ${formatTime(end)} hs`;
  }
  return 'Ventana de retiro sujeta a horario comercial';
});

const grossAmount = computed(() => {
  if (props.sale.effective_gross_amount !== undefined && props.sale.effective_gross_amount !== null) {
    return Number(props.sale.effective_gross_amount);
  }
  if (props.sale.gross_amount !== undefined) {
    return Math.max(0, Number(props.sale.gross_amount) - Number(props.sale.refunded_amount || 0));
  }
  if (props.sale.total_price !== undefined) return Number(props.sale.total_price);
  const details = props.sale.sell_details || props.sale.packs || [];
  return details.reduce((sum, item) => {
    const qty = Number(item.offer_quantity ?? item.pack_quantity ?? item.quantity ?? 1);
    const price = Number(item.pack_price ?? item.price ?? 0);
    return sum + qty * price;
  }, 0);
});

const commissionAmount = computed(() => {
  if (props.sale.commission_amount !== undefined && props.sale.commission_amount !== null) {
    return Number(props.sale.commission_amount);
  }
  return Math.round(grossAmount.value * 0.15);
});

const netAmount = computed(() => {
  if (props.sale.net_amount !== undefined && props.sale.net_amount !== null) {
    return Number(props.sale.net_amount);
  }
  return Math.max(0, grossAmount.value - commissionAmount.value);
});

const isPickedUp = computed(() => {
  return Boolean(props.sale.is_picked_up) || props.sale.state === 'picked_up' || props.sale.status === 'picked_up';
});

const isCancelled = computed(() => {
  return props.sale.state === 'cancelled' || props.sale.status === 'cancelled';
});

const isRefunded = computed(() => {
  return props.sale.payment_status === 'refunded';
});

const isPartiallyRefunded = computed(() => {
  return props.sale.payment_status === 'partially_refunded';
});

const isExpired = computed(() => {
  if (isPickedUp.value || isCancelled.value) return false;
  if (props.sale.state === 'expired' || props.sale.status === 'expired') return true;
  if (props.sale.max_pickup_datetime) {
    return new Date() > new Date(props.sale.max_pickup_datetime);
  }
  return false;
});

const statusLabel = computed(() => {
  if (isCancelled.value) return 'Reembolsado';
  if (isRefunded.value) return 'Reembolsado por reclamo';
  if (isPartiallyRefunded.value) return 'Reembolso parcial';
  if (isPickedUp.value) return 'Retirado';
  if (isExpired.value) return 'Expirado';
  return 'Pendiente';
});

const statusPillClass = computed(() => {
  if (isCancelled.value || isExpired.value || isRefunded.value) {
    return 'bg-danger/14 text-danger';
  }
  if (isPartiallyRefunded.value) {
    return 'bg-warning/14 text-brand';
  }
  if (isPickedUp.value) {
    return 'bg-content/8 text-content';
  }
  return 'bg-warning/14 text-brand';
});
</script>

<template>
  <div
    class="rounded-[18px] bg-surface border border-border-subtle px-7 py-[22px] shadow-card flex flex-col lg:flex-row lg:items-center justify-between gap-5 transition-all hover:border-brand/40"
  >
    <!-- SaleColumnsGroup -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-12 flex-1 min-w-0 items-center">
      <!-- Col1_OrderAndCustomer -->
      <div class="flex flex-col gap-1 min-w-0">
        <h4 class="text-[15.5px] font-bold text-content truncate">
          #{{ sale.id }} • {{ customerName }}
        </h4>
        <span class="text-[12.5px] font-medium text-content-secondary truncate">
          {{ formattedDateAndCode }}
        </span>
      </div>

      <!-- Col2_PacksSold -->
      <div class="flex flex-col gap-1 min-w-0">
        <span class="text-[14px] font-semibold text-content truncate">
          {{ packsSummary }}
        </span>
        <span class="text-[12px] text-content-secondary truncate">
          {{ pickupWindowText }}
        </span>
      </div>

      <!-- Col3_FinancialBreakdown -->
      <div class="flex items-center gap-6 sm:gap-8">
        <div class="flex flex-col gap-0.5">
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary">
            TOTAL VENTA
          </span>
          <span class="text-[14px] font-bold text-content tabular-nums">
            ${{ formatCurrency(grossAmount) }}
          </span>
        </div>

        <div class="flex flex-col gap-0.5">
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary">
            COMISIÓN (15%)
          </span>
          <span class="text-[14px] font-medium text-content-secondary tabular-nums">
            -${{ formatCurrency(commissionAmount) }}
          </span>
        </div>

        <div class="flex flex-col gap-0.5">
          <span class="text-[10px] font-bold uppercase tracking-wider text-brand">
            TU NETO (85%)
          </span>
          <span class="text-[15px] font-bold text-brand tabular-nums">
            ${{ formatCurrency(netAmount) }}
          </span>
        </div>
      </div>
    </div>

    <!-- SaleStatusPill -->
    <div class="shrink-0 flex flex-col items-start lg:items-end gap-1">
      <span
        class="inline-flex items-center px-3.5 py-1.5 rounded-full text-[12px] font-bold"
        :class="statusPillClass"
      >
        {{ statusLabel }}
      </span>
      <span
        v-if="Number(sale.refunded_amount || 0) > 0 && !isCancelled"
        class="text-[11px] font-medium text-danger"
      >
        Reembolsado: -${{ formatCurrency(sale.refunded_amount) }}
      </span>
      <span
        v-if="sale.claim?.status_label"
        class="text-[11px] font-medium text-content-secondary"
      >
        Reclamo: {{ sale.claim.status_label }}
      </span>
    </div>
  </div>
</template>
