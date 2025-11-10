<template>
  <div class="sells-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Ventas</h1>
        <div class="stats-inline">
          <span class="stat">{{ sells.length }} ventas</span>
          <span class="stat-separator">·</span>
          <span class="stat">${{ calculateTotalRevenue() }}</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <p>Cargando ventas...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="getSells" class="retry-btn">Reintentar</button>
    </div>

    <div v-else-if="sells.length === 0" class="empty">
      <p>No se han realizado ventas aún</p>
    </div>

    <div v-else class="sells-list">
      <SellCard
        v-for="sell in sells"
        :key="sell.id"
        :sell="sell"
      />
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
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.stats-inline {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.7;
}

.stat {
  font-variant-numeric: tabular-nums;
}

.stat-separator {
  opacity: 0.5;
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

.sells-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (max-width: 768px) {
  .sells-container {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .page-title {
    font-size: 1.25rem;
  }
}
</style>
