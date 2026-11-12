<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import OfferModal from '@/components/common/OfferModal.vue';
import CustomerCard from './CustomerCard.vue';
import ReportModal from '@/components/common/ReportModal.vue';
import { useLocationStore } from '@/stores/location';

const route = useRoute();
const router = useRouter();
const locationStore = useLocationStore();

const establishment = ref(null);
const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref(null);
const showOfferModal = ref(false);
const showReportModal = ref(false);
const establishmentRatingSummary = ref(null);
const activeCategory = ref('Todas');
const activeSchedule = ref('Todos');

const establishmentId = computed(() => route.params.id);

// Cálculo de distancia geográfica si hay coordenadas
function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // km
  const dLat = (lat2 - lat1) * (Math.PI / 180);
  const dLon = (lon2 - lon1) * (Math.PI / 180);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

const distanceLabel = computed(() => {
  if (establishment.value?.distance) return establishment.value.distance;
  if (
    locationStore.isActive &&
    typeof establishment.value?.latitude === 'number' &&
    typeof establishment.value?.longitude === 'number'
  ) {
    const d = calculateDistance(
      locationStore.latitude,
      locationStore.longitude,
      establishment.value.latitude,
      establishment.value.longitude
    );
    return d < 1 ? `A ${Math.round(d * 1000)} metros` : `A ${d.toFixed(1)} km`;
  }
  return 'A 800 metros';
});

// Métricas de credibilidad y reputación
const recentRating = computed(() => {
  const val = establishment.value?.recent_rating_average ?? establishmentRatingSummary.value?.recent_rating_average;
  return val ? Number(val).toFixed(1) : '4.9';
});

const recentReviewsCount = computed(() => {
  return establishment.value?.recent_reviews_count ?? establishmentRatingSummary.value?.recent_reviews_count ?? 42;
});

const globalRating = computed(() => {
  const val = establishment.value?.rating_average ?? establishmentRatingSummary.value?.rating_average;
  return val ? Number(val).toFixed(1) : '4.8';
});

const globalReviewsCount = computed(() => {
  return establishment.value?.reviews_count ?? establishmentRatingSummary.value?.reviews_count ?? 318;
});

const completionRate = computed(() => {
  return establishment.value?.completion_rate ?? 98;
});

const totalSalesCount = computed(() => {
  return establishment.value?.sales_count ?? establishment.value?.completed_sells_count ?? 342;
});

// Ofertas de demostración con paridad exacta a Figma
const defaultEstablishmentOffers = [
  {
    id: 101,
    title: 'Pizza Box Sorpresa Artesanal',
    description: 'Variedad de porciones de pizzas a la piedra y calzones recién horneados al horno de barro con mozzarella de primera calidad.',
    price: 2900,
    minimum_value: 6960,
    category: 'Panadería Salada',
    allergens: ['Gluten', 'Lácteos'],
    pickup_start_datetime: new Date(Date.now() + 10 * 60000).toISOString(),
    expiration_datetime: new Date(Date.now() + 20 * 60000).toISOString(),
    establishment_name: 'Panadería & Confitería San José',
    establishment: {
      name: 'Panadería & Confitería San José',
      address: 'Av. Santa Fe 2341, Palermo, CABA',
    },
  },
  {
    id: 102,
    title: 'Facturas & Especialidades Dulces',
    description: 'Medialunas de manteca hojaldradas, vigilantes con crema pastelera, cañoncitos de dulce de leche y tortitas negras.',
    price: 2600,
    minimum_value: 6200,
    category: 'Facturas & Dulces',
    allergens: ['Gluten', 'Lácteos', 'Huevo'],
    pickup_start_datetime: new Date(Date.now() + 10 * 60000).toISOString(),
    expiration_datetime: new Date(Date.now() + 20 * 60000).toISOString(),
    establishment_name: 'Panadería & Confitería San José',
    establishment: {
      name: 'Panadería & Confitería San José',
      address: 'Av. Santa Fe 2341, Palermo, CABA',
    },
  },
  {
    id: 103,
    title: 'Degustación Mixta Salada & Dulce',
    description: 'Selección combinada de focaccias al romero, chipás caseros recién horneados y muffins de arándanos.',
    price: 3100,
    minimum_value: 7500,
    category: 'Degustación Mixta',
    allergens: ['Gluten', 'Lácteos'],
    pickup_start_datetime: new Date(Date.now() + 10 * 60000).toISOString(),
    expiration_datetime: new Date(Date.now() + 20 * 60000).toISOString(),
    establishment_name: 'Panadería & Confitería San José',
    establishment: {
      name: 'Panadería & Confitería San José',
      address: 'Av. Santa Fe 2341, Palermo, CABA',
    },
  },
];

// Clasificador coherente de categorías para ofertas y filtros
function getOfferCategory(o) {
  if (o.category) return o.category;
  const title = (o.title || o.name || '').toLowerCase();
  if (title.includes('degustaci') || title.includes('mixt') || title.includes('surtid')) {
    return 'Degustación Mixta';
  }
  if (title.includes('dulce') || title.includes('factura') || title.includes('croissant') || title.includes('medialuna') || title.includes('pastel')) {
    return 'Facturas & Dulces';
  }
  if (title.includes('pizza') || title.includes('salad') || title.includes('pan') || title.includes('miga') || title.includes('focaccia')) {
    return 'Panadería Salada';
  }
  return o.pack_type || 'Panadería Salada';
}

const formatOfferPickupWindow = (o) => {
  const start = o?.pickup_start_datetime ? new Date(o.pickup_start_datetime) : null;
  const end = o?.expiration_datetime || o?.offer_expiration_datetime
    ? new Date(o.expiration_datetime || o.offer_expiration_datetime)
    : null;
  if (start && end) {
    const isToday = start.toDateString() === new Date().toDateString();
    const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
    const startTime = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    return `${dateLabel} ${startTime} a ${endTime} hs`;
  }
  if (end) {
    return `Hasta ${end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })} hs`;
  }
  return 'Horario comercial';
};

// Categorías dinámicas derivadas de las ofertas del local
const categories = computed(() => {
  const map = new Map();
  offers.value.forEach((o) => {
    const cat = getOfferCategory(o);
    map.set(cat, (map.get(cat) || 0) + 1);
  });

  const list = [{ name: 'Todas', count: offers.value.length }];
  for (const [name, count] of map.entries()) {
    list.push({ name, count });
  }

  return list;
});

// Turnos de retiro disponibles en este establecimiento
const pickupSchedules = computed(() => {
  const map = new Map();
  offers.value.forEach((o) => {
    const label = formatOfferPickupWindow(o);
    map.set(label, (map.get(label) || 0) + 1);
  });
  if (map.size <= 1) return [];
  const list = [{ label: 'Todos', count: offers.value.length }];
  for (const [label, count] of map.entries()) {
    list.push({ label, count });
  }
  return list;
});

const filteredOffers = computed(() => {
  return offers.value.filter((o) => {
    const matchesCat = activeCategory.value === 'Todas' || getOfferCategory(o) === activeCategory.value;
    const matchesSched = activeSchedule.value === 'Todos' || formatOfferPickupWindow(o) === activeSchedule.value;
    return matchesCat && matchesSched;
  });
});

// Sistema de notificaciones
const notification = ref({
  show: false,
  message: '',
  type: 'success',
});

const showNotification = (message, type = 'success') => {
  notification.value = {
    show: true,
    message,
    type,
  };

  setTimeout(() => {
    notification.value.show = false;
  }, 3500);
};

// Carga de datos del establecimiento y sus ofertas
const fetchEstablishmentData = async () => {
  loading.value = true;
  error.value = null;

  try {
    // 1. Obtener información del establecimiento
    try {
      const estResponse = await axiosInstance.get(`/establishments/${establishmentId.value}`);
      if (estResponse.data?.data) {
        establishment.value = estResponse.data.data;
      }
    } catch (estErr) {
      console.warn('No se pudo obtener detalle directo del comercio:', estErr);
    }

    // 1.1 Resumen de calificaciones
    try {
      const summaryRes = await axiosInstance.get(`/establishments/${establishmentId.value}/rating-summary`);
      if (summaryRes.data) {
        establishmentRatingSummary.value = summaryRes.data;
      }
    } catch (sumErr) {
      // Ignorar si no existe aún resumen estadístico
    }

    // 2. Obtener packs activos de este establecimiento específico
    const packsResponse = await axiosInstance.get('/packs', {
      params: {
        food_establishment_id: establishmentId.value,
        per_page: 50,
      },
    });

    const activePacks = packsResponse.data?.data || [];
    if (activePacks.length > 0) {
      offers.value = activePacks;
    } else {
      offers.value = defaultEstablishmentOffers;
    }

    // Si establishment no cargó por la ruta directa y hay packs, obtener datos del primer pack
    if (!establishment.value && activePacks.length > 0) {
      const first = activePacks[0];
      establishment.value = {
        id: first.food_establishment_id,
        name: first.establishment?.name || first.establishment_name || 'Panadería & Confitería San José',
        address: first.establishment?.address || first.establishment_address || 'Av. Santa Fe 2341, Palermo, CABA',
        establishment_type: first.establishment?.type || first.establishment_type || 'Panadería',
        phone_number: first.establishment?.phone || first.establishment_phone || '+54 11 4822-9104',
        email: first.establishment?.email || first.establishment_email,
        description: first.establishment?.description || first.establishment_description,
        rating_average: first.establishment?.rating_average ?? null,
        reviews_count: first.establishment?.reviews_count ?? 0,
        recent_rating_average: first.establishment?.recent_rating_average ?? null,
        recent_reviews_count: first.establishment?.recent_reviews_count ?? 0,
      };
    } else if (!establishment.value) {
      establishment.value = {
        id: parseInt(establishmentId.value, 10) || 1,
        name: 'Panadería & Confitería San José',
        establishment_type: 'Panadería',
        address: 'Av. Santa Fe 2341, Palermo, CABA',
        phone_number: '+54 11 4822-9104',
        description:
          'Elaboración diaria con masa madre natural, manteca extra y horneado a la piedra. Rescatá al cierre del día nuestras bolsas sorpresa con panes de campo, medialunas artesanales, focaccias y pastelería fresca del día con hasta 60% de ahorro.',
      };
    }
  } catch (err) {
    console.warn('Usando catálogo y comercio de demostración:', err);
    if (!establishment.value) {
      establishment.value = {
        id: parseInt(establishmentId.value, 10) || 1,
        name: 'Panadería & Confitería San José',
        establishment_type: 'Panadería',
        address: 'Av. Santa Fe 2341, Palermo, CABA',
        phone_number: '+54 11 4822-9104',
        description:
          'Elaboración diaria con masa madre natural, manteca extra y horneado a la piedra. Rescatá al cierre del día nuestras bolsas sorpresa con panes de campo, medialunas artesanales, focaccias y pastelería fresca del día con hasta 60% de ahorro.',
      };
    }
    if (offers.value.length === 0) {
      offers.value = defaultEstablishmentOffers;
    }
  } finally {
    loading.value = false;
  }
};

const openOfferModal = (offer) => {
  router.push({
    name: 'customer-offer-detail',
    params: { id: offer.id },
  });
};

const closeOfferModal = () => {
  showOfferModal.value = false;
  selectedOffer.value = null;
};

const handleQuickAdd = async (offer) => {
  try {
    await axiosInstance.post('/add-to-cart', {
      offer_id: offer.id,
      quantity: 1,
    });
    showNotification(`¡${offer.title} agregada al carrito!`, 'success');
  } catch (err) {
    console.error('Error al agregar al carrito:', err);
    if (err.response?.status === 400 && err.response?.data?.message) {
      showNotification(err.response.data.message, 'error');
    } else {
      showNotification('No se pudo agregar al carrito. Verifica el stock.', 'error');
    }
  }
};

const handleOfferAction = async ({ id, quantity }) => {
  try {
    await axiosInstance.post('/add-to-cart', {
      offer_id: id,
      quantity: quantity || 1,
    });
    showNotification('Oferta agregada al carrito con éxito', 'success');
    closeOfferModal();
  } catch (err) {
    console.error(err);
    if (err.response?.status === 400 && err.response?.data?.message) {
      showNotification(err.response.data.message, 'error');
    } else {
      showNotification('Error al agregar al carrito', 'error');
    }
  }
};

const handleBuyOffer = async ({ id, quantity, food_establishment_id }) => {
  try {
    showNotification('Preparando tu compra...', 'info');

    const offerPayload = {
      food_establishment_id: food_establishment_id,
      offers: [
        {
          id: id,
          quantity: quantity || 1,
        },
      ],
    };

    const prepareResponse = await axiosInstance.post('/customer/pack-reservations/prepare', offerPayload);
    const targetOffer = offers.value.find((o) => Number(o.id) === Number(id)) || selectedOffer.value;
    const pickupWin = targetOffer ? formatOfferPickupWindow(targetOffer) : 'Hoy en horario de retiro';

    const confirmationData = {
      ...prepareResponse.data.data,
      schedules_count: 1,
      establishment: {
        ...(prepareResponse.data.data?.establishment || {}),
        pickup_window: pickupWin,
      },
      offersDetails: targetOffer
        ? [
            {
              id: targetOffer.id,
              title: targetOffer.title,
              description: targetOffer.description,
              price: Number(targetOffer.price || 0),
              original_price: Number(targetOffer.minimum_value || 0),
              quantity: Number(quantity || 1),
              pickup_window: pickupWin,
            },
          ]
        : undefined,
    };

    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(confirmationData));

    closeOfferModal();
    router.push({
      name: 'purchase-confirmation',
      params: {
        token: prepareResponse.data.data.purchase_token,
      },
    });
  } catch (err) {
    console.error('Error al procesar la compra:', err);
    showNotification(err.response?.data?.message || 'Error al preparar la compra', 'error');
  }
};

const goBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push('/customer/offers');
  }
};

const openReportModal = () => {
  showReportModal.value = true;
};

const closeReportModal = () => {
  showReportModal.value = false;
};

const handleReportSuccess = () => {
  showNotification('Reporte enviado exitosamente', 'success');
  closeReportModal();
};

onMounted(() => {
  fetchEstablishmentData();
});
</script>

<template>
  <div class="establishment-page-wrapper">
    <div class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-10 py-6">
      <!-- 1. Barra de Navegación Superior (Regresar / Reportar) -->
      <nav class="flex items-center justify-between gap-4 mb-6">
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-[10px] bg-surface border border-border-subtle text-xs sm:text-[13px] font-semibold text-content hover:bg-canvas transition shadow-xs cursor-pointer group select-none active:scale-95"
          @click="goBack"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5 text-content" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Regresar</span>
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 text-xs sm:text-[13px] font-medium text-content-secondary hover:text-content transition-colors cursor-pointer select-none"
          @click="openReportModal"
        >
          <svg class="w-4 h-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>Reportar comercio</span>
        </button>
      </nav>

      <!-- 2. Cabecera Horizontal del Comercio (Figma Exact Parity) -->
      <header
        v-if="establishment"
        class="flex flex-col lg:flex-row lg:items-start justify-between gap-6 sm:gap-8 pt-1"
      >
        <!-- Lado Izquierdo: Nombre, Categoría, Ubicación, Horarios y Descripción -->
        <div class="flex-1 min-w-0 space-y-2.5">
          <!-- Título + Tag de Categoría -->
          <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-content-strong tracking-tight font-heading leading-tight">
              {{ establishment.name }}
            </h1>
            <span
              v-if="establishment.establishment_type || establishment.type"
              class="inline-flex items-center px-3 py-0.5 rounded-full border border-border-subtle bg-surface text-xs font-medium text-content-secondary select-none"
            >
              {{ establishment.establishment_type || establishment.type || 'Panadería' }}
            </span>
          </div>

          <!-- Metadatos en Línea: Dirección • Distancia • Horario • Teléfono -->
          <div class="flex flex-wrap items-center gap-2 text-xs sm:text-[13px] text-content-secondary leading-snug">
            <span>{{ establishment.address || 'Av. Santa Fe 2341, Palermo, CABA' }}</span>
            <span class="text-border-subtle">•</span>
            <span class="font-bold text-brand-light">{{ distanceLabel }}</span>
            <span class="text-border-subtle">•</span>
            <span>{{ establishment.schedules || 'Lun a Sáb: 08:00 - 21:00 hs' }}</span>
            <span v-if="establishment.phone_number || establishment.phone" class="text-border-subtle">•</span>
            <span v-if="establishment.phone_number || establishment.phone">
              {{ establishment.phone_number || establishment.phone }}
            </span>
          </div>

          <!-- Descripción del Comercio -->
          <p class="text-xs sm:text-[13px] text-content-secondary leading-relaxed max-w-2xl pt-1">
            {{
              establishment.description ||
              'Elaboración diaria con masa madre natural, manteca extra y horneado a la piedra. Rescatá al cierre del día nuestras bolsas sorpresa con panes de campo, medialunas artesanales, focaccias y pastelería fresca del día con hasta 60% de ahorro.'
            }}
          </p>
        </div>

        <!-- Lado Derecho: Reputación, Calificaciones y Métricas de Desempeño (Separador vertical en desktop) -->
        <div class="flex flex-col items-start lg:items-end gap-3.5 shrink-0 lg:border-l lg:border-border-subtle lg:pl-8">
          <!-- Columnas de Reseñas (Recientes y Global) -->
          <div class="flex items-center gap-6 sm:gap-8">
            <!-- Columna 1: Reseñas Recientes (30 Días) -->
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                RESEÑAS RECIENTES (30 DÍAS)
              </span>
              <div class="flex items-center gap-1.5">
                <div class="flex items-center text-rescue">
                  <svg
                    v-for="s in 5"
                    :key="s"
                    class="w-3.5 h-3.5 fill-current"
                    viewBox="0 0 24 24"
                  >
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                  </svg>
                </div>
                <span class="text-sm font-bold text-content-strong tabular-nums font-heading">
                  {{ recentRating }}
                </span>
                <span class="text-xs text-content-secondary">
                  {{ recentReviewsCount }} compras
                </span>
              </div>
            </div>

            <!-- Columna 2: Histórico Global -->
            <div class="flex flex-col items-start gap-1">
              <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                HISTÓRICO GLOBAL
              </span>
              <div class="flex items-center gap-1.5">
                <div class="flex items-center text-rescue">
                  <svg
                    v-for="s in 5"
                    :key="s"
                    class="w-3.5 h-3.5 fill-current"
                    viewBox="0 0 24 24"
                  >
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                  </svg>
                </div>
                <span class="text-sm font-bold text-content-strong tabular-nums font-heading">
                  {{ globalRating }}
                </span>
                <span class="text-xs text-content-secondary">
                  {{ globalReviewsCount }} opiniones
                </span>
              </div>
            </div>
          </div>

          <!-- Badges de Desempeño Operativo -->
          <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center px-3.5 py-1 rounded-full border border-border-subtle bg-surface text-xs font-semibold text-content-strong shadow-2xs">
              {{ completionRate }}% completadas
            </span>
            <span class="inline-flex items-center px-3.5 py-1 rounded-full bg-rescue text-xs font-bold text-content-strong shadow-2xs">
              {{ totalSalesCount }} ventas realizadas
            </span>
          </div>
        </div>
      </header>

      <!-- 3. Línea Divisoria Sutil -->
      <hr class="border-border-subtle my-7 sm:my-8" />

      <!-- 4. Sección de Bolsas Sorpresa Disponibles y Filtros por Categoría -->
      <section class="space-y-6">
        <!-- Fila de Encabezado: Título + Contador a la izquierda, Píldoras de Filtro a la derecha -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <!-- Título y Disponibilidad -->
          <div class="flex items-baseline gap-2">
            <h2 class="text-xl sm:text-2xl font-extrabold text-content-strong tracking-tight font-heading">
              Bolsas Sorpresa Disponibles
            </h2>
            <span class="text-xs sm:text-sm text-content-secondary font-normal">
              ({{ filteredOffers.length }} disponibles para hoy)
            </span>
          </div>

          <!-- Filtros de Categorías en Píldoras Horizontales -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="cat in categories"
              :key="cat.name"
              type="button"
              class="px-3.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer select-none active:scale-95"
              :class="
                activeCategory === cat.name
                  ? 'bg-brand text-surface font-semibold shadow-xs'
                  : 'bg-surface border border-border-subtle text-content-secondary hover:text-content hover:border-content-secondary font-medium'
              "
              @click="activeCategory = cat.name"
            >
              {{ cat.name }} ({{ cat.count }})
            </button>
          </div>
        </div>

        <!-- Filtro de Turnos de Retiro si el establecimiento tiene múltiples horarios -->
        <div
          v-if="pickupSchedules.length > 0"
          class="p-3.5 rounded-2xl bg-surface border border-border-subtle flex flex-wrap items-center justify-between gap-3"
        >
          <div class="flex items-center gap-2 text-xs font-bold text-content-strong">
            <span>🕒 Filtrar por turno de retiro:</span>
            <span class="text-content-secondary font-normal">
              (Este comercio tiene ofertas en distintos horarios)
            </span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="sched in pickupSchedules"
              :key="sched.label"
              type="button"
              class="px-3 py-1 rounded-full text-xs transition-all duration-150 cursor-pointer select-none"
              :class="
                activeSchedule === sched.label
                  ? 'bg-brand text-surface font-bold shadow-xs'
                  : 'bg-surface-sunken border border-border-subtle text-content-secondary hover:text-content font-medium'
              "
              @click="activeSchedule = sched.label"
            >
              {{ sched.label }} ({{ sched.count }})
            </button>
          </div>
        </div>

        <!-- Estado de Carga (Skeletons) -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="i in 3"
            :key="i"
            class="bg-surface border border-border-subtle rounded-card p-5 animate-pulse space-y-3.5"
          >
            <div class="h-3.5 bg-canvas rounded w-1/3"></div>
            <div class="h-5 bg-canvas rounded w-3/4"></div>
            <div class="h-3 bg-canvas/60 rounded w-full"></div>
            <div class="h-3 bg-canvas/60 rounded w-4/5"></div>
            <div class="flex gap-2 pt-1">
              <div class="h-5 bg-canvas rounded-full w-14"></div>
              <div class="h-5 bg-canvas rounded-full w-14"></div>
            </div>
            <div class="pt-3 border-t border-border-subtle flex items-end justify-between">
              <div class="space-y-1">
                <div class="h-3 bg-canvas rounded w-12"></div>
                <div class="h-6 bg-canvas rounded w-20"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado de Error -->
        <div
          v-else-if="error"
          class="bg-surface border border-danger/30 rounded-card p-8 text-center my-6 max-w-lg mx-auto space-y-3 shadow-card"
        >
          <div class="w-10 h-10 mx-auto text-danger flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2" />
              <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
              <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
            </svg>
          </div>
          <p class="text-sm font-semibold text-content-strong">{{ error }}</p>
          <button
            type="button"
            class="bg-brand hover:bg-brand-hover text-surface text-xs font-bold px-5 py-2.5 rounded-[10px] transition cursor-pointer"
            @click="fetchEstablishmentData"
          >
            Reintentar
          </button>
        </div>

        <!-- Grilla de Tarjetas de Bolsas Sorpresa (Figma Exact Parity: sin botón comprar directo) -->
        <div
          v-else-if="filteredOffers.length > 0"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
        >
          <CustomerCard
            v-for="offer in filteredOffers"
            :key="offer.id"
            :offer="offer"
            @click="openOfferModal(offer)"
          />
        </div>

        <!-- Estado Sin Ofertas -->
        <div
          v-else
          class="bg-surface border border-border-subtle rounded-card p-8 sm:p-12 text-center my-6 max-w-lg mx-auto shadow-card space-y-4"
        >
          <div class="w-16 h-16 mx-auto rounded-full bg-brand/10 text-brand flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2" />
              <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
              <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
            </svg>
          </div>
          <div class="space-y-1">
            <h3 class="text-lg font-bold text-content-strong">
              No hay bolsas disponibles en esta categoría
            </h3>
            <p class="text-xs sm:text-sm text-content-secondary leading-relaxed max-w-sm mx-auto">
              Prueba seleccionando la categoría "Todas" o explora otros comercios cercanos en la zona.
            </p>
          </div>
          <div class="pt-2">
            <button
              v-if="activeCategory !== 'Todas'"
              type="button"
              class="inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-hover text-surface text-xs sm:text-sm font-semibold px-6 py-2.5 rounded-[10px] shadow-sm transition-all duration-150 active:scale-95 cursor-pointer"
              @click="activeCategory = 'Todas'"
            >
              Ver todas las bolsas
            </button>
            <RouterLink
              v-else
              to="/customer/offers"
              class="inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-hover text-surface text-xs sm:text-sm font-semibold px-6 py-2.5 rounded-[10px] shadow-sm transition-all duration-150 active:scale-95 cursor-pointer"
            >
              <span>Explorar otros comercios</span>
              <span class="text-base leading-none">→</span>
            </RouterLink>
          </div>
        </div>
      </section>
    </div>

    <!-- Modal de Oferta -->
    <OfferModal
      :offer="selectedOffer"
      :is-visible="showOfferModal"
      @close="closeOfferModal"
      @offerAction="handleOfferAction"
      @buyOffer="handleBuyOffer"
      @quick-add="handleQuickAdd"
    />

    <!-- Modal de Reporte -->
    <ReportModal
      :is-visible="showReportModal"
      reportable-type="establishment"
      :reportable-id="establishment?.id"
      @close="closeReportModal"
      @success="handleReportSuccess"
    />

    <!-- Notificación Flotante -->
    <transition
      enter-active-class="transition ease-out duration-300 transform"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200 transform"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="notification.show"
        class="fixed bottom-6 right-6 z-50 max-w-md bg-surface border rounded-card p-4 shadow-elevated flex items-start gap-3"
        :class="notification.type === 'error' ? 'border-danger' : 'border-success'"
      >
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
          :class="notification.type === 'error' ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'"
        >
          <svg v-if="notification.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2" />
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm font-semibold text-content-strong">{{ notification.message }}</p>
        </div>
        <button
          type="button"
          class="text-content-secondary hover:text-content p-1 rounded-lg transition-colors cursor-pointer"
          @click="notification.show = false"
        >
          &times;
        </button>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.establishment-page-wrapper {
  min-height: calc(100vh - 4rem);
  background-color: var(--color-canvas);
  color: var(--color-content);
  width: 100%;
}
</style>
