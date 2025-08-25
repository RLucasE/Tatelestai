<template>
  <div class="admin-offers-container">
    <div class="admin-header">
      <h1 class="page-title">Gestión de Ofertas</h1>
      <p class="page-subtitle">Lista de ofertas creadas por los usuarios</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando ofertas...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="getOffers" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="offers.length === 0" class="empty-container">
      <p>No hay ofertas disponibles en este momento</p>
    </div>

    <!-- Offers Table Component -->
    <OffersTable
      v-else
      :offers="offers"
      @offer-click="handleOfferClick"
      @toggle-status="toggleOfferStatus"
    />

    <!-- Offer Detail Modal Component -->
    <OfferModal
      :visible="isModalVisible"
      :offer="selectedOffer"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import axiosInstance from "@/lib/axios";
import { ref, onMounted } from "vue";
import OffersTable from "./OffersTable.vue";
import OfferModal from "./OfferModal.vue";

const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref({});
const isModalVisible = ref(false);

const getOffers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/adm-offers");
    console.log(response.data);
    offers.value = response.data.data || response.data;
    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar las ofertas";
    offers.value = [];
  } finally {
    loading.value = false;
  }
};

const toggleOfferStatus = async (offer) => {
  try {
    const newStatus = offer.state === 'active' ? 'inactive' : 'active';
    await axiosInstance.patch(`/adm-offers/${offer.id}/status`, {
      state: newStatus
    });

    // Update local state
    offer.state = newStatus;

    // Show success message
    alert(`Oferta ${newStatus === 'active' ? 'activada' : 'desactivada'} exitosamente`);
  } catch (error) {
    console.error("Error updating offer status:", error);
    alert("Error al actualizar el estado de la oferta");
  }
};

const handleOfferClick = (offer) => {
  selectedOffer.value = offer;
  isModalVisible.value = true;
};

const closeModal = () => {
  isModalVisible.value = false;
  selectedOffer.value = {};
};

onMounted(() => {
  getOffers();
});
</script>

<style scoped>
.admin-offers-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.admin-header {
  margin-bottom: 30px;
  text-align: center;
}

.page-title {
  font-size: 2.5em;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 10px 0;
}

.page-subtitle {
  font-size: 1.2em;
  color: var(--color-text);
  opacity: 0.8;
  margin: 0;
}

.loading-container, .error-container, .empty-container {
  text-align: center;
  padding: 40px;
  background: var(--color-secondary);
  border-radius: 12px;
  margin: 20px 0;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-focus);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.retry-btn {
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 16px;
}

.retry-btn:hover {
  background: var(--color-primary, #2563eb);
}
</style>
