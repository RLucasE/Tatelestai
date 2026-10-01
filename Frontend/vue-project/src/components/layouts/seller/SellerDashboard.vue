<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import DashboardHeader from './dashboard/DashboardHeader.vue';
import DashboardKpiCards from './dashboard/DashboardKpiCards.vue';
import QuickRedeemCounter from './dashboard/QuickRedeemCounter.vue';
import TodayPickupsSection from './dashboard/TodayPickupsSection.vue';
import LiveStockSection from './dashboard/LiveStockSection.vue';

const router = useRouter();

// Estado del Dashboard
const dashboardData = ref(null);
const loading = ref(true);
const refreshing = ref(false);
const error = ref(null);

// Formateo de fecha actual
const currentDateFormatted = computed(() => {
  const now = new Date();
  return now.toLocaleDateString('es-AR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
});

// Petición al backend
const fetchDashboard = async (isManual = false) => {
  if (isManual) {
    refreshing.value = true;
  } else {
    loading.value = true;
  }
  error.value = null;

  try {
    const response = await axiosInstance.get('/seller/dashboard');
    dashboardData.value = response.data?.data || response.data;
  } catch (err) {
    console.error('Error al cargar dashboard del vendedor:', err);
    error.value = 'No se pudo cargar la información del panel operativo.';
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
};

// Navegación
const goToCreateOffer = () => {
  router.push({ name: 'create-offer' });
};

const goToMyOffers = () => {
  router.push({ name: 'my-offers' });
};

const goToEditOffer = (offerId) => {
  router.push({ name: 'edit-offer', params: { id: offerId } });
};

// Scroll al validador al querer canjear un pedido
const handleRedeemRequest = () => {
  window.scrollTo({ top: 120, behavior: 'smooth' });
  const inputEl = document.querySelector('input[placeholder="XXXX-XXXX-XXXX"]');
  if (inputEl) {
    inputEl.focus();
  }
};

onMounted(() => {
  fetchDashboard();
});
</script>

<template>
  <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Encabezado Principal -->
    <DashboardHeader
      :establishment-name="dashboardData?.establishment?.name"
      :current-date="currentDateFormatted"
      :refreshing="refreshing"
      :loading="loading"
      @refresh="fetchDashboard(true)"
      @create-offer="goToCreateOffer"
    />

    <!-- Estado de Carga (Skeleton minimalista) -->
    <div v-if="loading && !dashboardData" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div v-for="n in 5" :key="n" class="h-20 rounded-2xl bg-[#2D2438] border border-white/[0.04]"></div>
      </div>
      <div class="h-36 rounded-2xl bg-[#2D2438] border border-white/[0.04]"></div>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 h-64 rounded-2xl bg-[#2D2438] border border-white/[0.04]"></div>
        <div class="lg:col-span-5 h-64 rounded-2xl bg-[#2D2438] border border-white/[0.04]"></div>
      </div>
    </div>

    <!-- Estado de Error -->
    <div
      v-else-if="error && !dashboardData"
      class="p-5 rounded-2xl bg-[#EF4444]/10 border border-[#EF4444]/25 flex items-center justify-between gap-4 text-xs text-[#F87171]"
    >
      <div class="flex items-center gap-2.5">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span class="font-semibold">{{ error }}</span>
      </div>
      <button
        type="button"
        class="px-3.5 py-1.5 rounded-xl bg-[#EF4444] hover:bg-[#DC2626] text-white font-bold transition-colors cursor-pointer"
        @click="fetchDashboard(false)"
      >
        Reintentar
      </button>
    </div>

    <!-- Contenido del Dashboard -->
    <div v-else-if="dashboardData" class="space-y-6">
      <!-- 1. Tarjetas de Resumen Operativo (KPIs de Hoy) -->
      <DashboardKpiCards :summary="dashboardData.summary" />

      <!-- 2. Canje Rápido de Código ("Express Counter") -->
      <QuickRedeemCounter
        @delivery-confirmed="fetchDashboard(true)"
      />

      <!-- 3. Columnas Operativas: Retiros de Hoy y Stock en Vivo -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Columna Izquierda: Retiros de Hoy (7 cols) -->
        <div class="lg:col-span-7">
          <TodayPickupsSection
            :pickups="dashboardData.today_pickups || []"
            @redeem="handleRedeemRequest"
          />
        </div>

        <!-- Columna Derecha: Stock en Vivo (5 cols) -->
        <div class="lg:col-span-5">
          <LiveStockSection
            :stock="dashboardData.live_stock || []"
            @create-offer="goToCreateOffer"
            @edit-offer="goToEditOffer"
            @view-all="goToMyOffers"
          />
        </div>
      </div>
    </div>
  </div>
</template>
