<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 text-content">
    <!-- Encabezado de la Página -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border-subtle">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-bold uppercase tracking-wider text-brand">Economía Circular</span>
          <span
            v-if="!loading && history.length > 0"
            class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-brand/10 border border-brand/20 text-brand"
          >
            {{ history.length }} {{ history.length === 1 ? 'rescate' : 'rescates' }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight">
          Historial de Rescates
        </h1>
        <p class="text-xs sm:text-sm text-content-secondary mt-1">
          Cada pack sorpresa completado es un alimento que salvaste del desperdicio.
        </p>
      </div>

      <!-- Acciones de Cabecera -->
      <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[10px] text-xs font-semibold bg-surface hover:bg-surface-sunken text-content border border-border-subtle transition-all hover:scale-[1.02] active:scale-95 shadow-2xs cursor-pointer"
          :disabled="loading"
          @click="getHistory"
        >
          <svg
            class="w-3.5 h-3.5 text-brand"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>{{ loading ? 'Actualizando...' : 'Actualizar' }}</span>
        </button>

        <RouterLink
          to="/customer/purchases"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[10px] text-xs font-bold bg-brand hover:bg-brand-hover text-surface shadow-sm transition-all hover:scale-[1.02] active:scale-95 cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
          </svg>
          <span>Ver Reservas Activas</span>
        </RouterLink>
      </div>
    </header>

    <!-- Resumen de Métricas Ecológicas (cuando hay historial) -->
    <div
      v-if="!loading && history.length > 0"
      class="grid grid-cols-1 sm:grid-cols-3 gap-3"
    >
      <!-- Packs Rescatados -->
      <div class="bg-surface border border-border-subtle rounded-[18px] p-4 flex items-center gap-3.5 shadow-card">
        <div class="w-11 h-11 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-center text-brand shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-content-secondary font-bold">Packs Salvados</span>
          <div class="text-xl font-extrabold text-content-strong mt-0.5">
            {{ totalPacksRescued }} <span class="text-xs font-normal text-brand">packs</span>
          </div>
        </div>
      </div>

      <!-- Pedidos Completados -->
      <div class="bg-surface border border-border-subtle rounded-[18px] p-4 flex items-center gap-3.5 shadow-card">
        <div class="w-11 h-11 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-center text-brand-light shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-content-secondary font-bold">Visitas a Locales</span>
          <div class="text-xl font-extrabold text-content-strong mt-0.5">
            {{ history.length }} <span class="text-xs font-normal text-content-secondary">{{ history.length === 1 ? 'retiro' : 'retiros' }}</span>
          </div>
        </div>
      </div>

      <!-- Total Invertido en Rescate -->
      <div class="bg-surface border border-border-subtle rounded-[18px] p-4 flex items-center gap-3.5 shadow-card">
        <div class="w-11 h-11 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-center text-success shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-content-secondary font-bold">Total Invertido</span>
          <div class="text-xl font-extrabold text-content-strong mt-0.5">
            ${{ Number(totalAmountSpent).toLocaleString('es-AR') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Skeletons durante carga -->
    <div v-if="loading" class="space-y-4">
      <div
        v-for="n in 2"
        :key="n"
        class="bg-surface border border-border-subtle rounded-[22px] p-6 space-y-4 animate-pulse shadow-card"
      >
        <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-surface-sunken"></div>
            <div class="space-y-1.5">
              <div class="w-32 h-4 rounded bg-surface-sunken"></div>
              <div class="w-48 h-3 rounded bg-surface-sunken/60"></div>
            </div>
          </div>
          <div class="w-24 h-6 rounded-full bg-surface-sunken"></div>
        </div>
        <div class="w-full h-8 rounded-xl bg-surface-sunken/60"></div>
        <div class="space-y-2">
          <div class="w-full h-12 rounded-xl bg-surface-sunken/40"></div>
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-border-subtle">
          <div class="w-24 h-6 rounded bg-surface-sunken"></div>
          <div class="w-36 h-8 rounded-xl bg-surface-sunken"></div>
        </div>
      </div>
    </div>

    <!-- Estado de Error -->
    <div
      v-else-if="error"
      class="bg-surface border border-danger/30 rounded-[22px] p-8 text-center space-y-4 shadow-card"
    >
      <div class="w-12 h-12 rounded-2xl bg-danger/10 border border-danger/30 flex items-center justify-center mx-auto text-danger">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <div>
        <h3 class="text-base font-extrabold text-content-strong">No se pudo cargar el historial</h3>
        <p class="text-xs text-content-secondary mt-1">{{ error }}</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-[10px] text-xs font-bold text-surface bg-brand hover:bg-brand-hover shadow-sm transition-all cursor-pointer"
        @click="getHistory"
      >
        Reintentar
      </button>
    </div>

    <!-- Estado Vacío -->
    <div
      v-else-if="history.length === 0"
      class="bg-surface border-2 border-dashed border-border-subtle rounded-[22px] p-10 sm:p-14 text-center space-y-4 shadow-card"
    >
      <div class="w-16 h-16 rounded-2xl bg-surface-sunken border border-border-subtle flex items-center justify-center mx-auto text-brand">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.8"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <div class="max-w-sm mx-auto space-y-1">
        <h3 class="text-lg font-extrabold text-content-strong">Aún no tienes compras completadas</h3>
        <p class="text-xs sm:text-sm text-content-secondary leading-relaxed">
          Tus packs retirados y el impacto ambiental de los alimentos rescatados se mostrarán aquí una vez que completes tu primer retiro.
        </p>
      </div>
      <div class="pt-2">
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-2 px-6 py-3 rounded-[10px] text-xs sm:text-sm font-bold text-surface bg-brand hover:bg-brand-hover shadow-sm transition-all hover:scale-[1.02] active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span>Rescatar mi primer pack</span>
        </RouterLink>
      </div>
    </div>

    <!-- Lista de Historial -->
    <div v-else class="space-y-4">
      <HistoryCard
        v-for="purchase in history"
        :key="purchase.sell_id || purchase.id"
        :purchase="purchase"
        @claim-created="getHistory(true)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import HistoryCard from './HistoryCard.vue';
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();

const history = ref([]);
const loading = ref(true);
const error = ref(null);

const totalPacksRescued = computed(() => {
  return history.value.reduce((total, p) => {
    if (p.payment_status === 'refunded') return total;
    const offers = p.offers || p.sell_details || [];
    return total + offers.reduce((sub, o) => sub + Number(o.offer_quantity || 1), 0);
  }, 0);
});

const totalAmountSpent = computed(() => {
  return history.value.reduce((total, p) => {
    if (p.payment_status === 'refunded') return total;
    const effective = Math.max(0, Number(p.total_price || 0) - Number(p.refunded_amount || 0));
    return total + effective;
  }, 0);
});

const getHistory = async (silent = false) => {
  const isSilent = silent === true;
  try {
    if (!isSilent) loading.value = true;
    error.value = null;
    const response = await axiosInstance.get('/customer/pack-reservations/history');
    history.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Error fetching history:', err);
    error.value = 'No pudimos conectar con el servidor para consultar tu historial.';
  } finally {
    if (!isSilent) loading.value = false;
  }
};

watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (event) {
      getHistory(true);
    }
  }
);

onMounted(() => {
  getHistory();
});
</script>
