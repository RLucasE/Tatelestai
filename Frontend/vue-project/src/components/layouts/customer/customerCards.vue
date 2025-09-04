<script setup>
import axiosInstance from "@/lib/axios";
import CustomerCard from "./CustomerCard.vue";
import OfferModal from "../../common/OfferModal.vue";
import SearchBar from "../../common/SearchBar.vue";
import { ref, onMounted } from "vue";

const offers = ref([]);
const originalOffers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref({});
const isVisible = ref(false);
const isSearchActive = ref(false);

const getOffers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/offers");
    console.log(response.data);
    const fetchedOffers = response.data.data || response.data;
    offers.value = fetchedOffers;
    originalOffers.value = fetchedOffers;
    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar las ofertas";
    offers.value = [];
  } finally {
    loading.value = false;
  }
};

const handleSearchResults = (searchResults) => {
  offers.value = searchResults;
  isSearchActive.value = true;
};

const handleSearchError = (errorMessage) => {
  error.value = errorMessage;
};

const handleSearchClear = () => {
  offers.value = originalOffers.value;
  isSearchActive.value = false;
  error.value = null;
};

const addOfferToCart = async ({ id, quantity }) => {
  const offerPayload = {
    offer_id: id,
    quantity: quantity || 1, // Assuming a default quantity of 1
  };

  try {
    console.log(offerPayload);
    const response = await axiosInstance.post("/add-to-cart", offerPayload);
    console.log(response);
  } catch (error) {
    console.log(error);
    if (error.status === 400) {
      if ((error.data = "OfferQuantityExceded")) {
        alert("Ya no se pueden agregar más unidades de esta oferta");
      }
    }
  }
};

const buyOffer = async ({ id, quantity ,food_establishment_id}) => {
  const offerPayload = {
    food_establishment_id: food_establishment_id,
    offers: [
      {
        id: id,
        quantity: quantity || 1,
      },
    ],
  };

  try {
    const response = await axiosInstance.post("/buy-offers", offerPayload);
  } catch (error) {
    if (error.status === 400) {
      if ((error.data = "OfferQuantityExceded")) {
        alert("Ya no se pueden agregar más unidades de esta oferta");
      }
    }
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

const changueLoading = (state) => {
  loading.value = state;
};
</script>

<template>
  <div class="offers-container">
    <!-- Loading State -->
    <SearchBar
        @search-results="handleSearchResults"
        @search-error="handleSearchError"
        @search-clear="handleSearchClear"
        @search-leading="changueLoading"
        placeholder="Buscar ofertas por nombre, descripción o establecimiento..."
    />

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
    <div v-else-if="offers.length === 0 && !isSearchActive" class="empty-container">
      <p>No hay ofertas disponibles en este momento</p>
    </div>

    <!-- Search Results Empty State -->
    <div v-else-if="offers.length === 0 && isSearchActive" class="empty-container">
      <p>No se encontraron ofertas que coincidan con tu búsqueda</p>
    </div>

    <!-- Content (Search Bar + Offers Grid) -->
    <div v-else>
      <!-- Search Bar -->
      <!-- Offers Grid -->
      <div class="offers-grid">
        <CustomerCard
          v-for="offer in offers"
          :key="offer.id"
          :offer="offer"
          @click="handleOfferClick(offer)"
        />
      </div>
    </div>

    <OfferModal
      :isVisible="isVisible"
      :offer="selectedOffer"
      @close="handleCloseOffer"
      @offerAction="addOfferToCart"
      @buyOffer="buyOffer"
    />
  </div>
</template>

<style scoped>
.offers-container {
  padding: 20px;
  max-width: 1200px;
  min-width: 70%;
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
