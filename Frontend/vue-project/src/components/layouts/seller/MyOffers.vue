<script setup>
import { ref, onMounted } from "vue";
import axiosInstance from "@/lib/axios";
import OfferSellerCard from "./OfferSellerCard.vue";

const offers = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchOffers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/my-offers");
    console.log(response.data);
    offers.value = response.data.data || response.data;
    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar tus ofertas";
    offers.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchOffers();
});
</script>

<template>
  <div class="my-offers-container">
    <!-- Header -->
    <div class="header-section">
      <h1 class="page-title">Mis Ofertas</h1>
      <p class="page-subtitle">
        Gestiona y visualiza todas tus ofertas creadas
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando tus ofertas...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="fetchOffers" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="offers.length === 0" class="empty-container">
      <div class="empty-icon">📋</div>
      <h3>No tienes ofertas creadas</h3>
      <p>Comienza creando tu primera oferta para atraer más clientes</p>
    </div>

    <!-- Offers Grid -->
    <div v-else class="offers-grid">
      <OfferSellerCard v-for="offer in offers" :key="offer.id" :offer="offer" />
    </div>
  </div>
</template>

<style scoped>
.my-offers-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

/* Header Section */
.header-section {
  margin-bottom: 30px;
  text-align: center;
}

.page-title {
  font-size: 2.5em;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 10px 0;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  font-size: 1.1em;
  color: var(--color-text);
  opacity: 0.8;
  margin: 0;
  font-weight: 400;
}

/* Offers Grid */
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

  .page-title {
    font-size: 2.2em;
  }
}

@media (max-width: 768px) {
  .offers-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .my-offers-container {
    padding: 15px;
  }

  .page-title {
    font-size: 2em;
  }

  .page-subtitle {
    font-size: 1em;
  }
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  text-align: center;
  color: var(--color-text);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.loading-container p {
  font-size: 1.1em;
  font-weight: 500;
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  text-align: center;
  color: var(--color-text);
  background-color: var(--color-darkest);
  border-radius: 16px;
  margin: 20px;
  border: 2px solid var(--color-secondary);
}

.error-container p {
  font-size: 1.1em;
  margin-bottom: 20px;
  font-weight: 500;
}

.retry-btn {
  padding: 12px 24px;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  font-size: 1em;
}

.retry-btn:hover {
  background-color: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Empty State */
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 40px;
  text-align: center;
  color: var(--color-text);
  background-color: var(--color-secondary);
  border-radius: 16px;
  margin: 20px;
  border: 2px dashed var(--color-focus);
}

.empty-icon {
  font-size: 4em;
  margin-bottom: 20px;
  opacity: 0.6;
}

.empty-container h3 {
  font-size: 1.5em;
  font-weight: 600;
  margin: 0 0 10px 0;
  color: var(--color-text);
}

.empty-container p {
  font-size: 1em;
  margin: 0;
  opacity: 0.8;
  max-width: 400px;
  line-height: 1.5;
}

/* Hover effects for cards */
.offers-grid .customer-card {
  transition: all 0.3s ease;
}

.offers-grid .customer-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(34, 32, 31, 0.5);
}
</style>
