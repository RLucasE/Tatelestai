<template>
  <div class="purchase-confirmation">
    <div class="confirmation-header">
      <h2>Confirmar Compra</h2>
      <p class="confirmation-subtitle">Revisa los detalles de tu pedido antes de confirmar</p>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando datos de confirmación...</p>
    </div>

    <div v-else-if="purchaseData" class="confirmation-content">
      <div class="establishment-info">
        <h3>Establecimiento: {{ establishmentName }}</h3>
        <p class="expiration-notice">
          ⏰ Esta confirmación expira el {{ formatExpirationDate(purchaseData.expires_at) }}
        </p>
      </div>

      <div class="offers-summary">
        <h4>Resumen de tu pedido</h4>
        <div class="offers-list">
          <div
            v-for="offer in purchaseData.offers.offers"
            :key="offer.id"
            class="offer-item"
          >
            <div class="offer-header">
              <h5>{{ offer.title }}</h5>
              <span class="offer-quantity">Cantidad: {{ offer.quantity }}</span>
            </div>
            <p class="offer-description">{{ offer.description }}</p>

            <div class="products-grid">
              <div
                v-for="product in offer.products"
                :key="product.name"
                class="product-item"
              >
                <div class="product-info">
                  <span class="product-name">{{ product.name }}</span>
                  <span class="product-quantity">x{{ product.quantity }}</span>
                  <span class="product-price">${{ product.price }}</span>
                </div>
                <p v-if="product.description" class="product-description">
                  {{ product.description }}
                </p>
              </div>
            </div>

            <div class="offer-total">
              <span class="total-label">Subtotal:</span>
              <span class="total-amount">${{ calculateOfferTotal(offer) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="total-section">
        <div class="grand-total">
          <span class="total-label">Total a pagar:</span>
          <span class="total-amount">${{ calculateGrandTotal() }}</span>
        </div>
      </div>

      <div class="action-buttons">
        <button
          class="btn btn-secondary"
          @click="goBack"
          :disabled="processingPurchase"
        >
          Volver al carrito
        </button>

        <button
          class="btn btn-primary purchase-btn"
          @click="startPurchase"
          :disabled="processingPurchase"
          :class="{ 'loading': processingPurchase }"
        >
          <span v-if="!processingPurchase">Confirmar Compra</span>
          <div v-if="processingPurchase" class="purchase-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
            </div>
            <span class="progress-text">{{ progressText }}</span>
          </div>
        </button>
      </div>
    </div>

    <div v-else class="error-container">
      <div class="error-message">
        <h3>Error al cargar la confirmación</h3>
        <p>{{ errorMessage || 'No se pudieron cargar los datos de confirmación.' }}</p>
        <button class="btn btn-primary" @click="goBack">
          Volver al carrito
        </button>
      </div>
    </div>

    <!-- Notificación de éxito -->
    <div v-if="showSuccessNotification" class="notification success">
      <div class="notification-content">
        <i class="fas fa-check-circle"></i>
        <div class="notification-text">
          <h4>¡Compra realizada con éxito!</h4>
          <p>Tu pedido ha sido procesado correctamente.</p>
        </div>
      </div>
    </div>

    <!-- Notificación de error -->
    <div v-if="showErrorNotification" class="notification error">
      <div class="notification-content">
        <i class="fas fa-exclamation-circle"></i>
        <div class="notification-text">
          <h4>Error en la compra</h4>
          <p>{{ purchaseErrorMessage }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const purchaseData = ref(null);
const establishmentName = ref('');
const errorMessage = ref('');
const processingPurchase = ref(false);
const progressPercentage = ref(0);
const progressText = ref('');
const showSuccessNotification = ref(false);
const showErrorNotification = ref(false);
const purchaseErrorMessage = ref('');

const purchaseToken = route.params.token;

onMounted(async () => {
  if (!purchaseToken) {
    errorMessage.value = 'Token de compra no válido';
    loading.value = false;
    return;
  }

  // Los datos de confirmación ya vienen del preparePurchase
  const sessionData = sessionStorage.getItem('purchaseConfirmation');
  if (sessionData) {
    try {
      purchaseData.value = JSON.parse(sessionData);
      establishmentName.value = `Establecimiento ${purchaseData.value.food_establishment_id}`;
      loading.value = false;
    } catch (error) {
      errorMessage.value = 'Error al procesar los datos de confirmación';
      loading.value = false;
    }
  } else {
    errorMessage.value = 'No se encontraron datos de confirmación';
    loading.value = false;
  }
});

const calculateOfferTotal = (offer) => {
  const total = offer.products.reduce((sum, product) => {
    return sum + (product.price * product.quantity);
  }, 0);
  return (total * offer.quantity).toFixed(2);
};

const calculateGrandTotal = () => {
  if (!purchaseData.value?.offers?.offers) return '0.00';

  return purchaseData.value.offers.offers.reduce((total, offer) => {
    const offerTotal = offer.products.reduce((sum, product) => {
      return sum + (product.price * product.quantity);
    }, 0);
    return total + (offerTotal * offer.quantity);
  }, 0).toFixed(2);
};

const formatExpirationDate = (dateString) => {
  return new Date(dateString).toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const startPurchase = async () => {
  processingPurchase.value = true;
  progressPercentage.value = 0;
  progressText.value = 'Iniciando compra...';

  // Simular progreso de carga
  const progressInterval = setInterval(() => {
    if (progressPercentage.value < 90) {
      progressPercentage.value += Math.random() * 15;
      if (progressPercentage.value < 30) {
        progressText.value = 'Validando ofertas...';
      } else if (progressPercentage.value < 60) {
        progressText.value = 'Procesando pago...';
      } else if (progressPercentage.value < 90) {
        progressText.value = 'Finalizando compra...';
      }
    }
  }, 200);

  try {
    const response = await axiosInstance.post('/buy-offers', {
      purchase_token: purchaseToken
    });

    // Completar la barra de progreso
    progressPercentage.value = 100;
    progressText.value = 'Compra completada';

    clearInterval(progressInterval);

    setTimeout(() => {
      processingPurchase.value = false;
      showSuccessNotification.value = true;

      // Limpiar datos de sesión
      sessionStorage.removeItem('purchaseConfirmation');

      // Ocultar notificación después de 3 segundos y redirigir
      setTimeout(() => {
        showSuccessNotification.value = false;
        router.push({ name: 'customer-purchases' });
      }, 3000);
    }, 500);

  } catch (error) {
    clearInterval(progressInterval);
    processingPurchase.value = false;
    showErrorNotification.value = true;
    purchaseErrorMessage.value = error.response?.data?.error || 'Error desconocido al procesar la compra';

    setTimeout(() => {
      showErrorNotification.value = false;
    }, 5000);
  }
};

const goBack = () => {
  router.push({ name: 'cart' });
};
</script>

<style scoped>
.purchase-confirmation {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--color-bg);
  min-height: 100vh;
}

.confirmation-header {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--color-text);
}

.confirmation-header h2 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  color: var(--color-text);
}

.confirmation-subtitle {
  font-size: 1.2rem;
  color: var(--color-text);
  opacity: 0.8;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: var(--color-text);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-primary);
  border-top: 4px solid var(--color-text);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.confirmation-content {
  background: var(--color-primary);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.establishment-info {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--color-secondary);
}

.establishment-info h3 {
  color: var(--color-text);
  font-size: 1.8rem;
  margin-bottom: 0.5rem;
}

.expiration-notice {
  color: var(--color-text);
  background: var(--color-secondary);
  padding: 0.8rem 1rem;
  border-radius: 8px;
  font-weight: 500;
}

.offers-summary h4 {
  color: var(--color-text);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.offer-item {
  background: var(--color-secondary);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.offer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.offer-header h5 {
  color: var(--color-text);
  font-size: 1.3rem;
  margin: 0;
}

.offer-quantity {
  background: var(--color-focus);
  color: var(--color-text);
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-weight: 600;
}

.offer-description {
  color: var(--color-text);
  margin-bottom: 1rem;
  opacity: 0.9;
}

.products-grid {
  margin-bottom: 1rem;
}

.product-item {
  background: var(--color-focus);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.8rem;
}

.product-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.product-name {
  color: var(--color-text);
  font-weight: 600;
}

.product-quantity {
  color: var(--color-text);
  opacity: 0.8;
}

.product-price {
  color: var(--color-text);
  font-weight: 700;
  font-size: 1.1rem;
}

.product-description {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 0.9rem;
  margin: 0;
}

.offer-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid var(--color-focus);
}

.total-label {
  color: var(--color-text);
  font-weight: 600;
}

.total-amount {
  color: var(--color-text);
  font-weight: 700;
  font-size: 1.2rem;
}

.total-section {
  margin: 2rem 0;
  padding: 1.5rem;
  background: var(--color-secondary);
  border-radius: 12px;
  border: 2px solid var(--color-focus);
}

.grand-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.grand-total .total-label {
  font-size: 1.4rem;
  font-weight: 700;
}

.grand-total .total-amount {
  font-size: 2rem;
  font-weight: 700;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 150px;
}

.btn-secondary {
  background: var(--color-secondary);
  color: var(--color-text);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-focus);
  transform: translateY(-2px);
}

.btn-primary {
  background: var(--color-focus);
  color: var(--color-text);
  position: relative;
  overflow: hidden;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-darkest);
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.purchase-btn.loading {
  padding: 0.5rem 2rem;
}

.purchase-progress {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: var(--color-secondary);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--color-text);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.9rem;
  color: var(--color-text);
}

.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.error-message {
  text-align: center;
  background: var(--color-primary);
  padding: 2rem;
  border-radius: 12px;
  color: var(--color-text);
}

.error-message h3 {
  color: var(--color-text);
  margin-bottom: 1rem;
}

.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  max-width: 400px;
  border-radius: 12px;
  padding: 1rem;
  animation: slideIn 0.5s ease;
}

.notification.success {
  background: #10b981;
  color: white;
}

.notification.error {
  background: #ef4444;
  color: white;
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notification-content i {
  font-size: 1.5rem;
}

.notification-text h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
}

.notification-text p {
  margin: 0;
  opacity: 0.9;
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

@media (max-width: 768px) {
  .purchase-confirmation {
    padding: 1rem;
  }

  .confirmation-header h2 {
    font-size: 2rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .offer-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .product-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.3rem;
  }

  .notification {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
}
</style>
