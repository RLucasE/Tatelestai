<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const route = useRoute();
const router = useRouter();
const sellerId = route.params.id;

const seller = ref(null);
const loading = ref(true);
const error = ref(null);
const activating = ref(false);

const fetchSeller = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get(`/users/${sellerId}`);
    seller.value = data || null;
    error.value = null;
  } catch (err) {
    console.error('Error fetching seller detail:', err);
    error.value = 'No se pudo cargar el vendedor';
    seller.value = null;
  } finally {
    loading.value = false;
  }
};

const activateSeller = async () => {
  if (!seller.value || activating.value) return;

  try {
    activating.value = true;
    const { data } = await axiosInstance.patch(`/users/${sellerId}/activate-seller`);

    // Mostrar mensaje de éxito
    alert('Vendedor activado correctamente');

    // Redirigir a la lista de nuevos vendedores
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error activating seller:', err);
    const message = err.response?.data?.message || 'Error al activar el vendedor';
    alert(message);
  } finally {
    activating.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'new-sellers' });
};

onMounted(fetchSeller);
</script>

<template>
  <div class="new-seller-page">
    <div class="new-seller-container">
      <div class="header">
        <button class="back-btn" @click="goBack">← Volver</button>
        <h1 class="title">Solicitud de Vendedor</h1>
        <p class="subtitle" v-if="seller">ID: {{ seller.id }}</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando información del vendedor...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchSeller">Reintentar</button>
      </div>

      <div v-else-if="seller" class="content">
        <!-- Información básica del usuario -->
        <section class="card">
          <h2 class="section-title">Información del Usuario</h2>
          <div class="data-grid">
            <div class="data-item">
              <span class="label">Nombre</span>
              <span class="value">{{ seller.name }}</span>
            </div>
            <div class="data-item">
              <span class="label">Apellido</span>
              <span class="value">{{ seller.last_name }}</span>
            </div>
            <div class="data-item full">
              <span class="label">Email</span>
              <span class="value">{{ seller.email }}</span>
            </div>
            <div class="data-item">
              <span class="label">Estado</span>
              <span class="value status-waiting">{{ seller.state }}</span>
            </div>
            <div class="data-item">
              <span class="label">Roles</span>
              <div class="roles">
                <span
                  v-for="role in seller.roles"
                  :key="role"
                  class="role-chip"
                >
                  {{ role }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Información del establecimiento -->
        <section class="card" v-if="seller.food_establishment">
          <h2 class="section-title">Establecimiento de Comida</h2>
          <div class="establishment-details">
            <div class="establishment-item">
              <span class="label">Nombre del Establecimiento</span>
              <span class="value">{{ seller.food_establishment.name }}</span>
            </div>
            <div class="establishment-item" v-if="seller.food_establishment.establishment_type">
              <span class="label">Tipo de Establecimiento</span>
              <span class="value">{{ seller.food_establishment.establishment_type.name }}</span>
            </div>
          </div>
        </section>

        <!-- Acciones -->
        <section class="card actions-card">
          <h2 class="section-title">Acciones</h2>
          <div class="actions">
            <button
              class="activate-btn"
              @click="activateSeller"
              :disabled="activating || seller.state !== 'waiting_for_confirmation'"
            >
              <span v-if="activating">Activando...</span>
              <span v-else>✓ Activar Vendedor</span>
            </button>
            <button class="reject-btn" disabled>
              ✗ Rechazar (No implementado)
            </button>
          </div>
          <p class="action-note">
            Al activar al vendedor, su estado cambiará a "activo" y podrá comenzar a usar la plataforma.
          </p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.new-seller-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem;
}

.new-seller-container {
  max-width: 800px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--color-text);
  position: relative;
}

.back-btn {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: var(--color-focus);
}

.title {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  font-size: 1rem;
  opacity: 0.9;
  color: var(--color-text);
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--color-text);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-radius: 50%;
  border-top-color: var(--color-primary);
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 2rem;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  color: var(--color-text);
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.retry-btn:hover {
  background: var(--color-focus);
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card {
  background: var(--color-background);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 8px 32px rgba(34, 32, 31, 0.2);
  border: 1px solid var(--color-border);
}

.section-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.data-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.data-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.data-item.full {
  grid-column: 1 / -1;
}

.establishment-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.establishment-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.value {
  color: var(--color-heading);
  font-size: 1.1rem;
}

.status-waiting {
  color: var(--color-focus);
  font-weight: 600;
  text-transform: capitalize;
}

.roles {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.role-chip {
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  color: var(--color-text);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.actions-card {
  border: 2px solid var(--color-border);
}

.actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.activate-btn {
  background: var(--color-primary);
  color: var(--color-text);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  flex: 1;
}

.activate-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  background: var(--color-focus);
  box-shadow: 0 4px 12px rgba(111, 108, 105, 0.3);
}

.activate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.reject-btn {
  background: var(--color-secondary);
  color: var(--color-text);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: not-allowed;
  opacity: 0.6;
  flex: 1;
}

.action-note {
  color: var(--color-primary);
  font-size: 0.9rem;
  font-style: italic;
  margin: 0;
}
</style>
