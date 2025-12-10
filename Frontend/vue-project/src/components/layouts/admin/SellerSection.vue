<template>
  <section class="seller-section">
    <div class="section-header">
      <h2 class="section-title">Información de Vendedor</h2>
      <div class="seller-status">
        <span class="status-label">Estado:</span>
        <span class="status-badge" :class="user.state === 'active' ? 'active' : 'inactive'">
          {{ user.state === 'active' ? 'Activo' : 'Inactivo' }}
        </span>
      </div>
    </div>

    <div class="info-grid">
      <div class="info-item">
        <span class="label">Fecha de registro</span>
        <span class="value">{{ formatDate(user.created_at) }}</span>
      </div>
      <div class="info-item">
        <span class="label">Productos</span>
        <span class="value">0</span>
      </div>
      <div class="info-item">
        <span class="label">Ofertas activas</span>
        <span class="value">0</span>
      </div>
      <div class="info-item">
        <span class="label">Ventas</span>
        <span class="value">0</span>
      </div>
      <div class="info-item">
        <span class="label">Ingresos</span>
        <span class="value">$0.00</span>
      </div>
    </div>

    <div class="actions-section">
      <button
        class="action-btn primary"
        @click="user.state === 'active' ? desactivarSeller() : activarSeller()"
      >
        {{ user.state === 'active' ? 'Desactivar' : 'Activar' }}
      </button>
      <button class="action-btn" @click="verOfertas">
        Ver Ofertas
      </button>
      <button class="action-btn" @click="verVentas">
        Ver Ventas
      </button>
    </div>
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const emit = defineEmits(['updated']);

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
});

const formatDate = (dateStr) => {
  if (!dateStr) return 'No disponible';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-AR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

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

const verOfertas = () => {
  router.push({ name: 'admin-seller-offers', params: { id: props.user.id } });
};

const verVentas = () => {
  router.push({ name: 'adm-seller-sells', params: { id: props.user.id } });
};
</script>

<style scoped>
.seller-section {
  background: var(--color-darkest);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 1.25rem;
  margin-top: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.section-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
}

.seller-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-label {
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge {
  padding: 0.25rem 0.625rem;
  border-radius: var(--border-radius);
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid var(--color-border);
}

.status-badge.active {
  background: var(--color-success);
  color: var(--color-text);
  border-color: var(--color-success);
}

.status-badge.inactive {
  background: var(--color-secondary);
  color: var(--color-text);
  opacity: 0.7;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--color-border);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.6;
}

.value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}

.actions-section {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-secondary);
  color: var(--color-text);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  flex: 1;
  min-width: 120px;
}

.action-btn:hover {
  background: var(--color-focus);
}

.action-btn.primary {
  background: var(--color-accent);
  border-color: var(--color-accent);
}

.action-btn.primary:hover {
  background: var(--color-accent-hover);
  border-color: var(--color-accent-hover);
}

@media (max-width: 640px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .actions-section {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
  }
}
</style>
