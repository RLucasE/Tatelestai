<template>
  <div class="ventas-container">
    <div class="header">
      <h2>Mis Ventas</h2>
    </div>

    <div v-if="loading" class="loading">Cargando ventas...</div>

    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-else-if="!ventas.length" class="no-ventas">
      No tienes ventas registradas aún.
    </div>

    <div v-else class="ventas-list">
      <div v-for="venta in ventas" :key="venta.id" class="venta-card">
        <div class="venta-header">
          <span class="venta-id">Venta #{{ venta.id }}</span>
          <span class="venta-date">{{ formatDate(venta.created_at) }}</span>
        </div>

        <div class="venta-details">
          <h4>Productos vendidos:</h4>
          <ul class="product-list">
            <li v-for="detalle in venta.sell_details" :key="detalle.id" class="product-item">
              <div class="product-info">
                <span class="product-name">{{ detalle.product_name }}</span>
                <span class="product-quantity">x{{ detalle.product_quantity * detalle.offer_quantity}}</span>
                <span class="product-price">${{ detalle.product_price }}</span>
              </div>
              <p v-if="detalle.product_description" class="product-description">
                {{ detalle.product_description }}
              </p>
            </li>
          </ul>
        </div>

        <div class="venta-footer">
          <div class="venta-total">
            <span>Total:</span>
            <span class="total-amount">${{ totalAmount(venta.sell_details,venta.offer_quantity)}}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axiosInstance from "@/lib/axios";

const ventas = ref([]);
const loading = ref(true);
const error = ref(null);

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleString();
};

const totalAmount = (sellDetails, offerQuantity = null) => {
  if (!sellDetails || sellDetails.length === 0) return 0;

  // Si no hay offerQuantity proporcionado y hay al menos un elemento en sell_details
  // intentamos obtenerlo del primer elemento
  if (offerQuantity === null && sellDetails.length > 0 && sellDetails[0].offer_quantity) {
    offerQuantity = sellDetails[0].offer_quantity;
  }

  return sellDetails.reduce((total, detalle) => {
    // Multiplicamos por offer_quantity solo si existe
    const quantity = detalle.product_quantity * (detalle.offer_quantity || offerQuantity || 1);
    return total + (quantity * detalle.product_price);
  }, 0);
};

const fetchVentas = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get("/sells");
    ventas.value = response.data;
  } catch (err) {
    error.value = "Error al cargar las ventas";
    console.error("Error:", err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchVentas);
</script>

<style scoped>
.ventas-container {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.loading, .error-message, .no-ventas {
  text-align: center;
  padding: 2rem;
  background: var(--color-secondary);
  border-radius: var(--border-radius);
  margin: 1rem 0;
  color: var(--color-text);
}

.error-message {
  color: var(--color-text);
  background: var(--color-darkest);
}

.ventas-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

.venta-card {
  background: var(--color-primary);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.venta-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.venta-header {
  padding: 1rem;
  background: var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-darkest);
}

.venta-id {
  font-weight: bold;
  font-size: 1.1rem;
  color: var(--color-text);
}

.venta-date {
  color: var(--color-text);
  opacity: 0.8;
  font-size: 0.9rem;
}

.venta-details {
  padding: 1rem;
}

.venta-details h4 {
  margin-top: 0;
  margin-bottom: 0.75rem;
  color: var(--color-text);
}

.product-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.product-item {
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-focus);
}

.product-item:last-child {
  border-bottom: none;
}

.product-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-name {
  font-weight: bold;
  flex: 1;
  color: var(--color-text);
}

.product-quantity {
  color: var(--color-text);
  opacity: 0.8;
  margin: 0 1rem;
}

.product-price {
  font-weight: bold;
  color: var(--color-text);
}

.product-description {
  margin-top: 0.5rem;
  color: var(--color-text);
  opacity: 0.8;
  font-size: 0.9rem;
}

.venta-footer {
  padding: 1rem;
  background: var(--color-focus);
  border-top: 1px solid var(--color-darkest);
}

.venta-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
}

.total-amount {
  font-size: 1.2rem;
  color: var(--color-text);
}

@media (min-width: 768px) {
  .ventas-list {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  }
}
</style>
