<script setup>
import { computed } from "vue";
import { RouterLink } from "vue-router";

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
  "toggleSelect",
]);

// Identificador y datos del establecimiento (Figma: EstablishmentInfo)
const establishmentId = computed(() => {
  return props.offers[0]?.establishment_id ?? props.offers[0]?.food_establishment_id ?? null;
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

const getScheduleKey = (offer) => {
  const start = offer?.pickup_start_datetime ? new Date(offer.pickup_start_datetime).toISOString().slice(0, 16) : "no-start";
  const endRaw = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  const end = endRaw ? new Date(endRaw).toISOString().slice(0, 16) : "no-end";
  return `${start}|${end}`;
};

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

const getGroupStatusBadge = (offer) => {
  const exp = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  const start = offer?.pickup_start_datetime ? new Date(offer.pickup_start_datetime).getTime() : null;
  if (!exp) return null;

  const now = Date.now();
  const expTime = new Date(exp).getTime();
  const diffMinutes = Math.round((expTime - now) / 60000);

  if (diffMinutes <= 0) {
    return {
      label: "Finalizado",
      classes: "text-danger bg-danger/10 border border-danger/30 font-bold",
    };
  }

  if (start && start > now) {
    const startDiff = Math.round((start - now) / 60000);
    const label = startDiff > 60 ? `Abre en ${Math.floor(startDiff / 60)}h` : `Abre en ${startDiff}m`;
    return {
      label,
      classes: "text-content-secondary bg-surface border border-border-subtle font-medium",
    };
  }

  if (diffMinutes < 60) {
    return {
      label: `Cierra en ${diffMinutes}m`,
      classes: "text-brand bg-brand/10 border border-brand/30 font-semibold",
    };
  }

  return null;
};

// Agrupación de ofertas por turno de retiro
const pickupGroups = computed(() => {
  const map = new Map();
  for (const offer of props.offers || []) {
    const key = getScheduleKey(offer);
    if (!map.has(key)) {
      map.set(key, {
        key,
        label: formatPickupWindow(offer),
        statusBadge: getGroupStatusBadge(offer),
        offers: [],
      });
    }
    map.get(key).offers.push(offer);
  }
  return Array.from(map.values()).map((group) => {
    const subtotal = group.offers.reduce(
      (sum, o) => sum + calculateOfferPrice(o) * Number(o.quantity || 1),
      0
    );
    return {
      ...group,
      subtotal,
    };
  });
});

const hasMultipleSchedules = computed(() => pickupGroups.value.length > 1);

const establishmentPickupWindow = computed(() => {
  if (!props.offers || !props.offers.length) return "Hoy";
  return formatPickupWindow(props.offers[0]);
});

// CountdownBadge para 1 sola ventana (Figma node 2013:846)
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
      return "text-brand";
    case "warning":
      return "text-warning";
    case "neutral":
    default:
      return "text-content-secondary";
  }
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

const confirmRemoveAllOffers = () => {
  if (!establishmentId.value) return;
  const confirmed = window.confirm(
    `¿Deseas eliminar todas las bolsas de "${establishmentName.value}" de tu carrito?`
  );
  if (confirmed) {
    emit("removeAllOffers", establishmentId.value);
  }
};

const handleSelect = () => {
  const id = establishmentId.value;
  if (id != null) {
    emit("toggleSelect", id);
  }
};
</script>

<template>
  <section
    class="store-cart-card bg-surface rounded-2xl overflow-hidden shadow-card transition-all duration-200 cursor-pointer"
    :class="[
      isSelected
        ? 'border-2 border-brand ring-2 ring-brand/20 shadow-md'
        : 'border border-border-subtle opacity-80 hover:opacity-100 hover:border-brand/40'
    ]"
    @click="handleSelect"
  >
    <!-- StoreHeader (Figma node 2013:837) -->
    <header
      class="px-6 py-4 flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 border-b border-border-subtle/70 transition-colors select-none"
      :class="isSelected ? 'bg-surface' : 'bg-surface-sunken/40'"
    >
      <!-- Lado Izquierdo: Checkbox de selección + Nombre y Dirección -->
      <div class="flex items-center gap-3.5 min-w-0 flex-1">
        <!-- Checkbox de selección del carrito -->
        <button
          type="button"
          class="w-5 h-5 rounded-md flex items-center justify-center transition-all cursor-pointer border shrink-0"
          :class="[
            isSelected
              ? 'bg-brand border-brand text-surface shadow-xs'
              : 'bg-surface border-border-subtle hover:border-brand/60'
          ]"
          :aria-checked="isSelected"
          role="checkbox"
          :title="isSelected ? 'Desmarcar este carrito' : 'Seleccionar este carrito'"
          @click.stop="handleSelect"
        >
          <svg v-if="isSelected" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- NameAndAddress (Figma node 2013:841) -->
        <div class="flex flex-col min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <!-- Nombre del comercio: enlace al perfil del comercio -->
            <RouterLink
              v-if="establishmentId"
              :to="{ name: 'establishment-view', params: { id: establishmentId } }"
              class="text-sm font-bold text-content-strong truncate hover:text-brand hover:underline transition-colors cursor-pointer"
              @click.stop
              title="Ver comercio"
            >
              <h2>{{ establishmentName }}</h2>
            </RouterLink>
            <h2
              v-else
              class="text-sm font-bold text-content-strong truncate"
              @click.stop
            >
              {{ establishmentName }}
            </h2>
            <span
              v-if="isSelected"
              class="hidden sm:inline-flex items-center gap-1 text-[11px] font-semibold text-brand bg-brand/10 border border-brand/20 px-2 py-0.5 rounded-full shrink-0"
            >
              Seleccionado
            </span>
          </div>
          <p v-if="establishmentAddress" class="text-xs text-content-secondary font-normal truncate mt-0.5">
            {{ establishmentAddress }}
          </p>
        </div>
      </div>

      <!-- Lado derecho: Horario de Retiro (SOLO cuando hay 1 única ventana de retiro) y Botón Vaciar -->
      <div class="flex items-center gap-3 shrink-0" @click.stop>
        <!-- PickupWindowBadge (Figma node 2013:844) -->
        <div
          v-if="!hasMultipleSchedules"
          class="inline-flex items-center gap-2.5 bg-surface-sunken border border-border-subtle rounded-full px-3 py-1 shrink-0"
        >
          <span class="text-xs font-medium text-content">
            Retiro: {{ establishmentPickupWindow }}
          </span>
          <!-- CountdownBadge (Figma node 2013:846) -->
          <span class="text-xs font-semibold" :class="countdownToneClass">
            {{ countdownData.label }}
          </span>
        </div>

        <!-- Botón Vaciar Local -->
        <button
          type="button"
          class="text-content-secondary hover:text-danger p-1.5 rounded-lg transition-colors cursor-pointer"
          @click.stop="confirmRemoveAllOffers"
          title="Eliminar todas las bolsas de este comercio"
          aria-label="Vaciar bolsas de este comercio"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </header>

    <!-- CartItemsList agrupado por ventana horaria (Figma: CartItemsList) -->
    <div class="p-6 space-y-0">
      <div
        v-for="(group, gIdx) in pickupGroups"
        :key="group.key"
        class="pickup-window-group"
      >
        <!-- Línea divisoria holgada entre grupos de turnos diferentes (Figma: SlotSeparator) -->
        <div
          v-if="gIdx > 0"
          class="py-6 flex items-center justify-center"
          role="separator"
        >
          <div class="w-full border-t border-border-subtle"></div>
        </div>

        <!-- Badge de Horario arriba a la derecha de este grupo (solo cuando hay 2+ ventanas de retiro) -->
        <div
          v-if="hasMultipleSchedules"
          class="flex items-center justify-end pb-3 pt-1"
        >
          <div class="inline-flex items-center gap-2 bg-surface-sunken border border-border-subtle rounded-full px-3 py-1 text-xs shrink-0">
            <span class="font-medium text-content">
              Retiro: {{ group.label }}
            </span>
            <span
              v-if="group.statusBadge"
              class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
              :class="group.statusBadge.classes"
            >
              {{ group.statusBadge.label }}
            </span>
          </div>
        </div>

        <!-- Ofertas dentro del grupo de horario (Figma: CartItem) -->
        <div class="divide-y divide-border-subtle/50">
          <article
            v-for="offer in group.offers"
            :key="offer.offer_id || offer.id"
            class="py-4 px-1 flex flex-col sm:flex-row sm:items-center justify-between gap-6"
          >
            <!-- PackDetails (Figma node 2013:850) -->
            <div class="min-w-0 flex-1 space-y-1.5">
              <!-- TitleAndTags (Figma node 2013:851) -->
              <div class="flex flex-wrap items-center gap-2">
                <!-- Nombre de la bolsa: enlace al detalle de la oferta -->
                <RouterLink
                  v-if="offer.offer_id || offer.id"
                  :to="{ name: 'customer-offer-detail', params: { id: offer.offer_id || offer.id } }"
                  class="text-base font-bold text-content-strong tracking-tight hover:text-brand hover:underline transition-colors cursor-pointer"
                  @click.stop
                  title="Ver detalle de la oferta"
                >
                  <h3>{{ offer.offer_title || offer.title }}</h3>
                </RouterLink>
                <h3
                  v-else
                  class="text-base font-bold text-content-strong tracking-tight"
                  @click.stop
                >
                  {{ offer.offer_title || offer.title }}
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
                  class="bg-warning/10 border border-warning/30 text-warning text-[11px] font-bold px-3 py-0.5 rounded-full"
                >
                  Máx: {{ offer.offer_max_quantity }}
                </span>
              </div>

              <!-- Offer Description (Figma node 2013:857) -->
              <p
                v-if="offer.offer_description || offer.description"
                class="text-xs font-normal text-content-secondary line-clamp-2 leading-relaxed"
              >
                {{ offer.offer_description || offer.description }}
              </p>

              <!-- StockAndUnitMeta (Figma node 2013:858) -->
              <div class="flex items-center gap-2 text-xs font-semibold text-warning pt-0.5">
                <span v-if="offer.offer_max_quantity != null">
                  {{ offer.offer_max_quantity }} disponibles
                </span>
              </div>
            </div>

            <!-- QuantityAndPriceControls (Figma node 2013:861) -->
            <div class="flex items-center gap-6 shrink-0 self-end sm:self-center">
              <!-- StepperColumn (Figma node 2013:862) -->
              <div class="flex flex-col items-center gap-1">
                <!-- QuantityStepper (Figma node 2013:863, w: 100px, h: 34px) -->
                <div class="flex items-center justify-between w-[100px] h-[34px] rounded-lg bg-surface-sunken border border-border-subtle overflow-hidden">
                  <button
                    type="button"
                    class="w-8 h-full flex items-center justify-center text-[15px] font-semibold text-content hover:bg-border-subtle/30 transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
                    :disabled="Number(offer.quantity) <= 1 || isUnavailable(offer)"
                    @click.stop="decreaseQuantity(offer)"
                    aria-label="Disminuir cantidad"
                  >
                    −
                  </button>
                  <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    :value="offer.quantity"
                    :disabled="isUnavailable(offer)"
                    class="w-8 text-center text-xs font-bold text-content-strong bg-transparent border-0 focus:outline-none focus:ring-0 p-0"
                    aria-label="Cantidad de packs"
                    @click.stop
                    @change.stop="updateQuantity(offer, $event.target.value)"
                  />
                  <button
                    type="button"
                    class="w-8 h-full flex items-center justify-center text-[15px] font-semibold text-content-strong hover:bg-border-subtle/30 transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer active:scale-95"
                    :disabled="Number(offer.quantity) >= (offer.offer_max_quantity ?? 99) || isUnavailable(offer) || quantityExceedsMax(offer)"
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
                  @click.stop="removeOffer(offer.offer_id || offer.id)"
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
        </div>
      </div>
    </div>

    <!-- Advertencia de Stock Si Aplica -->
    <div
      v-if="hasUnavailableOffers"
      class="mx-6 mb-4 p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger flex items-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <span>Hay bolsas con stock insuficiente o ventana cerrada. Ajusta tu pedido para continuar.</span>
    </div>

    <!-- StoreSubtotalFooter (Figma node 2013:904) -->
    <footer class="bg-surface-sunken px-6 py-3.5 border-t border-border-subtle flex items-center justify-end">
      <!-- StoreSubtotalRight (Figma node 2013:908) -->
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-content-secondary">
          Subtotal {{ establishmentName }}:
        </span>
        <span class="text-base font-bold text-content-strong">
          $ {{ formatPrice(sellerTotalNumber) }}
        </span>
      </div>
    </footer>
  </section>
</template>
