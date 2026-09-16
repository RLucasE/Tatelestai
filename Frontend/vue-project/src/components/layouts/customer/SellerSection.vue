<template>
  <div class="seller-section">
    <div class="seller-header" v-if="offers && offers.length > 0">
      <div class="establishment-info">
        <h2 class="establishment-name">{{ offers[0]?.establishment_name || 'Comercio adherido' }}</h2>
        <h3 class="establishment-address" v-if="offers[0]?.establishment_address">{{ offers[0]?.establishment_address }}</h3>
      </div>
      <button
        class="delete-all-offers-btn"
        @click="confirmRemoveAllOffers"
        type="button"
        title="Vaciar ofertas de este comercio"
      >
        <svg class="w-4 h-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Eliminar todas</span>
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
              <span v-if="quantityExceedsMax(offer)" class="offer-badge badge-exceeded">Cantidad excede el stock</span>
            </span>
          </div>
          <div class="offer-actions">
            <div class="offer-quantity-control">
              <button
                class="quantity-btn quantity-decrease"
                @click="decreaseQuantity(offer)"
                :disabled="offer.quantity <= 1 || isUnavailable(offer)"
                type="button"
                aria-label="Disminuir cantidad"
              >
                <span>-</span>
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
                :disabled="offer.quantity >= (offer.offer_max_quantity ?? 99) || isUnavailable(offer) || quantityExceedsMax(offer)"
                aria-label="Aumentar cantidad"
              >
                <span>+</span>
              </button>
            </div>
            <button
              class="delete-offer-btn"
              @click="removeOffer(offer.offer_id)"
              type="button"
              aria-label="Eliminar oferta"
            >
              <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>Eliminar</span>
            </button>
          </div>
        </div>
        <div class="offer-description" v-if="offer.offer_description">{{ offer.offer_description }}</div>
        <div v-if="isUnavailable(offer)" class="offer-status">
          <span v-if="isSoldOut(offer)">Esta oferta no tiene stock o ya fue comprada.</span>
          <span v-if="isExpired(offer)">Esta oferta ha expirado.</span>
        </div>

        <!-- Detalle de Pack Sorpresa (cuando no tiene lista de productos individuales) -->
        <div v-if="!offer.products || offer.products.length === 0" class="pack-meta-row flex flex-wrap items-center justify-between gap-2 py-2 my-1 border-t border-b border-[#3D3450]/40">
          <div class="flex items-baseline gap-2">
            <span
              v-if="offer.minimum_value && Number(offer.minimum_value) > calculateOfferPrice(offer)"
              class="text-xs text-[#787596] line-through font-medium"
            >
              ${{ Number(offer.minimum_value).toLocaleString('es-AR') }}
            </span>
            <span class="text-sm font-bold text-white">
              ${{ calculateOfferPrice(offer).toLocaleString('es-AR') }} <span class="text-xs text-[#A5A8C2] font-normal">c/u</span>
            </span>
          </div>

          <div v-if="formatPickupWindow(offer)" class="flex items-center gap-1.5 text-xs text-[#F59E0B] bg-[#221C33] px-2.5 py-1 rounded-lg border border-[#3D3450]">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2"/>
              <polyline points="12 6 12 12 16 14" stroke-width="2"/>
            </svg>
            <span>Retiro: <strong>{{ formatPickupWindow(offer) }}</strong></span>
          </div>
        </div>

        <!-- Grilla de productos (solo si existen productos individuales) -->
        <div class="products-grid" v-if="offer.products && offer.products.length > 0">
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
            <div
              class="product-expiration"
              v-if="product.product_expiration_date"
            >
              <span class="expiration-label">Vence:</span>
              <span class="expiration-date">{{ formatExpirationDate(product.product_expiration_date) }}</span>
            </div>
            <div class="product-footer">
              <span class="product-price">${{ Number(product.product_price).toLocaleString('es-AR') }}</span>
            </div>
          </div>
        </div>

        <div class="offer-total">
          <span class="total-label">SubTotal:</span>
          <span class="total-amount">${{ Number(calculateOfferPrice(offer) * (offer.quantity || 1)).toLocaleString('es-AR') }}</span>
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
        :disabled="loading || !offers || offers.length === 0"
      >
        {{ loading ? "Procesando..." : "Comprar" }}
      </button>
    </div>

    <!-- Notificación de error -->
    <div v-if="showErrorNotification" class="notification error">
      <div class="notification-content">
        <div class="notification-text">
          <h4>Error al preparar la compra</h4>
          <p>{{ errorMessage }}</p>
        </div>
        <button @click="showErrorNotification = false" class="notification-close" type="button">
          &times;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  offers: {
    type: Array,
    default: () => [],
  },
});

const loading = ref(false);
const router = useRouter();
const showErrorNotification = ref(false);
const errorMessage = ref('');
const emit = defineEmits(["offerRemoved", "quantityUpdated","removeAllOffers"]);

const calculateOfferPrice = (offer) => {
  if (offer.offer_price != null) return Number(offer.offer_price);
  if (offer.price != null) return Number(offer.price);
  if (Array.isArray(offer.products) && offer.products.length > 0) {
    return offer.products.reduce((total, product) => {
      return total + Number(product.product_price || 0) * Number(product.product_quantity || 1);
    }, 0);
  }
  return 0;
};

const calculateOfferTotal = (offer) => {
  return calculateOfferPrice(offer);
};

const sellerTotal = computed(() => {
  if (!props.offers || !props.offers.length) return '0';
  const total = props.offers.reduce((sum, offer) => {
    return sum + calculateOfferPrice(offer) * Number(offer.quantity || 1);
  }, 0);
  return total.toLocaleString('es-AR');
});

const formatPickupWindow = (offer) => {
  const start = offer.pickup_start_datetime ? new Date(offer.pickup_start_datetime) : null;
  const end = (offer.offer_expiration_datetime || offer.expiration_datetime)
    ? new Date(offer.offer_expiration_datetime || offer.expiration_datetime)
    : null;
  if (!start && !end) return '';

  if (start && end) {
    const isToday = start.toDateString() === new Date().toDateString();
    const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
    const startTime = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    return `${dateLabel}, ${startTime} - ${endTime} hs`;
  }

  if (end) {
    return `Hasta ${end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })} hs`;
  }

  return '';
};

const handlePurchase = async () => {
  if (loading.value || !props.offers || !props.offers.length) return;
  try {
    loading.value = true;
    const establishmentId = props.offers[0]?.establishment_id;
    if (!establishmentId) {
      throw new Error("No se encontró el identificador del establecimiento");
    }

    const purchaseData = {
      offers: props.offers.map((offer) => ({
        id: offer.offer_id,
        quantity: offer.quantity,
      })),
      food_establishment_id: establishmentId,
    };

    // Llamar a preparePurchase en lugar de comprar directamente
    const response = await axiosInstance.post("/prepare-purchase", purchaseData);

    // Guardar los datos de confirmación en sessionStorage
    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(response.data.data));

    // Redirigir a la vista de confirmación con el token
    router.push({
      name: 'purchase-confirmation',
      params: { token: response.data.data.purchase_token }
    });

  } catch (error) {
    console.error("Error al preparar la compra:", error);

    // Mostrar notificación de error
    showErrorNotification.value = true;
    errorMessage.value = error.response?.data?.message || error.response?.data?.error || "Error al preparar la compra. Por favor, intente nuevamente.";

    setTimeout(() => {
      showErrorNotification.value = false;
    }, 5000);
  } finally {
    loading.value = false;
  }
};

const removeOffer = (index) => {
  emit("offerRemoved", index);
};

const increaseQuantity = (offer) => {
  const max = offer.offer_max_quantity ?? 99;
  if(offer.quantity < max) {
    emit("quantityUpdated", offer, offer.quantity + 1);
  }
};

const decreaseQuantity = (offer) => {
  if (offer.quantity > 1) {
    emit("quantityUpdated", offer, offer.quantity - 1);
  }
};

const updateQuantity = (offer, value) => {
  const max = offer.offer_max_quantity ?? 99;
  const newValue = parseInt(value);
  if (isNaN(newValue) || newValue < 1) {
    emit("quantityUpdated", offer, 1);
  } else if (newValue > max) {
    emit("quantityUpdated", offer, max);
  } else {
    emit("quantityUpdated", offer, newValue);
  }
};

const confirmRemoveAllOffers = () => {
  if (!props.offers || !props.offers.length) return;
  const establishmentId = props.offers[0]?.establishment_id;
  const confirmed = confirm("¿Estás seguro de que deseas eliminar todas las ofertas de este vendedor?");
  if (confirmed && establishmentId) {
    emit("removeAllOffers", establishmentId);
  }
};

const isExpired = (offer) => {
  const exp = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  if (!exp) return false;
  const now = new Date();
  const expiration = new Date(exp);
  return expiration.getTime() < now.getTime();
};

const isSoldOut = (offer) => {
  return offer?.offer_state === 'purchased' || Number(offer?.offer_max_quantity ?? 1) === 0;
};

const quantityExceedsMax = (offer) => {
  if (offer.offer_max_quantity == null) return false;
  return offer.quantity > offer.offer_max_quantity;
};

const isUnavailable = (offer) => isExpired(offer) || isSoldOut(offer);

const formatExpirationDate = (dateString) => {
  if (!dateString) return '';
  const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', options);
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
  padding: 1.5rem 2.5rem;
  background: var(--color-secondary);
  border-bottom: 2px solid var(--color-focus);
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.establishment-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

.establishment-name {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.establishment-address {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 500;
  color: var(--color-text);
  opacity: 0.8;
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

.product-expiration {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--color-text);
  margin-top: 0.5rem;
}

.product-expiration i {
  font-size: 1rem;
  color: var(--color-focus);
}

.expiration-label {
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

.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  max-width: 400px;
  border-radius: 12px;
  padding: 1rem;
  animation: slideIn 0.5s ease;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.notification.error {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 2px solid var(--color-focus);
}

.notification-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.notification-content i {
  font-size: 1.5rem;
  color: var(--color-text);
  margin-top: 0.2rem;
}

.notification-text {
  flex: 1;
}

.notification-text h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  color: var(--color-text);
  font-weight: 700;
}

.notification-text p {
  margin: 0;
  color: var(--color-text);
  opacity: 0.9;
  font-size: 0.9rem;
}

.notification-close {
  background: transparent;
  border: none;
  color: var(--color-text);
  cursor: pointer;
  font-size: 1rem;
  padding: 0.2rem;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.notification-close:hover {
  background: var(--color-focus);
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
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

  .establishment-name {
    font-size: 1.4rem;
  }

  .establishment-address {
    font-size: 1rem;
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
