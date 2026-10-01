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
    }),
  },
});

const { formatCurrency } = useFormatters();

const safeSummary = computed(() => ({
  orders_today: props.summary?.orders_today ?? 0,
  packs_sold_today: props.summary?.packs_sold_today ?? 0,
  pending_pickups_today: props.summary?.pending_pickups_today ?? 0,
  completed_pickups_today: props.summary?.completed_pickups_today ?? 0,
  earnings_today: props.summary?.earnings_today ?? 0,
}));
</script>

<template>
  <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
    <!-- Packs Vendidos Hoy -->
    <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] flex items-center gap-3.5 transition-all hover:border-[#10B981]/40 hover:bg-[#3D3450]/70">
      <div class="w-10 h-10 rounded-xl bg-[#10B981]/15 text-[#10B981] flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wide">Packs Vendidos</p>
        <p class="text-xl sm:text-2xl font-black text-white leading-tight mt-0.5">
          {{ safeSummary.packs_sold_today }}
        </p>
      </div>
    </div>

    <!-- Por Entregar Hoy -->
    <div
      class="p-4 rounded-2xl bg-[#2D2438] border flex items-center gap-3.5 transition-all"
      :class="safeSummary.pending_pickups_today > 0 ? 'border-[#F59E0B]/40 bg-[#F59E0B]/5 hover:bg-[#F59E0B]/10' : 'border-white/[0.08] hover:bg-[#3D3450]/70'"
    >
      <div
        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
        :class="safeSummary.pending_pickups_today > 0 ? 'bg-[#F59E0B]/20 text-[#FBBF24]' : 'bg-white/[0.05] text-[#A5A8C2]'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wide">Por Entregar</p>
        <p
          class="text-xl sm:text-2xl font-black leading-tight mt-0.5"
          :class="safeSummary.pending_pickups_today > 0 ? 'text-[#FBBF24]' : 'text-white'"
        >
          {{ safeSummary.pending_pickups_today }}
        </p>
      </div>
    </div>

    <!-- Pedidos Recibidos Hoy -->
    <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] flex items-center gap-3.5 transition-all hover:border-[#7C3AED]/40 hover:bg-[#3D3450]/70">
      <div class="w-10 h-10 rounded-xl bg-[#7C3AED]/15 text-[#A78BFA] flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wide">Pedidos Hoy</p>
        <p class="text-xl sm:text-2xl font-black text-white leading-tight mt-0.5">
          {{ safeSummary.orders_today }}
        </p>
      </div>
    </div>

    <!-- Retiros Completados -->
    <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] flex items-center gap-3.5 transition-all hover:border-[#3B82F6]/40 hover:bg-[#3D3450]/70">
      <div class="w-10 h-10 rounded-xl bg-[#3B82F6]/15 text-[#60A5FA] flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wide">Entregados</p>
        <p class="text-xl sm:text-2xl font-black text-white leading-tight mt-0.5">
          {{ safeSummary.completed_pickups_today }}
        </p>
      </div>
    </div>

    <!-- Recaudación de Hoy -->
    <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] flex items-center gap-3.5 col-span-2 sm:col-span-1 transition-all hover:border-[#A78BFA]/40 hover:bg-[#3D3450]/70">
      <div class="w-10 h-10 rounded-xl bg-[#A78BFA]/15 text-[#C4B5FD] flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wide">Recaudación</p>
        <p class="text-xl sm:text-2xl font-black text-[#C4B5FD] leading-tight mt-0.5">
          ${{ formatCurrency(safeSummary.earnings_today) }}
        </p>
      </div>
    </div>
  </section>
</template>
