<script setup>
import { computed } from "vue";

const props = defineProps({
  offers: {
    type: Array,
    default: () => [],
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
  establishmentsCount: {
    type: Number,
    default: 1,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "offerRemoved",
  "quantityUpdated",
  "removeAllOffers",
  "selectEstablishment",
]);

// Identificador y datos del establecimiento (Figma: EstablishmentInfo)
const establishmentId = computed(() => {
  return props.offers[0]?.establishment_id ?? null;
});

const establishmentName = computed(() => {
  return props.offers[0]?.establishment_name || "Comercio adherido";
});

const establishmentAddress = computed(() => {
  return props.offers[0]?.establishment_address || null;
});

// Precios y cálculos de subtotales
const calculateOfferPrice = (offer) => {
  if (offer.offer_price != null) return Number(offer.offer_price);
  if (offer.price != null) return Number(offer.price);
  return 0;
};

const getOriginalPrice = (offer) => {
  if (offer.minimum_value != null && Number(offer.minimum_value) > 0) {
    return Number(offer.minimum_value);
  }
  const price = calculateOfferPrice(offer);
  return price > 0 ? Math.round(price * 2.5) : 0;
};

const sellerTotalNumber = computed(() => {
  if (!props.offers || !props.offers.length) return 0;
  return props.offers.reduce((sum, offer) => {
    return sum + calculateOfferPrice(offer) * Number(offer.quantity || 1);
  }, 0);
});

const formatPrice = (val) => {
  return Number(val || 0).toLocaleString("es-AR");
};

// Ventana horaria de retiro (Figma: PickupWindowBadge)
const formatPickupWindow = (offer) => {
  const start = offer?.pickup_start_datetime ? new Date(offer.pickup_start_datetime) : null;
  const end = offer?.offer_expiration_datetime || offer?.expiration_datetime
    ? new Date(offer.offer_expiration_datetime || offer.expiration_datetime)
    : null;
  if (!start && !end) return "Hoy en horario comercial";

  if (start && end) {
    const isToday = start.toDateString() === new Date().toDateString();
    const dateLabel = isToday ? "Hoy" : start.toLocaleDateString("es-AR", { day: "numeric", month: "short" });
    const startTime = start.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    const endTime = end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    return `${dateLabel} ${startTime} a ${endTime} hs`;
  }

  if (end) {
    return `Hasta ${end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })} hs`;
  }

  return "Hoy en horario comercial";
};

const establishmentPickupWindow = computed(() => {
  if (!props.offers || !props.offers.length) return "Hoy";
  return formatPickupWindow(props.offers[0]);
});

// CountdownBadge (Figma variants: Tone=Danger, Tone=Warning, Tone=Neutral)
const countdownData = computed(() => {
  const first = props.offers[0];
  const start = first?.pickup_start_datetime ? new Date(first.pickup_start_datetime).getTime() : null;
  const exp = first?.offer_expiration_datetime || first?.expiration_datetime;
  if (!exp) return { label: "Disponible", tone: "neutral" };

  const now = Date.now();
  const expTime = new Date(exp).getTime();
  const diffMinutes = Math.round((expTime - now) / 60000);

  if (diffMinutes <= 0) {
    return { label: "Cerrado", tone: "danger" };
  }

  if (start && start > now) {
    const startDiffMinutes = Math.round((start - now) / 60000);
    if (startDiffMinutes < 60) {
      return { label: `Abre en ${startDiffMinutes}m`, tone: "neutral" };
    }
    const hours = Math.floor(startDiffMinutes / 60);
    const mins = startDiffMinutes % 60;
    return {
      label: mins > 0 ? `Abre en ${hours}h ${mins}m` : `Abre en ${hours}h`,
      tone: "neutral",
    };
  }

  if (diffMinutes < 60) {
    return { label: `Cierra en ${diffMinutes}m`, tone: "danger" };
  }

  const hours = Math.floor(diffMinutes / 60);
  return { label: `Quedan ${hours}h`, tone: "warning" };
});

const countdownToneClass = computed(() => {
  switch (countdownData.value.tone) {
    case "danger":
      return "text-brand"; // Tone=Danger (#f24f13)
    case "warning":
      return "text-warning"; // Tone=Warning (#f2921d)
    case "neutral":
    default:
      return "text-content-secondary"; // Tone=Neutral (#8082a6)
  }
});

// Disponibilidad y stock
const isExpired = (offer) => {
  const exp = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  if (!exp) return false;
  return new Date(exp).getTime() < Date.now();
};

const isSoldOut = (offer) => {
  return offer?.offer_state === "purchased" || Number(offer?.offer_max_quantity ?? 1) === 0;
};

const quantityExceedsMax = (offer) => {
  if (offer.offer_max_quantity == null) return false;
  return Number(offer.quantity) > Number(offer.offer_max_quantity);
};

const isUnavailable = (offer) => isExpired(offer) || isSoldOut(offer);

const hasUnavailableOffers = computed(() => {
  if (!props.offers || !props.offers.length) return false;
  return props.offers.some((offer) => isUnavailable(offer) || quantityExceedsMax(offer));
});

// Tags / Chips helper (Figma: PillBadge Color=Amarillo vs Color=Sin color)
const isSpecialTag = (tag) => {
  const t = String(tag).toLowerCase();
  return t.includes("vegano") || t.includes("sin tacc") || t.includes("vegetariano");
};

// Acciones sobre ofertas
const removeOffer = (offerId) => {
  emit("offerRemoved", offerId);
};

const increaseQuantity = (offer) => {
  const max = offer.offer_max_quantity ?? 99;
  if (Number(offer.quantity) < max) {
    emit("quantityUpdated", offer, Number(offer.quantity) + 1);
  }
};

const decreaseQuantity = (offer) => {
  if (Number(offer.quantity) > 1) {
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

const handleSelect = () => {
  if (establishmentId.value) {
    emit("selectEstablishment", establishmentId.value);
  }
};
</script>

<template>
  <section
    class="store-cart-card bg-surface border border-border-subtle rounded-2xl overflow-hidden shadow-card transition-all duration-200"
    @click="handleSelect"
  >
    <!-- StoreHeader (Figma node 2013:837) -->
    <header class="bg-surface px-6 py-4 flex flex-wrap sm:flex-nowrap items-center justify-between gap-3">
      <!-- NameAndAddress (Figma node 2013:841) -->
      <div class="flex flex-col min-w-0 flex-1">
        <h2 class="text-sm font-bold text-content-strong truncate">
          {{ establishmentName }}
        </h2>
        <p v-if="establishmentAddress" class="text-xs text-content-secondary font-normal truncate mt-0.5">
          {{ establishmentAddress }}
        </p>
      </div>

      <!-- PickupWindowBadge (Figma node 2013:844) -->
      <div class="inline-flex items-center gap-2.5 bg-surface-sunken border border-border-subtle rounded-full px-3 py-1 shrink-0">
        <span class="text-xs font-medium text-content">
          Retiro: {{ establishmentPickupWindow }}
        </span>
        <!-- CountdownBadge (Figma node 2013:846) -->
        <span class="text-xs font-semibold" :class="countdownToneClass">
          {{ countdownData.label }}
        </span>
      </div>
    </header>

    <!-- CartItemsList (Figma node 2013:848) -->
    <div class="px-6 py-2">
      <template v-for="(offer, idx) in offers" :key="offer.offer_id">
        <!-- ItemDivider (Figma node 2013:876) -->
        <div v-if="idx > 0" class="h-px bg-border-subtle my-0"></div>

        <!-- CartItem (Figma node 2013:849 & 2013:877) -->
        <article class="py-4 px-1 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
          <!-- PackDetails (Figma node 2013:850) -->
          <div class="min-w-0 flex-1 space-y-1.5">
            <!-- TitleAndTags (Figma node 2013:851) -->
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="text-base font-bold text-content-strong tracking-tight">
                {{ offer.offer_title }}
              </h3>

              <!-- PillBadges (Figma PillBadge instances: h=30, text=13px, rounded-full) -->
              <span
                v-if="offer.category"
                class="bg-surface border border-content-secondary/40 text-content text-[13px] font-medium px-3.5 py-1 rounded-full select-none capitalize"
              >
                {{ offer.category }}
              </span>

              <span
                v-for="(tag, tagIdx) in (offer.allergens || []).slice(0, 3)"
                :key="tagIdx"
                class="text-[13px] font-medium px-3.5 py-1 rounded-full select-none capitalize"
                :class="[
                  isSpecialTag(tag)
                    ? 'bg-rescue border border-rescue text-content'
                    : 'bg-surface border border-content-secondary/40 text-content'
                ]"
              >
                {{ tag }}
              </span>

              <!-- Status Alert Badges -->
              <span
                v-if="isSoldOut(offer)"
                class="bg-danger/10 border border-danger/30 text-danger text-[11px] font-bold px-3 py-0.5 rounded-full"
              >
                Agotada
              </span>
              <span
                v-else-if="isExpired(offer)"
                class="bg-danger/10 border border-danger/30 text-danger text-[11px] font-bold px-3 py-0.5 rounded-full"
              >
                Ventana cerrada
              </span>
              <span
                v-else-if="quantityExceedsMax(offer)"
                class="bg-warning/15 border border-warning/30 text-brand text-[11px] font-bold px-3 py-0.5 rounded-full"
              >
                Máx. {{ offer.offer_max_quantity }}
              </span>
            </div>

            <!-- Description (Figma node 2013:857) -->
            <p
              v-if="offer.offer_description"
              class="text-[13px] text-content-secondary font-normal line-clamp-2 max-w-xl leading-snug"
            >
              {{ offer.offer_description }}
            </p>

            <!-- StockAndUnitMeta (Figma node 2013:858) -->
            <div class="flex items-center gap-3 text-xs">
              <span
                v-if="Number(offer.quantity) > 1"
                class="text-content-secondary font-normal"
              >
                Precio unitario: $ {{ formatPrice(calculateOfferPrice(offer)) }}
              </span>
              <span v-if="offer.offer_max_quantity" class="font-medium text-warning">
                {{ offer.offer_max_quantity }} disponibles
              </span>
              <span v-if="offer.estimated_weight_kg" class="text-content-muted">
                • ~{{ offer.estimated_weight_kg }} kg
              </span>
            </div>
          </div>

          <!-- QuantityAndPriceControls (Figma node 2013:861) -->
          <div class="flex items-center gap-6 shrink-0 justify-between sm:justify-end">
            <!-- StepperColumn (Figma node 2013:862) -->
            <div class="flex flex-col items-center gap-1">
              <!-- QuantityStepper (Figma node 2013:863, 100x34px, rounded-lg, #f2ece4) -->
              <div
                class="inline-flex items-center h-[34px] w-[100px] bg-surface-sunken border border-border-subtle rounded-lg overflow-hidden select-none"
              >
                <button
                  type="button"
                  class="w-8 h-full flex items-center justify-center text-[15px] font-semibold transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
                  :class="offer.quantity <= 1 ? 'text-content-secondary' : 'text-content-strong hover:bg-border-subtle/30'"
                  :disabled="offer.quantity <= 1 || isUnavailable(offer)"
                  @click.stop="decreaseQuantity(offer)"
                  aria-label="Disminuir cantidad"
                >
                  −
                </button>
                <input
                  type="number"
                  class="w-8.5 h-full text-center bg-transparent text-content-strong font-bold text-sm tabular-nums focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                  :value="offer.quantity"
                  min="1"
                  :max="offer.offer_max_quantity ?? 99"
                  :disabled="isUnavailable(offer)"
                  @change.stop="updateQuantity(offer, $event.target.value)"
                />
                <button
                  type="button"
                  class="w-8 h-full flex items-center justify-center text-[15px] font-semibold text-content-strong hover:bg-border-subtle/30 transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
                  :disabled="offer.quantity >= (offer.offer_max_quantity ?? 99) || isUnavailable(offer) || quantityExceedsMax(offer)"
                  @click.stop="increaseQuantity(offer)"
                  aria-label="Aumentar cantidad"
                >
                  +
                </button>
              </div>

              <!-- Quitar Link (Figma node 2013:870) -->
              <button
                type="button"
                class="text-[11px] font-medium text-content-secondary hover:text-danger transition-colors cursor-pointer select-none"
                @click.stop="removeOffer(offer.offer_id)"
              >
                Quitar
              </button>
            </div>

            <!-- ItemPriceColumn (Figma node 2013:871) -->
            <div class="text-right min-w-[100px]">
              <!-- PriceLine (Figma node 2013:872) -->
              <div class="flex items-baseline justify-end gap-1.5">
                <span
                  v-if="getOriginalPrice(offer) * Number(offer.quantity || 1) > calculateOfferPrice(offer) * Number(offer.quantity || 1)"
                  class="text-xs font-normal text-content-secondary line-through tabular-nums"
                >
                  $ {{ formatPrice(getOriginalPrice(offer) * Number(offer.quantity || 1)) }}
                </span>
                <span class="text-lg font-bold text-content-strong tracking-tight tabular-nums">
                  $ {{ formatPrice(calculateOfferPrice(offer) * Number(offer.quantity || 1)) }}
                </span>
              </div>
            </div>
          </div>
        </article>
      </template>
    </div>

    <!-- Advertencia de Stock Si Aplica -->
    <div
      v-if="hasUnavailableOffers"
      class="mx-6 mb-4 p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger flex items-center gap-2 font-medium"
    >
      <svg class="w-4 h-4 shrink-0 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <span>Hay bolsas con stock insuficiente o ventana cerrada. Ajusta tu pedido para continuar.</span>
    </div>

    <!-- StoreSubtotalFooter (Figma node 2013:904) -->
    <footer class="bg-surface-sunken px-6 py-3 border-t border-border-subtle flex items-center justify-end">
      <!-- StoreSubtotalRight (Figma node 2013:908) -->
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-content-secondary">
          Subtotal {{ establishmentName }}:
        </span>
        <span class="text-[15px] font-bold text-content-strong tabular-nums">
          $ {{ formatPrice(sellerTotalNumber) }}
        </span>
      </div>
    </footer>
  </section>
</template>
