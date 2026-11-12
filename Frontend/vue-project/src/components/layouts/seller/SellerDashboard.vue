<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import { useNotificationStore } from '@/stores/notifications';
import TodayPickupsSection from './dashboard/TodayPickupsSection.vue';
import LiveStockSection from './dashboard/LiveStockSection.vue';
import EditActiveOfferModal from './EditActiveOfferModal.vue';

const router = useRouter();
const notificationStore = useNotificationStore();

const dashboardData = ref(null);
const loading = ref(true);
const refreshing = ref(false);
const error = ref(null);

const showEditOfferModal = ref(false);
const selectedOfferId = ref(null);
const selectedOfferObj = ref(null);

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

const goToPacksAndTemplates = () => {
  router.push({ name: 'pack-templates' });
};

const handleEditOffer = (offerId, offerObj) => {
  selectedOfferId.value = offerId;
  selectedOfferObj.value = offerObj || null;
  showEditOfferModal.value = true;
};

watch(
  () => notificationStore.lastOrderEvent,
  () => {
    fetchDashboard(true);
  }
);

onMounted(() => {
  fetchDashboard();
});
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14">
    <!-- Loading State -->
    <div v-if="loading && !dashboardData" class="grid grid-cols-1 lg:grid-cols-2 gap-9 animate-pulse">
      <div class="space-y-4">
        <div class="h-10 rounded-full bg-surface border border-border-subtle"></div>
        <div class="h-11 rounded-full bg-surface border border-border-subtle"></div>
        <div v-for="n in 3" :key="n" class="h-36 rounded-[18px] bg-surface border border-border-subtle"></div>
      </div>
      <div class="space-y-4">
        <div class="h-10 rounded-full bg-surface border border-border-subtle"></div>
        <div class="h-11 rounded-full bg-surface border border-border-subtle"></div>
        <div v-for="n in 3" :key="n" class="h-28 rounded-[18px] bg-surface border border-border-subtle"></div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error && !dashboardData"
      class="p-6 rounded-[18px] bg-surface border border-danger/40 shadow-card flex items-center justify-between gap-4 text-sm text-danger"
    >
      <div class="flex items-center gap-2.5">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span class="font-semibold">{{ error }}</span>
      </div>
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-xs font-bold transition-colors cursor-pointer"
        @click="fetchDashboard(false)"
      >
        Reintentar
      </button>
    </div>

    <!-- OperationalColumnsGrid (6002:1955) -->
    <div v-else-if="dashboardData" class="grid grid-cols-1 lg:grid-cols-2 gap-9 items-start">
      <!-- ColLeft_RetirosDeHoy -->
      <div>
        <TodayPickupsSection
          :pickups="dashboardData.today_pickups || []"
          @reload="fetchDashboard(true)"
        />
      </div>

      <!-- ColRight_StockEnVivo -->
      <div>
        <LiveStockSection
          :stock="dashboardData.live_stock || []"
          @create-offer="goToPacksAndTemplates"
          @edit-offer="handleEditOffer"
          @view-all="goToPacksAndTemplates"
        />
      </div>
    </div>

    <!-- Modal Ajustar Oferta Activa (6005:2673) -->
    <EditActiveOfferModal
      v-model="showEditOfferModal"
      :offer-id="selectedOfferId"
      :offer="selectedOfferObj"
      @saved="fetchDashboard(true)"
      @deactivated="fetchDashboard(true)"
    />
  </div>
</template>
