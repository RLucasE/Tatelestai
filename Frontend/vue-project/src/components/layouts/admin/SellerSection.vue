<script setup>
import { defineProps, defineEmits } from 'vue';
import axiosInstance from '@/lib/axios.js';

const emit = defineEmits(['updated']);

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
});

const activarSeller = async () => {
  try {
    await axiosInstance.patch(`/users/${props.user.id}/activate-seller`);
    emit('updated');
  } catch (err) {
    console.error('Error al activar seller:', err);
  }
};

const desactivarSeller = async () => {
  try {
    await axiosInstance.patch(`/users/${props.user.id}/deactivate-seller`);
    emit('updated');
  } catch (err) {
    console.error('Error al desactivar seller:', err);
  }
};
</script>

<template>
  <section class="seller-section">
    <h2 class="section-title">Información de Vendedor</h2>
    <div class="seller-info">
      <div class="info-grid">
        <div class="info-item">
          <span class="label">Estado del vendedor</span>
          <span class="value">{{user.state || null}}</span>
        </div>
        <div class="info-item">
          <span class="label">Fecha de registro</span>
          <span class="value">{{ user.created_at || 'No disponible' }}</span>
        </div>
        <div class="info-item">
          <span class="label">Productos registrados</span>
          <span class="value">0</span>
        </div>
        <div class="info-item">
          <span class="label">Ofertas activas</span>
          <span class="value">0</span>
        </div>
        <div class="info-item">
          <span class="label">Ventas realizadas</span>
          <span class="value">0</span>
        </div>
        <div class="info-item">
          <span class="label">Total de ingresos</span>
          <span class="value">$0.00</span>
        </div>
      </div>

      <div class="seller-actions">
        <h3 class="sub-title">Acciones disponibles</h3>
        <div class="actions-grid">
          <button class="action-btn" @click="user.state === 'active' ? desactivarSeller() : activarSeller()">
            {{ user.state === 'active' ? 'Desactivar Seller' : 'Activar Seller' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.seller-section {
  margin-top: 20px;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 1.2rem;
  color: var(--color-text);
}

.seller-info {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 16px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.label {
  opacity: 0.7;
  font-size: 0.85rem;
  margin-bottom: 4px;
}

.value {
  font-weight: 700;
  color: var(--color-text);
}

.sub-title {
  margin: 0 0 12px 0;
  font-size: 1rem;
  color: var(--color-text);
}

.actions-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.action-btn {
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  border-radius: 8px;
  color: var(--color-text);
  padding: 8px 12px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.2s;
}

.action-btn:hover {
  background: var(--color-focus);
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    flex-direction: column;
  }
}
</style>
