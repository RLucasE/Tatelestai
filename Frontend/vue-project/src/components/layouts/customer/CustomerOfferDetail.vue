<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import BaseIcon from '@/components/ui/atoms/BaseIcon.vue';
import BaseSkeleton from '@/components/ui/atoms/BaseSkeleton.vue';
import QuantityStepper from '@/components/ui/molecules/QuantityStepper.vue';
import ToastAlert from '@/components/ui/molecules/ToastAlert.vue';
import EmptyStateCard from '@/components/ui/molecules/EmptyStateCard.vue';
import ReportModal from '@/components/common/ReportModal.vue';
import OfferCard from '@/components/ui/organisms/OfferCard.vue';

const route = useRoute();
const router = useRouter();

// Estados reactivos de la oferta principal
const offer = ref(null);
const otherOffers = ref([]);
const loading = ref(true);
const loadingOthers = ref(false);
const error = ref(null);
const quantity = ref(1);

// Modales y estados de acción
const showReportModal = ref(false);
const isAddingToCart = ref(false);
const isBuyingNow = ref(false);
const notification = ref({
  show: false,
  message: '',
  type: 'success',
});

let toastTimer = null;
const showNotification = (message, type = 'success') => {
  if (toastTimer) clearTimeout(toastTimer);
  notification.value = { show: true, message, type };
  toastTimer = setTimeout(() => {
    notification.value.show = false;
  }, 3500);
};

// Parámetros computados
const offerId = computed(() => route.params.id);

const establishment = computed(() => {
  return offer.value?.establishment || {};
});

const availableQuantity = computed(() => {
  return offer.value?.quantity != null ? Number(offer.value.quantity) : 3;
});

const hasStock = computed(() => {
  return availableQuantity.value > 0;
});

const minSelectableQuantity = computed(() => {
  return hasStock.value ? 1 : 0;
});

const unitPrice = computed(() => {
  return offer.value?.price ? Number(offer.value.price) : 0;
});

const totalPrice = computed(() => {
  return unitPrice.value * (quantity.value || 1);
});

// Normalización segura de alérgenos
const allergensList = computed(() => {
  if (!offer.value?.allergens) return [];
  if (Array.isArray(offer.value.allergens)) return offer.value.allergens;
  if (typeof offer.value.allergens === 'string') {
    try {
      const parsed = JSON.parse(offer.value.allergens);
      return Array.isArray(parsed) ? parsed : [offer.value.allergens];
    } catch {
      return [offer.value.allergens];
    }
  }
  return [];
});

// Formato de distancia dinámico y seguro
const formattedDistance = computed(() => {
  const d = offer.value?.distance;
  if (!d) return 'A 800 metros';
  if (typeof d === 'number') {
    return d < 1 ? `A ${Math.round(d * 1000)} metros` : `A ${d.toFixed(1)} km`;
  }
  const str = String(d).trim();
  if (str.toLowerCase().startsWith('a ')) return str;
  return `A ${str}`;
});

// Detección de badge destacado (ej. Vegetariano / Vegano según Figma: bg #46334F)
const isHighlightTag = (tag) => {
  const lower = String(tag || '').toLowerCase();
  return lower.includes('vegetariano') || lower.includes('vegano');
};

// Formato de horario de retiro según especificación Figma ("Retiro: Hoy 20:00 a 20:45 hs")
const formattedPickupSchedule = computed(() => {
  if (!offer.value?.pickup_start_datetime && !offer.value?.expiration_datetime) {
    return 'Retiro: Hoy 20:00 a 20:45 hs';
  }
  try {
    const start = offer.value.pickup_start_datetime ? new Date(offer.value.pickup_start_datetime) : null;
    const end = offer.value.expiration_datetime ? new Date(offer.value.expiration_datetime) : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
      const startTime = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false });
      const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false });
      return `Retiro: ${dateLabel} ${startTime} a ${endTime} hs`;
    }

    if (end) {
      const isToday = end.toDateString() === new Date().toDateString();
      const dateLabel = isToday ? 'Hoy' : end.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
      const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false });
      return `Retiro: ${dateLabel} hasta ${endTime} hs`;
    }
  } catch (e) {
    return 'Retiro: Horario a coordinar con el local';
  }
  return 'Retiro: Horario a coordinar con el local';
});

// Carga de la oferta por ID
const fetchOfferDetail = async () => {
  if (!offerId.value) return;

  try {
    loading.value = true;
    error.value = null;

    const response = await axiosInstance.get(`/packs/${offerId.value}`);
    offer.value = response.data.data;
    quantity.value = (offer.value?.quantity ?? 0) > 0 ? 1 : 0;

    // Cargar otras ofertas del mismo comercio
    const estId = offer.value?.food_establishment_id || establishment.value?.id;
    if (estId) {
      fetchOtherOffers(estId);
    }
  } catch (err) {
    console.error('Error al cargar detalle de oferta:', err);
    if (err.response?.status === 404) {
      error.value = 'Esta oferta no existe o ya no se encuentra activa.';
    } else {
      error.value = 'Ocurrió un problema de conexión al cargar la oferta. Por favor, reintenta.';
    }
  } finally {
    loading.value = false;
  }
};

// Carga de más ofertas del mismo local
const fetchOtherOffers = async (establishmentId) => {
  try {
    loadingOthers.value = true;
    const response = await axiosInstance.get('/packs', {
      params: { food_establishment_id: establishmentId },
    });
    const all = response.data.data || [];
    otherOffers.value = all.filter((o) => String(o.id) !== String(offerId.value));
  } catch (err) {
    console.error('Error al cargar otras ofertas del local:', err);
    otherOffers.value = [];
  } finally {
    loadingOthers.value = false;
  }
};

// Acciones de compra
const addToCart = async () => {
  if (!offer.value || isAddingToCart.value) return;

  try {
    isAddingToCart.value = true;
    await axiosInstance.post('/add-to-cart', {
      offer_id: offer.value.id,
      quantity: quantity.value,
    });
    showNotification(`¡${quantity.value} pack(s) agregado(s) a tu carrito con éxito!`, 'success');
  } catch (err) {
    console.error('Error al agregar al carrito:', err);
    if (err.response?.status === 400) {
      showNotification('Ya alcanzaste el límite de unidades disponibles para este pack', 'error');
    } else {
      showNotification('No se pudo agregar al carrito. Intenta nuevamente.', 'error');
    }
  } finally {
    isAddingToCart.value = false;
  }
};

const buyNow = async () => {
  if (!offer.value || isBuyingNow.value) return;

  const estId = offer.value.food_establishment_id || establishment.value?.id;
  if (!estId) {
    showNotification('No se pudo identificar el comercio de la reserva.', 'error');
    return;
  }

  try {
    isBuyingNow.value = true;
    showNotification('Preparando tu reserva...', 'info');

    const prepareResponse = await axiosInstance.post('/customer/pack-reservations/prepare', {
      food_establishment_id: estId,
      offers: [{ id: offer.value.id, quantity: quantity.value }],
    });

    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(prepareResponse.data.data));

    router.push({
      name: 'purchase-confirmation',
      params: {
        token: prepareResponse.data.data.purchase_token,
      },
    });
  } catch (err) {
    console.error('Error al procesar compra directa:', err);
    showNotification('No se pudo iniciar la compra. Por favor, reintenta.', 'error');
  } finally {
    isBuyingNow.value = false;
  }
};

const handleQuickAdd = async (otherOffer) => {
  try {
    await axiosInstance.post('/add-to-cart', {
      offer_id: otherOffer.id,
      quantity: 1,
    });
    showNotification(`¡${otherOffer.title} agregado al carrito!`, 'success');
  } catch (err) {
    console.error(err);
    showNotification('Error al agregar el pack al carrito.', 'error');
  }
};

const goToOffer = (targetOffer) => {
  router.push({
    name: 'customer-offer-detail',
    params: { id: targetOffer.id },
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const goBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push({ name: 'customer-offers' });
  }
};

// Reaccionar a cambios de ruta al seleccionar otra oferta
watch(
  () => route.params.id,
  (newId) => {
    if (newId) {
      fetchOfferDetail();
    }
  }
);

onMounted(() => {
  fetchOfferDetail();
});
</script>

<template>
  <div class="min-h-full pb-20 bg-canvas text-content">
    <div class="max-w-[1435px] mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-14">
      <!-- 1. Barra de Navegación Superior: Botón Volver y Reportar (Figma 19:3120) -->
      <nav class="flex items-center justify-between gap-4 h-[34px]" aria-label="Navegación secundaria">
        <!-- Botón Regresar (Figma 2023:1159) -->
        <button
          type="button"
          class="inline-flex items-center gap-2 h-[34px] px-[18px] py-2 rounded-[10px] bg-surface border border-content-muted text-[13px] font-medium text-content hover:bg-surface-sunken transition-colors cursor-pointer shadow-xs select-none"
          aria-label="Regresar a ofertas"
          @click="goBack"
        >
          <BaseIcon name="arrow-left" size="xs" class="w-3.5 h-3.5 text-content-muted" />
          <span>Regresar</span>
        </button>

        <!-- Botón Reportar Oferta (Figma 19:3238) -->
        <button
          v-if="offer"
          type="button"
          class="inline-flex items-center gap-1.5 h-7 px-2.5 py-1.5 text-xs text-content-muted hover:text-content transition-colors cursor-pointer select-none"
          title="Reportar esta oferta"
          aria-label="Reportar esta oferta"
          @click="showReportModal = true"
        >
          <BaseIcon name="alert" size="xs" class="w-3.5 h-3.5 text-content-muted" />
          <span>Reportar oferta</span>
        </button>
      </nav>

      <!-- 2. Estado de Carga (Skeleton) -->
      <div v-if="loading" class="flex flex-col lg:flex-row justify-between items-start gap-12">
        <div class="w-full lg:max-w-[960px] flex-1 space-y-10">
          <div class="space-y-4">
            <BaseSkeleton class="h-10 w-3/4 rounded-xl" />
            <BaseSkeleton class="h-6 w-32 rounded-lg" />
            <BaseSkeleton class="h-16 w-full rounded-xl" />
            <div class="flex gap-2">
              <BaseSkeleton class="h-8 w-24 rounded-full" />
              <BaseSkeleton class="h-8 w-32 rounded-full" />
            </div>
          </div>
          <div class="space-y-4 pt-4 border-b border-border-subtle pb-7">
            <BaseSkeleton class="h-8 w-64 rounded-lg" />
            <BaseSkeleton class="h-4 w-96 rounded-md" />
            <BaseSkeleton class="h-12 w-full rounded-xl" />
          </div>
        </div>

        <div class="w-full lg:w-[319px] shrink-0 space-y-8">
          <BaseSkeleton class="h-10 w-44 rounded-lg" />
          <BaseSkeleton class="h-8 w-56 rounded-lg" />
          <BaseSkeleton class="h-10 w-full rounded-lg" />
          <BaseSkeleton class="h-4 w-3/4 mx-auto rounded" />
          <BaseSkeleton class="h-[50px] w-full rounded-xl" />
          <BaseSkeleton class="h-[50px] w-full rounded-xl" />
        </div>
      </div>

      <!-- 3. Estado de Error -->
      <EmptyStateCard
        v-else-if="error || !offer"
        variant="error"
        :title="error || 'Oferta no encontrada'"
        description="No pudimos cargar la información de esta bolsa sorpresa. Es posible que haya expirado o ya no tenga stock."
      >
        <template #actions>
          <button
            type="button"
            class="h-10 px-5 rounded-[10px] bg-brand text-surface text-sm font-semibold hover:bg-brand-hover transition-colors cursor-pointer"
            @click="goBack"
          >
            Volver a las ofertas disponibles
          </button>
        </template>
      </EmptyStateCard>

      <!-- 4. Contenido Principal de Detalle: Master_TwoColumn_Layout (Figma 19:3050) -->
      <div v-else class="space-y-14">
        <div class="flex flex-col lg:flex-row justify-between items-start gap-12">
          <!-- Columna Izquierda: LeftColumn_MainContent (Figma 19:3051) -->
          <section class="w-full lg:max-w-[960px] flex-1 space-y-10">
            <!-- PackInfo_Section (Figma 19:3052) -->
            <div class="space-y-5">
              <!-- Título de la Oferta (Figma 19:3053) -->
              <h1 class="text-2xl sm:text-3xl lg:text-[32px] font-bold text-content-strong leading-tight tracking-tight font-heading">
                {{ offer.title }}
              </h1>

              <!-- Description_Block (Figma 19:3054) -->
              <div class="space-y-2">
                <h2 class="text-lg font-bold text-content-strong font-heading">
                  Descripción
                </h2>
                <p class="text-sm text-content leading-relaxed">
                  {{ offer.description || 'Este pack sorpresa rescata los excedentes frescos elaborados artesanalmente en el día. Puede incluir: medialunas de manteca, panes de masa madre de campo, baguettes rústicas, facturas surtidas y especialidades horneadas. La variedad exacta depende del excedente del local al cierre, garantizando máxima frescura y calidad.' }}
                </p>
              </div>

              <!-- Allergens_Block (Figma 19:3057) -->
              <div class="space-y-2.5">
                <span class="text-xs text-content-secondary font-normal block">
                  Alérgenos e información nutricional:
                </span>
                <div class="flex flex-wrap items-center gap-2.5">
                  <template v-if="allergensList.length">
                    <span
                      v-for="(alg, idx) in allergensList"
                      :key="idx"
                      class="h-[30px] px-3.5 py-1.5 rounded-full border text-[13px] font-medium leading-none inline-flex items-center justify-center transition-colors select-none"
                      :class="isHighlightTag(alg) ? 'bg-content border-content text-canvas' : 'bg-surface border-content-secondary text-content'"
                    >
                      {{ alg }}
                    </span>
                  </template>
                  <template v-else>
                    <span class="h-[30px] px-3.5 py-1.5 rounded-full border border-content-secondary bg-surface text-[13px] font-medium text-content inline-flex items-center justify-center select-none">
                      Gluten
                    </span>
                    <span class="h-[30px] px-3.5 py-1.5 rounded-full border border-content-secondary bg-surface text-[13px] font-medium text-content inline-flex items-center justify-center select-none">
                      Contiene Lácteos
                    </span>
                    <span class="h-[30px] px-3.5 py-1.5 rounded-full border border-content bg-content text-[13px] font-medium text-canvas inline-flex items-center justify-center select-none">
                      Apto Vegetariano
                    </span>
                    <span class="h-[30px] px-3.5 py-1.5 rounded-full border border-content-secondary bg-surface text-[13px] font-medium text-content inline-flex items-center justify-center select-none">
                      Puede contener trazas de frutos secos
                    </span>
                  </template>
                </div>
              </div>
            </div>

            <!-- EstablishmentHeroCard — Vertical (Figma 4039:2450) -->
            <article class="w-full lg:max-w-[820px] space-y-5 pt-2 pb-7 border-b border-border-subtle">
              <!-- Información del Establecimiento (Figma I4039:2450;4039:2282) -->
              <div class="space-y-3">
                <!-- TitleAndBadgesRow -->
                <div class="flex items-center gap-3.5 flex-wrap">
                  <RouterLink
                    v-if="establishment.id || offer.food_establishment_id"
                    :to="`/customer/establishment/${establishment.id || offer.food_establishment_id}`"
                    class="text-2xl sm:text-[30px] font-bold text-content-strong leading-tight hover:text-brand transition-colors font-heading"
                  >
                    {{ establishment.name || offer.establishment_name || 'Panadería & Confitería San José' }}
                  </RouterLink>
                  <h2 v-else class="text-2xl sm:text-[30px] font-bold text-content-strong leading-tight font-heading">
                    {{ establishment.name || offer.establishment_name || 'Panadería & Confitería San José' }}
                  </h2>

                  <!-- CategoryPillBadge -->
                  <span class="h-[30px] px-3.5 py-1.5 rounded-full bg-surface border border-content-secondary text-[13px] font-medium text-content inline-flex items-center justify-center select-none">
                    {{ establishment.establishment_type?.name || establishment.category || offer.category || 'Panadería' }}
                  </span>
                </div>

                <!-- ContactMetadataRow -->
                <div class="flex items-center gap-2.5 flex-wrap text-[13px]">
                  <span class="font-medium text-content">
                    {{ establishment.address || offer.establishment_address || 'Av. Santa Fe 2341, Palermo, CABA' }}
                  </span>
                  <span class="text-content-muted select-none">•</span>
                  <span class="font-semibold text-brand-light">
                    {{ formattedDistance }}
                  </span>
                  <span class="text-content-muted select-none">•</span>
                  <span class="font-normal text-content-secondary">
                    {{ establishment.schedule || 'Lun a Sáb: 08:00 - 21:00 hs' }}
                  </span>
                  <span class="text-content-muted select-none">•</span>
                  <span class="font-normal text-content-muted">
                    {{ establishment.phone || '+54 11 4822-9104' }}
                  </span>
                </div>

                <!-- StoreDescription -->
                <p class="text-sm text-content-secondary leading-relaxed max-w-[740px]">
                  {{ establishment.description || 'Elaboración diaria con masa madre natural, manteca extra y horneado a la piedra. Rescatá al cierre del día nuestras bolsas sorpresa con panes de campo, medialunas artesanales, focaccias y pastelería fresca del día con hasta 60% de ahorro.' }}
                </p>
              </div>

              <!-- HorizontalHairline (Figma I4039:2450;4039:2296) -->
              <div class="h-px w-full bg-border-subtle"></div>

              <!-- Credibilidad del Establecimiento (Figma I4039:2450;4039:2297) -->
              <div class="space-y-4.5">
                <!-- RatingsComparisonRow (Figma I4039:2450;4039:2297;4039:2185) -->
                <div class="flex items-center gap-9 flex-wrap">
                  <!-- Block_ReseñasRecientes -->
                  <div class="flex flex-col gap-1.5">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">
                      RESEÑAS RECIENTES (30 DÍAS)
                    </span>
                    <div class="flex items-center gap-2">
                      <!-- 5 Star Icons en text-rescue -->
                      <div class="flex items-center gap-[3px] text-rescue">
                        <BaseIcon v-for="s in 5" :key="s" name="star-fill" size="xs" class="w-3.5 h-3.5" />
                      </div>
                      <span class="text-sm font-bold text-content-strong tabular-nums font-heading">
                        {{ Number(establishment.recent_rating_average || establishment.rating_average || 4.9).toFixed(1) }}
                      </span>
                      <span class="text-xs font-normal text-content-secondary">
                        {{ establishment.recent_reviews_count != null ? establishment.recent_reviews_count : 42 }} compras
                      </span>
                    </div>
                  </div>

                  <!-- Block_ReseñasHistóricas -->
                  <div class="flex flex-col gap-1.5">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">
                      HISTÓRICO GLOBAL
                    </span>
                    <div class="flex items-center gap-2">
                      <!-- 5 Star Icons en text-rescue -->
                      <div class="flex items-center gap-[3px] text-rescue">
                        <BaseIcon v-for="s in 5" :key="s" name="star-fill" size="xs" class="w-3.5 h-3.5" />
                      </div>
                      <span class="text-sm font-bold text-content-strong tabular-nums font-heading">
                        {{ Number(establishment.rating_average || 4.8).toFixed(1) }}
                      </span>
                      <span class="text-xs font-normal text-content-secondary">
                        {{ establishment.reviews_count != null ? establishment.reviews_count : 318 }} opiniones
                      </span>
                    </div>
                  </div>
                </div>

                <!-- TrustBadgesRow (Figma I4039:2450;4039:2297;4039:2196) -->
                <div class="flex items-center gap-2.5 flex-wrap">
                  <span class="h-[30px] px-3.5 py-1.5 rounded-full bg-surface border border-content-secondary text-[13px] font-medium text-content inline-flex items-center justify-center select-none">
                    {{ establishment.completion_rate ? `${establishment.completion_rate}% completadas` : '98% completadas' }}
                  </span>
                  <span class="h-[30px] px-3.5 py-1.5 rounded-full bg-rescue border border-rescue text-[13px] font-medium text-content inline-flex items-center justify-center shadow-xs select-none">
                    {{ establishment.sales_count ? `${establishment.sales_count} ventas realizadas` : '342 ventas realizadas' }}
                  </span>
                </div>
              </div>
            </article>
          </section>

          <!-- Columna Derecha: RightColumn_PurchaseSidebar (Figma 19:3095) -->
          <aside class="w-full lg:w-[319px] shrink-0 space-y-8 lg:sticky lg:top-24">
            <!-- Price_Row (Figma 19:3096) -->
            <div class="flex items-baseline gap-3">
              <span class="text-[32px] font-bold text-content-strong leading-none tabular-nums font-heading">
                $ {{ unitPrice.toLocaleString('es-AR') }}
              </span>
              <span
                v-if="offer.minimum_value && Number(offer.minimum_value) > unitPrice"
                class="text-lg font-bold text-content-muted line-through leading-none tabular-nums font-heading"
              >
                $ {{ Number(offer.minimum_value).toLocaleString('es-AR') }}
              </span>
            </div>

            <!-- Pickup_Block (Figma 19:3099) -->
            <div class="space-y-1.5">
              <div class="text-sm font-normal text-content">
                {{ formattedPickupSchedule }}
              </div>
              <div
                class="text-xs font-normal"
                :class="hasStock ? 'text-brand-light' : 'text-danger font-medium'"
              >
                <template v-if="availableQuantity > 1">
                  Quedan {{ availableQuantity }} packs disponibles
                </template>
                <template v-else-if="availableQuantity === 1">
                  Queda 1 pack disponible
                </template>
                <template v-else>
                  ¡Bolsa agotada!
                </template>
              </div>
            </div>

            <!-- Stepper_Row (Figma 19:3102) -->
            <div class="flex items-center gap-4">
              <span class="text-sm font-normal text-content shrink-0">
                Cantidad a reservar:
              </span>
              <QuantityStepper
                v-model="quantity"
                :min="minSelectableQuantity"
                :max="availableQuantity"
                :available="availableQuantity"
                :disabled="!hasStock"
              />
            </div>

            <!-- Cancelación sin cargo (Figma 19:3113) -->
            <p class="text-xs font-normal text-content-muted text-center">
              Cancelación sin cargo hasta 2 horas antes del retiro.
            </p>

            <!-- Botón Comprar (Figma Button CTA 4014:1401) -->
            <button
              type="button"
              class="w-full h-[50px] px-6 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[15px] font-semibold transition-all shadow-md active:scale-[0.99] flex items-center justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed select-none"
              :disabled="!hasStock || isBuyingNow"
              @click="buyNow"
            >
              <template v-if="isBuyingNow">
                <BaseIcon name="spinner" size="sm" class="animate-spin mr-2" />
                <span>Preparando reserva...</span>
              </template>
              <template v-else>
                <span>Comprar</span>
              </template>
            </button>

            <!-- Botón Agregar al carrito (Figma Button CTA 4026:3208) -->
            <button
              type="button"
              class="w-full h-[50px] px-6 rounded-[10px] bg-surface border-[1.5px] border-brand hover:bg-brand/5 text-brand text-[15px] font-semibold transition-all shadow-sm active:scale-[0.99] flex items-center justify-center gap-2.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed select-none"
              :disabled="!hasStock || isAddingToCart"
              @click="addToCart"
            >
              <template v-if="isAddingToCart">
                <BaseIcon name="spinner" size="sm" class="animate-spin mr-2" />
                <span>Agregando...</span>
              </template>
              <template v-else>
                <BaseIcon name="cart" size="sm" class="w-[18px] h-[18px] text-brand" />
                <span>Agregar al carrito</span>
              </template>
            </button>
          </aside>
        </div>

        <!-- 5. MoreOffers_Section y OffersGrid (Figma 19:2805 y 19:3248) -->
        <section v-if="loadingOthers || otherOffers.length > 0" class="space-y-14 pt-4">
          <!-- MoreOffers_Section Heading (Figma 19:2806) -->
          <h2 class="text-2xl sm:text-3xl lg:text-[32px] font-bold text-content-strong leading-tight tracking-tight font-heading">
            Más de {{ establishment.name || offer.establishment_name || 'este local' }}
          </h2>

          <!-- Estado de carga de más ofertas -->
          <div v-if="loadingOthers" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <BaseSkeleton v-for="i in 3" :key="i" class="h-[298px] rounded-2xl w-full" />
          </div>

          <!-- OffersGrid (Figma 19:3248) -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <OfferCard
              v-for="other in otherOffers"
              :key="other.id"
              :offer="other"
              @click="goToOffer(other)"
              @quick-add="handleQuickAdd(other)"
            />
          </div>
        </section>
      </div>
    </div>

    <!-- Modal de Reporte -->
    <ReportModal
      v-if="offer"
      :is-visible="showReportModal"
      reportable-type="offer"
      :reportable-id="offer.id"
      @close="showReportModal = false"
    />

    <!-- Alertas Toast -->
    <ToastAlert
      :show="notification.show"
      :message="notification.message"
      :type="notification.type"
      @close="notification.show = false"
    />
  </div>
</template>
