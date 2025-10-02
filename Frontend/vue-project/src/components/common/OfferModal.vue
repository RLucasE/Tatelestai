<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <!-- Header del modal -->
      <div class="modal-header">
        <h2 class="modal-title">{{ offer.title }}</h2>
        <button class="close-button" @click="closeModal">
          <svg
            width="24"
            height="24"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Contenido del modal -->
      <div class="modal-content">
        <!-- Información del establecimiento -->
        <div class="establishment-info">
          <h3 class="establishment-name">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            {{ offer.establishment_name }}
          </h3>
          <p class="establishment-address" v-if="offer.establishment_address">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            {{ offer.establishment_address }}
          </p>
        </div>

        <!-- Descripción de la oferta -->
        <div class="offer-description">
          <h4>Descripción</h4>
          <p>{{ offer.description }}</p>
        </div>

        <!-- Lista de productos -->
        <div
          class="products-section"
          v-if="offer.products && offer.products.length > 0"
        >
          <h4>Productos incluidos</h4>
          <div class="products-grid">
            <div
              v-for="product in offer.products"
              :key="product.id"
              class="product-card"
            >
              <div class="product-header">
                <h5 class="product-name">{{ product.name }}</h5>
                <span class="product-quantity"
                  >x{{ product.pivot?.quantity || 1 }}</span
                >
              </div>

              <p class="product-description" v-if="product.description">
                {{ product.description }}
              </p>

              <div class="product-footer">
                <div class="product-info">
                  <span class="product-price" v-if="product.pivot?.price">
                    ${{ formatPrice(product.pivot.price) }}
                  </span>
                  <span class="product-expiration" v-if="product.expiration_date">
                    <svg
                      width="14"
                      height="14"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    Caduca: {{ formatDate(product.expiration_date) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Información de precio total -->
        <div class="total-section" v-if="totalPrice > 0">
          <div class="total-price">
            <span class="total-label">Precio total:</span>
            <span class="total-amount">${{ formatPrice(totalPrice) }}</span>
          </div>
        </div>

        <!-- Información de expiración -->
        <div class="expiration-section">
          <div class="expiration-info">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12,6 12,12 16,14"></polyline>
            </svg>
            <div>
              <span class="expiration-label">Válida hasta:</span>
              <span class="expiration-date">{{ formattedDate }}</span>
            </div>
          </div>
        </div>
      </div>
      <!-- Footer del modal -->
      <div class="modal-footer">
        <div
          class="offer-availability"
          v-if="offer.offer_quantity !== undefined"
        >
          <p class="availability-info">
            <span class="quantity-label">Cantidad disponible: </span>
            <span class="quantity-value">{{ offer.offer_quantity }}</span>
          </p>
        </div>
        <div class="quantity-section">
          <label for="quantity">Cantidad:</label>
          <input
            id="quantity"
            type="number"
            min="1"
            placeholder="1"
            v-model.number="quantity"
            @input="validateQuantity"
            @blur="handleBlur"
          />
        </div>
        <button class="action-button primary" @click="handleOfferAction">
          Carrito
        </button>
        <button class="action-button primary" @click="buyOffer">
          Comprar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineEmits, ref } from "vue";

const quantity = ref(1);

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
  isVisible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "offerAction","buyOffer"]);

const validateQuantity = () => {
  if (quantity.value < 0) {
    quantity.value = 1;
  }
};

const handleBlur = () => {
  if (quantity.value <= 0) {
    quantity.value = 1;
  }

  if (quantity.value > props.offer.offer_quantity) {
    quantity.value = props.offer.offer_quantity;
  }
};

const formattedDate = computed(() => {
  if (!props.offer.expiration_date && !props.offer.expiration_datetime)
    return "";

  const date = props.offer.expiration_datetime || props.offer.expiration_date;
  return new Date(date).toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: props.offer.expiration_datetime ? "2-digit" : undefined,
    minute: props.offer.expiration_datetime ? "2-digit" : undefined,
  });
});

const totalPrice = computed(() => {
  if (!props.offer.products) return 0;

  return props.offer.products.reduce((total, product) => {
    const price = product.pivot?.price || 0;
    const quantity = product.pivot?.quantity || 1;
    return total + price * quantity;
  }, 0);
});

const formatPrice = (price) => {
  return new Intl.NumberFormat("es-AR", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(price);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

const closeModal = () => {
  emit("close");
};

const handleOfferAction = () => {
  emit("offerAction", { id: props.offer.id, quantity: quantity.value });
};

const buyOffer = () => {
  emit("buyOffer", { id: props.offer.id, quantity: quantity.value , food_establishment_id: props.offer.food_establishment_id });
}

</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(34, 32, 31, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
  box-sizing: border-box;
}

.modal-container {
  background: var(--color-primary);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(34, 32, 31, 0.5);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: var(--color-text);
}

/* Header */
.modal-header {
  padding: 24px;
  background: var(--color-secondary);
  border-bottom: 2px solid var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.5em;
  font-weight: 600;
  color: var(--color-text);
}

.close-button {
  background: none;
  border: none;
  color: var(--color-text);
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-button:hover {
  background: var(--color-focus);
  transform: scale(1.1);
}

/* Content */
.modal-content {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.establishment-info {
  margin-bottom: 20px;
  padding: 16px;
  background: var(--color-secondary);
  border-radius: 12px;
  border: 1px solid var(--color-focus);
}

.establishment-name {
  margin: 0;
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
  display: flex;
  align-items: center;
  gap: 8px;
}

.establishment-address {
  margin: 4px 0 0 0;
  font-size: 0.9em;
  color: var(--color-text);
  display: flex;
  align-items: center;
  gap: 4px;
}

.offer-description {
  margin-bottom: 24px;
}

.offer-description h4 {
  margin: 0 0 12px 0;
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.offer-description p {
  margin: 0;
  line-height: 1.6;
  color: var(--color-text);
  opacity: 0.9;
}

.offer-availability {
  align-content: center;
}

/* Products */
.products-section h4 {
  margin: 0 0 16px 0;
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.products-grid {
  display: grid;
  gap: 12px;
}

.product-card {
  background: var(--color-secondary);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid var(--color-focus);
  transition: all 0.3s ease;
}

.product-card:hover {
  background: var(--color-darkest);
  border-color: var(--color-text);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.product-name {
  margin: 0;
  font-size: 1em;
  font-weight: 600;
  color: var(--color-text);
}

.product-quantity {
  background: var(--color-focus);
  color: var(--color-text);
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.9em;
  font-weight: 500;
}

.product-description {
  margin: 0 0 12px 0;
  font-size: 0.9em;
  color: var(--color-text);
  opacity: 0.8;
  line-height: 1.4;
}

.product-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
}

.product-price {
  background: var(--color-focus);
  color: var(--color-text);
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1em;
}

.product-expiration {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.9em;
  color: var(--color-text);
}

/* Total */
.total-section {
  margin: 24px 0;
  padding: 16px;
  background: var(--color-darkest);
  border-radius: 12px;
  border: 2px solid var(--color-focus);
}

.total-price {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.total-amount {
  font-size: 1.3em;
  font-weight: 700;
  color: var(--color-text);
  background: var(--color-focus);
  padding: 8px 16px;
  border-radius: 8px;
}

/* Expiration */
.expiration-section {
  margin-top: 20px;
  padding: 16px;
  background: var(--color-secondary);
  border-radius: 12px;
  border: 1px solid var(--color-focus);
}

.expiration-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.expiration-label {
  font-weight: 600;
  color: var(--color-text);
  margin-right: 8px;
}

.expiration-date {
  color: var(--color-text);
  font-weight: 500;
}

/* Footer */
.modal-footer {
  padding: 24px;
  background: var(--color-secondary);
  border-top: 2px solid var(--color-focus);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.action-button {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1em;
}

.action-button.secondary {
  background: var(--color-focus);
  color: var(--color-text);
}

.action-button.secondary:hover {
  background: var(--color-darkest);
  transform: translateY(-2px);
}

.action-button.primary {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 2px solid var(--color-focus);
}

.action-button.primary:hover {
  background: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(34, 32, 31, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 10px;
  }

  .modal-container {
    max-height: 95vh;
  }

  .modal-header,
  .modal-content,
  .modal-footer {
    padding: 16px;
  }

  .modal-footer {
    flex-direction: column;
  }

  .action-button {
    width: 100%;
  }
}

/* Añadir estilos para la sección de cantidad */
.quantity-section {
  margin: 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.quantity-section input {
  width: 60px;
  padding: 4px 8px;
  border-radius: 4px;
  border: 1px solid var(--color-focus);
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Remove spinner buttons for all browsers */
input[type="number"] {
  -webkit-appearance: textfield;
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
