<template>
  <div class="seller-offers-container">
    <div class="admin-header">
      <button @click="goBack" class="back-btn">
        ← Volver
      </button>
      <h1 class="page-title">Ofertas del Vendedor</h1>
      <p v-if="sellerInfo" class="page-subtitle">
        Ofertas de {{ sellerInfo.name }} ({{ sellerInfo.email }})
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando ofertas...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="getSellerOffers" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="offers.length === 0" class="empty-container">
      <p>Este vendedor no tiene ofertas disponibles</p>
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
import { useRoute, useRouter } from "vue-router";
import OffersTable from "./OffersTable.vue";
import OfferModal from "./OfferModal.vue";

const route = useRoute();
const router = useRouter();

const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref({});
const isModalVisible = ref(false);
const sellerInfo = ref(null);

const sellerId = parseInt(route.params.id);

const getSellerOffers = async () => {
  try {
    loading.value = true;
    console.log(sellerId);
    const response = await axiosInstance.get(`user/${sellerId}/offers`);
    offers.value = response.data.offers || response.data.data || response.data;
    sellerInfo.value = response.data.seller || null;
    error.value = null;
  } catch (err) {
    console.error("Error fetching seller offers:", err);
    error.value = "Error al cargar las ofertas del vendedor";
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

const goBack = () => {
  router.go(-1);
};

onMounted(() => {
  getSellerOffers();
});
</script>

<style scoped>
.seller-offers-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.admin-header {
  margin-bottom: 30px;
  text-align: center;
  position: relative;
}

.back-btn {
  position: absolute;
  left: 0;
  top: 0;
  background: var(--color-primary);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.back-btn:hover {
  background: var(--color-surface-sunken-base, #F2ECE4);
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.page-title {
  font-size: 2.5em;
  font-weight: 700;
  color: var(--color-heading);
  margin: 0 0 10px 0;
}

.page-subtitle {
  font-size: 1.2em;
  color: var(--color-text-secondary);
  opacity: 0.8;
  margin: 0;
}

.loading-container, .error-container, .empty-container {
  text-align: center;
  padding: 40px;
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-card);
  border-radius: 12px;
  margin: 20px 0;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-accent);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.retry-btn {
  background: var(--color-accent);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  margin-top: 16px;
  transition: background-color 0.2s ease;
}

.retry-btn:hover {
  background: var(--color-accent-hover);
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2em;
  }

  .admin-header {
    text-align: left;
    padding-top: 50px;
  }

  .back-btn {
    position: static;
    margin-bottom: 16px;
  }
}
</style>
