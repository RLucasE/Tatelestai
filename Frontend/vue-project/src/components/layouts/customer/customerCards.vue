<script setup>
import axiosInstance from "@/lib/axios";
import CustomerCard from "./CustomerCard.vue";
import OfferModal from "../../common/OfferModal.vue";
import SearchBar from "../../common/SearchBar.vue";
import { ref, onMounted, onUnmounted } from "vue";

const offers = ref([]);
const originalOffers = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const error = ref(null);
const selectedOffer = ref({});
const isVisible = ref(false);
const isSearchActive = ref(false);
const currentPage = ref(1);
const hasMorePages = ref(true);
const searchQuery = ref('');

// Sistema de notificaciones
const notification = ref({
  show: false,
  message: '',
  type: 'success' // 'success', 'error', 'info'
});

const showNotification = (message, type = 'success') => {
  notification.value = {
    show: true,
    message,
    type
  };

  // Auto-ocultar después de 3 segundos
  setTimeout(() => {
    notification.value.show = false;
  }, 3000);
};

const getOffers = async (page = 1, isLoadMore = false) => {
  try {
    if (isLoadMore) {
      loadingMore.value = true;
    } else {
      loading.value = true;
    }

    const params = { page };
    if (isSearchActive.value && searchQuery.value) {
      params.search = searchQuery.value;
    }

    const response = await axiosInstance.get("/offers", { params });
    console.log(response.data);

    const fetchedOffers = response.data.data;
    currentPage.value = response.data.current_page;
    hasMorePages.value = response.data.has_more;

    if (isLoadMore) {
      // Agregar nuevas ofertas al final de la lista existente
      offers.value = [...offers.value, ...fetchedOffers];
    } else {
      // Reemplazar la lista completa
      offers.value = fetchedOffers;
      originalOffers.value = fetchedOffers;
    }

    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar las ofertas";
    if (!isLoadMore) {
      offers.value = [];
    }
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

const handleScroll = () => {
  const scrollHeight = document.documentElement.scrollHeight;
  const scrollTop = window.scrollY;
  const clientHeight = document.documentElement.clientHeight;

  // Cuando el usuario está cerca del final (200px antes)
  if (scrollHeight - scrollTop - clientHeight < 200) {
    if (!loadingMore.value && hasMorePages.value) {
      loadMoreOffers();
    }
  }
};

const loadMoreOffers = async () => {
  if (loadingMore.value || !hasMorePages.value) return;
  await getOffers(currentPage.value + 1, true);
};

const handleSearchResults = (results) => {
  searchQuery.value = results;
  isSearchActive.value = true;
  currentPage.value = 1;
  getOffers(1, false);
};

const handleSearchError = (errorMessage) => {
  error.value = errorMessage;
};

const handleSearchClear = () => {
  searchQuery.value = '';
  isSearchActive.value = false;
  currentPage.value = 1;
  getOffers(1, false);
};

const addOfferToCart = async ({ id, quantity }) => {
  const offerPayload = {
    offer_id: id,
    quantity: quantity || 1,
  };

  try {
    const response = await axiosInstance.post("/add-to-cart", offerPayload);
    showNotification('Oferta agregada al carrito', 'success');
  } catch (error) {
    console.log(error);
    if (error.status === 400) {
      if ((error.data = "OfferQuantityExceded")) {
        alert("Ya no se pueden agregar más unidades de esta oferta");
      }
    }
  }
};

const buyOffer = async ({ id, quantity, food_establishment_id }) => {
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

    // Cartel de éxito cuando la compra se realiza correctamente
    showNotification('🎉 ¡Compra realizada con éxito! Tu pedido está siendo procesado', 'success');

    // Cerrar el modal después de la compra exitosa
    setTimeout(() => {
      isVisible.value = false;
    }, 1500);

  } catch (error) {
    console.log(error);
    if (error.status === 400) {
      if (error.data === "OfferQuantityExceded") {
        showNotification('Ya no se pueden agregar más unidades de esta oferta', 'error');
      } else {
        showNotification('Error al procesar la compra. Verifica los datos', 'error');
      }
    } else {
      showNotification('Error de conexión. Intenta nuevamente', 'error');
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
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
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

    <div v-if="loading && !loadingMore" class="loading-container">
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
      <!-- Offers Grid -->
      <div class="offers-grid">
        <CustomerCard
          v-for="offer in offers"
          :key="offer.id"
          :offer="offer"
          @click="handleOfferClick(offer)"
        />
      </div>

      <!-- Loading More Indicator -->
      <div v-if="loadingMore" class="loading-more">
        <div class="loading-spinner-small"></div>
        <p>Cargando más ofertas...</p>
      </div>

      <!-- End Message -->
      <div v-else-if="!hasMorePages && offers.length > 0" class="end-message">
        <p>No hay más ofertas para mostrar</p>
      </div>
    </div>

    <OfferModal
      :isVisible="isVisible"
      :offer="selectedOffer"
      @close="handleCloseOffer"
      @offerAction="addOfferToCart"
      @buyOffer="buyOffer"
    />

    <!-- Notification Component -->
    <Transition name="notification" appear>
      <div v-if="notification.show" :class="`notification notification-${notification.type}`">
        <div class="notification-content">
          <div class="notification-icon">
            <svg v-if="notification.type === 'success'" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            <svg v-else-if="notification.type === 'error'" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
            <svg v-else width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div class="notification-message">
            {{ notification.message }}
          </div>
          <button class="notification-close" @click="notification.show = false" aria-label="Cerrar notificación">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
              <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
          </button>
        </div>
        <div class="notification-progress"></div>
      </div>
    </Transition>
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

/* Loading More Indicator */
.loading-more {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 15px;
  margin-top: 20px;
  text-align: center;
}

.loading-spinner-small {
  width: 25px;
  height: 25px;
  border: 3px solid var(--color-secondary);
  border-top: 3px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
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

/* End Message */
.end-message {
  text-align: center;
  padding: 20px;
  margin-top: 10px;
  color: var(--color-text-light, #999);
  font-style: italic;
}

/* Notification - Estilos mejorados */
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  min-width: 320px;
  max-width: 400px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(34, 32, 31, 0.4);
  z-index: 1000;
  overflow: hidden;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(175, 173, 171, 0.2);
}

.notification-success {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  color: var(--color-text);
  border-left: 4px solid #10b981;
}

.notification-error {
  background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-focus) 100%);
  color: var(--color-text);
  border-left: 4px solid #ef4444;
}

.notification-info {
  background: linear-gradient(135deg, var(--color-focus) 0%, var(--color-darkest) 100%);
  color: var(--color-text);
  border-left: 4px solid #3b82f6;
}

.notification-content {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  position: relative;
}

.notification-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  padding: 8px;
  border-radius: 50%;
  background: rgba(175, 173, 171, 0.2);
  flex-shrink: 0;
}

.notification-success .notification-icon {
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
}

.notification-error .notification-icon {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.notification-info .notification-icon {
  background: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
}

.notification-message {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.4;
  color: var(--color-text);
}

.notification-close {
  background: none;
  border: none;
  color: var(--color-text);
  cursor: pointer;
  padding: 4px;
  margin-left: 12px;
  border-radius: 4px;
  transition: all 0.2s ease;
  opacity: 0.7;
  flex-shrink: 0;
}

.notification-close:hover {
  opacity: 1;
  background: var(--color-focus);
  transform: scale(1.1);
}

.notification-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: rgba(175, 173, 171, 0.3);
  animation: progress 3s linear forwards;
}

.notification-success .notification-progress {
  background: rgba(16, 185, 129, 0.4);
}

.notification-error .notification-progress {
  background: rgba(239, 68, 68, 0.4);
}

.notification-info .notification-progress {
  background: rgba(59, 130, 246, 0.4);
}
</style>
