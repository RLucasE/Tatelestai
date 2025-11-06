<template>
  <div class="pending-offers-container">
    <div class="admin-header">
      <h1 class="page-title">Verificación de Ofertas</h1>
      <p class="page-subtitle">Ofertas pendientes de aprobación</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando ofertas pendientes...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="fetchPendingOffers" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="offers.length === 0" class="empty-container">
      <div class="empty-icon">✓</div>
      <h2>No hay ofertas pendientes</h2>
      <p>Todas las ofertas han sido verificadas</p>
    </div>

    <!-- Offers List -->
    <div v-else class="offers-list">
      <div
        v-for="offer in offers"
        :key="offer.id"
        class="offer-card"
      >
        <div class="offer-header">
          <div class="offer-title-section">
            <h3 class="offer-title">{{ offer.title }}</h3>
            <span class="offer-date">{{ formatDate(offer.created_at) }}</span>
          </div>
          <span class="offer-status-badge">Pendiente</span>
        </div>

        <div class="offer-body">
          <div class="offer-info-section">
            <div class="info-group">
              <label>Descripción:</label>
              <p>{{ offer.description }}</p>
            </div>

            <div class="info-row">
              <div class="info-group">
                <label>Cantidad disponible:</label>
                <p>{{ offer.quantity }}</p>
              </div>
              <div class="info-group">
                <label>Fecha de expiración:</label>
                <p>{{ formatDateTime(offer.expiration_datetime) }}</p>
              </div>
            </div>

            <div class="info-group">
              <label>Establecimiento:</label>
              <p><strong>{{ offer.establishment.name }}</strong> ({{ offer.establishment.type }})</p>
              <p class="text-muted">{{ offer.establishment.address }}</p>
            </div>

            <div class="info-group">
              <label>Vendedor:</label>
              <p><strong>{{ offer.seller.name }}</strong></p>
              <p class="text-muted">{{ offer.seller.email }}</p>
            </div>

            <div class="info-group">
              <label>Productos incluidos:</label>
              <div class="products-list">
                <div
                  v-for="product in offer.products"
                  :key="product.id"
                  class="product-item"
                >
                  <div class="product-info">
                    <strong>{{ product.name }}</strong>
                    <p class="product-description">{{ product.description }}</p>
                  </div>
                  <div class="product-details">
                    <span class="product-price">${{ product.price }}</span>
                    <span class="product-quantity">x{{ product.quantity }}</span>
                    <span class="product-expiration" v-if="product.expiration_date">
                      Vence: {{ formatDate(product.expiration_date) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="offer-actions">
            <button
              @click="approveOffer(offer.id)"
              class="btn btn-approve"
              :disabled="processingOfferId === offer.id"
            >
              <span v-if="processingOfferId === offer.id && actionType === 'approve'">
                Procesando...
              </span>
              <span v-else>✓ Aprobar</span>
            </button>
            <button
              @click="rejectOffer(offer.id)"
              class="btn btn-reject"
              :disabled="processingOfferId === offer.id"
            >
              <span v-if="processingOfferId === offer.id && actionType === 'reject'">
                Procesando...
              </span>
              <span v-else>✗ Rechazar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axiosInstance from '@/lib/axios';

const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const processingOfferId = ref(null);
const actionType = ref(null);

const fetchPendingOffers = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axiosInstance.get('/adm-pending-offers');
    offers.value = response.data.data || [];
  } catch (err) {
    console.error('Error fetching pending offers:', err);
    error.value = 'Error al cargar las ofertas pendientes';
    offers.value = [];
  } finally {
    loading.value = false;
  }
};

const approveOffer = async (offerId) => {
  if (!confirm('¿Estás seguro de que quieres aprobar esta oferta?')) {
    return;
  }

  try {
    processingOfferId.value = offerId;
    actionType.value = 'approve';

    await axiosInstance.patch(`/adm-offers/${offerId}/approve`);

    // Remover la oferta de la lista
    offers.value = offers.value.filter(offer => offer.id !== offerId);

    alert('Oferta aprobada exitosamente');
  } catch (err) {
    console.error('Error approving offer:', err);
    alert(err.response?.data?.message || 'Error al aprobar la oferta');
  } finally {
    processingOfferId.value = null;
    actionType.value = null;
  }
};

const rejectOffer = async (offerId) => {
  if (!confirm('¿Estás seguro de que quieres rechazar esta oferta?')) {
    return;
  }

  try {
    processingOfferId.value = offerId;
    actionType.value = 'reject';

    await axiosInstance.patch(`/adm-offers/${offerId}/reject`);

    // Remover la oferta de la lista
    offers.value = offers.value.filter(offer => offer.id !== offerId);

    alert('Oferta rechazada exitosamente');
  } catch (err) {
    console.error('Error rejecting offer:', err);
    alert(err.response?.data?.message || 'Error al rechazar la oferta');
  } finally {
    processingOfferId.value = null;
    actionType.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchPendingOffers();
});
</script>

<style scoped>
.pending-offers-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.admin-header {
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 1rem;
}

.loading-container,
.error-container,
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-container {
  color: var(--color-danger);
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.retry-btn:hover {
  background-color: var(--color-focus);
}

.empty-container {
  color: var(--color-text);
  opacity: 0.7;
}

.empty-icon {
  font-size: 4rem;
  color: var(--color-primary);
  margin-bottom: 1rem;
}

.empty-container h2 {
  font-size: 1.5rem;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.offers-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.offer-card {
  background: var(--color-darkest);
  border-radius: var(--border-radius);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  transition: box-shadow 0.2s;
  border: 1px solid var(--color-border);
}

.offer-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
}

.offer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background-color: color-mix(in oklab, var(--color-darkest), var(--color-secondary) 15%);
  border-bottom: 1px solid var(--color-border);
}

.offer-title-section {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.offer-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.offer-date {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.6;
}

.offer-status-badge {
  padding: 0.375rem 0.75rem;
  background-color: var(--color-warning);
  color: var(--color-warning-text);
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.offer-body {
  padding: 1.5rem;
}

.offer-info-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.info-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-primary);
}

.info-group p {
  color: var(--color-text);
  margin: 0;
}

.text-muted {
  color: var(--color-text) !important;
  opacity: 0.6;
  font-size: 0.875rem;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: start;
  padding: 1rem;
  background-color: color-mix(in oklab, var(--color-darkest), var(--color-bg) 10%);
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
}

.product-info {
  flex: 1;
}

.product-info strong {
  display: block;
  color: var(--color-text);
  margin-bottom: 0.25rem;
}

.product-description {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 0.875rem;
  margin: 0;
}

.product-details {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-shrink: 0;
}

.product-price {
  font-weight: 600;
  color: var(--color-success);
  font-size: 1.125rem;
}

.product-quantity {
  color: var(--color-text);
  font-weight: 500;
  opacity: 0.8;
}

.product-expiration {
  color: var(--color-text);
  opacity: 0.6;
  font-size: 0.875rem;
}

.offer-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-approve {
  background-color: var(--color-success);
  color: white;
}

.btn-approve:hover:not(:disabled) {
  background-color: var(--color-success-hover);
}

.btn-reject {
  background-color: var(--color-danger);
  color: white;
}

.btn-reject:hover:not(:disabled) {
  background-color: var(--color-danger-hover);
}

@media (max-width: 768px) {
  .pending-offers-container {
    padding: 1rem;
  }

  .offer-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .product-item {
    flex-direction: column;
    gap: 0.75rem;
  }

  .product-details {
    width: 100%;
    justify-content: space-between;
  }

  .offer-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
