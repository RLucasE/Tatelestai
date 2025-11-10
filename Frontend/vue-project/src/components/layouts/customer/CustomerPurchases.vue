<template>
  <div class="purchases-container">
    <div class="page-header">
      <h1 class="page-title">Mis Compras</h1>
    </div>

    <div v-if="loading" class="loading">
      <p>Cargando compras...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="getPurchases" class="retry-btn">Reintentar</button>
    </div>

    <div v-else-if="purchases.length === 0" class="empty">
      <p>No has realizado compras aún</p>
    </div>

    <div v-else class="purchases-list">
      <PurchaseCard
        v-for="purchase in purchases"
        :key="purchase.id"
        :purchase="purchase"
      />
    </div>
  </div>
</template>

<script setup>
import axiosInstance from "@/lib/axios";
import PurchaseCard from "./PurchaseCard.vue";
import { ref, onMounted } from "vue";

const purchases = ref([]);
const loading = ref(true);
const error = ref(null);

const getPurchases = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/customer/purchases");
    console.log(response.data);
    purchases.value = response.data.data || response.data;
    error.value = null;
  } catch (err) {
    console.error("Error fetching purchases:", err);
    error.value = "Error al cargar las compras";
    purchases.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getPurchases();
});
</script>

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
}
</style>
