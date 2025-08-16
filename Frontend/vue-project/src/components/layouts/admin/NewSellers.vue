<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const newSellers = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchNewSellers = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get('/new-sellers');
    newSellers.value = Array.isArray(data) ? data : [];
    error.value = null;
  } catch (err) {
    console.error('Error fetching new sellers:', err);
    error.value = 'No se pudieron cargar las solicitudes de vendedores';
    newSellers.value = [];
  } finally {
    loading.value = false;
  }
};

const viewSellerDetail = (sellerId) => {
  router.push({ name: 'new-seller', params: { id: sellerId } });
};

onMounted(fetchNewSellers);
</script>

<template>
  <div class="new-sellers-page">
    <div class="new-sellers-container">
      <div class="header">
        <h1 class="title">Solicitudes de Nuevos Vendedores</h1>
        <p class="subtitle">Usuarios en espera de confirmación de establecimiento</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando solicitudes...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchNewSellers">Reintentar</button>
      </div>

      <div v-else class="content">
        <div v-if="newSellers.length === 0" class="no-data">
          <p>No hay solicitudes de vendedores pendientes</p>
        </div>

        <div v-else class="sellers-grid">
          <div
            v-for="seller in newSellers"
            :key="seller.id"
            class="seller-card"
            @click="viewSellerDetail(seller.id)"
          >
            <div class="seller-info">
              <h3 class="seller-name">Vendedor: {{seller.name}}</h3>
              <p class="seller-email">{{ seller.email }}</p>
              <div class="establishment-info" v-if="seller.food_establishment">
                <p class="establishment-name">
                  <strong>Establecimiento:</strong> {{ seller.food_establishment.name }}
                </p>
                <p class="establishment-type" v-if="seller.food_establishment.establishment_type">
                  <strong>Tipo:</strong> {{ seller.food_establishment.establishment_type.name }}
                </p>
              </div>
              <div class="status">
                <span class="status-badge waiting">Esperando confirmación</span>
              </div>
            </div>
            <div class="actions">
              <button class="view-btn">Ver detalles →</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.new-sellers-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem;
}

.new-sellers-container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--color-text);
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  font-size: 1.1rem;
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

.no-data {
  text-align: center;
  padding: 3rem;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  color: var(--color-text);
  font-size: 1.1rem;
}

.sellers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.seller-card {
  background: var(--color-secondary);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 8px 32px rgba(34, 32, 31, 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid var(--color-secondary);
}

.seller-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(34, 32, 31, 0.3);
  border-color: var(--color-focus);
}

.seller-info {
  margin-bottom: 1rem;

}

.seller-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.seller-email {
  color: var(--color-text);
  margin-bottom: 1rem;
}

.establishment-info {
  background: var(--color-background-soft);
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  border: 1px solid var(--color-border);
}

.establishment-name,
.establishment-type {
  margin: 0.25rem 0;
  color: var(--color-heading);
  font-size: 0.95rem;
}

.status {
  margin-bottom: 1rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-badge.waiting {
  background: var(--color-primary);
  color: var(--color-text);
}

.actions {
  display: flex;
  justify-content: flex-end;
}

.view-btn {
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  color: var(--color-text);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.view-btn:hover {
  transform: translateX(2px);
  box-shadow: 0 4px 12px rgba(111, 108, 105, 0.3);
}
</style>
