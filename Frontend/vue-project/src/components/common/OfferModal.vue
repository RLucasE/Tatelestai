<script setup>
import { computed, ref, watch } from "vue";
import { useRouter } from "vue-router";
import ReportModal from "./ReportModal.vue";

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

const emit = defineEmits(["close", "offerAction", "buyOffer"]);

const quantity = ref(1);
const showReportModal = ref(false);
const router = useRouter();

// Reiniciar cantidad cada vez que se abre con una oferta
watch(
  () => props.offer,
  () => {
    quantity.value = 1;
  }
);

// Disponibilidad
const availableQuantity = computed(() => {
  return props.offer?.quantity ?? props.offer?.offer_quantity ?? props.offer?.offer_max_quantity;
});

// Nombre y datos del establecimiento
const establishmentName = computed(() => {
  return props.offer?.establishment?.name
    || props.offer?.establishment_name
    || 'Comercio adherido';
});

const establishmentAddress = computed(() => {
  return props.offer?.establishment?.address
    || props.offer?.establishment_address
    || '';
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'CA';
});

// Precios
const unitPrice = computed(() => {
  if (props.offer?.price != null) return Number(props.offer.price);
  if (props.offer?.offer_price != null) return Number(props.offer.offer_price);
  return 0;
});

const totalPrice = computed(() => {
  return unitPrice.value * (quantity.value || 1);
});

const formatPrice = (price) => {
  return new Intl.NumberFormat("es-AR", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(price || 0);
};

// Horario de retiro formateado
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

// Control de cantidad
const handleQuantityInput = (event) => {
  const val = parseInt(event.target.value, 10);
  const max = availableQuantity.value ?? 99;
  if (isNaN(val) || val < 1) {
    quantity.value = 1;
  } else if (val > max) {
    quantity.value = max;
  } else {
    quantity.value = val;
  }
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
      params: { id: establishmentId },
    });
  }
};

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

<template>
  <div
    v-if="isVisible && offer"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm transition-all duration-200"
    @click="closeModal"
  >
    <div
      class="bg-[#2D2438] border border-[#3D3450] rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh] text-[#E8EAF6]"
      @click.stop
    >
      <!-- Cabecera del Modal -->
      <header class="p-5 sm:p-6 pb-4 border-b border-[#3D3450]/60 flex items-start justify-between gap-3 bg-[#2D2438]">
        <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight leading-snug flex-1">
          {{ offer.title }}
        </h2>

        <div class="flex items-center gap-1 shrink-0 -mt-1">
          <!-- Botón Reportar Oferta -->
          <button
            type="button"
            class="text-[#F59E0B]/70 hover:text-[#F59E0B] hover:bg-[#F59E0B]/10 p-2 rounded-xl transition-colors cursor-pointer"
            @click="openReportModal"
            title="Reportar oferta"
            aria-label="Reportar oferta"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </button>

          <!-- Botón Cerrar -->
          <button
            type="button"
            class="text-[#A5A8C2] hover:text-white hover:bg-white/[0.08] p-2 rounded-xl transition-colors cursor-pointer"
            @click="closeModal"
            aria-label="Cerrar modal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </header>

      <!-- Contenido del Modal -->
      <div class="p-5 sm:p-6 overflow-y-auto space-y-4 flex-1">
        <!-- Tarjeta del Establecimiento (Clickable) -->
        <div
          class="bg-[#221C33] border border-[#3D3450] hover:border-[#7C3AED]/60 rounded-xl p-3 sm:p-3.5 flex items-center justify-between gap-3 transition-all cursor-pointer group"
          @click="goToEstablishment"
          title="Ver comercio y más ofertas"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span class="w-8 h-8 rounded-full bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-[#A78BFA] text-xs font-bold flex items-center justify-center shrink-0">
              {{ establishmentInitials }}
            </span>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-white group-hover:text-[#A78BFA] transition-colors truncate">
                <span>{{ establishmentName }}</span>
                <span class="text-xs text-[#A5A8C2] transition-transform group-hover:translate-x-0.5">→</span>
              </div>
              <p v-if="establishmentAddress" class="text-xs text-[#A5A8C2] truncate flex items-center gap-1 mt-0.5">
                <svg class="w-3.5 h-3.5 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="truncate">{{ establishmentAddress }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Descripción de la Oferta -->
        <div v-if="offer.description" class="space-y-1">
          <span class="text-[11px] font-semibold text-[#A5A8C2] uppercase tracking-wider">Descripción</span>
          <p class="text-xs sm:text-sm text-[#CBD5E1] leading-relaxed bg-[#221C33]/50 border border-[#3D3450]/40 p-3.5 rounded-xl">
            {{ offer.description }}
          </p>
        </div>

        <!-- Detalle de la Bolsa Sorpresa -->
        <div class="bg-[#221C33] border border-[#3D3450] rounded-xl p-4 space-y-3">
          <!-- Precios Unitarios -->
          <div class="flex items-baseline justify-between gap-3">
            <div>
              <span class="text-[11px] text-[#A5A8C2] block mb-0.5">Precio por pack:</span>
              <div class="flex items-baseline gap-2">
                <span
                  v-if="offer.minimum_value && Number(offer.minimum_value) > unitPrice"
                  class="text-xs text-[#787596] line-through font-medium"
                >
                  ${{ formatPrice(offer.minimum_value) }}
                </span>
                <span class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                  ${{ formatPrice(unitPrice) }}
                </span>
                <span class="text-xs text-[#A5A8C2] font-normal">c/u</span>
              </div>
            </div>

            <!-- Peso Estimado si existe -->
            <span
              v-if="offer.estimated_weight_kg"
              class="text-xs text-[#A5A8C2] bg-white/[0.04] border border-white/[0.08] px-2.5 py-1 rounded-md"
            >
              ~{{ Number(offer.estimated_weight_kg).toFixed(2) }} kg aprox.
            </span>
          </div>

          <!-- Horario de Retiro con Icono Reloj -->
          <div
            v-if="formattedPickupWindow"
            class="flex items-center gap-2 text-xs text-[#A5A8C2] pt-2 border-t border-[#3D3450]/50"
          >
            <svg class="w-4 h-4 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2"/>
              <polyline points="12 6 12 12 16 14" stroke-width="2"/>
            </svg>
            <span class="truncate">
              Retiro: <strong class="text-white font-medium">{{ formattedPickupWindow }}</strong>
            </span>
          </div>

          <!-- Etiquetas Minimalistas (Categoría / Alérgenos) -->
          <div
            v-if="offer.category || (offer.allergens && offer.allergens.length)"
            class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-[#3D3450]/50"
          >
            <span
              v-if="offer.category"
              class="bg-white/[0.04] border border-white/[0.08] text-[#CBD5E1] text-[10px] font-medium px-2 py-0.5 rounded"
            >
              {{ offer.category }}
            </span>
            <span
              v-for="(alg, idx) in offer.allergens"
              :key="idx"
              class="bg-white/[0.03] text-[#A5A8C2] text-[10px] font-medium px-2 py-0.5 rounded"
            >
              {{ alg }}
            </span>
          </div>
        </div>

        <!-- Selector de Cantidad y Total Calculado -->
        <div class="bg-[#1F1A2C] border border-[#3D3450] rounded-xl p-3.5 flex items-center justify-between gap-3">
          <!-- Control de Cantidad -->
          <div class="space-y-1">
            <span class="text-[11px] text-[#A5A8C2] block">Cantidad a reservar:</span>
            <div class="flex items-center gap-2">
              <div class="inline-flex items-center bg-[#2D2438] border border-[#3D3450] rounded-xl p-0.5 shadow-inner">
                <button
                  type="button"
                  class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white hover:bg-[#3D3450] transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold cursor-pointer"
                  :disabled="quantity <= 1"
                  @click="quantity > 1 && quantity--"
                  aria-label="Disminuir cantidad"
                >
                  −
                </button>
                <input
                  type="number"
                  class="w-8 sm:w-10 text-center bg-transparent text-white font-bold text-xs sm:text-sm focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                  :value="quantity"
                  min="1"
                  :max="availableQuantity ?? 99"
                  @change="handleQuantityInput"
                />
                <button
                  type="button"
                  class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white hover:bg-[#3D3450] transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold cursor-pointer"
                  :disabled="quantity >= (availableQuantity ?? 99)"
                  @click="quantity < (availableQuantity ?? 99) && quantity++"
                  aria-label="Aumentar cantidad"
                >
                  +
                </button>
              </div>
              <span v-if="availableQuantity" class="text-[11px] text-[#787596]">
                (disp: {{ availableQuantity }})
              </span>
            </div>
          </div>

          <!-- Total a Pagar -->
          <div class="text-right">
            <span class="text-[11px] text-[#A5A8C2] block">Precio total:</span>
            <span class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
              ${{ formatPrice(totalPrice) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Pie del Modal con Acciones -->
      <footer class="p-4 sm:p-5 bg-[#1F1A2C] border-t border-[#3D3450] flex flex-wrap sm:flex-nowrap items-center gap-3">
        <!-- Agregar al Carrito -->
        <button
          type="button"
          class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold bg-[#3D3450] hover:bg-[#4E4366] text-white transition-colors flex items-center justify-center gap-2 active:scale-95 cursor-pointer select-none"
          @click="handleOfferAction"
        >
          <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
          <span>Agregar al Carrito</span>
        </button>

        <!-- Comprar Ahora -->
        <button
          type="button"
          class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white transition-all shadow-lg shadow-[#7C3AED]/25 flex items-center justify-center gap-2 active:scale-95 cursor-pointer select-none"
          @click="buyOffer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span>Comprar Ahora</span>
        </button>
      </footer>
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
