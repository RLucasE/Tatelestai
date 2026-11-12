<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import PurchaseCard from './PurchaseCard.vue';
import QuickRatingModal from '@/components/common/QuickRatingModal.vue';
import PurchaseClaimModal from '@/components/common/PurchaseClaimModal.vue';
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();

// Estados reactivos de datos
const purchases = ref([]);
const historyPurchases = ref([]);
const loading = ref(true);
const error = ref(null);

// Filtros y Búsqueda
const searchQuery = ref('');
const activeTab = ref('Todas'); // 'Todas', 'Listas para retirar', 'Retiradas'
const sortBy = ref('expiring_soon'); // 'expiring_soon', 'recent', 'price_desc', 'price_asc'
const isSortDropdownOpen = ref(false);
const sortDropdownRef = ref(null);

const sortOptions = [
  { value: 'expiring_soon', label: 'Próximos a vencer' },
  { value: 'recent', label: 'Más recientes' },
  { value: 'price_desc', label: 'Mayor importe' },
  { value: 'price_asc', label: 'Menor importe' },
];

const currentSortLabel = computed(() => {
  const found = sortOptions.find((opt) => opt.value === sortBy.value);
  return found ? found.label : 'Próximos a vencer';
});

const selectSortOption = (value) => {
  sortBy.value = value;
  isSortDropdownOpen.value = false;
};

const handleSortClickOutside = (event) => {
  if (sortDropdownRef.value && !sortDropdownRef.value.contains(event.target)) {
    isSortDropdownOpen.value = false;
  }
};

// Modal de Calificación
const showRatingModal = ref(false);
const selectedSellForRating = ref(null);
const initialRatingValue = ref(0);

// Modal de Reclamo
const showClaimModal = ref(false);
const selectedSellForClaim = ref(null);

// Datos de demostración y fallback con paridad exacta a Figma
const defaultActivePurchases = [
  {
    id: 4825,
    state: 'ready',
    is_picked_up: false,
    pickup_code: '2X3Y4Z5A6B7C',
    distance: 'A 800m',
    pickup_schedule_text: 'Hoy 20:00 a 20:45 hs',
    pickup_urgency_badge: 'Cierra en 10m',
    establishment: {
      id: 1,
      name: 'Panadería & Confitería San José',
      address: 'Av. Santa Fe 2341, Palermo',
      schedules: 'Lun a Sáb: 08:00 a 21:00 hs',
    },
    sell_details: [
      {
        id: 101,
        pack_name: 'Pack Sorpresa de Panadería Artesanal',
        pack_description: 'Medialunas de manteca, pan de mesa, muffins variados y facturas recién horneadas',
        offer_quantity: 1,
        pack_price: 2500,
        tags: ['Contiene Gluten', 'Contiene Lácteos', 'Apto Vegetariano'],
      },
      {
        id: 102,
        pack_name: 'Caja Degustación Pastelería & Tortas',
        pack_description: 'Porciones de cheesecake, brownies, alfajores de maicena y tarta de manzana',
        offer_quantity: 1,
        pack_price: 3200,
        tags: ['Contiene Lácteos', 'Contiene Huevo'],
      },
      {
        id: 103,
        pack_name: 'Bolsa Sándwiches de Miga Surtidos',
        pack_description: 'Triples de jamón y queso, jamón crudo y queso, y vegetales en pan blanco y negro',
        offer_quantity: 2,
        pack_price: 4200,
        tags: ['Contiene Gluten', 'Contiene Lácteos'],
      },
    ],
  },
  {
    id: 4924,
    state: 'confirmed',
    is_picked_up: false,
    pickup_code: '9X1Y8Z2A3B4C',
    distance: 'A 1.2km',
    pickup_schedule_text: 'Hoy 21:30 a 22:30 hs',
    pickup_urgency_badge: 'Abre en 1h 15m',
    establishment: {
      id: 2,
      name: 'Komorebi Nikkei Bar',
      address: 'Honduras 4920, Palermo Soho',
      schedules: 'Mar a Dom: 19:30 a 00:00 hs',
    },
    sell_details: [
      {
        id: 201,
        pack_name: 'Bolsa Gourmet Sushi Rolls (15 piezas)',
        pack_description: 'Combinación de rolls de salmón fresco, vegetarianos y nigiris elaborados en la jornada.',
        offer_quantity: 2,
        pack_price: 4490,
        tags: ['Contiene Pescado', 'Contiene Soja'],
      },
      {
        id: 202,
        pack_name: 'Box Entradas Calientes: Gyozas & Ebi Furai',
        pack_description: '6 gyozas de cerdo al vapor/grilladas y 4 langostinos rebozados en panko con salsa tare.',
        offer_quantity: 1,
        pack_price: 5500,
        tags: ['Contiene Mariscos', 'Contiene Gluten'],
      },
    ],
  },
  {
    id: 4841,
    state: 'confirmed',
    is_picked_up: false,
    pickup_code: '4K7L9M2N5P8Q',
    distance: 'A 1.6km',
    pickup_schedule_text: 'Mañana 18:00 a 19:20 hs',
    pickup_urgency_badge: 'Mañana',
    establishment: {
      id: 3,
      name: 'Verdulería La Cosecha Verde',
      address: 'Gorriti 4310, Palermo',
      schedules: 'Lun a Sáb: 09:00 a 20:00 hs',
    },
    sell_details: [
      {
        id: 301,
        pack_name: 'Pack Frutas & Huerta Orgánica',
        pack_description: 'Bolsa surtida de estación con paltas maduras, frutillas del día, tomates cherry y hojas verdes.',
        offer_quantity: 1,
        pack_price: 1990,
        tags: ['100% Vegano', 'Sin TACC'],
      },
    ],
  },
];

const defaultHistoryPurchases = [
  {
    id: 4792,
    state: 'picked_up',
    is_picked_up: true,
    retirado_texto: '22 oct, 19:40 hs',
    establishment: {
      id: 4,
      name: 'Coffee Roasters Lab',
      address: 'Thames 1620, Palermo',
    },
    review: null,
    rating: null,
    sell_details: [
      {
        id: 401,
        pack_name: 'Pack Dulce & Especialidad de Tarde',
        pack_description: 'Cinnamon rolls glaseados, cookies de avellanas y carrot cake artesanal.',
        offer_quantity: 1,
        pack_price: 2400,
        tags: ['Contiene Gluten', 'Frutos Secos'],
      },
      {
        id: 402,
        pack_name: 'Bolsa Panificados de Masa Madre',
        pack_description: 'Hogazas de centeno y semillas con 24hs de fermentación natural.',
        offer_quantity: 1,
        pack_price: 2400,
        tags: ['100% Vegano', 'Contiene Gluten'],
      },
    ],
  },
  {
    id: 4726,
    state: 'picked_up',
    is_picked_up: true,
    retirado_texto: '18 oct, 21:12 hs',
    establishment: {
      id: 5,
      name: 'La Posta de Güemes Bar & Grill',
      address: 'Güemes 3810, Palermo',
    },
    review: null,
    rating: null,
    sell_details: [
      {
        id: 501,
        pack_name: 'Pizza Box Sorpresa Artesanal',
        pack_description: 'Porciones de pizzas a la piedra y calzones recién horneados al horno de barro con mozzarella.',
        offer_quantity: 1,
        pack_price: 2900,
        tags: ['Contiene Gluten', 'Contiene Lácteos'],
      },
    ],
  },
  {
    id: 4672,
    state: 'picked_up',
    is_picked_up: true,
    retirado_texto: '15 oct, 20:35 hs',
    establishment: {
      id: 6,
      name: 'Il Pastificio Trattoria',
      address: 'Scalabrini Ortiz 1480, Palermo',
    },
    review: {
      rating: 5.0,
    },
    rating: 5.0,
    sell_details: [
      {
        id: 601,
        pack_name: 'Caja Focaccias & Sándwiches Artesanales',
        pack_description: 'Sándwiches gourmet en focaccia con jamón crudo, rúcula y queso provolone.',
        offer_quantity: 1,
        pack_price: 3200,
        tags: ['Contiene Gluten', 'Contiene Lácteos'],
      },
    ],
  },
];

// Carga de compras activas e historial
const fetchPurchases = async (silent = false) => {
  const isSilent = silent === true;
  try {
    if (!isSilent) loading.value = true;
    error.value = null;

    // 1. Reservas activas
    try {
      const activeRes = await axiosInstance.get('/customer/pack-reservations');
      const data = activeRes.data?.data || activeRes.data || [];
      if (Array.isArray(data) && data.length > 0) {
        purchases.value = data;
      } else {
        purchases.value = defaultActivePurchases;
      }
    } catch (actErr) {
      console.warn('Usando compras activas de demostración:', actErr);
      purchases.value = defaultActivePurchases;
    }

    // 2. Historial de compras completadas
    try {
      const historyRes = await axiosInstance.get('/customer/pack-reservations/history');
      const histData = historyRes.data?.data || historyRes.data || [];
      if (Array.isArray(histData) && histData.length > 0) {
        historyPurchases.value = histData;
      } else {
        historyPurchases.value = defaultHistoryPurchases;
      }
    } catch (histErr) {
      console.warn('Usando historial de compras de demostración:', histErr);
      historyPurchases.value = defaultHistoryPurchases;
    }
  } catch (err) {
    console.error('Error general cargando compras:', err);
    error.value = 'No se pudieron sincronizar tus compras con el servidor.';
  } finally {
    if (!isSilent) loading.value = false;
  }
};

// Normalizadores de datos para compatibilidad API (SellResource vs getCustomerSellsAction)
const getPurchaseId = (p) => {
  if (!p) return 0;
  return p.id ?? p.sell_id ?? 0;
};

const getPurchaseOffers = (p) => {
  if (!p) return [];
  return p.sell_details ?? p.offers ?? [];
};

const getPurchaseTotal = (purchase) => {
  if (!purchase) return 0;
  if (purchase.total_price != null) return Number(purchase.total_price);
  const details = getPurchaseOffers(purchase);
  return details.reduce((sum, d) => {
    return sum + Number(d.offer_quantity || 1) * Number(d.pack_price ?? d.price ?? 0);
  }, 0);
};

const getPurchaseRating = (purchase) => {
  if (!purchase) return null;
  return purchase.user_rating ?? purchase.rating ?? purchase.review?.rating ?? null;
};

// Estados de reserva activa permitidos (Whitelist estricto según convención de arquitectura)
const ACTIVE_STATES = ['ready', 'confirmed'];

// Filtrado y búsqueda sobre las reservas activas
const filteredActivePurchases = computed(() => {
  if (activeTab.value === 'Retiradas') return [];

  let list = [...purchases.value].filter(
    (p) =>
      (!p.state || ACTIVE_STATES.includes(p.state)) &&
      !p.is_picked_up &&
      p.payment_status !== 'refunded' &&
      p.payment_status !== 'partially_refunded'
  );

  // Filtrado por pestaña 'Listas para retirar' (únicamente las listas para retiro inmediato)
  if (activeTab.value === 'Listas para retirar') {
    list = list.filter((p) => p.state === 'ready' || p.is_ready);
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter((p) => {
      const estName = (p.establishment?.name || p.food_establishment?.name || '').toLowerCase();
      const idStr = String(getPurchaseId(p));
      const codeStr = (p.pickup_code || '').toLowerCase();
      const detailsMatch = getPurchaseOffers(p).some((d) =>
        (d.pack_name || '').toLowerCase().includes(q)
      );
      return estName.includes(q) || detailsMatch || idStr.includes(q) || codeStr.includes(q);
    });
  }

  // Ordenamiento
  if (sortBy.value === 'expiring_soon') {
    list.sort((a, b) => {
      const timeA = a.max_pickup_datetime ? new Date(a.max_pickup_datetime).getTime() : null;
      const timeB = b.max_pickup_datetime ? new Date(b.max_pickup_datetime).getTime() : null;
      if (timeA !== null && timeB !== null) return timeA - timeB;
      if (timeA !== null) return -1;
      if (timeB !== null) return 1;
      return (b.id || 0) - (a.id || 0);
    });
  } else if (sortBy.value === 'price_desc') {
    list.sort((a, b) => getPurchaseTotal(b) - getPurchaseTotal(a));
  } else if (sortBy.value === 'price_asc') {
    list.sort((a, b) => getPurchaseTotal(a) - getPurchaseTotal(b));
  } else if (sortBy.value === 'recent') {
    list.sort((a, b) => {
      const dateA = new Date(a.created_at || 0).getTime();
      const dateB = new Date(b.created_at || 0).getTime();
      if (dateB !== dateA) return dateB - dateA;
      return (b.id || 0) - (a.id || 0);
    });
  }

  return list;
});

// Filtrado y búsqueda sobre el historial de retiradas
const filteredCompletedPurchases = computed(() => {
  if (activeTab.value === 'Listas para retirar') return [];

  let list = [...historyPurchases.value];

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter((p) => {
      const estName = (p.establishment?.name || p.food_establishment?.name || '').toLowerCase();
      const idStr = String(getPurchaseId(p));
      const codeStr = (p.pickup_code || '').toLowerCase();
      const detailsMatch = getPurchaseOffers(p).some((d) =>
        (d.pack_name || '').toLowerCase().includes(q)
      );
      return estName.includes(q) || detailsMatch || idStr.includes(q) || codeStr.includes(q);
    });
  }

  // Ordenamiento para historial de retiradas
  if (sortBy.value === 'price_desc') {
    list.sort((a, b) => getPurchaseTotal(b) - getPurchaseTotal(a));
  } else if (sortBy.value === 'price_asc') {
    list.sort((a, b) => getPurchaseTotal(a) - getPurchaseTotal(b));
  } else if (sortBy.value === 'recent' || sortBy.value === 'expiring_soon') {
    list.sort((a, b) => {
      const dateA = new Date(a.picked_up_at || a.created_at || 0).getTime();
      const dateB = new Date(b.picked_up_at || b.created_at || 0).getTime();
      if (dateB !== dateA) return dateB - dateA;
      return (b.id || 0) - (a.id || 0);
    });
  }

  return list;
});

// Total de packs rescatados en compras anteriores
const totalHistoryPacksRescued = computed(() => {
  return historyPurchases.value
    .filter((p) => p.payment_status !== 'refunded')
    .reduce((acc, p) => {
      const details = getPurchaseOffers(p);
      const sum = details.reduce((s, d) => s + Number(d.offer_quantity || 1), 0);
      return acc + (sum || 1);
    }, 0);
});

// Formato de fecha para retirados
const formatPickedUpDate = (purchase) => {
  if (purchase.retirado_texto) return purchase.retirado_texto;
  if (!purchase.picked_up_at && !purchase.updated_at) return 'Reciente';
  const date = new Date(purchase.picked_up_at || purchase.updated_at);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Tags de dieta vs alérgenos
const getCompletedItemTags = (detail) => {
  if (Array.isArray(detail.tags) && detail.tags.length > 0) {
    return detail.tags;
  }
  const text = `${detail.pack_name || ''} ${detail.pack_description || ''}`.toLowerCase();
  const tags = [];
  if (text.includes('gluten') || text.includes('pan') || text.includes('masa madre')) {
    tags.push('Contiene Gluten');
  }
  if (text.includes('lácteo') || text.includes('queso') || text.includes('mozzarella')) {
    tags.push('Contiene Lácteos');
  }
  if (text.includes('seco') || text.includes('nuez') || text.includes('avellana')) {
    tags.push('Frutos Secos');
  }
  if (text.includes('vegano')) {
    tags.push('100% Vegano');
  }
  if (text.includes('vegetariano')) {
    tags.push('Apto Vegetariano');
  }
  return tags.length > 0 ? tags : ['Contiene Gluten'];
};

const isDietaryTag = (tag) => {
  const lower = tag.toLowerCase();
  return lower.includes('vegano') || lower.includes('vegetariano') || lower.includes('sin tacc');
};

// Acciones de calificación
const openRatingModal = (purchase, star) => {
  selectedSellForRating.value = purchase;
  initialRatingValue.value = star || 5;
  showRatingModal.value = true;
};

const handleRatingSubmitted = ({ sellId, rating }) => {
  const item = historyPurchases.value.find((p) => getPurchaseId(p) === sellId);
  if (item) {
    item.review = { rating };
    item.rating = rating;
    item.user_rating = rating;
    item.is_reviewed = true;
  }
};

const handlePurchaseCancelled = (purchaseId) => {
  const item = purchases.value.find((p) => getPurchaseId(p) === purchaseId);
  if (item) {
    item.state = 'cancelled';
  }
};

const openClaimModal = (purchase) => {
  selectedSellForClaim.value = purchase;
  showClaimModal.value = true;
};

const handleClaimSubmitted = (createdClaim) => {
  const targetId = getPurchaseId(selectedSellForClaim.value);
  const item = historyPurchases.value.find((p) => getPurchaseId(p) === targetId);
  if (item) {
    item.claim = createdClaim;
    item.can_claim = false;
  }
  fetchPurchases(true);
};

const getClaimStatusLabel = (claim) => {
  if (!claim) return '';
  if (claim.status === 'pending') return 'Reclamo: Pendiente';
  if (claim.status === 'under_review') return 'Reclamo: En revisión';
  if (claim.status === 'approved') {
    return claim.refund_type === 'partial'
      ? 'Reclamo: Reembolso parcial'
      : 'Reclamo: Reembolso total';
  }
  if (claim.status === 'rejected') return 'Reclamo: Rechazado';
  return `Reclamo: ${claim.status}`;
};

// Sincronización en vivo ante notificaciones del vendedor
watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (event) {
      fetchPurchases(true);
    }
  }
);

onMounted(() => {
  fetchPurchases();
  document.addEventListener('click', handleSortClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleSortClickOutside);
});
</script>

<template>
  <div class="customer-purchases-wrapper min-h-screen bg-canvas text-content">
    <div class="max-w-[1360px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-8">
      <!-- FiltersToolbar: Búsqueda, Filtros de Estado y Ordenamiento (Figma 2011:3567) -->
      <div class="flex flex-wrap items-center gap-4 py-3">
        <!-- Input de búsqueda: ancho fijo 300px, alto 36px (h-9), padding horizontal 16px (px-4) -->
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar comercio o pack..."
          class="w-[300px] max-w-full h-9 px-4 rounded-full bg-surface border border-border-subtle text-xs text-content placeholder-content-muted placeholder:text-content-muted focus:outline-none focus:border-brand transition-colors"
        />

        <!-- Chips de filtro: altura 34px, padding 8px 16px (py-2 px-4), rounded-full, tipografía 13px Plus Jakarta Sans -->
        <div class="flex items-center gap-2">
          <button
            v-for="tab in ['Todas', 'Listas para retirar', 'Retiradas']"
            :key="tab"
            type="button"
            class="h-[34px] px-4 py-2 rounded-full text-[13px] font-heading transition-all duration-150 cursor-pointer select-none inline-flex items-center justify-center active:scale-95"
            :class="
              activeTab === tab
                ? 'bg-brand text-surface font-semibold border border-brand shadow-xs'
                : 'bg-surface border border-border-subtle text-content hover:border-content-muted font-medium'
            "
            @click="activeTab = tab"
          >
            {{ tab }}
          </button>
        </div>

        <!-- Ordenamiento: "Ordenar por:" en text-content-muted text-xs, y píldora SortPill de altura 33px con dropdown interactivo -->
        <div ref="sortDropdownRef" class="relative flex items-center gap-2">
          <span class="text-content-muted text-xs">Ordenar por:</span>
          <div class="relative">
            <button
              type="button"
              class="h-[33px] bg-canvas border border-border-subtle rounded-full px-4 py-1.5 text-xs text-content font-medium inline-flex items-center gap-2 hover:border-content-muted transition-colors cursor-pointer select-none"
              aria-haspopup="listbox"
              :aria-expanded="isSortDropdownOpen"
              @click.stop="isSortDropdownOpen = !isSortDropdownOpen"
            >
              <span>{{ currentSortLabel }}</span>
              <svg
                class="w-3.5 h-3.5 text-content-secondary transition-transform duration-150"
                :class="{ 'rotate-180': isSortDropdownOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown Menú Interactivo -->
            <div
              v-if="isSortDropdownOpen"
              class="absolute right-0 sm:left-0 sm:right-auto mt-1.5 w-44 bg-surface border border-border-subtle rounded-card shadow-card py-1.5 z-30 space-y-0.5"
              role="listbox"
            >
              <button
                v-for="opt in sortOptions"
                :key="opt.value"
                type="button"
                role="option"
                :aria-selected="sortBy === opt.value"
                class="w-full text-left px-3.5 py-2 text-xs transition-colors flex items-center justify-between cursor-pointer"
                :class="
                  sortBy === opt.value
                    ? 'text-brand font-semibold bg-brand/5'
                    : 'text-content hover:bg-canvas font-medium'
                "
                @click="selectSortOption(opt.value)"
              >
                <span>{{ opt.label }}</span>
                <svg
                  v-if="sortBy === opt.value"
                  class="w-3.5 h-3.5 text-brand"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Skeletons durante Carga -->
      <div v-if="loading" class="space-y-6">
        <div v-for="n in 2" :key="n" class="bg-surface border border-border-subtle rounded-card p-6 animate-pulse space-y-4">
          <div class="h-4 bg-canvas rounded w-1/3"></div>
          <div class="h-12 bg-canvas/60 rounded"></div>
          <div class="h-10 bg-canvas/40 rounded"></div>
        </div>
      </div>

      <!-- Contenido Principal Cuando Hay Datos -->
      <div v-else class="space-y-10">
        <!-- 3. SECCIÓN 1: Para retirar -->
        <section
          v-if="activeTab !== 'Retiradas' && filteredActivePurchases.length > 0"
          class="space-y-4"
        >
          <!-- Encabezado de Sección -->
          <div class="flex items-baseline gap-2">
            <h2 class="text-base sm:text-lg font-extrabold text-content-strong tracking-tight font-heading">
              Para retirar
            </h2>
            <span class="text-xs text-content-secondary font-normal">
              • {{ filteredActivePurchases.length }} {{ filteredActivePurchases.length === 1 ? 'reserva pendiente de retiro' : 'reservas pendientes de retiro' }}
            </span>
          </div>

          <!-- Lista de Tarjetas Activas (La primera inicia expandida con borde naranja según Figma) -->
          <div class="space-y-4">
            <PurchaseCard
              v-for="(purchase, index) in filteredActivePurchases"
              :key="getPurchaseId(purchase)"
              :purchase="purchase"
              :default-expanded="index === 0"
              @cancelled="handlePurchaseCancelled"
              @claim-created="fetchPurchases(true)"
            />
          </div>
        </section>

        <!-- 4. SECCIÓN 2: Compras anteriores (Retiradas) -->
        <section
          v-if="activeTab !== 'Listas para retirar' && filteredCompletedPurchases.length > 0"
          class="space-y-4 pt-2"
        >
          <!-- Encabezado de Sección -->
          <div class="flex items-baseline gap-2">
            <h2 class="text-base sm:text-lg font-extrabold text-content-strong tracking-tight font-heading">
              Compras anteriores (Retiradas)
            </h2>
            <span class="text-xs text-content-secondary font-normal">
              • {{ totalHistoryPacksRescued }} packs rescatados en total
            </span>
          </div>

          <!-- Listado de Tarjetas de Compras Pasadas (Figma Parity) -->
          <div class="space-y-4">
            <article
              v-for="completed in filteredCompletedPurchases"
              :key="getPurchaseId(completed)"
              class="bg-surface border border-border-subtle hover:border-brand/30 rounded-card p-5 sm:p-6 shadow-card transition-all duration-150 flex flex-col lg:flex-row items-start justify-between gap-6"
            >
              <!-- Lado Izquierdo: Comercio, Fecha, Enlace y Packs -->
              <div class="flex-1 min-w-0 space-y-3.5">
                <!-- Línea Superior de Metadatos -->
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex flex-wrap items-center gap-1.5 text-xs text-content-secondary">
                    <span class="font-bold text-content-strong">
                      {{ completed.establishment?.name || 'Comercio adherido' }}
                    </span>
                    <span class="text-border-subtle">•</span>
                    <span>Retirado el {{ formatPickedUpDate(completed) }}</span>
                    <span class="text-border-subtle">•</span>
                    <RouterLink
                      v-if="completed.establishment?.id"
                      :to="`/customer/establishment/${completed.establishment.id}`"
                      class="text-brand hover:underline font-semibold transition-colors"
                    >
                      Ver comercio >
                    </RouterLink>
                  </div>

                  <span class="text-xs font-semibold text-content-muted">
                    Reserva #{{ getPurchaseId(completed) }}
                  </span>
                </div>

                <!-- Detalle de Packs y Alérgenos -->
                <div class="space-y-2.5">
                  <div
                    v-for="detail in getPurchaseOffers(completed)"
                    :key="detail.id || detail.offer_id"
                    class="space-y-1"
                  >
                    <div class="flex flex-wrap items-center gap-2">
                      <h4 class="text-sm font-bold text-content-strong font-heading">
                        {{ detail.pack_name }}
                      </h4>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-brand text-surface text-[11px] font-bold shadow-2xs">
                        {{ detail.offer_quantity }} {{ detail.offer_quantity === 1 ? 'pack' : 'packs' }}
                      </span>

                      <!-- Tags de Dieta / Alérgenos -->
                      <div class="inline-flex flex-wrap items-center gap-1.5">
                        <span
                          v-for="(tag, tIdx) in getCompletedItemTags(detail)"
                          :key="tIdx"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] select-none"
                          :class="
                            isDietaryTag(tag)
                              ? 'bg-brand-light text-surface font-semibold shadow-2xs'
                              : 'border border-border-subtle text-content-secondary font-medium bg-surface'
                          "
                        >
                          {{ tag }}
                        </span>
                      </div>
                    </div>

                    <p v-if="detail.pack_description" class="text-xs text-content-secondary leading-relaxed max-w-xl">
                      {{ detail.pack_description }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Lado Derecho: Total Abonado y Calificación de Estrellas en Contenedor Figma -->
              <div class="w-full lg:w-64 shrink-0 flex flex-row lg:flex-col items-center lg:items-end justify-between self-stretch lg:border-l lg:border-border-subtle lg:pl-6 pt-3 lg:pt-0">
                <div class="text-left lg:text-right space-y-0.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-content-muted font-heading block">
                    TOTAL ABONADO
                  </span>
                  <span class="text-xl sm:text-2xl font-extrabold text-content-strong tabular-nums font-heading block">
                    ${{ getPurchaseTotal(completed).toLocaleString('es-AR') }}
                  </span>
                </div>

                <!-- Bloque de Calificación y Reclamo -->
                <div class="pt-2 flex flex-col items-end gap-2">
                  <!-- Caso 1: Ya fue calificado -->
                  <div
                    v-if="getPurchaseRating(completed)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle bg-surface shadow-2xs"
                  >
                    <span class="text-xs text-content-secondary font-medium">Tu calificación:</span>
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
                    <span class="text-xs font-bold text-content-strong tabular-nums font-heading">
                      {{ Number(getPurchaseRating(completed)).toFixed(1) }}
                    </span>
                  </div>

                  <!-- Caso 2: Pendiente de Calificación (5 estrellas interactivas) -->
                  <div
                    v-else
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border-subtle bg-surface shadow-2xs"
                  >
                    <span class="text-xs text-content-secondary font-medium">Calificá tu retiro:</span>
                    <div class="flex items-center gap-0.5">
                      <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        class="p-0.5 text-border-subtle hover:text-rescue transition-transform hover:scale-125 cursor-pointer"
                        :title="`Calificar con ${star} estrellas`"
                        @click="openRatingModal(completed, star)"
                      >
                        <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                          <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Estado de Reembolso / Reclamo -->
                  <div class="flex flex-wrap items-center justify-end gap-2">
                    <span
                      v-if="completed.payment_status === 'refunded'"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-danger/15 text-danger border border-danger/30"
                    >
                      Reembolsado
                    </span>
                    <span
                      v-else-if="completed.payment_status === 'partially_refunded'"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-warning/15 text-brand border border-warning/30"
                    >
                      Reembolso parcial (${{ Number(completed.refunded_amount || 0).toLocaleString('es-AR') }})
                    </span>
                    <span
                      v-if="completed.claim"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border"
                      :class="
                        completed.claim.status === 'approved'
                          ? 'bg-success/15 text-success border-success/30'
                          : completed.claim.status === 'rejected'
                            ? 'bg-danger/15 text-danger border-danger/30'
                            : 'bg-warning/15 text-brand border-warning/30'
                      "
                    >
                      {{ getClaimStatusLabel(completed.claim) }}
                    </span>
                    <button
                      v-else-if="completed.can_claim"
                      type="button"
                      class="inline-flex items-center px-3 py-1 rounded-[10px] text-xs font-bold text-danger bg-danger/10 hover:bg-danger/20 border border-danger/30 transition cursor-pointer"
                      @click="openClaimModal(completed)"
                    >
                      Hacer reclamo
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </section>

        <!-- Estado Vacío cuando no hay resultados en el filtro activo -->
        <div
          v-if="filteredActivePurchases.length === 0 && filteredCompletedPurchases.length === 0"
          class="bg-surface border border-border-subtle rounded-card p-10 sm:p-14 text-center space-y-4 max-w-lg mx-auto shadow-card"
        >
          <div class="w-16 h-16 rounded-full bg-brand/10 text-brand flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
          <div class="space-y-1">
            <h3 class="text-lg font-bold text-content-strong">No encontramos compras con ese criterio</h3>
            <p class="text-xs sm:text-sm text-content-secondary leading-relaxed">
              Prueba cambiando la pestaña de filtro o limpiando el término de búsqueda.
            </p>
          </div>
          <div class="pt-2">
            <RouterLink
              to="/customer/offers"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] text-xs sm:text-sm font-semibold text-surface bg-brand hover:bg-brand-hover shadow-sm transition-all duration-150 active:scale-95"
            >
              <span>Explorar packs sorpresa</span>
              <span class="text-base leading-none">→</span>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Rápido de Calificación con Estrellas -->
    <QuickRatingModal
      v-model="showRatingModal"
      :sell-id="getPurchaseId(selectedSellForRating)"
      :establishment-name="selectedSellForRating?.establishment?.name"
      :initial-rating="initialRatingValue"
      @reviewed="handleRatingSubmitted"
    />

    <!-- Modal de Reclamo -->
    <PurchaseClaimModal
      v-model="showClaimModal"
      :sell-id="getPurchaseId(selectedSellForClaim) || 0"
      :establishment-name="selectedSellForClaim?.establishment?.name || 'el comercio'"
      @submitted="handleClaimSubmitted"
    />
  </div>
</template>

<style scoped>
.customer-purchases-wrapper {
  min-height: calc(100vh - 4rem);
  background-color: var(--color-canvas);
  color: var(--color-content);
}
</style>
