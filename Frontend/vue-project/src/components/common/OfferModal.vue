<template>
  <div v-if="isVisible && offer" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <!-- Header del modal -->
      <div class="modal-header">
        <h2 class="modal-title">{{ offer.title }}</h2>
        <div class="header-actions">
          <button
            class="report-button"
            @click="openReportModal"
            title="Reportar oferta"
            type="button"
          >
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </button>
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
      </div>

      <!-- Contenido del modal -->
      <div class="modal-content">
        <!-- Información del establecimiento -->
        <div class="establishment-info">
          <h3
            class="establishment-name clickable"
            @click="goToEstablishment"
            title="Ver más ofertas de este establecimiento"
          >
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
            {{ offer.establishment?.name || offer.establishment_name || 'Comercio adherido' }}
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
              class="arrow-icon"
            >
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </h3>
          <p class="establishment-address" v-if="offer.establishment?.address || offer.establishment_address">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            {{ offer.establishment?.address || offer.establishment_address }}
          </p>
        </div>

        <!-- Descripción de la oferta -->
        <div class="offer-description" v-if="offer.description">
          <h4>Descripción</h4>
          <p>{{ offer.description }}</p>
        </div>

        <!-- Detalle del pack cuando no tiene productos individuales -->
        <div v-if="!offer.products || offer.products.length === 0" class="pack-info-box my-3 p-4 bg-[#221C33] rounded-xl border border-[#3D3450]">
          <div class="flex items-center justify-between gap-3 mb-2">
            <div>
              <span v-if="offer.minimum_value && Number(offer.minimum_value) > Number(offer.price || offer.offer_price)" class="text-xs text-[#787596] line-through font-medium mr-2">
                ${{ formatPrice(offer.minimum_value) }}
              </span>
              <span class="text-lg font-extrabold text-white">
                ${{ formatPrice(offer.price || offer.offer_price) }}
              </span>
            </div>
            <span v-if="discountPercentage > 0" class="bg-[#10B981] text-white text-xs font-bold px-2.5 py-0.5 rounded-full">
              -{{ discountPercentage }}% OFF
            </span>
          </div>

          <div v-if="formattedPickupWindow" class="flex items-center gap-2 text-xs text-[#F59E0B]">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2"/>
              <polyline points="12 6 12 12 16 14" stroke-width="2"/>
            </svg>
            <span>Retiro: <strong class="text-white">{{ formattedPickupWindow }}</strong></span>
          </div>

          <div v-if="offer.allergens && offer.allergens.length" class="flex flex-wrap gap-1.5 mt-2 pt-2 border-t border-[#3D3450]/60">
            <span v-for="(alg, idx) in offer.allergens" :key="idx" class="text-[10px] bg-[#1F1A2C] text-[#CBD5E1] px-2 py-0.5 rounded">
              {{ alg }}
            </span>
          </div>
        </div>

        <!-- Lista de productos individuales (si existen) -->
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
        <div class="expiration-section" v-if="formattedDate">
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
          v-if="availableQuantity !== undefined"
        >
          <p class="availability-info">
            <span class="quantity-label">Disponibles: </span>
            <span class="quantity-value">{{ availableQuantity }}</span>
          </p>
        </div>
        <div class="quantity-section">
          <label for="quantity">Cantidad:</label>
          <input
            id="quantity"
            type="number"
            min="1"
            :max="availableQuantity ?? 99"
            placeholder="1"
            v-model.number="quantity"
            @input="validateQuantity"
            @blur="handleBlur"
          />
        </div>
        <button class="action-button secondary" @click="handleOfferAction">
          Agregar al Carrito
        </button>
        <button class="action-button primary" @click="buyOffer">
          Comprar Ahora
        </button>
      </div>
    </div>

    <!-- Modal de Reporte -->
    <ReportModal
      :is-visible="showReportModal"
      reportable-type="offer"
      :reportable-id="offer.id"
      @close="closeReportModal"
      @success="handleReportSuccess"
    />
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import ReportModal from "./ReportModal.vue";

const quantity = ref(1);
const showReportModal = ref(false);
const router = useRouter();

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

const availableQuantity = computed(() => {
  return props.offer?.quantity ?? props.offer?.offer_quantity ?? props.offer?.offer_max_quantity;
});

const discountPercentage = computed(() => {
  const price = Number(props.offer?.price || props.offer?.offer_price || 0);
  const minVal = Number(props.offer?.minimum_value || 0);
  if (!price || !minVal || minVal <= price) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const formattedPickupWindow = computed(() => {
  const start = props.offer?.pickup_start_datetime ? new Date(props.offer.pickup_start_datetime) : null;
  const end = (props.offer?.expiration_datetime || props.offer?.offer_expiration_datetime)
    ? new Date(props.offer.expiration_datetime || props.offer.offer_expiration_datetime)
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
});

const validateQuantity = () => {
  if (quantity.value < 1) {
    quantity.value = 1;
  }
};

const handleBlur = () => {
  if (!quantity.value || quantity.value <= 0) {
    quantity.value = 1;
  }

  const max = availableQuantity.value;
  if (max !== undefined && quantity.value > max) {
    quantity.value = max;
  }
};

const formattedDate = computed(() => {
  if (!props.offer?.expiration_date && !props.offer?.expiration_datetime && !props.offer?.offer_expiration_datetime)
    return "";

  const date = props.offer.expiration_datetime || props.offer.offer_expiration_datetime || props.offer.expiration_date;
  return new Date(date).toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
});

const totalPrice = computed(() => {
  const unitPrice = props.offer?.price != null
    ? Number(props.offer.price)
    : (props.offer?.offer_price != null ? Number(props.offer.offer_price) : 0);

  if (unitPrice > 0) {
    return unitPrice * (quantity.value || 1);
  }

  if (Array.isArray(props.offer?.products) && props.offer.products.length > 0) {
    return props.offer.products.reduce((total, product) => {
      const price = product.pivot?.price || product.product_price || 0;
      const q = product.pivot?.quantity || product.product_quantity || 1;
      return total + Number(price) * Number(q);
    }, 0) * (quantity.value || 1);
  }

  return 0;
});

const formatPrice = (price) => {
  return new Intl.NumberFormat("es-AR", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(price || 0);
};

const formatDate = (date) => {
  if (!date) return "";
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
  emit("offerAction", {
    id: props.offer?.id || props.offer?.offer_id,
    quantity: quantity.value,
  });
  closeModal();
};

const buyOffer = () => {
  const establishmentId = props.offer?.food_establishment_id
    || props.offer?.establishment?.id
    || props.offer?.establishment_id;

  emit("buyOffer", {
    id: props.offer?.id || props.offer?.offer_id,
    quantity: quantity.value,
    food_establishment_id: establishmentId,
  });
};

const goToEstablishment = () => {
  const establishmentId = props.offer?.food_establishment_id
    || props.offer?.establishment?.id
    || props.offer?.establishment_id;

  if (establishmentId) {
    closeModal();
    router.push({
      name: 'establishment-view',
      params: { id: establishmentId }
    });
  }
};

// Funciones para el modal de reporte
const openReportModal = () => {
  showReportModal.value = true;
};

const closeReportModal = () => {
  showReportModal.value = false;
};

const handleReportSuccess = () => {
  console.log('Reporte enviado exitosamente');
};

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

.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.report-button {
  background: none;
  border: none;
  color: #f59e0b;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.report-button:hover {
  background: rgba(245, 158, 11, 0.1);
  transform: scale(1.1);
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

.establishment-name.clickable {
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 8px;
  margin: -8px;
  border-radius: 8px;
}

.establishment-name.clickable:hover {
  color: var(--color-text);
  background: var(--color-focus);
}

.establishment-name.clickable .arrow-icon {
  transition: transform 0.3s ease;
}

.establishment-name.clickable:hover .arrow-icon {
  transform: translateX(4px);
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
