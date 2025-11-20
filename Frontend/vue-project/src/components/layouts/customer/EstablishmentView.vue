<template>
  <div class="establishment-view">
    <!-- Header del establecimiento -->
    <div class="establishment-header" v-if="establishment">
      <div class="establishment-title-row">
        <div class="establishment-title-main">
          <div class="title-with-badge">
            <h1 class="establishment-name">{{ establishment.name }}</h1>
            <span v-if="establishment.establishment_type" class="establishment-type">
              {{ establishment.establishment_type }}
            </span>
          </div>

          <div class="establishment-stats" v-if="offers.length > 0">
            <span class="stat-value">{{ offers.length }}</span>
            <span class="stat-label">
              {{ offers.length === 1 ? 'oferta activa' : 'ofertas activas' }}
            </span>
          </div>
        </div>

        <div class="header-actions">
          <button
            class="report-button-header"
            @click="openReportModal"
            title="Reportar establecimiento"
            type="button"
          >
            <svg
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
              <line x1="12" y1="9" x2="12" y2="13" />
              <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
          </button>

          <button class="back-button" @click="goBack">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
            >
              <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            <span>Volver</span>
          </button>
        </div>
      </div>

      <div class="establishment-info-section">
        <div class="establishment-details">
          <p class="establishment-detail" v-if="establishment.address">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
            >
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
            <span>{{ establishment.address }}</span>
          </p>

          <p class="establishment-detail" v-if="establishment.phone_number">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
            >
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
            <span>{{ establishment.phone_number }}</span>
          </p>

          <p class="establishment-detail" v-if="establishment.email">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
            >
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
              <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>{{ establishment.email }}</span>
          </p>
        </div>

        <p class="establishment-description" v-if="establishment.description">
          {{ establishment.description }}
        </p>
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando ofertas...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-container">
      <svg
        width="48"
        height="48"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
        fill="none"
      >
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <p>{{ error }}</p>
      <button @click="fetchOffers" class="retry-button">Reintentar</button>
    </div>

    <!-- Lista de ofertas -->
    <div v-else-if="offers.length > 0" class="offers-section">
      <h2 class="section-title">Ofertas Activas</h2>
      <div class="offers-grid">
        <CustomerCard
          v-for="offer in offers"
          :key="offer.id"
          :offer="offer"
          @click="openOfferModal(offer)"
        />
      </div>
    </div>

    <!-- Sin ofertas -->
    <div v-else class="empty-state">
      <svg
        width="64"
        height="64"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
        fill="none"
      >
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <h3>No hay ofertas activas</h3>
      <p>Este establecimiento no tiene ofertas disponibles en este momento.</p>
    </div>

    <!-- Modal de oferta -->
    <OfferModal
      :offer="selectedOffer"
      :is-visible="showOfferModal"
      @close="closeOfferModal"
      @offerAction="handleOfferAction"
      @buyOffer="handleBuyOffer"
    />

    <!-- Modal de reporte -->
    <ReportModal
      :is-visible="showReportModal"
      reportable-type="establishment"
      :reportable-id="establishment?.id"
      @close="closeReportModal"
      @success="handleReportSuccess"
    />

    <!-- Sistema de notificaciones -->
    <div v-if="notification.show" class="notification-container" :class="notification.type">
      <div class="notification-content">
        <svg
          v-if="notification.type === 'success'"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <svg
          v-else-if="notification.type === 'error'"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <svg
          v-else
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>{{ notification.message }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import OfferModal from '@/components/common/OfferModal.vue';
import CustomerCard from './CustomerCard.vue';
import ReportModal from '@/components/common/ReportModal.vue';

const route = useRoute();
const router = useRouter();

const establishment = ref(null);
const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref(null);
const showOfferModal = ref(false);
const showReportModal = ref(false);

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

const establishmentId = computed(() => route.params.id);

const fetchOffers = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Obtener todas las ofertas activas
    const response = await axiosInstance.get('/offers');

    if (response.data.data) {
      // Filtrar ofertas por el establecimiento específico
      const allOffers = response.data.data;
      const establishmentOffers = allOffers.filter(
        offer => offer.food_establishment_id === parseInt(establishmentId.value)
      );

      if (establishmentOffers.length > 0) {
        // Usar los datos del establecimiento de la primera oferta
        establishment.value = {
          id: establishmentOffers[0].food_establishment_id,
          name: establishmentOffers[0].establishment_name,
          address: establishmentOffers[0].establishment_address,
          // Agregar más datos si están disponibles en el response
          establishment_type: establishmentOffers[0].establishment_type,
          phone_number: establishmentOffers[0].establishment_phone,
          email: establishmentOffers[0].establishment_email,
          description: establishmentOffers[0].establishment_description
        };
        offers.value = establishmentOffers;
      } else {
        // Si no hay ofertas, intentar obtener info del establecimiento de alguna manera
        establishment.value = {
          id: parseInt(establishmentId.value),
          name: 'Establecimiento'
        };
        offers.value = [];
      }
    }
  } catch (err) {
    console.error('Error al cargar ofertas:', err);
    error.value = 'No se pudieron cargar las ofertas del establecimiento.';
  } finally {
    loading.value = false;
  }
};

const openOfferModal = (offer) => {
  selectedOffer.value = offer;
  showOfferModal.value = true;
};

const closeOfferModal = () => {
  showOfferModal.value = false;
  selectedOffer.value = null;
};

const handleOfferAction = async ({ id, quantity }) => {
  const offerPayload = {
    offer_id: id,
    quantity: quantity || 1,
  };

  try {
    await axiosInstance.post("/add-to-cart", offerPayload);
    showNotification('Oferta agregada al carrito', 'success');
    closeOfferModal();
  } catch (error) {
    console.log(error);
    if (error.status === 400) {
      if (error.data === "OfferQuantityExceded") {
        showNotification('Ya no se pueden agregar más unidades de esta oferta', 'error');
      }
    } else {
      showNotification('Error al agregar al carrito', 'error');
    }
  }
};

const handleBuyOffer = async ({ id, quantity, food_establishment_id }) => {
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
    // Mostrar que está preparando la compra
    showNotification('Preparando tu compra...', 'info');

    // Primero preparar la compra
    const prepareResponse = await axiosInstance.post("/prepare-purchase", offerPayload);

    // Guardar los datos de confirmación en sessionStorage
    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(prepareResponse.data.data));

    // Cerrar el modal
    closeOfferModal();

    // Redirigir a la página de confirmación con el token
    router.push({
      name: 'purchase-confirmation',
      params: {
        token: prepareResponse.data.data.purchase_token
      }
    });

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

const goBack = () => {
  router.back();
};

const openReportModal = () => {
  showReportModal.value = true;
};

const closeReportModal = () => {
  showReportModal.value = false;
};

const handleReportSuccess = () => {
  showNotification('Reporte enviado exitosamente', 'success');
  closeReportModal();
};

onMounted(() => {
  fetchOffers();
});
</script>

<style scoped>
.establishment-view {
  min-height: 100vh;
  padding: 32px 48px;
  color: var(--color-text);
}

/* Header */
.establishment-header {
  margin-bottom: 48px;
  padding-bottom: 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.establishment-title-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 24px;
}

.establishment-title-main {
  flex: 1;
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.establishment-name {
  margin: 0;
  font-size: 2.8rem;
  font-weight: 700;
  letter-spacing: -0.05em;
  line-height: 1.1;
}

.establishment-type {
  display: inline-block;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  opacity: 0.8;
}

.establishment-stats {
  display: inline-flex;
  align-items: baseline;
  gap: 8px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-focus);
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.report-button-header {
  background: transparent;
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #fbbf24;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.report-button-header:hover {
  background: rgba(245, 158, 11, 0.08);
  border-color: rgba(245, 158, 11, 0.6);
  transform: translateY(-1px);
}

.report-button-header:active {
  transform: translateY(0);
}

.back-button {
  background: transparent;
  color: var(--color-text);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s ease;
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.2);
}

.establishment-info-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.establishment-details {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 24px;
}

.establishment-detail {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 0.95rem;
  opacity: 0.75;
}

.establishment-detail svg {
  flex-shrink: 0;
  opacity: 0.6;
}

.establishment-description {
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.7;
  opacity: 0.8;
  max-width: 800px;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 96px 24px;
  gap: 20px;
}

.spinner {
  width: 64px;
  height: 64px;
  border: 5px solid var(--color-focus);
  border-top-color: var(--color-text);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-container p {
  font-size: 1.1em;
  font-weight: 600;
  opacity: 0.8;
}

/* Error */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 96px 24px;
  gap: 20px;
  text-align: center;
}

.error-container svg {
  color: #ef4444;
}

.error-container p {
  font-size: 1.1em;
  max-width: 500px;
}

.retry-button {
  background: var(--color-focus);
  color: var(--color-text);
  border: none;
  padding: 14px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1.05em;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.retry-button:hover {
  background: var(--color-darkest);
  transform: translateY(-3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 96px 24px;
  gap: 20px;
  text-align: center;
}

.empty-state svg {
  color: var(--color-focus);
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0;
  font-size: 1.8em;
  font-weight: 700;
  color: var(--color-text);
}

.empty-state p {
  margin: 0;
  font-size: 1.1em;
  color: var(--color-text);
  opacity: 0.7;
  max-width: 500px;
}

/* Offers Section */
.offers-section {
  margin-top: 48px;
}

.section-title {
  margin: 0 0 28px 0;
  font-size: 2em;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.5px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--color-focus);
  position: relative;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 80px;
  height: 2px;
  background: var(--color-text);
  opacity: 0.6;
}

.offers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 20px;
}

/* Para pantallas extra grandes (4K y superiores) */
@media (min-width: 2560px) {
  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 24px;
  }

  .establishment-view {
    padding: 48px 100px;
  }
}

/* Para pantallas extra grandes */
@media (min-width: 1920px) and (max-width: 2559px) {
  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 22px;
  }

  .establishment-view {
    padding: 48px 80px;
  }

  .establishment-header {
    padding: 64px;
  }

  .section-title {
    font-size: 2.5em;
  }
}

/* Para pantallas medianas-grandes */
@media (min-width: 1440px) and (max-width: 1919px) {
  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 20px;
  }

  .establishment-view {
    padding: 40px 64px;
  }
}

/* Para laptops grandes */
@media (min-width: 1280px) and (max-width: 1439px) {
  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .establishment-view {
    padding: 24px 32px;
  }

  .establishment-name {
    font-size: 2.2rem;
  }

  .section-title {
    font-size: 1.8em;
  }

  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
  }
}

@media (max-width: 768px) {
  .establishment-view {
    padding: 16px 20px;
  }

  .establishment-title-row {
    flex-direction: column;
    gap: 16px;
  }

  .establishment-name {
    font-size: 2rem;
  }

  .stat-value {
    font-size: 1.6rem;
  }

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .back-button {
    flex: 1;
    justify-content: center;
  }

  .section-title {
    font-size: 1.5em;
  }

  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }
}

@media (max-width: 600px) {
  .offers-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

@media (max-width: 480px) {
  .establishment-view {
    padding: 12px 16px;
  }

  .establishment-name {
    font-size: 1.6rem;
  }

  .title-with-badge {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .section-title {
    font-size: 1.3em;
  }

  .establishment-stats {
    margin-top: 8px;
  }
}

/* Sistema de notificaciones */
.notification-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  padding: 16px 24px;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  animation: slideIn 0.3s ease-out;
  max-width: 400px;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification-container.success {
  background: #10b981;
  color: white;
}

.notification-container.error {
  background: #ef4444;
  color: white;
}

.notification-container.info {
  background: #3b82f6;
  color: white;
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
}

.notification-content svg {
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .notification-container {
    top: 12px;
    right: 12px;
    left: 12px;
    max-width: none;
  }
}
</style>
