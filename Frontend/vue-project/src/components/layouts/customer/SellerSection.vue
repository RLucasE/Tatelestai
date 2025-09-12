<template>
  <div class="seller-section">
    <div class="seller-header">
      <h3>Vendedor {{ offers[0].establishment_id }}</h3>
      <button
        class="delete-all-offers-btn"
        @click="confirmRemoveAllOffers"
        type="button"
      >
        <i class="fas fa-trash"></i> Eliminar todas las ofertas de este vendedor
      </button>
    </div>
    <div class="offers-container">
      <div
        v-for="offer in offers"
        :key="offer.offer_id"
        class="offer-container"
      >
        <div class="offer-header">
          <div class="offer-title">
            {{ offer.offer_title }}
            <span class="offer-badges">
              <span v-if="isSoldOut(offer)" class="offer-badge badge-soldout">Agotada / Comprada</span>
              <span v-if="isExpired(offer)" class="offer-badge badge-expired">Expirada</span>
              <span v-if="quantityExceedsMax(offer)" class="offer-badge badge-exceeded">Cantidad excede el máximo disponible</span>
            </span>
          </div>
          <div class="offer-actions">
            <div class="offer-quantity-control">
              <button
                class="quantity-btn quantity-decrease"
                @click="decreaseQuantity(offer)"
                :disabled="offer.quantity <= 1 || isUnavailable(offer)"
                type="button"
              >
                <i class="fas fa-minus">-</i>
              </button>
              <input
                type="number"
                :value="offer.quantity"
                min="1"
                @input="updateQuantity(offer, $event.target.value)"
                :disabled="isUnavailable(offer)"
                class="quantity-input"
              />
              <button
                class="quantity-btn quantity-increase"
                @click="increaseQuantity(offer)"
                type="button"
                :disabled="offer.quantity >= offer.offer_max_quantity || isUnavailable(offer) || quantityExceedsMax(offer)"
              >
                <i class="fas fa-plus">+</i>
              </button>
            </div>
            <button
              class="delete-offer-btn"
              @click="removeOffer(offer.offer_id)"
              type="button"
            >
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        </div>
        <div class="offer-description">{{ offer.offer_description }}</div>
        <div v-if="isUnavailable(offer)" class="offer-status">
          <span v-if="isSoldOut(offer)">Esta oferta no tiene stock o ya fue comprada.</span>
          <span v-if="isExpired(offer)">Esta oferta ha expirado.</span>
        </div>
        <div class="products-grid">
          <div
            v-for="product in offer.products"
            :key="product.product_id"
            class="product-card"
          >
            <div class="product-header">
              <h5 class="product-name">{{ product.product_name }}</h5>
              <span class="product-quantity"
                >x{{ product.product_quantity }}</span
              >
            </div>
            <div
              class="product-description"
              v-if="product.product_description"
            >
              {{ product.product_description }}
            </div>
            <div class="product-footer">
              <span class="product-price">${{ product.product_price }}</span>
            </div>
          </div>
        </div>
        <div class="offer-total">
          <span class="total-label">SubTotal:</span>
          <span class="total-amount">${{ calculateOfferTotal(offer) * offer.quantity}}</span>
        </div>
      </div>
    </div>
    <div class="action-footer">
      <div class="cart-total">
        <span class="total-label">Total:</span>
        <span class="total-amount">${{ sellerTotal }}</span>
      </div>
      <button
        class="action-button"
        @click="handlePurchase"
        :disabled="loading || offers.length === 0"
      >
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
const emit = defineEmits(["offerRemoved", "quantityUpdated","removeAllOffers"]);

const calculateOfferTotal = (offer) => {
  return offer.products
    .reduce((total, product) => {
      return total + product.product_price * product.product_quantity;
    }, 0)
    .toFixed(2);
};

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
    await axiosInstance.post("/prepare-purchase", purchaseData);
    alert("¡Compra realizada con éxito!");
    window.location.reload();
  } catch (error) {
    console.error("Error al procesar la compra:", error);
    alert("Error al procesar la compra. Por favor, intente nuevamente.");
  } finally {
    loading.value = false;
  }
};

const removeOffer = (index) => {
  console.log("removeOffer", index);
  emit("offerRemoved", index);
};

const increaseQuantity = (offer) => {
  if(offer.quantity < offer.offer_max_quantity) {
    emit("quantityUpdated", offer, offer.quantity + 1);
  }
};

const decreaseQuantity = (offer) => {
  if (offer.quantity > 1) {
    emit("quantityUpdated", offer, offer.quantity - 1);
  }
};

const updateQuantity = (offer, value) => {
  const newValue = parseInt(value);
  if (isNaN(newValue) || newValue < 1) {
    emit("quantityUpdated", offer, 1);
  } else if (newValue > offer.offer_max_quantity) {
    emit("quantityUpdated", offer, offer.offer_max_quantity);
  } else {
    emit("quantityUpdated", offer, newValue);
  }
};

const confirmRemoveAllOffers = () => {
  const confirmed = confirm("¿Estás seguro de que deseas eliminar todas las ofertas de este vendedor?");
  if (confirmed) {
    console.log("Removing all offers for establishment", props.offers[0].establishment_id);
    emit("removeAllOffers", props.offers[0].establishment_id ?? null);
  }
};

const isExpired = (offer) => {
  if (!offer?.offer_expiration_datetime) return false;
  const now = new Date();
  const expiration = new Date(offer.offer_expiration_datetime);
  return expiration.getTime() < now.getTime();
};

const isSoldOut = (offer) => {
  return offer?.offer_state === 'purchased' || Number(offer?.offer_max_quantity ?? 0) === 0;
};

const quantityExceedsMax = (offer) => {
  return offer.quantity > offer.offer_max_quantity;
};

const isUnavailable = (offer) => isExpired(offer) || isSoldOut(offer);
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
  width: 100%;
  max-width: 1400px;
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

.delete-all-offers-btn {
  background: var(--color-darkest);
  color: var(--color-text);
  cursor: pointer;
  font-size: 0.95rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-weight: 600;
}

.delete-all-offers-btn:hover {
  background: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

.delete-all-offers-btn i {
  margin-right: 0.5rem;
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

.offer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  margin-bottom: 1rem;
}

.offer-title {
  font-size: 1.4rem; /* Aumentado el tamaño */
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

.offer-badges {
  margin-left: 0.75rem;
  display: inline-flex;
  gap: 0.5rem;
}

.offer-badge {
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  border: 1px solid transparent;
}

.badge-exceeded {
  background: var(--color-darkest);
  border-color: var(--color-focus);
  color: var(--color-text);
}

.badge-expired {
  background: var(--color-secondary);
  border-color: var(--color-focus);
  color: var(--color-text);
}

.badge-expired {
  background: var(--color-secondary);
  border-color: var(--color-focus);
  color: var(--color-text);
}

.offer-actions {
  display: flex;
  align-items: center;
}

.offer-quantity-control {
  display: flex;
  align-items: center;
  margin-right: 1rem;
}

.quantity-btn {
  background: var(--color-darkest);
  color: var(--color-text);
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
}

.quantity-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quantity-decrease {
  margin-right: 0.5rem;
}

.quantity-input {
  width: 60px;
  text-align: center;
  font-size: 0.9rem;
  padding: 0.3rem;
  border: 1px solid var(--color-focus);
  border-radius: 6px;
  background: var(--color-secondary);
  color: var(--color-text);
  margin-right: 0.5rem;
  appearance: textfield; /* estándar */
  -moz-appearance: textfield; /* Firefox */
}

.quantity-input::-webkit-outer-spin-button,
.quantity-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.delete-offer-btn {
  background: transparent;
  color: var(--color-darkest);
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
  margin-left: 1rem;
  display: flex;
  align-items: center;
}

.delete-offer-btn i {
  margin-right: 0.3rem;
}

.offer-description {
  font-size: 1.1rem; /* Aumentado el tamaño */
  line-height: 1.5;
  color: var(--color-text);
  opacity: 0.9;
  margin-bottom: 1.25rem;
}

.offer-status {
  margin: 0.5rem 0 1rem;
  color: var(--color-text);
  font-size: 0.95rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  border-left: 3px solid var(--color-focus);
  padding-left: 0.5rem;
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

.product-description {
  font-size: 0.9rem;
  color: var(--color-text);
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
