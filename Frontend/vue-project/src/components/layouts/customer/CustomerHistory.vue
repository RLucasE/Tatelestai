<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Encabezado de la Página -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/[0.08]">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-bold uppercase tracking-wider text-[#10B981]">Economía Circular</span>
          <span
            v-if="!loading && history.length > 0"
            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#10B981]/20 border border-[#10B981]/30 text-[#34D399]"
          >
            {{ history.length }} {{ history.length === 1 ? 'rescate' : 'rescates' }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
          Historial de Rescates
        </h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2] mt-1">
          Cada pack sorpresa completado es un alimento que salvaste del desperdicio.
        </p>
      </div>

      <!-- Acciones de Cabecera -->
      <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-[#2D2438] hover:bg-[#3D3450] text-[#E8EAF6] border border-white/[0.08] transition-all hover:scale-[1.02] active:scale-95 shadow-sm"
          :disabled="loading"
          @click="getHistory"
        >
          <svg
            class="w-3.5 h-3.5 text-[#10B981]"
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
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-md transition-all hover:scale-[1.02] active:scale-95"
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
      <div class="bg-[#2D2438] border border-white/[0.06] rounded-2xl p-4 flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-[#10B981]/15 border border-[#10B981]/30 flex items-center justify-center text-[#10B981] shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-[#A5A8C2] font-semibold">Packs Salvados</span>
          <div class="text-xl font-extrabold text-white mt-0.5">
            {{ totalPacksRescued }} <span class="text-xs font-normal text-[#34D399]">packs</span>
          </div>
        </div>
      </div>

      <!-- Pedidos Completados -->
      <div class="bg-[#2D2438] border border-white/[0.06] rounded-2xl p-4 flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-[#7C3AED]/15 border border-[#7C3AED]/30 flex items-center justify-center text-[#A78BFA] shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-[#A5A8C2] font-semibold">Visitas a Locales</span>
          <div class="text-xl font-extrabold text-white mt-0.5">
            {{ history.length }} <span class="text-xs font-normal text-[#C4B5FD]">{{ history.length === 1 ? 'retiro' : 'retiros' }}</span>
          </div>
        </div>
      </div>

      <!-- Total Invertido en Rescate -->
      <div class="bg-[#2D2438] border border-white/[0.06] rounded-2xl p-4 flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-[#F59E0B]/15 border border-[#F59E0B]/30 flex items-center justify-center text-[#F59E0B] shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] uppercase tracking-wider text-[#A5A8C2] font-semibold">Total Invertido</span>
          <div class="text-xl font-extrabold text-white mt-0.5">
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
        class="bg-[#2D2438] border border-white/[0.06] rounded-2xl p-6 space-y-4 animate-pulse"
      >
        <div class="flex items-center justify-between pb-3 border-b border-[#3D3450]/40">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/[0.05]"></div>
            <div class="space-y-1.5">
              <div class="w-32 h-4 rounded bg-white/[0.08]"></div>
              <div class="w-48 h-3 rounded bg-white/[0.04]"></div>
            </div>
          </div>
          <div class="w-24 h-6 rounded-full bg-white/[0.06]"></div>
        </div>
        <div class="w-full h-8 rounded-xl bg-white/[0.03]"></div>
        <div class="space-y-2">
          <div class="w-full h-12 rounded-xl bg-white/[0.04]"></div>
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-[#3D3450]/40">
          <div class="w-24 h-6 rounded bg-white/[0.06]"></div>
          <div class="w-36 h-8 rounded-xl bg-white/[0.06]"></div>
        </div>
      </div>
    </div>

    <!-- Estado de Error -->
    <div
      v-else-if="error"
      class="bg-[#2D2438] border border-[#EF4444]/30 rounded-2xl p-8 text-center space-y-4"
    >
      <div class="w-12 h-12 rounded-2xl bg-[#EF4444]/15 border border-[#EF4444]/30 flex items-center justify-center mx-auto text-[#EF4444]">
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
        <h3 class="text-base font-bold text-white">No se pudo cargar el historial</h3>
        <p class="text-xs text-[#A5A8C2] mt-1">{{ error }}</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] transition-all"
        @click="getHistory"
      >
        Reintentar
      </button>
    </div>

    <!-- Estado Vacío -->
    <div
      v-else-if="history.length === 0"
      class="bg-[#2D2438]/40 border-2 border-dashed border-[#3D3450] rounded-2xl p-10 sm:p-14 text-center space-y-4"
    >
      <div class="w-16 h-16 rounded-2xl bg-[#10B981]/15 border border-[#10B981]/30 flex items-center justify-center mx-auto text-[#10B981]">
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
        <h3 class="text-lg font-bold text-white">Aún no tienes compras completadas</h3>
        <p class="text-xs sm:text-sm text-[#A5A8C2] leading-relaxed">
          Tus packs retirados y el impacto ambiental de los alimentos rescatados se mostrarán aquí una vez que completes tu primer retiro.
        </p>
      </div>
      <div class="pt-2">
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#10B981] hover:bg-[#059669] shadow-lg shadow-[#10B981]/25 transition-all hover:scale-[1.02] active:scale-95"
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
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import HistoryCard from './HistoryCard.vue';

const history = ref([]);
const loading = ref(true);
const error = ref(null);

const totalPacksRescued = computed(() => {
  return history.value.reduce((total, p) => {
    const offers = p.offers || p.sell_details || [];
    return total + offers.reduce((sub, o) => sub + Number(o.offer_quantity || 1), 0);
  }, 0);
});

const totalAmountSpent = computed(() => {
  return history.value.reduce((total, p) => {
    return total + Number(p.total_price || 0);
  }, 0);
});

const getHistory = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axiosInstance.get('/customer/pack-reservations/history');
    history.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Error fetching history:', err);
    error.value = 'No pudimos conectar con el servidor para consultar tu historial.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getHistory();
});
</script>
