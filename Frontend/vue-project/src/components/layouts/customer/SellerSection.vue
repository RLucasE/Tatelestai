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

const emit = defineEmits(["offerRemoved", "quantityUpdated", "removeAllOffers"]);

const loading = ref(false);
const router = useRouter();
const showErrorNotification = ref(false);
const errorMessage = ref('');

// Nombre e iniciales del establecimiento
const establishmentName = computed(() => {
  return props.offers[0]?.establishment_name || 'Comercio adherido';
});

const establishmentAddress = computed(() => {
  return props.offers[0]?.establishment_address || null;
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'TA';
});

// Precios y totales
const calculateOfferPrice = (offer) => {
  if (offer.offer_price != null) return Number(offer.offer_price);
  if (offer.price != null) return Number(offer.price);
  return 0;
};

const totalPacksCount = computed(() => {
  if (!props.offers || !props.offers.length) return 0;
  return props.offers.reduce((sum, offer) => sum + Number(offer.quantity || 1), 0);
});

const sellerTotalNumber = computed(() => {
  if (!props.offers || !props.offers.length) return 0;
  return props.offers.reduce((sum, offer) => {
    return sum + calculateOfferPrice(offer) * Number(offer.quantity || 1);
  }, 0);
});

const sellerTotal = computed(() => {
  return sellerTotalNumber.value.toLocaleString('es-AR');
});

// Ventana horaria de retiro formateada
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

// Validaciones de estado
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

const hasUnavailableOffers = computed(() => {
  if (!props.offers || !props.offers.length) return false;
  return props.offers.some(offer => isUnavailable(offer) || quantityExceedsMax(offer));
});

// Acciones de cantidad y eliminación
const removeOffer = (offerId) => {
  emit("offerRemoved", offerId);
};

const increaseQuantity = (offer) => {
  const max = offer.offer_max_quantity ?? 99;
  if (offer.quantity < max) {
    emit("quantityUpdated", offer, Number(offer.quantity) + 1);
  }
};

const decreaseQuantity = (offer) => {
  if (offer.quantity > 1) {
    emit("quantityUpdated", offer, Number(offer.quantity) - 1);
  }
};

const updateQuantity = (offer, value) => {
  const max = offer.offer_max_quantity ?? 99;
  const newValue = parseInt(value, 10);
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
  const confirmed = window.confirm(`¿Deseas eliminar todas las bolsas de "${establishmentName.value}"?`);
  if (confirmed && establishmentId) {
    emit("removeAllOffers", establishmentId);
  }
};

// Proceso de compra
const handlePurchase = async () => {
  if (loading.value || !props.offers || !props.offers.length || hasUnavailableOffers.value) return;
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

    const response = await axiosInstance.post("/prepare-purchase", purchaseData);

    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(response.data.data));

    router.push({
      name: 'purchase-confirmation',
      params: { token: response.data.data.purchase_token },
    });
  } catch (error) {
    console.error("Error al preparar la compra:", error);
    showErrorNotification.value = true;
    errorMessage.value = error.response?.data?.message || error.response?.data?.error || "Error al preparar la compra. Por favor, intente nuevamente.";

    setTimeout(() => {
      showErrorNotification.value = false;
    }, 5000);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <section class="seller-cart-group bg-[#221C33] border border-[#3D3450] rounded-2xl overflow-hidden shadow-lg transition-all duration-200">
    <!-- Cabecera del Establecimiento -->
    <header class="p-4 sm:p-5 bg-[#2D2438]/80 border-b border-[#3D3450] flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <!-- Avatar circular con iniciales -->
        <span class="w-8 h-8 rounded-full bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-[#A78BFA] text-xs font-bold flex items-center justify-center shrink-0">
          {{ establishmentInitials }}
        </span>
        <div class="min-w-0">
          <h2 class="text-base sm:text-lg font-bold text-white tracking-tight truncate">
            {{ establishmentName }}
          </h2>
          <p v-if="establishmentAddress" class="text-xs text-[#A5A8C2] flex items-center gap-1 truncate mt-0.5">
            <svg class="w-3.5 h-3.5 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="truncate">{{ establishmentAddress }}</span>
          </p>
        </div>
      </div>

      <!-- Botón de Vaciar Local -->
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-[#A5A8C2] hover:text-[#EF4444] hover:bg-[#EF4444]/10 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
        @click="confirmRemoveAllOffers"
        title="Vaciar las bolsas de este comercio"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Vaciar comercio</span>
      </button>
    </header>

    <!-- Lista de Bolsas Sorpresa (patrón CustomerCard) -->
    <div class="p-4 sm:p-5 space-y-3">
      <article
        v-for="offer in offers"
        :key="offer.offer_id"
        class="bg-[#2D2438] border border-[#3D3450] hover:border-[#7C3AED]/50 rounded-xl p-3.5 sm:p-4 transition-all duration-200 shadow-sm flex flex-col gap-2.5"
      >
        <!-- Fila Superior: Título, Badges de Estado y Botón Eliminar -->
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="text-sm sm:text-base font-bold text-white tracking-tight leading-snug">
                {{ offer.offer_title }}
              </h3>

              <!-- Badges de Advertencia de Estado -->
              <span
                v-if="isSoldOut(offer)"
                class="bg-[#EF4444]/15 border border-[#EF4444]/30 text-[#FCA5A5] text-[10px] font-bold px-2 py-0.5 rounded-md"
              >
                Agotada / Sin stock
              </span>
              <span
                v-else-if="isExpired(offer)"
                class="bg-[#EF4444]/15 border border-[#EF4444]/30 text-[#FCA5A5] text-[10px] font-bold px-2 py-0.5 rounded-md"
              >
                Ventana cerrada
              </span>
              <span
                v-else-if="quantityExceedsMax(offer)"
                class="bg-[#F59E0B]/15 border border-[#F59E0B]/30 text-[#FDE68A] text-[10px] font-bold px-2 py-0.5 rounded-md"
              >
                Excede stock (máx. {{ offer.offer_max_quantity }})
              </span>
            </div>

            <!-- Descripción Breve -->
            <p v-if="offer.offer_description" class="text-xs text-[#94A3B8] line-clamp-1 sm:line-clamp-2 mt-1 leading-relaxed">
              {{ offer.offer_description }}
            </p>
          </div>

          <!-- Botón de Eliminar Bolsa -->
          <button
            type="button"
            class="text-[#A5A8C2] hover:text-[#EF4444] hover:bg-[#EF4444]/15 p-1.5 rounded-lg transition-colors shrink-0 cursor-pointer"
            @click="removeOffer(offer.offer_id)"
            aria-label="Eliminar bolsa del carrito"
            title="Eliminar bolsa"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>

        <!-- Metadatos: Horario de Retiro y Etiquetas -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-[#3D3450]/40">
          <!-- Horario de Retiro con Icono Reloj -->
          <div
            v-if="formatPickupWindow(offer)"
            class="flex items-center gap-1.5 text-xs text-[#A5A8C2]"
          >
            <svg class="w-3.5 h-3.5 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2"/>
              <polyline points="12 6 12 12 16 14" stroke-width="2"/>
            </svg>
            <span class="truncate">
              Retiro: <strong class="text-white font-medium">{{ formatPickupWindow(offer) }}</strong>
            </span>
          </div>

          <!-- Etiquetas Minimalistas (Categoría / Alérgenos) -->
          <div v-if="offer.allergens?.length || offer.category" class="flex flex-wrap items-center gap-1">
            <span
              v-if="offer.category"
              class="bg-white/[0.04] border border-white/[0.08] text-[#CBD5E1] text-[10px] font-medium px-1.5 py-0.5 rounded"
            >
              {{ offer.category }}
            </span>
            <span
              v-for="(alg, idx) in (offer.allergens || []).slice(0, 3)"
              :key="idx"
              class="bg-white/[0.03] text-[#A5A8C2] text-[10px] font-medium px-1.5 py-0.5 rounded"
            >
              {{ alg }}
            </span>
          </div>
        </div>

        <!-- Fila Inferior: Selector de Cantidad y Precio Subtotal -->
        <div class="flex items-center justify-between gap-3 pt-2 border-t border-[#3D3450]/40">
          <!-- Selector de Cantidad Compacto -->
          <div class="flex items-center gap-2">
            <div class="inline-flex items-center bg-[#1F1A2C] border border-[#3D3450] rounded-xl p-0.5 shadow-inner">
              <button
                type="button"
                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white hover:bg-[#3D3450] transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold cursor-pointer"
                :disabled="offer.quantity <= 1 || isUnavailable(offer)"
                @click="decreaseQuantity(offer)"
                aria-label="Disminuir cantidad"
              >
                −
              </button>
              <input
                type="number"
                class="w-8 sm:w-10 text-center bg-transparent text-white font-bold text-xs sm:text-sm focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                :value="offer.quantity"
                min="1"
                :max="offer.offer_max_quantity ?? 99"
                :disabled="isUnavailable(offer)"
                @change="updateQuantity(offer, $event.target.value)"
              />
              <button
                type="button"
                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white hover:bg-[#3D3450] transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold cursor-pointer"
                :disabled="offer.quantity >= (offer.offer_max_quantity ?? 99) || isUnavailable(offer) || quantityExceedsMax(offer)"
                @click="increaseQuantity(offer)"
                aria-label="Aumentar cantidad"
              >
                +
              </button>
            </div>
            <span v-if="offer.offer_max_quantity" class="text-[11px] text-[#787596]">
              (disp: {{ offer.offer_max_quantity }})
            </span>
          </div>

          <!-- Precios y Subtotal -->
          <div class="flex flex-col items-end">
            <div class="flex items-center gap-1.5 leading-none">
              <span
                v-if="offer.minimum_value && Number(offer.minimum_value) > calculateOfferPrice(offer)"
                class="text-[10px] text-[#787596] line-through font-medium"
              >
                ${{ Number(offer.minimum_value * (offer.quantity || 1)).toLocaleString('es-AR') }}
              </span>
              <span class="text-[11px] text-[#A5A8C2]">
                ${{ calculateOfferPrice(offer).toLocaleString('es-AR') }} c/u
              </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
              <span class="text-xs text-[#A5A8C2] font-medium">Subtotal:</span>
              <span class="text-base sm:text-lg font-extrabold text-white tracking-tight">
                ${{ Number(calculateOfferPrice(offer) * (offer.quantity || 1)).toLocaleString('es-AR') }}
              </span>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Advertencia de Stock o Disponibilidad -->
    <div
      v-if="hasUnavailableOffers"
      class="mx-4 sm:mx-5 mb-3 p-3 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#FCA5A5] flex items-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <span>Hay packs agotados, expirados o con cantidad superior al stock. Ajusta tu pedido para continuar.</span>
    </div>

    <!-- Pie de Resumen y Checkout del Establecimiento -->
    <footer class="p-4 sm:p-5 bg-[#1F1A2C] border-t border-[#3D3450] flex flex-wrap items-center justify-between gap-4">
      <div class="space-y-1">
        <div class="text-xs text-[#A5A8C2]">
          {{ totalPacksCount }} {{ totalPacksCount === 1 ? 'pack' : 'packs' }} a retirar
        </div>

        <div class="flex items-baseline gap-2">
          <span class="text-xs text-[#A5A8C2] font-medium">Total:</span>
          <span class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
            ${{ sellerTotal }}
          </span>
        </div>
      </div>

      <!-- Botón Principal de Compra por Local -->
      <button
        type="button"
        class="bg-[#7C3AED] hover:bg-[#6D28D9] disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-[#7C3AED]/25 transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 cursor-pointer select-none"
        @click="handlePurchase"
        :disabled="loading || !offers || offers.length === 0 || hasUnavailableOffers"
        :aria-label="`Comprar en ${establishmentName}`"
      >
        <template v-if="loading">
          <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>Preparando pedido...</span>
        </template>
        <template v-else>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
          <span>Comprar en este local</span>
        </template>
      </button>
    </footer>

    <!-- Notificación Flotante de Error -->
    <transition
      enter-active-class="transition ease-out duration-300 transform"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200 transform"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="showErrorNotification"
        class="fixed bottom-6 right-6 z-50 max-w-md bg-[#2D2438] border border-[#EF4444] rounded-2xl p-4 shadow-2xl flex items-start gap-3"
      >
        <div class="w-8 h-8 rounded-full bg-[#EF4444]/20 text-[#EF4444] flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="text-sm font-bold text-white">Error al preparar la compra</h4>
          <p class="text-xs text-[#CBD5E1] mt-0.5">{{ errorMessage }}</p>
        </div>
        <button
          type="button"
          class="text-[#A5A8C2] hover:text-white p-1 rounded-lg transition-colors cursor-pointer"
          @click="showErrorNotification = false"
        >
          &times;
        </button>
      </div>
    </transition>
  </section>
</template>
