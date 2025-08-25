<template>
  <div v-if="visible" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>{{ offer.title }}</h2>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="modal-section">
          <h3>Información General</h3>
          <p><strong>Descripción:</strong> {{ offer.description }}</p>
          <p><strong>Estado:</strong> {{ getStatusText(offer.state) }}</p>
          <p><strong>Cantidad disponible:</strong> {{ offer.quantity }}</p>
          <p><strong>Establecimiento:</strong> {{ offer.establishment?.name || 'No especificado' }}</p>
          <p><strong>Tipo de establecimiento:</strong> {{ offer.establishment?.type || 'No especificado' }}</p>
        </div>

        <div class="modal-section">
          <h3>Fechas</h3>
          <p><strong>Creada:</strong> {{ formatDate(offer.created_at) }}</p>
          <p><strong>Expira:</strong> {{ formatDate(offer.expiration_datetime) }} a las {{ formatTime(offer.expiration_datetime) }}</p>
        </div>

        <div v-if="offer.products?.length" class="modal-section">
          <h3>Productos incluidos ({{ offer.products.length }})</h3>
          <div class="products-grid">
            <div v-for="product in offer.products" :key="product.id" class="product-item">
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
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  offer: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close']);

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
</script>

<style scoped>
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
  padding: 20px;
}

.modal-content {
  background: var(--color-secondary);
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  border: 1px solid var(--color-focus);
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: var(--color-text);
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--color-text);
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.3s ease;
}

.close-btn:hover {
  background: var(--color-focus);
}

.modal-body {
  padding: 24px;
}

.modal-section {
  margin-bottom: 24px;
}

.modal-section:last-child {
  margin-bottom: 0;
}

.modal-section h3 {
  margin: 0 0 12px 0;
  color: var(--color-text);
  font-size: 1.1em;
}

.modal-section p {
  margin: 8px 0;
  color: var(--color-text);
  line-height: 1.5;
}

.products-grid {
  display: grid;
  gap: 12px;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-bg);
  border-radius: 8px;
  border: 1px solid var(--color-focus);
}

.product-info {
  display: flex;
  flex-direction: column;
}

.product-name {
  font-weight: 600;
  color: var(--color-text);
}

.product-description {
  font-size: 0.9em;
  opacity: 0.7;
  color: var(--color-text);
}

.product-price {
  font-weight: 600;
  color: var(--color-primary);
}

@media (max-width: 768px) {
  .modal-overlay {
    padding: 10px;
  }

  .modal-content {
    max-height: 90vh;
  }

  .modal-header {
    padding: 16px 20px;
  }

  .modal-body {
    padding: 20px;
  }

  .product-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
