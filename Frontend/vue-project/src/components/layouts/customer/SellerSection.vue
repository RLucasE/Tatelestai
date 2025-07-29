<template>
  <div class="seller-section">
    <div class="seller-header">
      <h3>Vendedor {{ offers[0].establishment_id }}</h3>
    </div>
    <div class="offers-container">
      <div v-for="offer in offers" class="offer-conainer">
        <div class="offer-title">{{ offer.offer_title }}</div>
        <div class="offer-description">{{ offer.offer_description }}</div>
        <div class="products">
          <div v-for="product in offer.products">
            <div class="product">
              <div class="product-name">
                {{ product.product_name }}
              </div>
              <div class="product-quantity">
                x {{ product.product_quantity }}
              </div>
              <div class="product-description">
                ${{ product.product_price }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="buy-button-container">
      <button class="buy-button">Comprar</button>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  offers: {
    type: Array,
    default: () => [],
  },
});
</script>

<style scoped>
.seller-section {
  display: flex;
  flex-direction: column;
  border: 1.5px solid var(--color-border);
  border-radius: calc(var(--border-radius) * 1.5);
  margin-bottom: 5rem;
  padding: 2rem 2.5rem;
  position: relative;
  background-color: var(--color-primary);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
  transition: box-shadow 0.3s ease, transform 0.2s ease;
  will-change: transform, box-shadow;
}

.seller-section:hover {
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
  transform: translateY(-4px);
}

.seller-header {
  margin-bottom: 1.5rem;
  border-bottom: 3px solid var(--color-focus);
  padding-bottom: 0.75rem;
}

.seller-header h3 {
  font-size: 1.75rem;
  font-weight: 800;
  margin: 0;
  color: var(--color-text);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.offers-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.offer-conainer {
  background-color: var(--color-secondary);
  border-radius: calc(var(--border-radius) * 1.2);
  padding: 1.25rem 1.5rem;
  box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.07);
  color: var(--color-text);
  transition: background-color 0.3s ease;
}

.offer-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--color-darkest);
  margin-bottom: 0.5rem;
  text-transform: capitalize;
  letter-spacing: 0.03em;
}

.offer-description {
  font-size: 1rem;
  font-weight: 400;
  color: var(--color-text);
  opacity: 0.85;
  line-height: 1.4;
  margin-bottom: 1rem;
}

.products {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.products > div {
  background-color: var(--color-focus);
  padding: 0.4rem 1rem;
  border-radius: calc(var(--border-radius) * 1.2);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  transition: background-color 0.3s ease;
}

.products > div:hover {
  background-color: var(--color-darkest);
  cursor: pointer;
}

.buy-button-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.buy-button {
  background-color: var(--color-focus);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: calc(var(--border-radius) * 1.5);
  cursor: pointer;
  font-size: 1.1rem;
  color: var(--color-text);
  font-weight: 700;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  transition: background-color 0.3s ease, transform 0.25s ease;
}

.buy-button:hover {
  background-color: var(--color-darkest);
  transform: scale(1.1);
}

@media (max-width: 768px) {
  .seller-section {
    padding: 1.25rem 1.5rem;
  }
  .seller-header h3 {
    font-size: 1.4rem;
  }
  .offer-conainer {
    padding: 1rem 1.25rem;
  }
  .products > div {
    font-size: 0.95rem;
    padding: 0.3rem 0.8rem;
  }
  .buy-button {
    font-size: 1rem;
    padding: 0.6rem 1.25rem;
  }
}
</style>
