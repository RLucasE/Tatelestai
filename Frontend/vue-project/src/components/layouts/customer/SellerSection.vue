<template>
  <div class="seller-section">
    <div class="seller-header">
      <h3>Vendedor {{ offers[0].establishment_id }}</h3>
    </div>
    <div class="offers-container">
      <div v-for="offer in offers" class="offer-container">
        <div class="offer-title">
          {{ offer.offer_title }} X {{ offer.quantity }}
        </div>
        <div class="offer-description">{{ offer.offer_description }}</div>
        <div class="products-grid">
          <div v-for="product in offer.products" class="product-card">
            <div class="product-header">
              <h5 class="product-name">{{ product.product_name }}</h5>
              <span class="product-quantity"
                >x{{ product.product_quantity }}</span
              >
            </div>
            <div class="product-footer">
              <span class="product-price">${{ product.product_price }}</span>
            </div>
          </div>
        </div>
        <div class="offer-total">
          <span class="total-label">SubTotal:</span>
          <span class="total-amount">${{ calculateOfferTotal(offer) }}</span>
        </div>
      </div>
    </div>
    <div class="action-footer">
      <div class="cart-total">
        <span class="total-label">SubTotal:</span>
        <span class="total-amount">${{ sellerTotal }}</span>
      </div>
      <button class="action-button" @click="handlePurchase" :disabled="loading">
        {{ loading ? "Procesando..." : "Comprar" }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed, ref } from "vue";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  offers: {
    type: Array,
    default: () => [],
  },
});

const loading = ref(false);

// Función para calcular el total de una oferta
const calculateOfferTotal = (offer) => {
  return offer.products
    .reduce((total, product) => {
      return total + product.product_price * product.product_quantity;
    }, 0)
    .toFixed(2);
};

// Calcular el total del vendedor (todas las ofertas * offer_quantity)
const sellerTotal = computed(() => {
  return props.offers
    .reduce((total, offer) => {
      const offerTotal = offer.products.reduce((sum, product) => {
        return sum + product.product_price * product.product_quantity;
      }, 0);
      return total + offerTotal * offer.quantity;
    }, 0)
    .toFixed(2);
});

const handlePurchase = async () => {
  if (loading.value) return;
  try {
    loading.value = true;
    const purchaseData = {
      offers: props.offers.map((offer) => ({
        id: offer.offer_id,
        quantity: offer.quantity,
      })),
      food_establishment_id: props.offers[0].establishment_id,
    };
    await axiosInstance.post("/buy-offers", purchaseData);
    alert("¡Compra realizada con éxito!");
    window.location.reload();
  } catch (error) {
    console.error("Error al procesar la compra:", error);
    alert("Error al procesar la compra. Por favor, intente nuevamente.");
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.seller-section {
  display: flex;
  flex-direction: column;
  background: var(--color-primary);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  margin-bottom: 2rem;
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  width: 100%; /* Asegura que ocupe todo el ancho disponible */
  max-width: 1400px; /* Aumentado de 1200px típico a 1400px */
  margin-left: auto;
  margin-right: auto;
}

.seller-section:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.seller-header {
  padding: 1.5rem 2.5rem; /* Aumentado el padding horizontal */
  background: var(--color-secondary);
  border-bottom: 2px solid var(--color-focus);
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.seller-header h3 {
  margin: 0;
  font-size: 1.75rem; /* Aumentado el tamaño */
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.seller-total {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text);
  background: var(--color-darkest);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: 2px solid var(--color-focus);
}

.offers-container {
  padding: 1.5rem 2.5rem; /* Aumentado el padding horizontal */
  width: 100%;
}

.offer-container {
  background: var(--color-secondary);
  border-radius: 12px;
  padding: 1.5rem 2rem; /* Aumentado el padding */
  margin-bottom: 1.5rem;
  border: 1px solid var(--color-focus);
  transition: all 0.3s ease;
  width: 100%;
}

.offer-container:hover {
  border-color: var(--color-text);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.offer-title {
  font-size: 1.4rem; /* Aumentado el tamaño */
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 0.75rem;
}

.offer-description {
  font-size: 1.1rem; /* Aumentado el tamaño */
  line-height: 1.5;
  color: var(--color-text);
  opacity: 0.9;
  margin-bottom: 1.25rem;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(
    auto-fill,
    minmax(300px, 1fr)
  ); /* Aumentado el ancho mínimo de las columnas */
  gap: 1.25rem; /* Aumentado el espacio entre elementos */
  width: 100%;
  margin-bottom: 1.5rem;
}

.product-card {
  background: var(--color-focus);
  border-radius: 10px;
  padding: 1.25rem; /* Aumentado el padding */
  transition: all 0.3s ease;
  border: 1px solid transparent;
  width: 100%;
}

.product-card:hover {
  background: var(--color-darkest);
  border-color: var(--color-text);
  transform: translateY(-3px);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  width: 100%;
}

.product-name {
  margin: 0;
  font-size: 1.1rem; /* Aumentado el tamaño */
  font-weight: 600;
  color: var(--color-text);
}

.product-quantity {
  background: var(--color-darkest);
  color: var(--color-text);
  padding: 0.3rem 0.6rem; /* Aumentado el padding */
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}

.product-footer {
  display: flex;
  justify-content: flex-end;
  width: 100%;
}

.product-price {
  background: var(--color-darkest);
  color: var(--color-text);
  padding: 0.6rem 1rem; /* Aumentado el padding */
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.1rem; /* Aumentado el tamaño */
}

.offer-total {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-focus);
}

.total-label {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text);
  margin-right: 1rem;
}

.total-amount {
  background: var(--color-darkest);
  color: var(--color-text);
  padding: 0.6rem 1rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.2rem;
  border: 2px solid var(--color-focus);
}

.action-footer {
  padding: 1.5rem 2.5rem; /* Aumentado el padding horizontal */
  background: var(--color-secondary);
  border-top: 2px solid var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.cart-total {
  display: flex;
  align-items: center;
}

.action-button {
  background: var(--color-darkest);
  color: var(--color-text);
  border: none;
  padding: 0.9rem 2rem; /* Aumentado el padding */
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.1rem; /* Aumentado el tamaño */
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid var(--color-focus);
  min-width: 150px; /* Asegura un ancho mínimo para el botón */
}

.action-button:hover:not(:disabled) {
  background: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

.action-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  background: var(--color-focus);
}

@media (max-width: 992px) {
  /* Ajustado el breakpoint */
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  }
}

@media (max-width: 768px) {
  .seller-header,
  .offers-container,
  .action-footer {
    padding: 1.25rem 1.5rem;
  }

  .seller-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .seller-header h3 {
    font-size: 1.4rem;
  }

  .offer-title {
    font-size: 1.2rem;
  }

  .products-grid {
    grid-template-columns: 1fr;
  }

  .action-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .cart-total {
    width: 100%;
    justify-content: space-between;
  }

  .action-button {
    width: 100%;
    padding: 0.75rem 1.25rem;
  }
}
</style>
