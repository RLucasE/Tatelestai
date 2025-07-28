<template>
  <div class="seller-section">
    <div class="seller-header">
      <h3>{{ sellerName }}</h3>
    </div>
    <div class="products-container">
      <div
        v-for="(product, index) in products"
        :key="index"
        class="product-item"
      >
        {{ product }}
      </div>
    </div>
    <div class="cart-button-container">
      <button class="cart-button">Carrito</button>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  sellerName: {
    type: String,
    required: true,
  },
  products: {
    type: Array,
    default: () => [],
  },
});
</script>

<style scoped>
.seller-section {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 1rem;
  position: relative;
  background-color: var(--color-primary);
}

.seller-header {
  margin-bottom: 1rem;
}

.seller-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: var(--color-text);
}

.products-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.product-item {
  border: 2px solid var(--color-darkest);
  padding: 0.75rem 1rem;
  border-radius: var(--border-radius);
  font-size: 1rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  background-color: var(--color-secondary);
  color: var(--color-text);
}

.cart-button-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.cart-button {
  background-color: var(--color-focus);
  border: 1px solid var(--color-darkest);
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--color-text);
  transition: background-color 0.3s ease;
}

.cart-button:hover {
  background-color: var(--color-darkest);
}

@media (max-width: 768px) {
  .seller-section {
    padding: 0.75rem;
  }
}
</style>
