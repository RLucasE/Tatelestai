<template>
  <div class="offers-table-container">
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
        @click="$emit('offerClick', offer)"
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
            @click.stop="$emit('toggleStatus', offer)"
            :class="['action-btn', offer.state === 'active' ? 'deactivate-btn' : 'activate-btn']"
            :title="offer.state === 'active' ? 'Desactivar oferta' : 'Activar oferta'"
          >
            {{ offer.state === 'active' ? 'Desactivar' : 'Activar' }}
          </button>
          <button
            @click.stop="$emit('offerClick', offer)"
            class="action-btn view-btn"
            title="Ver detalles"
          >
            Ver
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  offers: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['offerClick', 'toggleStatus']);

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
</script>

<style scoped>
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

.offer-title {
  font-weight: 600;
  font-size: 1em;
  margin-bottom: 4px;
  color: var(--color-text);
}

.offer-description {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

.establishment-name {
  font-weight: 500;
  margin-bottom: 2px;
  color: var(--color-text);
}

.establishment-type {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

.quantity-value {
  font-weight: 600;
  font-size: 1.1em;
  margin-bottom: 2px;
  color: var(--color-text);
}

.products-count {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

.expiration-date {
  font-weight: 500;
  margin-bottom: 2px;
  color: var(--color-text);
}

.expiration-time {
  font-size: 0.8em;
  opacity: 0.7;
  color: var(--color-text);
}

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
  background: #10b981;
  color: white;
}

.status-inactive {
  background: #6b7280;
  color: white;
}

.status-expired {
  background: #ef4444;
  color: white;
}

.status-unknown {
  background: var(--color-focus);
  color: var(--color-text);
}

.actions-col {
  flex-direction: row;
  gap: 8px;
  align-items: center;
}

.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 0.8em;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.activate-btn {
  background: #10b981;
  color: white;
}

.activate-btn:hover {
  background: #059669;
}

.deactivate-btn {
  background: #ef4444;
  color: white;
}

.deactivate-btn:hover {
  background: #dc2626;
}

.view-btn {
  background: var(--color-focus);
  color: var(--color-text);
}

.view-btn:hover {
  background: var(--color-focus-dark, #6b7280);
}

@media (max-width: 768px) {
  .table-header,
  .offer-row {
    grid-template-columns: 1fr;
  }

  .header-cell,
  .row-cell {
    border-right: none;
    border-bottom: 1px solid var(--color-focus);
  }

  .actions-col {
    flex-direction: column;
  }
}
</style>
