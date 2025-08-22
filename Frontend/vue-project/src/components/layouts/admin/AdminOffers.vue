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

    <!-- Offers Table -->
    <div v-else class="offers-table-container">
      <div class="table-header">
        <div class="header-cell title-col">Título</div>
        <div class="header-cell establishment-col">Establecimiento</div>
        <div class="header-cell quantity-col">Cantidad</div>
        <div class="header-cell expiration-col">Expiración</div>
        <div class="header-cell status-col">Estado</div>
        <div class="header-cell actions-col">Acciones</div>
      </div>

      <div class="offers-list">
        <div
          v-for="offer in offers"
          :key="offer.id"
          class="offer-row"
          @click="handleOfferClick(offer)"
        >
          <div class="row-cell title-col">
            <div class="offer-title">{{ offer.title }}</div>
            <div class="offer-description">{{ truncateText(offer.description, 60) }}</div>
          </div>

          <div class="row-cell establishment-col">
            <div class="establishment-name">
              {{ offer.establishment?.name || 'No especificado' }}
            </div>
            <div class="establishment-type">
              {{ offer.establishment?.type || '' }}
            </div>
          </div>

          <div class="row-cell quantity-col">
            <div class="quantity-value">{{ offer.quantity }}</div>
            <div class="products-count">{{ offer.products?.length || 0 }} productos</div>
          </div>

          <div class="row-cell expiration-col">
            <div class="expiration-date">{{ formatDate(offer.expiration_datetime) }}</div>
            <div class="expiration-time">{{ formatTime(offer.expiration_datetime) }}</div>
          </div>

          <div class="row-cell status-col">
            <span :class="['status-badge', getStatusClass(offer.state)]">
              {{ getStatusText(offer.state) }}
            </span>
          </div>

          <div class="row-cell actions-col">
            <button
              @click.stop="toggleOfferStatus(offer)"
              :class="['action-btn', offer.state === 'active' ? 'deactivate-btn' : 'activate-btn']"
              :title="offer.state === 'active' ? 'Desactivar oferta' : 'Activar oferta'"
            >
              {{ offer.state === 'active' ? 'Desactivar' : 'Activar' }}
            </button>
            <button
              @click.stop="handleOfferClick(offer)"
              class="action-btn view-btn"
              title="Ver detalles"
            >
              Ver
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Detail Modal -->
    <div v-if="isModalVisible" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ selectedOffer.title }}</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <div class="modal-section">
            <h3>Información General</h3>
            <p><strong>Descripción:</strong> {{ selectedOffer.description }}</p>
            <p><strong>Estado:</strong> {{ getStatusText(selectedOffer.state) }}</p>
            <p><strong>Cantidad disponible:</strong> {{ selectedOffer.quantity }}</p>
            <p><strong>Establecimiento:</strong> {{ selectedOffer.establishment?.name || 'No especificado' }}</p>
            <p><strong>Tipo de establecimiento:</strong> {{ selectedOffer.establishment?.type || 'No especificado' }}</p>
          </div>

          <div class="modal-section">
            <h3>Fechas</h3>
            <p><strong>Creada:</strong> {{ formatDate(selectedOffer.created_at) }}</p>
            <p><strong>Expira:</strong> {{ formatDate(selectedOffer.expiration_datetime) }} a las {{ formatTime(selectedOffer.expiration_datetime) }}</p>
          </div>

          <div v-if="selectedOffer.products?.length" class="modal-section">
            <h3>Productos incluidos ({{ selectedOffer.products.length }})</h3>
            <div class="products-grid">
              <div v-for="product in selectedOffer.products" :key="product.id" class="product-item">
                <div class="product-info">
                  <span class="product-name">{{ product.name }}</span>
                  <span class="product-description">{{ product.description }}</span>
                </div>
                <div class="product-price">${{ product.pivot?.price || product.price }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axiosInstance from "@/lib/axios";
import { ref, onMounted } from "vue";

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

const formatDate = (datetime) => {
  if (!datetime) return 'No disponible';
  return new Date(datetime).toLocaleDateString('es-ES');
};

const formatTime = (datetime) => {
  if (!datetime) return 'No disponible';
  return new Date(datetime).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const truncateText = (text, maxLength) => {
  if (!text) return '';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getStatusClass = (state) => {
  switch (state) {
    case 'active':
      return 'status-active';
    case 'inactive':
      return 'status-inactive';
    case 'expired':
      return 'status-expired';
    default:
      return 'status-unknown';
  }
};

const getStatusText = (state) => {
  switch (state) {
    case 'active':
      return 'Activa';
    case 'inactive':
      return 'Inactiva';
    case 'expired':
      return 'Expirada';
    default:
      return 'Desconocido';
  }
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

/* Table Layout */
.offers-table-container {
  background: var(--color-primary);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(34, 32, 31, 0.3);
}

.table-header {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 1.2fr 1fr 1.5fr;
  background: var(--color-secondary);
  border-bottom: 2px solid var(--color-focus);
}

.header-cell {
  padding: 15px 12px;
  font-weight: 600;
  color: var(--color-text);
  font-size: 0.9em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-right: 1px solid var(--color-focus);
}

.header-cell:last-child {
  border-right: none;
}

.offers-list {
  max-height: 70vh;
  overflow-y: auto;
}

.offer-row {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 1.2fr 1fr 1.5fr;
  border-bottom: 1px solid var(--color-focus);
  transition: all 0.3s ease;
  cursor: pointer;
}

.offer-row:hover {
  background: var(--color-secondary);
}

.offer-row:last-child {
  border-bottom: none;
}

.row-cell {
  padding: 15px 12px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-right: 1px solid var(--color-focus);
  color: var(--color-text);
}

.row-cell:last-child {
  border-right: none;
}

/* Title Column */
.title-col .offer-title {
  font-weight: 600;
  font-size: 1em;
  margin-bottom: 4px;
  color: var(--color-text);
}

.title-col .offer-description {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

/* Establishment Column */
.establishment-col .establishment-name {
  font-weight: 500;
  margin-bottom: 2px;
  color: var(--color-text);
}

.establishment-col .establishment-type {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

/* Quantity Column */
.quantity-col .quantity-value {
  font-weight: 600;
  font-size: 1.1em;
  margin-bottom: 2px;
  color: var(--color-text);
}

.quantity-col .products-count {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

/* Expiration Column */
.expiration-col .expiration-date {
  font-weight: 500;
  margin-bottom: 2px;
  color: var(--color-text);
}

.expiration-col .expiration-time {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

/* Status Column */
.status-col {
  align-items: center;
}

.status-badge {
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 0.8em;
  font-weight: 600;
  text-transform: uppercase;
  text-align: center;
}

.status-active {
  background-color: #4CAF50;
  color: white;
}

.status-inactive {
  background-color: #FF9800;
  color: white;
}

.status-expired {
  background-color: #F44336;
  color: white;
}

.status-unknown {
  background-color: var(--color-focus);
  color: var(--color-text);
}

/* Actions Column */
.actions-col {
  flex-direction: row;
  gap: 8px;
  align-items: center;
}

.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.8em;
  transition: all 0.3s;
  white-space: nowrap;
  color: var(--color-text);
}

.activate-btn {
  background-color: var(--color-secondary);
  border: 1px solid var(--color-focus);
}

.activate-btn:hover {
  background-color: var(--color-focus);
  transform: scale(1.05);
}

.deactivate-btn {
  background-color: var(--color-focus);
  border: 1px solid var(--color-darkest);
}

.deactivate-btn:hover {
  background-color: var(--color-darkest);
  transform: scale(1.05);
}

.view-btn {
  background-color: var(--color-primary);
  border: 1px solid var(--color-secondary);
}

.view-btn:hover {
  background-color: var(--color-secondary);
  transform: scale(1.05);
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: var(--color-primary);
  border-radius: 12px;
  max-width: 700px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  color: var(--color-text);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-secondary);
}

.modal-header h2 {
  margin: 0;
  color: var(--color-text);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
  color: var(--color-text);
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: var(--color-focus);
  border-radius: 50%;
}

.modal-body {
  padding: 20px;
}

.modal-section {
  margin-bottom: 25px;
}

.modal-section:last-child {
  margin-bottom: 0;
}

.modal-section h3 {
  color: var(--color-text);
  margin: 0 0 15px 0;
  font-size: 1.2em;
  border-bottom: 1px solid var(--color-focus);
  padding-bottom: 8px;
}

.modal-section p {
  margin: 8px 0;
  color: var(--color-text);
  line-height: 1.5;
}

.products-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-secondary);
  border-radius: 8px;
}

.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-name {
  color: var(--color-text);
  font-weight: 500;
  margin-bottom: 4px;
}

.product-description {
  color: var(--color-text);
  font-size: 0.9em;
  opacity: 0.8;
}

.product-price {
  color: var(--color-text);
  font-weight: 600;
  background: var(--color-focus);
  padding: 6px 12px;
  border-radius: 4px;
  margin-left: 15px;
}

/* Loading, Error, and Empty States */
.loading-container, .error-container, .empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
  color: var(--color-text);
  background-color: var(--color-primary);
  border-radius: 12px;
  margin: 20px 0;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-focus);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

.retry-btn {
  margin-top: 15px;
  padding: 10px 20px;
  background-color: var(--color-secondary);
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

/* Responsive Design */
@media (max-width: 1200px) {
  .table-header, .offer-row {
    grid-template-columns: 2fr 1.3fr 0.8fr 1fr 0.8fr 1.2fr;
  }
}

@media (max-width: 768px) {
  .admin-offers-container {
    padding: 15px;
  }

  .page-title {
    font-size: 2em;
  }

  .page-subtitle {
    font-size: 1em;
  }

  .table-header, .offer-row {
    grid-template-columns: 1fr;
    gap: 5px;
  }

  .offers-table-container {
    overflow-x: auto;
  }

  .table-header {
    display: none;
  }

  .offer-row {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: var(--color-primary);
    margin-bottom: 10px;
    border-radius: 8px;
  }

  .row-cell {
    border-right: none;
    padding: 5px 0;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }

  .actions-col {
    justify-content: center;
    margin-top: 10px;
  }
}
</style>
