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
    <div class="page-header">
      <h1 class="page-title">Compras de {{ customerName }}</h1>
    </div>

    <div v-if="loading" class="loading">
      <p>Cargando compras...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="getPurchases" class="retry-btn">Reintentar</button>
    </div>

    <div v-else-if="purchases.length === 0" class="empty">
      <p>Este cliente no ha realizado compras aún</p>
    </div>

    <div v-else>
      <div class="stats-section">
        <div class="stat-card">
          <span class="stat-label">Total de Compras</span>
          <span class="stat-value">{{ purchases.length }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total Gastado</span>
          <span class="stat-value">${{ calculateTotalSpent() }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Promedio</span>
          <span class="stat-value">${{ (calculateTotalSpent() / purchases.length).toFixed(2) }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Establecimientos</span>
          <span class="stat-value">{{ getUniqueEstablishments() }}</span>
        </div>
      </div>

      <div class="purchases-list">
        <SellCard
          v-for="purchase in purchases"
          :key="purchase.id"
          :sell="purchase"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.purchases-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.loading,
.error,
.empty {
  padding: 2rem;
  text-align: center;
  color: var(--color-text);
}

.error p,
.empty p {
  margin: 0 0 1rem 0;
  opacity: 0.7;
}

.retry-btn {
  padding: 0.5rem 1rem;
  background-color: var(--color-accent);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 0.875rem;
}

.retry-btn:hover {
  background-color: var(--color-accent-hover);
}

.stats-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--color-darkest);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.7;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}

.purchases-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .purchases-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 1.25rem;
  }

  .stats-section {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .stats-section {
    grid-template-columns: 1fr;
  }
}
</style>
