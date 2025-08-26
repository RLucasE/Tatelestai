<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axiosInstance from '@/lib/axios.js';
import SellCard from './SellCard.vue';

const route = useRoute();
const customerId = route.params.id;
const purchases = ref([]);
const loading = ref(true);
const error = ref(null);
const customerName = ref('');

const getPurchases = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get(`/adm-customer-purchases/${customerId}`);
    console.log(response.data);
    purchases.value = response.data.purchases || [];
    customerName.value = response.data.customer_name || 'Cliente';
    error.value = null;
  } catch (err) {
    console.error("Error fetching customer purchases:", err);
    error.value = "Error al cargar las compras del cliente";
    purchases.value = [];
  } finally {
    loading.value = false;
  }
};

const calculateTotalSpent = () => {
  return purchases.value.reduce((total, purchase) => {
    const purchaseTotal = purchase.sell_details.reduce((purchaseSum, detail) => {
      return purchaseSum + (detail.offer_quantity * detail.product_quantity * detail.product_price);
    }, 0);
    return total + purchaseTotal;
  }, 0).toFixed(2);
};

const getUniqueEstablishments = () => {
  const establishments = new Set();
  purchases.value.forEach(purchase => {
    if (purchase.seller && purchase.seller.establishment_name) {
      establishments.add(purchase.seller.establishment_name);
    }
  });
  return establishments.size;
};

onMounted(() => {
  getPurchases();
});
</script>

<template>
  <div class="purchases-container">
    <h1 class="page-title">Compras de {{ customerName }}</h1>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando compras...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="getPurchases" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="purchases.length === 0" class="empty-container">
      <p>Este cliente no ha realizado compras aún</p>
    </div>

    <!-- Purchases Grid -->
    <div v-else class="purchases-grid">
      <SellCard
        v-for="purchase in purchases"
        :key="purchase.id"
        :sell="purchase"
      />
    </div>

    <!-- Stats Section -->
    <div v-if="purchases.length > 0" class="stats-container">
      <div class="stats-card">
        <h3>Estadísticas del Cliente</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-label">Total de Compras:</span>
            <span class="stat-value">{{ purchases.length }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Total Gastado:</span>
            <span class="stat-value">${{ calculateTotalSpent() }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Promedio por Compra:</span>
            <span class="stat-value">${{ (calculateTotalSpent() / purchases.length).toFixed(2) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Establecimientos Visitados:</span>
            <span class="stat-value">{{ getUniqueEstablishments() }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.purchases-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.page-title {
  color: var(--color-text);
  font-size: 2.5em;
  font-weight: 700;
  text-align: center;
  margin-bottom: 30px;
  text-shadow: 2px 2px 4px rgba(34, 32, 31, 0.3);
}

.purchases-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 20px;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--color-text);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-text);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error State */
.error-container {
  text-align: center;
  padding: 40px 20px;
  color: var(--color-text);
}

.retry-btn {
  background: var(--color-primary);
  color: var(--color-text);
  border: 2px solid var(--color-text);
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1em;
  font-weight: 600;
  margin-top: 15px;
  transition: all 0.3s ease;
}

.retry-btn:hover {
  background: var(--color-secondary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(34, 32, 31, 0.3);
}

/* Empty State */
.empty-container {
  text-align: center;
  padding: 60px 20px;
  color: var(--color-text);
  font-size: 1.2em;
  opacity: 0.8;
}

/* Stats Section */
.stats-container {
  margin-top: 30px;
  padding: 20px 0;
}

.stats-card {
  background: var(--color-primary);
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 4px 6px rgba(34, 32, 31, 0.3);
  border: 2px solid var(--color-text);
}

.stats-card h3 {
  color: var(--color-text);
  font-size: 1.5em;
  font-weight: 600;
  margin-bottom: 20px;
  text-align: center;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  background: var(--color-secondary);
  border-radius: 8px;
  border: 1px solid var(--color-focus);
}

.stat-label {
  font-size: 0.9em;
  color: var(--color-text);
  opacity: 0.8;
  margin-bottom: 5px;
}

.stat-value {
  font-size: 1.3em;
  font-weight: 700;
  color: var(--color-text);
}

/* Responsive Design */
@media (max-width: 768px) {
  .purchases-container {
    padding: 15px;
  }

  .page-title {
    font-size: 2em;
    margin-bottom: 20px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.8em;
  }

  .purchases-container {
    padding: 10px;
  }

  .stats-card {
    padding: 15px;
  }
}
</style>
