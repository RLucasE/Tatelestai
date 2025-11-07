<template>
  <div class="sells-container">
    <div class="admin-header">
      <h1 class="page-title">Ventas Recientes</h1>
      <p class="page-subtitle">Historial de transacciones del sistema</p>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando ventas...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="getSells" class="retry-btn">Reintentar</button>
    </div>

    <div v-else-if="sells.length === 0" class="empty-container">
      <p>No se han realizado ventas aún</p>
    </div>

    <div v-else>
      <div class="sells-grid">
        <SellCard
          v-for="sell in sells"
          :key="sell.id"
          :sell="sell"
        />
      </div>

      <div class="stats-container">
        <div class="stats-card">
          <h3 class="stats-title">Resumen General</h3>
          <div class="stats-grid">
            <div class="stat-item">
              <span class="stat-label">Total de Ventas</span>
              <span class="stat-value">{{ sells.length }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Ingresos Totales</span>
              <span class="stat-value">${{ calculateTotalRevenue() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axiosInstance from "@/lib/axios";
import SellCard from "./SellCard.vue";
import { ref, onMounted } from "vue";

const sells = ref([]);
const loading = ref(true);
const error = ref(null);

const getSells = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/adm-sells");
    console.log(response.data);
    sells.value = response.data || [];
    error.value = null;
  } catch (err) {
    console.error("Error fetching sells:", err);
    error.value = "Error al cargar las ventas";
    sells.value = [];
  } finally {
    loading.value = false;
  }
};

const calculateTotalRevenue = () => {
  return sells.value.reduce((total, sell) => {
    const sellTotal = sell.sell_details.reduce((sellSum, detail) => {
      return sellSum + (detail.offer_quantity * detail.product_quantity * detail.product_price);
    }, 0);
    return total + sellTotal;
  }, 0).toFixed(2);
};

onMounted(() => {
  getSells();
});
</script>

<style scoped>
.sells-container {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: 100vh;
}

.admin-header {
  margin-bottom: 2.5rem;
  text-align: center;
}

.page-title {
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 0.75rem 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  color: var(--color-text);
  opacity: 0.6;
  font-size: 1rem;
  margin: 0;
  font-weight: 400;
}

.sells-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.loading-container,
.error-container,
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.loading-spinner {
  width: 48px;
  height: 48px;
  border: 3px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-container {
  color: var(--color-text);
}

.error-container p {
  margin: 0 0 1.5rem 0;
  font-size: 1rem;
}

.retry-btn {
  padding: 0.75rem 1.5rem;
  background-color: var(--color-accent);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.retry-btn:hover {
  background-color: var(--color-accent-hover);
  transform: translateY(-1px);
}

.empty-container {
  color: var(--color-text);
  opacity: 0.5;
}

.empty-container p {
  margin: 0;
  font-size: 1rem;
}

.stats-container {
  margin-top: 2rem;
}

.stats-card {
  background: var(--color-darkest);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.stats-title {
  color: var(--color-text);
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 1.5rem 0;
  text-align: center;
  letter-spacing: -0.2px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.4), rgba(37, 43, 58, 0.3));
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition: all 0.25s ease;
}

.stat-item:hover {
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.6), rgba(37, 43, 58, 0.5));
  border-color: rgba(99, 102, 241, 0.3);
  transform: translateY(-2px);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.6;
  margin-bottom: 0.75rem;
  text-align: center;
  font-weight: 500;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--color-success);
  letter-spacing: -0.5px;
}

@media (max-width: 768px) {
  .sells-container {
    padding: 1.5rem;
  }

  .page-title {
    font-size: 1.875rem;
  }

  .admin-header {
    margin-bottom: 2rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stats-card {
    padding: 1.5rem;
  }

  .stat-item {
    padding: 1.25rem;
  }
}
</style>
