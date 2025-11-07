<template>
  <div class="offers-table-container">
    <div class="offers-grid">
      <div
        v-for="offer in offers"
        :key="offer.id"
        class="offer-card"
      >
        <div class="card-header" @click="$emit('offerClick', offer)">
          <div class="header-content">
            <h3 class="offer-title">{{ offer.title }}</h3>
            <span :class="['status-badge', getStatusClass(offer.state)]">
              <span class="status-dot"></span>
              {{ getStatusText(offer.state) }}
            </span>
          </div>
          <p class="offer-description">{{ truncateText(offer.description, 100) }}</p>
        </div>

        <div class="card-body" @click="$emit('offerClick', offer)">
          <div class="info-section">
            <div class="info-item">
              <span class="info-label">Establecimiento</span>
              <span class="info-value">{{ offer.establishment?.name || 'No especificado' }}</span>
              <span class="info-badge" v-if="offer.establishment?.establishment_type?.name">
                {{ offer.establishment.establishment_type.name }}
              </span>
            </div>

            <div class="stats-row">
              <div class="stat-item">
                <div class="stat-content">
                  <span class="stat-value">{{ offer.quantity }}</span>
                  <span class="stat-label">Disponibles</span>
                </div>
              </div>

              <div class="stat-divider"></div>

              <div class="stat-item">
                <div class="stat-content">
                  <span class="stat-value">{{ offer.products?.length || 0 }}</span>
                  <span class="stat-label">Productos</span>
                </div>
              </div>
            </div>

            <div class="expiration-section">
              <div class="expiration-content">
                <span class="expiration-label">Expira el</span>
                <div class="expiration-datetime">
                  <span class="expiration-date">{{ formatDate(offer.expiration_datetime) }}</span>
                  <span class="expiration-separator">•</span>
                  <span class="expiration-time">{{ formatTime(offer.expiration_datetime) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <button
            @click.stop="$emit('offerClick', offer)"
            class="btn btn-secondary"
          >
            Ver Detalles
          </button>
          <button
            @click.stop="$emit('toggleStatus', offer)"
            :class="['btn', offer.state === 'active' ? 'btn-danger' : 'btn-success']"
          >
            {{ offer.state === 'active' ? 'Desactivar' : 'Activar' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="offers.length === 0" class="empty-state">
      <h3 class="empty-title">No hay ofertas disponibles</h3>
      <p class="empty-text">Las ofertas aparecerán aquí cuando sean creadas</p>
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
  padding: 0;
}

.offers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 20px;
  padding: 4px;
}

.offer-card {
  background: var(--color-darkest);
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--color-focus);
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.offer-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
  border-color: var(--color-secondary);
}

.card-header {
  padding: 20px;
  cursor: pointer;
  background: var(--color-primary);
  border-bottom: 1px solid var(--color-focus);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 10px;
}

.offer-title {
  font-size: 1.15em;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
  line-height: 1.4;
  flex: 1;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 0.7em;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.status-active {
  background: var(--color-success);
  color: white;
}

.status-active .status-dot {
  background: white;
}

.status-inactive {
  background: var(--color-danger);
  color: white;
}

.status-inactive .status-dot {
  background: white;
}

.status-expired {
  background: var(--color-focus);
  color: var(--color-text);
}

.status-expired .status-dot {
  background: var(--color-text);
  opacity: 0.6;
}

.status-unknown {
  background: var(--color-secondary);
  color: var(--color-text);
}

.status-unknown .status-dot {
  background: var(--color-text);
}

.offer-description {
  font-size: 0.85em;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
  line-height: 1.5;
}

.card-body {
  padding: 20px;
  cursor: pointer;
  flex: 1;
  background: var(--color-darkest);
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-label {
  font-size: 0.7em;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--color-text);
  opacity: 0.5;
}

.info-value {
  font-size: 0.95em;
  font-weight: 600;
  color: var(--color-text);
}

.info-badge {
  display: inline-block;
  padding: 4px 10px;
  background: var(--color-primary);
  color: var(--color-text);
  border-radius: 4px;
  font-size: 0.75em;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  align-self: flex-start;
  margin-top: 2px;
  border: 1px solid var(--color-focus);
}

.stats-row {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 16px;
  background: var(--color-primary);
  border-radius: 4px;
  gap: 16px;
  border: 1px solid var(--color-focus);
}

.stat-item {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}

.stat-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-value {
  font-size: 1.8em;
  font-weight: 700;
  color: var(--color-warning);
  line-height: 1;
}

.stat-label {
  font-size: 0.7em;
  color: var(--color-text);
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

.stat-divider {
  width: 1px;
  height: 35px;
  background: var(--color-focus);
}

.expiration-section {
  display: flex;
  align-items: center;
  padding: 14px;
  background: var(--color-primary);
  border-radius: 4px;
  border: 1px solid var(--color-focus);
}

.expiration-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.expiration-label {
  font-size: 0.7em;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--color-text);
  opacity: 0.5;
}

.expiration-datetime {
  display: flex;
  align-items: center;
  gap: 8px;
}

.expiration-date {
  font-size: 0.95em;
  font-weight: 600;
  color: var(--color-text);
}

.expiration-separator {
  color: var(--color-text);
  opacity: 0.5;
  font-weight: 700;
}

.expiration-time {
  font-size: 0.9em;
  color: var(--color-text);
  opacity: 0.7;
}

.card-footer {
  padding: 14px 20px;
  background: var(--color-primary);
  border-top: 1px solid var(--color-focus);
  display: flex;
  gap: 10px;
}

.btn {
  flex: 1;
  padding: 10px 18px;
  border: none;
  border-radius: 4px;
  font-size: 0.85em;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.btn:active {
  transform: translateY(0);
}

.btn-secondary {
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
}

.btn-secondary:hover {
  background: var(--color-focus);
}

.btn-success {
  background: var(--color-success);
  color: white;
}

.btn-success:hover {
  background: var(--color-success-hover);
}

.btn-danger {
  background: var(--color-danger);
  color: white;
}

.btn-danger:hover {
  background: var(--color-danger-hover);
}

.empty-state {
  text-align: center;
  padding: 60px 40px;
  background: var(--color-darkest);
  border-radius: 8px;
  border: 1px dashed var(--color-focus);
}

.empty-title {
  font-size: 1.3em;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 10px 0;
}

.empty-text {
  font-size: 0.95em;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
}

@media (max-width: 1400px) {
  .offers-grid {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
  }
}

@media (max-width: 768px) {
  .offers-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .card-header,
  .card-body {
    padding: 16px;
  }

  .stats-row {
    flex-direction: column;
    gap: 16px;
  }

  .stat-divider {
    width: 100%;
    height: 1px;
  }

  .stat-item {
    width: 100%;
  }

  .card-footer {
    flex-direction: column;
    gap: 10px;
  }

  .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .status-badge {
    align-self: flex-start;
  }
}
</style>
