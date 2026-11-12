<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({
      orders_today: 0,
      packs_sold_today: 0,
      pending_pickups_today: 0,
      completed_pickups_today: 0,
      earnings_today: 0,
      gross_earnings_today: 0,
      commission_percentage: 15,
      commission_today: 0,
      net_earnings_today: 0,
    }),
  },
});

const { formatCurrency } = useFormatters();

const safeSummary = computed(() => {
  const gross = props.summary?.gross_earnings_today ?? props.summary?.earnings_today ?? 0;
  const rate = props.summary?.commission_percentage ?? 15;
  const commission = props.summary?.commission_today ?? (gross * (rate / 100));
  const net = props.summary?.net_earnings_today ?? (gross - commission);

  return {
    orders_today: props.summary?.orders_today ?? 0,
    packs_sold_today: props.summary?.packs_sold_today ?? 0,
    pending_pickups_today: props.summary?.pending_pickups_today ?? 0,
    completed_pickups_today: props.summary?.completed_pickups_today ?? 0,
    gross_earnings_today: gross,
    commission_percentage: rate,
    commission_today: commission,
    net_earnings_today: net,
  };
});
</script>

<template>
  <div class="space-y-3.5">
    <!-- Fila 1: Resumen Financiero Claro (Total Vendido, Comisión, Ganancia Neta) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
      <!-- Total Vendido (Bruto) -->
      <div class="p-4 rounded-2xl bg-surface border border-border-subtle shadow-card flex items-center justify-between gap-3 transition-all hover:border-brand/40">
        <div>
          <span class="text-[11px] font-bold text-content-secondary uppercase tracking-wider block">Total Vendido</span>
          <p class="text-xl sm:text-2xl font-black text-content leading-tight mt-1">
            ${{ formatCurrency(safeSummary.gross_earnings_today) }}
          </p>
          <span class="text-[10px] text-content-secondary">Cobrado vía Mercado Pago</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-canvas text-content-secondary flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <!-- Comisión Tatelestai (15%) -->
      <div class="p-4 rounded-2xl bg-surface border border-border-subtle shadow-card flex items-center justify-between gap-3 transition-all hover:border-danger/40">
        <div>
          <span class="text-[11px] font-bold text-content-secondary uppercase tracking-wider block">
            Comisión App ({{ safeSummary.commission_percentage }}%)
          </span>
          <p class="text-xl sm:text-2xl font-black text-danger leading-tight mt-1">
            -${{ formatCurrency(safeSummary.commission_today) }}
          </p>
          <span class="text-[10px] text-content-secondary">Uso de plataforma y pasarela</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-danger/15 text-danger flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </div>

      <!-- Ganancia Neta (85%) -->
      <div class="p-4 rounded-2xl bg-surface-elevated border border-success/40 shadow-card flex items-center justify-between gap-3 transition-all hover:border-success">
        <div>
          <span class="text-[11px] font-bold text-success uppercase tracking-wider block">Ganancia Neta</span>
          <p class="text-xl sm:text-2xl font-black text-success leading-tight mt-1">
            ${{ formatCurrency(safeSummary.net_earnings_today) }}
          </p>
          <span class="text-[10px] text-success font-medium">85% a liquidar para tu local</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-success/15 text-success flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Fila 2: Métricas Operativas de la Jornada -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
      <!-- Packs Vendidos Hoy -->
      <div class="p-3.5 rounded-2xl bg-surface border border-border-subtle shadow-card flex items-center gap-3 transition-all hover:border-success/40">
        <div class="w-9 h-9 rounded-xl bg-success/15 text-success flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <div>
          <p class="text-[10px] font-semibold text-content-secondary uppercase tracking-wide">Packs Vendidos</p>
          <p class="text-lg sm:text-xl font-black text-content leading-tight mt-0.5">
            {{ safeSummary.packs_sold_today }}
          </p>
        </div>
      </div>

      <!-- Por Entregar Hoy -->
      <div
        class="p-3.5 rounded-2xl border flex items-center gap-3 transition-all"
        :class="safeSummary.pending_pickups_today > 0 ? 'bg-surface-elevated border-brand-light/40 hover:border-brand-light' : 'bg-surface border-border-subtle shadow-card hover:border-border-subtle'"
      >
        <div
          class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
          :class="safeSummary.pending_pickups_today > 0 ? 'bg-brand-light/15 text-brand-light' : 'bg-canvas text-content-secondary'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-[10px] font-semibold text-content-secondary uppercase tracking-wide">Por Entregar</p>
          <p
            class="text-lg sm:text-xl font-black leading-tight mt-0.5"
            :class="safeSummary.pending_pickups_today > 0 ? 'text-brand-light' : 'text-content'"
          >
            {{ safeSummary.pending_pickups_today }}
          </p>
        </div>
      </div>

      <!-- Pedidos Recibidos Hoy -->
      <div class="p-3.5 rounded-2xl bg-surface border border-border-subtle shadow-card flex items-center gap-3 transition-all hover:border-brand/40">
        <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <div>
          <p class="text-[10px] font-semibold text-content-secondary uppercase tracking-wide">Pedidos Hoy</p>
          <p class="text-lg sm:text-xl font-black text-content leading-tight mt-0.5">
            {{ safeSummary.orders_today }}
          </p>
        </div>
      </div>

      <!-- Retiros Completados -->
      <div class="p-3.5 rounded-2xl bg-surface border border-border-subtle shadow-card flex items-center gap-3 transition-all hover:border-success/40">
        <div class="w-9 h-9 rounded-xl bg-success/15 text-success flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <p class="text-[10px] font-semibold text-content-secondary uppercase tracking-wide">Entregados</p>
          <p class="text-lg sm:text-xl font-black text-content leading-tight mt-0.5">
            {{ safeSummary.completed_pickups_today }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
