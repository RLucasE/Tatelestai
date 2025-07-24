<script setup>
import axiosInstance from "@/lib/axios";
import CustomerCard from "./CustomerCard.vue";
import OfferModal from "../../common/OfferModal.vue";
import { ref, onMounted } from "vue";

const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref({});
const isVisible = ref(false);

const getOffers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/offers");
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

const handleOfferClick = (offer) => {
  selectedOffer.value = offer;
  isVisible.value = true;
};

const handleCloseOffer = () => {
  isVisible.value = false;
};

onMounted(() => {
  getOffers();
});
</script>

<template>
  <div class="offers-container">
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

    <!-- Offers Grid -->
    <div v-else class="offers-grid">
      <CustomerCard
        v-for="offer in offers"
        :key="offer.id"
        :offer="offer"
        @click="handleOfferClick(offer)"
      />
    </div>

    <OfferModal
      :isVisible="isVisible"
      :offer="selectedOffer"
      @close="handleCloseOffer"
    />
  </div>
</template>

<style scoped>
.offers-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.offers-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-top: 20px;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .offers-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
}

@media (max-width: 768px) {
  .offers-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .offers-container {
    padding: 15px;
  }
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
  color: var(--color-text);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
  color: var(--color-text);
  background-color: var(--color-darkest);
  border-radius: 12px;
  margin: 20px;
}

.retry-btn {
  margin-top: 15px;
  padding: 10px 20px;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
  font-weight: 500;
}

.retry-btn:hover {
  background-color: var(--color-focus);
}

/* Empty State */
.empty-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
  color: var(--color-text);
  font-size: 18px;
  background-color: var(--color-secondary);
  border-radius: 12px;
  margin: 20px;
}
</style>
