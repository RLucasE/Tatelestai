<template>
  <div class="history-container">
    <h1 class="page-title">Historial de Compras</h1>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando historial...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="getHistory" class="retry-btn">Reintentar</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="history.length === 0" class="empty-container">
      <p>No tienes compras completadas en tu historial</p>
    </div>

    <!-- History Grid -->
    <div v-else class="history-grid">
      <HistoryCard
        v-for="purchase in history"
        :key="purchase.sell_id"
        :purchase="purchase"
      />
    </div>
  </div>
</template>

<script setup>
import axiosInstance from "@/lib/axios";
import HistoryCard from "./HistoryCard.vue";
import { ref, onMounted } from "vue";

const history = ref([]);
const loading = ref(true);
const error = ref(null);

const getHistory = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/customerHistorySell");
    console.log(response.data);
    history.value = response.data.data || response.data;
    error.value = null;
  } catch (err) {
    console.error("Error fetching history:", err);
    error.value = "Error al cargar el historial";
    history.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getHistory();
});
</script>

<style scoped>
.history-container {
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

.history-grid {
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

/* Responsive Design */
@media (max-width: 768px) {
  .history-container {
    padding: 15px;
  }

  .page-title {
    font-size: 2em;
  }
}
</style>

