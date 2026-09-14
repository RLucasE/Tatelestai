<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";
import OfferSellerCard from "./OfferSellerCard.vue";

const router = useRouter();

const offers = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchOffers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/my-packs");
    offers.value = response.data?.data || response.data || [];
    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar tus ofertas";
    offers.value = [];
  } finally {
    loading.value = false;
  }
};

const handleOfferClick = (offer) => {
  router.push({ name: "edit-offer", params: { id: offer.id } });
};

const goToCreateOffer = () => {
  router.push({ name: "create-offer" });
};

onMounted(() => {
  fetchOffers();
});
</script>

<template>
  <div class="my-offers-container">
    <!-- Header -->
    <div class="header-section">
      <div class="header-titles">
        <h1 class="page-title">Mis Ofertas de Packs</h1>
        <p class="page-subtitle">
          Gestiona y supervisa tus bolsas sorpresa activas y pasadas.
        </p>
      </div>
      <button @click="goToCreateOffer" class="create-pack-cta">
        <span class="cta-icon">+</span>
        <span>Publicar Oferta</span>
      </button>
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
      <div class="empty-icon">🛍️</div>
      <h3>No tienes ofertas creadas</h3>
      <p>Comienza publicando tu primera bolsa sorpresa para rescatar excedentes y captar clientes.</p>
      <button @click="goToCreateOffer" class="empty-cta-btn">
        + Crear Mi Primera Oferta
      </button>
    </div>

    <!-- Offers Grid -->
    <div v-else class="offers-grid">
      <OfferSellerCard 
        v-for="offer in offers" 
        :key="offer.id" 
        :offer="offer" 
        @click="handleOfferClick(offer)"
        class="clickable-card"
      />
    </div>
  </div>
</template>

<style scoped>
.my-offers-container {
  padding: 1.5rem 2rem;
  max-width: 1300px;
  margin: 0 auto;
  color: var(--color-text, #e8eaf6);
  min-height: 100vh;
}

/* Header Section */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.header-titles {
  display: flex;
  flex-direction: column;
}

.page-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.35rem 0;
  letter-spacing: -0.02em;
}

.page-subtitle {
  font-size: 1.05rem;
  color: #b3acc0;
  margin: 0;
}

.create-pack-cta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 1.6rem;
  background: #7c3aed;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.35);
}

.create-pack-cta:hover {
  background: #6d28d9;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5);
}

.cta-icon {
  font-size: 1.25rem;
  line-height: 1;
}

/* Offers Grid */
.offers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.clickable-card {
  cursor: pointer;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: #b3acc0;
}

.loading-spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #3d3450;
  border-top: 4px solid #7c3aed;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 50px 20px;
  background: #2d2438;
  border-radius: 12px;
  border: 1px solid #ef4444;
  color: #fca5a5;
  text-align: center;
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.6rem 1.4rem;
  background: #7c3aed;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* Empty State */
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
  background: #2d2438;
  border-radius: 16px;
  border: 2px dashed #4a4058;
}

.empty-icon {
  font-size: 3.5rem;
  margin-bottom: 1rem;
}

.empty-container h3 {
  font-size: 1.4rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.5rem 0;
}

.empty-container p {
  color: #b3acc0;
  max-width: 440px;
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.empty-cta-btn {
  padding: 0.85rem 1.6rem;
  background: #7c3aed;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.35);
}

.empty-cta-btn:hover {
  background: #6d28d9;
  transform: translateY(-2px);
}

@media (max-width: 640px) {
  .my-offers-container {
    padding: 1rem;
  }

  .header-section {
    flex-direction: column;
    align-items: stretch;
  }

  .create-pack-cta {
    justify-content: center;
  }
}
</style>
