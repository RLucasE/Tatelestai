<script setup>
import SellerSection from "./SellerSection.vue";
import { ref, computed, onMounted, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";
import { useNotificationStore } from "@/stores/notifications";

const router = useRouter();
const notificationStore = useNotificationStore();

const establishments = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedEstablishmentIds = ref([]);
const hasInitializedSelection = ref(false);

const checkoutLoading = ref(false);
const checkoutError = ref("");
const showCheckoutError = ref(false);

const formatPrice = (val) => {
  return Number(val || 0).toLocaleString("es-AR");
};

const getScheduleKey = (offer) => {
  const start = offer?.pickup_start_datetime ? new Date(offer.pickup_start_datetime).toISOString().slice(0, 16) : "no-start";
  const endRaw = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  const end = endRaw ? new Date(endRaw).toISOString().slice(0, 16) : "no-end";
  return `${start}|${end}`;
};

// Control de selección de carritos
const getEstablishmentId = (offers) => {
  if (!Array.isArray(offers) || !offers.length) return null;
  return offers[0]?.establishment_id ?? offers[0]?.food_establishment_id ?? null;
};

const isEstablishmentSelected = (id) => {
  if (id == null) return false;
  return selectedEstablishmentIds.value.some((item) => Number(item) === Number(id));
};

const areAllSelected = computed(() => {
  if (!establishments.value.length) return false;
  return establishments.value.every((g) => isEstablishmentSelected(getEstablishmentId(g)));
});

const selectAll = () => {
  const allIds = establishments.value
    .map((g) => getEstablishmentId(g))
    .filter((id) => id != null)
    .map(Number);
  selectedEstablishmentIds.value = allIds;
};

const deselectAll = () => {
  selectedEstablishmentIds.value = [];
};

const toggleSelectAll = () => {
  if (areAllSelected.value) {
    deselectAll();
  } else {
    selectAll();
  }
};

const toggleEstablishmentSelection = (id) => {
  if (id == null) return;
  const numId = Number(id);
  const current = selectedEstablishmentIds.value.map(Number);
  const exists = current.includes(numId);
  if (exists) {
    selectedEstablishmentIds.value = current.filter((item) => item !== numId);
  } else {
    selectedEstablishmentIds.value = [...current, numId];
  }
};

// Establecimientos y ofertas seleccionadas activas
const selectedEstablishments = computed(() => {
  return establishments.value.filter(
    (group) => Array.isArray(group) && isEstablishmentSelected(getEstablishmentId(group))
  );
});

const selectedOffers = computed(() => {
  return selectedEstablishments.value.flat().filter(Boolean);
});

// Cantidad de packs seleccionados
const totalItemsCount = computed(() => {
  return selectedOffers.value.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0);
});

const totalPacksCount = computed(() => totalItemsCount.value);

// Totales de la reserva de carritos seleccionados (Figma: OrderSummaryCard)
const totalOriginalPrice = computed(() => {
  return selectedOffers.value.reduce((sum, offer) => {
    let orig = Number(offer.minimum_value || 0);
    if (!orig || orig <= 0) {
      const price = offer.offer_price != null ? Number(offer.offer_price) : Number(offer.price || 0);
      orig = price > 0 ? Math.round(price * 2.5) : 0;
    }
    return sum + orig * Number(offer.quantity || 1);
  }, 0);
});

const totalToPay = computed(() => {
  return selectedOffers.value.reduce((sum, offer) => {
    const price = offer.offer_price != null ? Number(offer.offer_price) : Number(offer.price || 0);
    return sum + price * Number(offer.quantity || 1);
  }, 0);
});

const totalSavings = computed(() => {
  return Math.max(0, totalOriginalPrice.value - totalToPay.value);
});

// Total de pases QR considerando locales y turnos seleccionados
const totalQrPassesCount = computed(() => {
  let passes = 0;
  for (const group of selectedEstablishments.value) {
    if (!Array.isArray(group) || !group.length) continue;
    const keys = new Set(group.map((o) => getScheduleKey(o)));
    passes += Math.max(1, keys.size);
  }
  return passes;
});

// Pases QR de retiro (Figma: Recibirás 2 pases QR de retiro)
const qrPassesCountText = computed(() => {
  const count = totalQrPassesCount.value;
  if (count === 0) return "0 pases QR de retiro";
  if (count === 1) return "Recibirás 1 pase QR de retiro";
  return `Recibirás ${count} pases QR de retiro`;
});

// Franja horaria combinada de retiro (Figma: "Hoy entre 20:00 y 22:30 hs en 2 locales.")
const pickupCommitmentText = computed(() => {
  const count = selectedEstablishments.value.length;
  if (!count) return "Selecciona un carrito para ver horario";

  const allOffers = selectedOffers.value;
  if (!allOffers.length) return "Selecciona un carrito para ver horario";

  let earliestStart = null;
  let latestEnd = null;

  for (const o of allOffers) {
    if (o.pickup_start_datetime) {
      const s = new Date(o.pickup_start_datetime);
      if (!earliestStart || s < earliestStart) earliestStart = s;
    }
    const exp = o.offer_expiration_datetime || o.expiration_datetime;
    if (exp) {
      const e = new Date(exp);
      if (!latestEnd || e > latestEnd) latestEnd = e;
    }
  }

  const localeSuffix = count === 1 ? "1 local" : `${count} locales`;

  if (earliestStart && latestEnd) {
    const isToday = earliestStart.toDateString() === new Date().toDateString();
    const datePrefix = isToday ? "Hoy" : earliestStart.toLocaleDateString("es-AR", { day: "numeric", month: "short" });
    const startTime = earliestStart.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    const endTime = latestEnd.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    return `${datePrefix} entre ${startTime} y ${endTime} hs en ${localeSuffix}.`;
  }

  if (latestEnd) {
    return `Hoy hasta las ${latestEnd.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })} hs en ${localeSuffix}.`;
  }

  return `Hoy en horario comercial en ${localeSuffix}.`;
});

const isOfferUnavailable = (offer) => {
  const now = Date.now();
  const exp = offer?.offer_expiration_datetime || offer?.expiration_datetime;
  const isExpired = exp ? new Date(exp).getTime() < now : false;
  const isSoldOut = offer?.offer_state === "purchased" || Number(offer?.offer_max_quantity ?? 1) === 0;
  const exceeds = offer?.offer_max_quantity != null && Number(offer.quantity) > Number(offer.offer_max_quantity);
  return isExpired || isSoldOut || exceeds;
};

// Disponibilidad en los carritos seleccionados
const hasAnyUnavailable = computed(() => {
  return selectedOffers.value.some((offer) => isOfferUnavailable(offer));
});

const getCart = async (showLoading = true) => {
  try {
    if (showLoading) {
      loading.value = true;
    }
    error.value = null;
    const response = await axiosInstance.get("/customer-cart");
    if (Array.isArray(response.data)) {
      establishments.value = response.data.filter((group) => Array.isArray(group) && group.length > 0);
      const currentValidIds = establishments.value
        .map((g) => getEstablishmentId(g))
        .filter((id) => id != null)
        .map(Number);

      if (!hasInitializedSelection.value) {
        selectedEstablishmentIds.value = currentValidIds;
        hasInitializedSelection.value = true;
      } else {
        selectedEstablishmentIds.value = selectedEstablishmentIds.value
          .map(Number)
          .filter((id) => currentValidIds.includes(id));
      }
    } else {
      establishments.value = [];
      selectedEstablishmentIds.value = [];
    }
  } catch (err) {
    if (err.response?.status === 404) {
      establishments.value = [];
    } else {
      console.error("Error al cargar carrito:", err);
      error.value = "No se pudieron cargar las bolsas de tu carrito. Intenta nuevamente.";
    }
  } finally {
    if (showLoading) {
      loading.value = false;
    }
  }
};

const handleRemoveOffer = async (offerId) => {
  try {
    await axiosInstance.delete(`/customer-cart/${offerId}`);
    await getCart(false);
  } catch (err) {
    console.error("Error removing offer:", err);
  }
};

function debounce(func, wait) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      func.apply(context, args);
    }, wait);
  };
}

const updateOfferQuantity = (offer, quantity) => {
  offer.quantity = quantity;
};

const updateQuantity = async (offer, quantity) => {
  try {
    await axiosInstance.put(`/customer-cart/${offer.offer_id}`, { quantity });
    await getCart(false);
  } catch (err) {
    console.error("Error updating quantity:", err);
  }
};

const debounceUpdateQuantity = debounce(updateQuantity, 400);

const handleQuantityChange = async (offer, quantity) => {
  const oldQuantity = offer.quantity;
  try {
    updateOfferQuantity(offer, quantity);
    debounceUpdateQuantity(offer, quantity);
  } catch (err) {
    console.error("Error updating quantity:", err);
    updateOfferQuantity(offer, oldQuantity);
  }
};

const removeEstablishmentOffers = async (establishmentId) => {
  try {
    await axiosInstance.delete(`/customer-cart/establishment/${establishmentId}`);
    await getCart(false);
  } catch (err) {
    console.error("Error removing establishment offers:", err);
  }
};

const selectEstablishment = (id) => {
  toggleEstablishmentSelection(id);
};

// Formato de franja horaria para un comercio
const formatPickupWindow = (offer) => {
  if (!offer) return "Hoy en horario comercial";
  const start = offer.pickup_start_datetime ? new Date(offer.pickup_start_datetime) : null;
  const end = offer.offer_expiration_datetime || offer.expiration_datetime
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

// Checkout centralizado (de un turno específico o de todas las ofertas del comercio)
const handleCheckout = async (offersGroup = null) => {
  const rawTarget = Array.isArray(offersGroup) && offersGroup.length > 0
    ? offersGroup
    : (selectedEstablishments.value[0] || []);

  const targetOffers = rawTarget.filter((o) => !isOfferUnavailable(o));
  if (!targetOffers.length) return;

  const id = targetOffers[0]?.establishment_id;
  if (!id) return;

  try {
    checkoutLoading.value = true;
    showCheckoutError.value = false;

    const purchaseData = {
      offers: targetOffers.map((offer) => ({
        id: offer.offer_id,
        quantity: Number(offer.quantity),
      })),
      food_establishment_id: id,
    };

    const response = await axiosInstance.post("/customer/pack-reservations/prepare", purchaseData);

    const firstOffer = targetOffers[0];
    const distinctWindows = Array.from(new Set(targetOffers.map((o) => formatPickupWindow(o))));
    const schedulesCount = new Set(targetOffers.map((o) => getScheduleKey(o))).size;

    const confirmationData = {
      ...response.data.data,
      schedules_count: schedulesCount,
      pickup_windows: distinctWindows,
      establishment: {
        ...response.data.data.establishment,
        category: firstOffer?.establishment_category || firstOffer?.category || "Gastronomía",
        pickup_window: distinctWindows.length > 1 ? distinctWindows.join(" • ") : formatPickupWindow(firstOffer),
      },
      offersDetails: targetOffers.map((o) => {
        const price = o.offer_price != null ? Number(o.offer_price) : Number(o.price || 0);
        let orig = Number(o.minimum_value || 0);
        if (!orig || orig <= 0) {
          orig = price > 0 ? Math.round(price * 2.5) : 0;
        }
        return {
          id: o.offer_id,
          title: o.offer_title,
          description: o.offer_description,
          price: price,
          original_price: orig,
          quantity: Number(o.quantity || 1),
          pickup_window: formatPickupWindow(o),
        };
      }),
    };

    sessionStorage.setItem("purchaseConfirmation", JSON.stringify(confirmationData));

    router.push({
      name: "purchase-confirmation",
      params: { token: response.data.data.purchase_token },
    });
  } catch (err) {
    console.error("Error al preparar la compra:", err);
    showCheckoutError.value = true;
    checkoutError.value =
      err.response?.data?.message ||
      err.response?.data?.error ||
      "No se pudo preparar la reserva. Por favor intenta de nuevo.";

    setTimeout(() => {
      showCheckoutError.value = false;
    }, 5000);
  } finally {
    checkoutLoading.value = false;
  }
};



// Sincronización en vivo ante eventos SSE
watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (!event) return;
    if (["cart_item_sold_out", "cart_item_stock_reduced"].includes(event.event_type)) {
      getCart(false);
    }
  }
);

onMounted(() => {
  getCart();
});

</script>

<template>
  <div class="cart-page-wrapper w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10 min-h-[calc(100vh-4rem)] bg-canvas text-content">
    
    <!-- HeaderSection (Figma node 2013:819) -->
    <header class="mb-8">
      <!-- TitleLine (Figma node 2013:829) -->
      <h1 class="text-[32px] font-bold text-content-strong tracking-tight leading-[38px]">
        Mi Carrito
      </h1>
    </header>

    <!-- Estado de Carga (Skeletons de 2 columnas) -->
    <div v-if="loading" class="flex flex-col lg:flex-row items-start gap-8 py-4">
      <div class="w-full lg:flex-1 space-y-6">
        <div
          v-for="i in 2"
          :key="i"
          class="bg-surface border border-border-subtle rounded-2xl p-6 shadow-card animate-pulse space-y-4"
        >
          <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
            <div class="space-y-1.5">
              <div class="h-4 bg-surface-sunken rounded w-44"></div>
              <div class="h-3 bg-surface-sunken/60 rounded w-32"></div>
            </div>
            <div class="h-6 bg-surface-sunken/60 rounded-full w-28 shrink-0"></div>
          </div>
          <div class="bg-surface-sunken rounded-xl p-4 space-y-3">
            <div class="h-4 bg-border-subtle rounded w-1/2"></div>
            <div class="h-3 bg-border-subtle/60 rounded w-3/4"></div>
          </div>
        </div>
      </div>
      <div class="w-full lg:w-[448px] shrink-0">
        <div class="bg-surface border border-border-subtle rounded-2xl p-6 shadow-card animate-pulse space-y-4">
          <div class="h-5 bg-surface-sunken rounded w-1/2"></div>
          <div class="space-y-2 py-4 border-y border-border-subtle">
            <div class="h-4 bg-surface-sunken rounded w-3/4"></div>
            <div class="h-4 bg-surface-sunken rounded w-2/3"></div>
          </div>
          <div class="h-12 bg-brand/30 rounded-xl w-full"></div>
        </div>
      </div>
    </div>

    <!-- Estado de Error de Carga -->
    <div
      v-else-if="error"
      class="bg-surface border border-danger/40 rounded-2xl p-8 text-center text-content my-6 max-w-lg mx-auto shadow-card space-y-4"
    >
      <div class="w-12 h-12 mx-auto rounded-2xl bg-danger/10 text-danger flex items-center justify-center">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke-width="2" />
          <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
          <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
        </svg>
      </div>
      <div class="space-y-1">
        <h3 class="text-base font-bold text-content-strong">Error al cargar el carrito</h3>
        <p class="text-xs sm:text-sm text-danger font-medium">{{ error }}</p>
      </div>
      <button
        type="button"
        class="bg-brand hover:bg-brand-hover text-surface text-xs font-bold px-6 py-2.5 rounded-[10px] transition cursor-pointer shadow-sm"
        @click="getCart"
      >
        Reintentar carga
      </button>
    </div>

    <!-- EmptyStateCard — Carrito (Figma node 4026:3432) -->
    <div
      v-else-if="establishments.length === 0"
      class="max-w-[605px] mx-auto bg-surface border border-border-subtle rounded-[20px] p-10 text-center shadow-card flex flex-col items-center gap-5 my-8"
    >
      <!-- Illustration — Empty Surprise Bag (Figma node 4026:3433) -->
      <div class="flex items-center justify-center">
        <svg width="136" height="112" viewBox="0 0 136 112" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M68 106C93.4051 106 114 102.418 114 98C114 93.5817 93.4051 90 68 90C42.5949 90 22 93.5817 22 98C22 102.418 42.5949 106 68 106Z" fill="var(--color-surface-sunken)"/>
          <path d="M68 96C92.3005 96 112 76.3005 112 52C112 27.6995 92.3005 8 68 8C43.6995 8 24 27.6995 24 52C24 76.3005 43.6995 96 68 96Z" fill="var(--color-surface-sunken)" fill-opacity="0.7"/>
          <path d="M24 28L26.2 33.8L32 36L26.2 38.2L24 44L21.8 38.2L16 36L21.8 33.8L24 28Z" fill="var(--color-rescue)"/>
          <path d="M112 20L113.5 24.5L118 26L113.5 27.5L112 32L110.5 27.5L106 26L110.5 24.5L112 20Z" fill="var(--color-brand-light)"/>
          <path d="M108 72C110.209 72 112 70.2091 112 68C112 65.7909 110.209 64 108 64C105.791 64 104 65.7909 104 68C104 70.2091 105.791 72 108 72Z" fill="var(--color-brand)" fill-opacity="0.35"/>
          <path d="M26 75.5C27.933 75.5 29.5 73.933 29.5 72C29.5 70.067 27.933 68.5 26 68.5C24.067 68.5 22.5 70.067 22.5 72C22.5 73.933 24.067 75.5 26 75.5Z" fill="var(--color-content)" fill-opacity="0.2"/>
          <path d="M52 38C52 29.1634 59.1634 22 68 22C76.8366 22 84 29.1634 84 38" stroke-width="3" stroke-linecap="round" stroke="var(--color-content)"/>
          <path d="M40 38H96L101 90C101.5 94.5 98 98 93.5 98H42.5C38 98 34.5 94.5 35 90L40 38Z" fill="var(--color-surface-elevated)" stroke-width="3" stroke-linejoin="round" stroke="var(--color-content)"/>
          <path d="M40 45L47 41L54 45L61 41L68 45L75 41L82 45L89 41L96 45" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" stroke="var(--color-brand-light)"/>
          <path d="M68 83C76.2843 83 83 76.2843 83 68C83 59.7157 76.2843 53 68 53C59.7157 53 53 59.7157 53 68C53 76.2843 59.7157 83 68 83Z" fill="var(--color-brand)" fill-opacity="0.14"/>
          <path d="M82.9288 66.5292C82.9759 67.0131 83 67.5037 83 68C83 68.4963 82.9759 68.9869 82.9288 69.4708M82.3578 63.6443C82.0704 62.6956 81.6915 61.7867 81.2315 60.9278M79.5956 58.484C78.9719 57.7249 78.2751 57.0281 77.516 56.4044M75.0722 54.7685C74.2133 54.3085 73.3044 53.9296 72.3557 53.6422M69.4708 53.0712C68.9869 53.0241 68.4963 53 68 53C67.5037 53 67.0131 53.0241 66.5292 53.0712M63.6443 53.6422C62.6956 53.9296 61.7867 54.3085 60.9278 54.7685M58.484 56.4044C57.7249 57.0281 57.0281 57.7249 56.4044 58.484M54.7685 60.9278C54.3085 61.7867 53.9296 62.6956 53.6422 63.6443M53.0712 66.5292C53.0241 67.0131 53 67.5037 53 68C53 68.4963 53.0241 68.9869 53.0712 69.4708M53.6422 72.3557C53.9296 73.3044 54.3085 74.2133 54.7685 75.0722M56.4044 77.516C57.0281 78.2751 57.7249 78.9719 58.484 79.5956M60.9278 81.2315C61.7867 81.6915 62.6956 82.0704 63.6443 82.3578M66.5292 82.9288C67.0131 82.9759 67.5037 83 68 83C68.4963 83 68.9869 82.9759 69.4708 82.9288M72.3557 82.3578C73.3044 82.0704 74.2133 81.6915 75.0722 81.2315M77.516 79.5956C78.2751 78.9719 78.9719 78.2751 79.5956 77.516M81.2315 75.0722C81.6915 74.2133 82.0704 73.3044 82.3578 72.3557" stroke-width="2" stroke="var(--color-brand)"/>
          <path d="M66.6314 70.158V69.818C66.6314 69.3193 66.7051 68.8773 66.8524 68.492C67.0111 68.1067 67.2094 67.7667 67.4474 67.472C67.6854 67.166 67.9348 66.8883 68.1954 66.639C68.5468 66.2877 68.8584 65.9647 69.1304 65.67C69.4138 65.364 69.5554 65.007 69.5554 64.599C69.5554 64.3497 69.4931 64.1173 69.3684 63.902C69.2438 63.6867 69.0568 63.5167 68.8074 63.392C68.5694 63.2673 68.2748 63.205 67.9234 63.205C67.4474 63.205 67.0338 63.3523 66.6824 63.647C66.3424 63.9417 66.1328 64.3327 66.0534 64.82L63.9794 64.31C64.0814 63.6753 64.3081 63.12 64.6594 62.644C65.0221 62.168 65.4868 61.7997 66.0534 61.539C66.6314 61.267 67.2718 61.131 67.9744 61.131C68.8018 61.131 69.5044 61.2953 70.0824 61.624C70.6718 61.9527 71.1194 62.3777 71.4254 62.899C71.7314 63.4203 71.8844 63.97 71.8844 64.548C71.8844 65.0013 71.7938 65.4037 71.6124 65.755C71.4311 66.1063 71.2044 66.4293 70.9324 66.724C70.6718 67.0073 70.3998 67.2907 70.1164 67.574C69.7538 67.914 69.4364 68.271 69.1644 68.645C68.8924 69.019 68.7564 69.4553 68.7564 69.954V70.158H66.6314ZM66.5804 74V71.62H68.8414V74H66.5804Z" fill="var(--color-brand)"/>
          <path d="M106 76H92C86.4772 76 82 80.4772 82 86C82 91.5228 86.4772 96 92 96H106C111.523 96 116 91.5228 116 86C116 80.4772 111.523 76 106 76Z" fill="var(--color-rescue)" stroke-width="2" stroke="var(--color-content)"/>
          <path d="M88.4078 86.28V85.12H91.6078V86.28H88.4078ZM95.9939 89.1C95.3206 89.1 94.7806 88.9333 94.3739 88.6C93.9672 88.26 93.7439 87.8 93.7039 87.22H94.9539C94.9806 87.4733 95.0806 87.67 95.2539 87.81C95.4339 87.9433 95.6806 88.01 95.9939 88.01C96.3406 88.01 96.6039 87.92 96.7839 87.74C96.9639 87.5533 97.0539 87.2967 97.0539 86.97V86.26C97.0539 85.9267 96.9639 85.6733 96.7839 85.5C96.6039 85.32 96.3406 85.23 95.9939 85.23C95.7806 85.23 95.5972 85.2733 95.4439 85.36C95.2906 85.4467 95.1739 85.5667 95.0939 85.72H93.8739L93.9239 81.7H98.0339V82.83H95.1039L95.0839 84.95H95.3739L95.0739 85.2C95.0739 84.8933 95.1939 84.6533 95.4339 84.48C95.6806 84.3 96.0106 84.21 96.4239 84.21C97.0172 84.2033 97.4772 84.3833 97.8039 84.75C98.1372 85.11 98.3039 85.6133 98.3039 86.26V86.97C98.3039 87.63 98.0972 88.15 97.6839 88.53C97.2772 88.91 96.7139 89.1 95.9939 89.1ZM102 89.1C101.527 89.1 101.117 89.01 100.77 88.83C100.423 88.65 100.153 88.3967 99.96 88.07C99.7733 87.7433 99.68 87.36 99.68 86.92V83.78C99.68 83.34 99.7733 82.9567 99.96 82.63C100.153 82.3033 100.423 82.05 100.77 81.87C101.117 81.69 101.527 81.6 102 81.6C102.48 81.6 102.89 81.69 103.23 81.87C103.577 82.05 103.843 82.3033 104.03 82.63C104.223 82.9567 104.32 83.34 104.32 83.78V86.92C104.32 87.36 104.223 87.7433 104.03 88.07C103.843 88.3967 103.577 88.65 103.23 88.83C102.883 89.01 102.473 89.1 102 89.1ZM102 88.02C102.347 88.02 102.623 87.92 102.83 87.72C103.037 87.52 103.14 87.2533 103.14 86.92V83.78C103.14 83.4467 103.037 83.18 102.83 82.98C102.623 82.78 102.347 82.68 102 82.68C101.653 82.68 101.377 82.78 101.17 82.98C100.963 83.18 100.86 83.4467 100.86 83.78V86.92C100.86 87.2533 100.963 87.52 101.17 87.72C101.377 87.92 101.653 88.02 102 88.02ZM102 86C101.807 86 101.647 85.9367 101.52 85.81C101.4 85.6833 101.34 85.52 101.34 85.32C101.34 85.12 101.4 84.96 101.52 84.84C101.64 84.72 101.8 84.66 102 84.66C102.2 84.66 102.36 84.72 102.48 84.84C102.6 84.96 102.66 85.12 102.66 85.32C102.66 85.52 102.6 85.6833 102.48 85.81C102.36 85.9367 102.2 86 102 86ZM104.966 89L110.126 81.7H111.026L105.866 89H104.966ZM109.416 89.05C108.989 89.05 108.639 88.93 108.366 88.69C108.099 88.4433 107.966 88.1133 107.966 87.7V87.05C107.966 86.6367 108.099 86.31 108.366 86.07C108.639 85.8233 108.989 85.7 109.416 85.7C109.843 85.7 110.193 85.8233 110.466 86.07C110.739 86.31 110.876 86.6367 110.876 87.05V87.7C110.876 88.1133 110.739 88.4433 110.466 88.69C110.193 88.93 109.843 89.05 109.416 89.05ZM109.416 88.27C109.796 88.27 109.986 88.08 109.986 87.7V87.05C109.986 86.6633 109.796 86.47 109.416 86.47C109.036 86.47 108.846 86.6633 108.846 87.05V87.7C108.846 88.08 109.036 88.27 109.416 88.27ZM106.576 85C106.156 85 105.806 84.88 105.526 84.64C105.253 84.4 105.116 84.07 105.116 83.65V83C105.116 82.58 105.253 82.25 105.526 82.01C105.806 81.77 106.156 81.65 106.576 81.65C106.996 81.65 107.343 81.77 107.616 82.01C107.889 82.25 108.026 82.58 108.026 83V83.65C108.026 84.07 107.889 84.4 107.616 84.64C107.343 84.88 106.996 85 106.576 85ZM106.576 84.23C106.956 84.23 107.146 84.0367 107.146 83.65V83C107.146 82.6133 106.956 82.42 106.576 82.42C106.389 82.42 106.246 82.4667 106.146 82.56C106.053 82.6533 106.006 82.8 106.006 83V83.65C106.006 83.85 106.053 83.9967 106.146 84.09C106.246 84.1833 106.389 84.23 106.576 84.23Z" fill="var(--color-content)"/>
        </svg>
      </div>

      <!-- CopyBlock (Figma node 4026:3447) -->
      <div class="space-y-2">
        <h2 class="text-[22px] font-extrabold text-content-strong leading-tight">
          Tu carrito está vacío
        </h2>
        <p class="text-sm font-normal text-content-secondary leading-[21px] max-w-[460px]">
          Todavía no agregaste ninguna bolsa sorpresa. ¡Explorá los comercios de tu zona y rescatá comida exquisita a precio reducido!
        </p>
      </div>

      <!-- CTA — Explorar Ofertas (Figma node 4026:3457) -->
      <RouterLink
        to="/customer/offers"
        class="inline-flex items-center justify-center gap-2.5 bg-brand hover:bg-brand-hover text-surface text-[15px] font-bold px-7 py-3.5 rounded-[10px] shadow-sm shadow-brand/20 transition-all duration-200 active:scale-95 cursor-pointer mt-1"
      >
        <span>Explorar ofertas</span>
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M3.75 9H14.25M9 14.25L14.25 9L9 3.75" stroke-width="1.875" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
        </svg>
      </RouterLink>
    </div>

    <!-- SplitCheckoutLayout (Figma node 2013:834) -->
    <div v-else class="flex flex-col lg:flex-row items-start gap-8">
      
      <!-- LeftColumn_StoreGroups (Figma node 2013:835) -->
      <div class="w-full lg:flex-1 lg:min-w-0 space-y-6">
        <!-- CartSelectionToolbar: Guía visual y control de selección múltiple -->
        <div
          v-if="establishments.length > 0"
          class="bg-surface border border-border-subtle rounded-2xl px-5 py-3.5 shadow-card flex flex-wrap items-center justify-between gap-3"
        >
          <!-- Master checkbox y estado visual -->
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="w-5 h-5 rounded-md flex items-center justify-center transition-all cursor-pointer border shrink-0"
              :class="[
                areAllSelected
                  ? 'bg-brand border-brand text-surface shadow-xs'
                  : selectedEstablishmentIds.length > 0
                    ? 'bg-brand/10 border-brand text-brand'
                    : 'bg-surface border-border-subtle hover:border-brand/60'
              ]"
              :aria-checked="areAllSelected"
              role="checkbox"
              title="Alternar selección de todos los carritos"
              @click="toggleSelectAll"
            >
              <svg v-if="areAllSelected" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="selectedEstablishmentIds.length > 0" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <rect x="4" y="9" width="12" height="2" rx="1" />
              </svg>
            </button>

            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-content-strong">
                <template v-if="areAllSelected">
                  Todos los carritos seleccionados
                </template>
                <template v-else-if="selectedEstablishmentIds.length > 0">
                  {{ selectedEstablishmentIds.length }} de {{ establishments.length }} carritos seleccionados
                </template>
                <template v-else>
                  Ningún carrito seleccionado
                </template>
              </span>
              <span
                v-if="areAllSelected"
                class="inline-flex items-center text-[11px] font-semibold text-brand bg-brand/10 px-2 py-0.5 rounded-full"
              >
                {{ establishments.length }} locales
              </span>
            </div>
          </div>

          <!-- Botones de Acción directos: Seleccionar todos y Desmarcar todos -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer border"
              :class="[
                areAllSelected
                  ? 'bg-brand/10 border-brand/30 text-brand pointer-events-none'
                  : 'bg-surface hover:bg-surface-sunken border-border-subtle text-content hover:text-content-strong'
              ]"
              @click="selectAll"
            >
              Seleccionar todos
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer border"
              :class="[
                selectedEstablishmentIds.length === 0
                  ? 'opacity-50 border-border-subtle text-content-secondary pointer-events-none'
                  : 'bg-surface hover:bg-surface-sunken border-border-subtle text-content-secondary hover:text-danger'
              ]"
              @click="deselectAll"
            >
              Desmarcar todos
            </button>
          </div>
        </div>

        <!-- Lista de Carritos de Comercio -->
        <SellerSection
          v-for="(offers, idx) in establishments"
          :key="getEstablishmentId(offers) ?? idx"
          :offers="offers"
          :is-selected="isEstablishmentSelected(getEstablishmentId(offers))"
          :establishments-count="establishments.length"
          :loading="checkoutLoading && isEstablishmentSelected(getEstablishmentId(offers))"
          @offerRemoved="handleRemoveOffer"
          @quantityUpdated="handleQuantityChange"
          @removeAllOffers="removeEstablishmentOffers"
          @toggleSelect="toggleEstablishmentSelection"
        />
      </div>

      <!-- RightColumn_CheckoutSidebar (Figma node 2013:964) -->
      <aside class="w-full lg:w-[448px] shrink-0 lg:sticky lg:top-8 space-y-4">
        <!-- OrderSummaryCard (Figma node 2013:965) -->
        <div class="bg-surface border border-border-subtle rounded-2xl p-6 shadow-card space-y-4">
          
          <!-- Encabezado del Resumen (Figma node 2013:966) -->
          <h2 class="text-lg font-bold text-content-strong tracking-tight">
            Resumen de tu Reserva
          </h2>

          <!-- PaymentMethodBlock (Figma node 2013:967) -->
          <div>
            <span class="text-[11px] font-bold text-content-secondary uppercase tracking-wider block">
              MEDIO DE PAGO RÁPIDO
            </span>
          </div>

          <!-- PriceBreakdown (Figma node 2013:976) -->
          <div class="space-y-2.5 py-2.5 px-1 text-[13px]">
            <!-- Row: Valor original de los packs (Figma node 2013:977) -->
            <div class="flex items-center justify-between text-content-secondary">
              <span>Valor original de los {{ totalPacksCount }} {{ totalPacksCount === 1 ? 'pack' : 'packs' }}</span>
              <span class="tabular-nums font-medium text-content-secondary">
                $ {{ formatPrice(totalOriginalPrice) }}
              </span>
            </div>

            <!-- Row: Descuento por rescate gastronómico (opcional si hay ahorro) -->
            <div v-if="totalSavings > 0" class="flex items-center justify-between text-content-secondary">
              <span>Descuento por rescate gastronómico</span>
              <span class="tabular-nums font-semibold text-brand">
                -$ {{ formatPrice(totalSavings) }}
              </span>
            </div>

            <!-- Row: Cargo de servicio Tatelestai (Figma node 2013:983) -->
            <div class="flex items-center justify-between text-content-secondary">
              <span>Cargo de servicio Tatelestai</span>
              <span class="text-content font-medium">$ 0 (Bonificado)</span>
            </div>
          </div>

          <!-- SummaryDivider (Figma node 2013:986) -->
          <div class="h-px bg-border-subtle my-1"></div>

          <!-- TotalAbonadoRow (Figma node 2013:987) -->
          <div class="flex items-baseline justify-between pt-1">
            <div class="space-y-0.5">
              <span class="text-xs font-bold text-content-secondary uppercase tracking-wider block">
                TOTAL A ABONAR
              </span>
              <span class="text-[11px] text-content-secondary">
                {{ qrPassesCountText }}
              </span>
            </div>
            <div class="text-right">
              <span class="text-3xl font-bold text-content-strong tracking-tight tabular-nums">
                $ {{ formatPrice(totalToPay) }}
              </span>
            </div>
          </div>

          <!-- PickupCommitmentNotice (Figma node 2013:997) -->
          <div class="bg-surface rounded-lg py-2.5 px-3 text-xs text-content font-medium">
            <p class="truncate">{{ pickupCommitmentText }}</p>
          </div>

          <!-- Button CTA (Figma node 2013:999, Variant=CTA, height: 50px, rounded-[10px]) -->
          <div class="pt-1 space-y-2">
            <button
              type="button"
              class="w-full h-[50px] rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-semibold text-sm shadow-sm shadow-brand/20 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="checkoutLoading || !totalPacksCount || hasAnyUnavailable || selectedEstablishments.length === 0"
              @click="handleCheckout()"
            >
              <svg
                v-if="checkoutLoading"
                class="animate-spin w-5 h-5 text-surface"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              <span v-else>Comprar</span>
            </button>

            <!-- Aclaración si hay múltiples comercios seleccionados -->
            <p
              v-if="selectedEstablishments.length > 1"
              class="text-[11px] text-content-secondary text-center leading-relaxed"
            >
              Como cada comercio tiene retiro y pago independiente, abonarás primero en <strong>{{ selectedEstablishments[0][0]?.establishment_name }}</strong>.
            </p>

            <!-- Cancelación gratuita (Figma node 2013:1001) -->
            <p class="text-[11px] text-content-secondary text-center leading-relaxed">
              Cancelación gratuita hasta 2 horas antes del inicio de cada ventana de retiro.
            </p>
          </div>

        </div>
      </aside>

    </div>

    <!-- Toast de Error de Checkout -->
    <Teleport to="body">
      <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showCheckoutError"
          class="fixed bottom-6 right-6 z-50 max-w-md bg-surface border border-danger/40 rounded-2xl p-4 shadow-elevated flex items-start gap-3"
        >
          <div class="w-8 h-8 rounded-full bg-danger/10 text-danger flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2" />
              <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
              <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-bold text-content-strong">Error al preparar la reserva</h4>
            <p class="text-xs text-content-secondary mt-0.5">{{ checkoutError }}</p>
          </div>
          <button
            type="button"
            class="text-content-secondary hover:text-content-strong p-1 rounded-lg transition-colors cursor-pointer"
            @click="showCheckoutError = false"
          >
            &times;
          </button>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
