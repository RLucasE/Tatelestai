<template>
  <div class="verify-code-container">
    <div class="verify-code-card">
      <h1 class="title">Verificar Código de Cliente</h1>
      <p class="subtitle">Ingresa el código de pickup del cliente para ver los detalles del pedido</p>

      <!-- Formulario para ingresar el código -->
      <form @submit.prevent="verifyCode" class="code-form">
        <div class="input-group">
          <label for="pickup-code" class="input-label">Código de Pickup</label>
          <input
            id="pickup-code"
            v-model="pickupCode"
            type="text"
            placeholder="XXXX-XXXX-XXXX"
            class="code-input"
            :disabled="loading"
            @input="formatCode"
            maxlength="14"
          />
          <span v-if="error" class="error-message">{{ error }}</span>
        </div>

        <button type="submit" class="verify-button" :disabled="loading || !pickupCode">
          <span v-if="!loading">Verificar Código</span>
          <span v-else>Verificando...</span>
        </button>
      </form>

      <!-- Mensaje de éxito -->
      <div v-if="successMessage" class="success-notification">
        {{ successMessage }}
      </div>

      <!-- Datos del pedido -->
      <div v-if="orderData" class="order-details">
        <div class="order-header">
          <h2 class="order-title">Detalles del Pedido</h2>
          <span class="pickup-code-badge">{{ orderData.pickup_code }}</span>
        </div>

        <!-- Información del cliente -->
        <div class="customer-info">
          <h3 class="section-title">Cliente</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Nombre:</span>
              <span class="info-value">{{ orderData.customer.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email:</span>
              <span class="info-value">{{ orderData.customer.email }}</span>
            </div>
          </div>
        </div>

        <!-- Ofertas del pedido -->
        <div class="offers-section">
          <h3 class="section-title">Ofertas</h3>
          <div v-for="(offer, index) in orderData.offers" :key="index" class="offer-card">
            <div class="offer-header">
              <span class="offer-title">{{ offer.offer_title }}</span>
              <span class="offer-quantity">x{{ offer.offer_quantity }}</span>
            </div>
            <div class="product-details">
              <div class="product-info">
                <span class="product-name">{{ offer.product_name }}</span>
                <span class="product-description">{{ offer.product_description }}</span>
              </div>
              <div class="product-meta">
                <span class="product-quantity">Cantidad: {{ offer.product_quantity }}</span>
                <span class="product-price">${{ offer.product_price }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Total -->
        <div class="order-total">
          <span class="total-label">Total:</span>
          <span class="total-amount">${{ orderData.total_price }}</span>
        </div>

        <!-- Fecha -->
        <div class="order-date">
          <span class="date-label">Fecha del pedido:</span>
          <span class="date-value">{{ formatDate(orderData.created_at) }}</span>
        </div>

        <!-- Botón de confirmar -->
        <div class="actions">
          <button
            class="confirm-button"
            @click="confirmDelivery"
            :disabled="confirmingDelivery"
          >
            <span v-if="!confirmingDelivery">Confirmar Entrega</span>
            <span v-else>Confirmando...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axiosInstance from "@/lib/axios.js";

const pickupCode = ref('');
const orderData = ref(null);
const loading = ref(false);
const error = ref('');
const confirmingDelivery = ref(false);
const successMessage = ref('');

const formatCode = (event) => {
  // Formatear el código mientras se escribe (agregar guiones automáticamente)
  let value = event.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();

  if (value.length > 4) {
    value = value.slice(0, 4) + '-' + value.slice(4);
  }
  if (value.length > 9) {
    value = value.slice(0, 9) + '-' + value.slice(9);
  }

  pickupCode.value = value;
};

const verifyCode = async () => {
  if (!pickupCode.value) {
    error.value = 'Por favor, ingresa un código de pickup';
    return;
  }

  loading.value = true;
  error.value = '';
  successMessage.value = '';
  orderData.value = null;

  try {
    const response = await axiosInstance.post('/check-customer-code', {
      pickup_code: pickupCode.value
    });

    orderData.value = response.data.data;
  } catch (err) {
    if (err.response) {
      if (err.response.status === 404) {
        error.value = 'Código de pickup no encontrado';
      } else if (err.response.status === 403) {
        error.value = 'No tienes permiso para verificar este código';
      } else if (err.response.status === 422) {
        error.value = 'Código de pickup inválido';
      } else {
        error.value = err.response.data.error || 'Error al verificar el código';
      }
    } else {
      error.value = 'Error de conexión. Por favor, intenta nuevamente';
    }
  } finally {
    loading.value = false;
  }
};

const confirmDelivery = async () => {
  if (!orderData.value || !orderData.value.sell_id) {
    error.value = 'No hay datos del pedido para confirmar';
    return;
  }

  confirmingDelivery.value = true;
  error.value = '';
  successMessage.value = '';

  try {
    const response = await axiosInstance.post(`/complete-sell/${orderData.value.sell_id}`, {
      pick_up_code: orderData.value.pickup_code
    });

    successMessage.value = 'Entrega confirmada exitosamente';

    // Limpiar los datos después de 2 segundos
    setTimeout(() => {
      orderData.value = null;
      pickupCode.value = '';
      successMessage.value = '';
    }, 2000);

  } catch (err) {
    if (err.response) {
      if (err.response.status === 404) {
        error.value = 'Venta no encontrada';
      } else if (err.response.status === 403) {
        error.value = 'No tienes permiso para completar esta venta';
      } else if (err.response.status === 422) {
        error.value = err.response.data.error || 'Datos inválidos';
      } else {
        error.value = err.response.data.error || 'Error al confirmar la entrega';
      }
    } else {
      error.value = 'Error de conexión. Por favor, intenta nuevamente';
    }
  } finally {
    confirmingDelivery.value = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
.verify-code-container {
  min-height: 100vh;
  padding: 2rem;
  background: var(--color-bg);
}

.verify-code-card {
  max-width: 800px;
  margin: 0 auto;
  background: var(--color-darkest);
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.subtitle {
  color: var(--color-primary);
  margin-bottom: 2rem;
  font-size: 0.95rem;
}

.code-form {
  margin-bottom: 2rem;
}

.input-group {
  margin-bottom: 1.5rem;
}

.input-label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--color-text);
  font-weight: 500;
  font-size: 0.9rem;
}

.code-input {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-align: center;
  border: 2px solid var(--color-secondary);
  border-radius: 8px;
  background: var(--color-focus);
  color: var(--color-text);
  transition: border-color 0.2s ease;
  text-transform: uppercase;
}

.code-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.code-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  display: block;
  margin-top: 0.5rem;
  color: #ef4444;
  font-size: 0.875rem;
}

.verify-button {
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: var(--color-secondary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.verify-button:hover:not(:disabled) {
  background: var(--color-primary);
  transform: translateY(-1px);
}

.verify-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.success-notification {
  padding: 1rem;
  background: #10b981;
  color: white;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  text-align: center;
  font-weight: 600;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.order-details {
  border-top: 2px solid var(--color-secondary);
  padding-top: 2rem;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.order-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text);
}

.pickup-code-badge {
  padding: 0.5rem 1rem;
  background: var(--color-secondary);
  color: var(--color-text);
  border-radius: 6px;
  font-weight: 700;
  letter-spacing: 1px;
  font-size: 0.9rem;
}

.customer-info, .offers-section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-secondary);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 500;
}

.info-value {
  color: var(--color-text);
  font-size: 1rem;
}

.offer-card {
  background: var(--color-focus);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  border: 1px solid var(--color-secondary);
}

.offer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.offer-title {
  font-weight: 600;
  color: var(--color-text);
  font-size: 1.05rem;
}

.offer-quantity {
  background: var(--color-secondary);
  color: var(--color-text);
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
}

.product-details {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-name {
  color: var(--color-text);
  font-weight: 500;
}

.product-description {
  color: var(--color-primary);
  font-size: 0.875rem;
}

.product-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.product-quantity {
  color: var(--color-primary);
  font-size: 0.875rem;
}

.product-price {
  color: var(--color-text);
  font-weight: 700;
  font-size: 1.1rem;
}

.order-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--color-focus);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.total-label {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--color-text);
}

.total-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
}

.order-date {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: var(--color-secondary);
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.date-label {
  color: var(--color-primary);
  font-size: 0.9rem;
}

.date-value {
  color: var(--color-text);
  font-weight: 500;
}

.actions {
  margin-top: 2rem;
}

.confirm-button {
  width: 100%;
  padding: 1rem 1.5rem;
  background: var(--color-secondary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.confirm-button:hover:not(:disabled) {
  background: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.confirm-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .verify-code-container {
    padding: 1rem;
  }

  .verify-code-card {
    padding: 1.5rem;
  }

  .title {
    font-size: 1.5rem;
  }

  .order-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .product-details {
    flex-direction: column;
  }

  .product-meta {
    align-items: flex-start;
  }
}
</style>
