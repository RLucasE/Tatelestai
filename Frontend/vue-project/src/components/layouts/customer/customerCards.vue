<script setup>
import axiosInstance from "@/lib/axios";
import CustomerCard from "./CustomerCard.vue";
import OfferModal from "../../common/OfferModal.vue";
import SearchBar from "../../common/SearchBar.vue";
import LocationBar from "../../common/LocationBar.vue";
import LocationPickerModal from "../../common/LocationPickerModal.vue";
import OffersMap from "../../common/OffersMap.vue";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useRouter } from 'vue-router';
import { useLocationStore } from '@/stores/location';
import { calculateDistance, formatDistance } from '@/lib/helpers/geo';

const router = useRouter();
const locationStore = useLocationStore();

// Estado de datos
const offers = ref([]);
const originalOffers = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const error = ref(null);
const selectedOffer = ref({});
const isVisible = ref(false);
const isSearchActive = ref(false);
const currentPage = ref(1);
const hasMorePages = ref(true);
const searchQuery = ref('');

// Geolocation state
const showLocationPicker = ref(false);
const viewMode = ref('list'); // 'list' | 'map'

// Filtros interactivos de categoría
const activeCategory = ref('all');
const sortBy = ref('distance');

const categories = [
  { id: 'all', label: 'Todas' },
  { id: 'surprise', label: 'Bolsas Sorpresa' },
  { id: 'bakery', label: 'Panaderías' },
  { id: 'dishes', label: 'Platos del Día' },
  { id: 'vegan', label: 'Vegano' },
  { id: 'pastry', label: 'Pastelería' },
];

// Ofertas con cálculo de distancia
const offersWithDistance = computed(() => {
  if (!locationStore.isActive) return offers.value;
  return offers.value.map(offer => {
    const lat = offer.establishment?.latitude ?? offer.establishment_latitude ?? null;
    const lng = offer.establishment?.longitude ?? offer.establishment_longitude ?? null;
    const hasCoords = lat != null && lng != null;
    const dist = hasCoords
      ? calculateDistance(locationStore.latitude, locationStore.longitude, lat, lng)
      : null;

    return {
      ...offer,
      _distance: dist,
      _distanceFormatted: dist != null ? formatDistance(dist) : null,
    };
  });
});

// Ofertas filtradas por categoría y ordenadas
const filteredOffers = computed(() => {
  let list = offersWithDistance.value;

  // Filtrado por categoría activa
  if (activeCategory.value !== 'all') {
    list = list.filter((o) => {
      const cat = (o.category || o.title || '').toLowerCase();
      switch (activeCategory.value) {
        case 'surprise':
          return cat.includes('bolsa') || cat.includes('pack') || cat.includes('sorpresa');
        case 'bakery':
          return cat.includes('panad') || cat.includes('pan');
        case 'dishes':
          return cat.includes('plato') || cat.includes('almuerzo') || cat.includes('comida') || cat.includes('pizza') || cat.includes('bistr');
        case 'vegan':
          return cat.includes('vegan') || cat.includes('vegetar') || cat.includes('saludable') || cat.includes('bowl');
        case 'pastry':
          return cat.includes('dulce') || cat.includes('pastel') || cat.includes('repost') || cat.includes('muffin') || cat.includes('cake');
        default:
          return true;
      }
    });
  }

  // Ordenamiento dinámico
  const sorted = [...list];
  if (sortBy.value === 'distance' && locationStore.isActive) {
    sorted.sort((a, b) => (a._distance ?? 99999) - (b._distance ?? 99999));
  } else if (sortBy.value === 'discount') {
    sorted.sort((a, b) => {
      const discA = a.minimum_value && a.price ? (a.minimum_value - a.price) / a.minimum_value : 0;
      const discB = b.minimum_value && b.price ? (b.minimum_value - b.price) / b.minimum_value : 0;
      return discB - discA;
    });
  } else if (sortBy.value === 'price_asc') {
    sorted.sort((a, b) => Number(a.price || 0) - Number(b.price || 0));
  } else if (sortBy.value === 'expiration') {
    sorted.sort((a, b) => {
      const timeA = a.expiration_datetime ? new Date(a.expiration_datetime).getTime() : 9999999999999;
      const timeB = b.expiration_datetime ? new Date(b.expiration_datetime).getTime() : 9999999999999;
      return timeA - timeB;
    });
  }

  return sorted;
});

// Sistema de notificaciones toast
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
  }, 3200);
};

// Carga de ofertas desde la API
const getOffers = async (page = 1, isLoadMore = false) => {
  try {
    if (isLoadMore) {
      loadingMore.value = true;
    } else {
      loading.value = true;
    }

    const params = { page };
    if (isSearchActive.value && searchQuery.value) {
      params.search = searchQuery.value;
    }
    if (locationStore.isActive) {
      params.lat = locationStore.latitude;
      params.lng = locationStore.longitude;
      params.radius = locationStore.radiusKm;
    }

    const response = await axiosInstance.get("/packs", { params });
    const fetchedOffers = response.data.data;
    currentPage.value = response.data.current_page;
    hasMorePages.value = response.data.has_more;

    if (isLoadMore) {
      offers.value = [...offers.value, ...fetchedOffers];
    } else {
      offers.value = fetchedOffers;
      originalOffers.value = fetchedOffers;
    }

    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar las ofertas gastronómicas";
    if (!isLoadMore) {
      offers.value = [];
    }
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

// Scroll infinito
const handleScroll = () => {
  const scrollHeight = document.documentElement.scrollHeight;
  const scrollTop = window.scrollY;
  const clientHeight = document.documentElement.clientHeight;

  if (scrollHeight - scrollTop - clientHeight < 250) {
    if (!loadingMore.value && hasMorePages.value) {
      loadMoreOffers();
    }
  }
};

const loadMoreOffers = async () => {
  if (loadingMore.value || !hasMorePages.value) return;
  await getOffers(currentPage.value + 1, true);
};

// Manejo de búsqueda
const handleSearchResults = (results) => {
  searchQuery.value = results;
  isSearchActive.value = true;
  currentPage.value = 1;
  getOffers(1, false);
};

const handleSearchError = (errorMessage) => {
  error.value = errorMessage;
};

const handleSearchClear = () => {
  searchQuery.value = '';
  isSearchActive.value = false;
  currentPage.value = 1;
  getOffers(1, false);
};

// Rescate rápido directo al carrito
const handleQuickAdd = async (offer) => {
  await addOfferToCart({ id: offer.id, quantity: 1 });
};

const addOfferToCart = async ({ id, quantity }) => {
  const offerId = id || selectedOffer.value?.id;
  if (!offerId) return;

  const offerPayload = {
    offer_id: offerId,
    quantity: quantity || 1,
  };

  try {
    await axiosInstance.post("/add-to-cart", offerPayload);
    showNotification('¡Bolsa agregada a tu carrito con éxito!', 'success');
  } catch (error) {
    console.error("Error al agregar al carrito:", error);
    if (error.status === 400) {
      showNotification('Ya alcanzaste el límite de unidades disponibles para este pack', 'error');
    } else {
      showNotification('No se pudo agregar al carrito. Intenta nuevamente', 'error');
    }
  }
};

// Compra directa / preparación
const buyOffer = async ({ id, quantity, food_establishment_id }) => {
  const estId = food_establishment_id || selectedOffer.value?.establishment?.id || selectedOffer.value?.food_establishment_id;
  const offerId = id || selectedOffer.value?.id;
  if (!estId || !offerId) {
    showNotification('No se pudo identificar el comercio para esta reserva', 'error');
    return;
  }

  const offerPayload = {
    food_establishment_id: estId,
    offers: [
      {
        id: offerId,
        quantity: quantity || 1,
      },
    ],
  };

  try {
    showNotification('Preparando tu reserva...', 'info');
    const prepareResponse = await axiosInstance.post("/prepare-purchase", offerPayload);
    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(prepareResponse.data.data));
    isVisible.value = false;

    router.push({
      name: 'purchase-confirmation',
      params: {
        token: prepareResponse.data.data.purchase_token,
      },
    });
  } catch (error) {
    console.error(error);
    showNotification('Error al procesar la reserva. Verifica los datos', 'error');
  }
};

// Apertura y cierre del modal de oferta
const handleOfferClick = (offer) => {
  selectedOffer.value = offer;
  isVisible.value = true;
};

const handleCloseOffer = () => {
  isVisible.value = false;
};

// Geolocalización
const handleLocationChanged = () => {
  currentPage.value = 1;
  getOffers(1, false);
};

const handleLocationConfirmed = () => {
  showLocationPicker.value = false;
  currentPage.value = 1;
  getOffers(1, false);
};

const handleMapOfferSelected = (offer) => {
  selectedOffer.value = offer;
  isVisible.value = true;
};

// Escucha reactiva de cambios en el radio
watch(
  () => locationStore.radiusKm,
  () => {
    if (locationStore.isActive) {
      currentPage.value = 1;
      getOffers(1, false);
    }
  }
);

onMounted(() => {
  getOffers();
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const changeLoading = (state) => {
  loading.value = state;
};
</script>

<template>
  <div class="offers-page-wrapper">
    <!-- Barra Superior Centrada: Búsqueda y Control de Ubicación -->
    <section class="search-location-bar-section mb-5">
      <div class="search-location-row flex items-center justify-between gap-2.5 max-w-3xl mx-auto">
        <!-- Input de Búsqueda -->
        <div class="flex-1 min-w-0">
          <SearchBar
            @search-results="handleSearchResults"
            @search-error="handleSearchError"
            @search-clear="handleSearchClear"
            @search-leading="changeLoading"
            placeholder="Buscar por comida, establecimiento o tipo de pack..."
          />
        </div>

        <!-- Selector de Proximidad / Ubicación -->
        <div class="shrink-0">
          <LocationBar
            @location-changed="handleLocationChanged"
            @open-picker="showLocationPicker = true"
          />
        </div>
      </div>
    </section>

    <!-- Fila Dinámica: Filtros de Categoría y Conmutador de Vista -->
    <section class="category-filters-section mb-6">
      <div class="flex items-center justify-between gap-3 flex-wrap border-b border-[#2D2438]/80 pb-3">
        <!-- Chips de Categoría Minimalistas -->
        <div class="flex items-center gap-1.5 overflow-x-auto py-1 scrollbar-none max-w-full">
          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="filter-chip inline-flex items-center px-3.5 py-1.5 rounded-full text-xs transition-all duration-150 shrink-0 select-none cursor-pointer"
            :class="[
              activeCategory === cat.id
                ? 'bg-[#7C3AED] text-white font-semibold shadow-xs'
                : 'text-[#9DA1BF] hover:text-white hover:bg-[#241D30] font-medium'
            ]"
            @click="activeCategory = cat.id"
          >
            <span>{{ cat.label }}</span>
          </button>
        </div>

        <!-- Conmutador Segmentado de Vista [ Lista | Mapa ] -->
        <div class="inline-flex items-center bg-[#181324] border border-[#2D2438] p-1 rounded-full text-xs font-medium shrink-0">
          <button
            type="button"
            class="px-3 py-1 rounded-full transition-all duration-150 flex items-center gap-1.5 cursor-pointer"
            :class="viewMode === 'list' ? 'bg-[#7C3AED] text-white font-semibold shadow-xs' : 'text-[#8E8BA7] hover:text-white'"
            @click="viewMode = 'list'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <line x1="8" y1="6" x2="21" y2="6" stroke-width="2"/>
              <line x1="8" y1="12" x2="21" y2="12" stroke-width="2"/>
              <line x1="8" y1="18" x2="21" y2="18" stroke-width="2"/>
              <line x1="3" y1="6" x2="3.01" y2="6" stroke-width="2"/>
              <line x1="3" y1="12" x2="3.01" y2="12" stroke-width="2"/>
              <line x1="3" y1="18" x2="3.01" y2="18" stroke-width="2"/>
            </svg>
            <span>Lista</span>
          </button>
          <button
            type="button"
            class="px-3 py-1 rounded-full transition-all duration-150 flex items-center gap-1.5 cursor-pointer"
            :class="viewMode === 'map' ? 'bg-[#7C3AED] text-white font-semibold shadow-xs' : 'text-[#8E8BA7] hover:text-white'"
            @click="viewMode = 'map'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" stroke-width="2"/>
              <line x1="8" y1="2" x2="8" y2="18"></line>
              <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <span>Mapa</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Fila de Resumen de Resultados y Ordenación Rápida -->
    <section v-if="viewMode === 'list'" class="flex items-center justify-between text-xs text-[#8E8BA7] mb-5 flex-wrap gap-2.5">
      <div class="flex items-center gap-1.5">
        <span><strong class="text-white font-semibold">{{ filteredOffers.length }}</strong> packs disponibles</span>
        <span v-if="locationStore.isActive" class="text-[#A78BFA]">
          • cerca de ti ({{ locationStore.radiusKm }} km)
        </span>
      </div>

      <!-- Selector de Ordenación Rápida -->
      <div class="flex items-center gap-2">
        <label for="sort-select" class="text-[#8E8BA7]">Ordenar:</label>
        <select
          id="sort-select"
          v-model="sortBy"
          class="bg-[#201A2C] border border-[#3D3450] text-[#E8EAF6] rounded-full px-3 py-1 text-xs focus:outline-none focus:border-[#7C3AED] cursor-pointer"
        >
          <option value="distance" v-if="locationStore.isActive">Más cercanas</option>
          <option value="discount">Mayor ahorro (%)</option>
          <option value="expiration">Retiro más próximo</option>
          <option value="price_asc">Menor precio</option>
        </select>
      </div>
    </section>


    <!-- Vista de Mapa -->
    <div v-if="viewMode === 'map'" class="map-view-container">
      <OffersMap
        :userLat="locationStore.latitude"
        :userLng="locationStore.longitude"
        :searchQuery="searchQuery"
        @offer-selected="handleMapOfferSelected"
      />
    </div>

    <!-- Vista de Lista -->
    <div v-else class="list-view-container">
      <!-- Loading Skeleton Cards (Reemplazo del spinner para mayor fluidez) -->
      <div v-if="loading && !loadingMore" class="offers-grid">
        <div
          v-for="n in 6"
          :key="n"
          class="skeleton-card bg-[#2D2438] border border-[#3D3450] rounded-2xl p-4 animate-pulse flex flex-col gap-2.5"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-[#3D3450]"></div>
              <div class="h-3.5 w-24 bg-[#3D3450] rounded"></div>
            </div>
            <div class="h-3.5 w-14 bg-[#3D3450] rounded-full"></div>
          </div>
          <div class="space-y-1.5">
            <div class="h-4 w-3/4 bg-[#3D3450] rounded"></div>
            <div class="h-3 w-full bg-[#3D3450]/70 rounded"></div>
          </div>
          <div class="flex gap-1.5">
            <div class="h-3.5 w-12 bg-[#3D3450]/60 rounded"></div>
            <div class="h-3.5 w-12 bg-[#3D3450]/60 rounded"></div>
          </div>
          <div class="space-y-2 pt-2 border-t border-[#3D3450]/40 mt-auto">
            <div class="h-5 w-40 bg-[#3D3450]/50 rounded"></div>
            <div class="flex items-center justify-between pt-1">
              <div class="h-6 w-20 bg-[#3D3450] rounded"></div>
              <div class="h-7 w-20 bg-[#7C3AED]/40 rounded-xl"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado de Error -->
      <div v-else-if="error" class="error-container text-center py-12 px-4 bg-[#2D2438] border border-[#EF4444]/40 rounded-2xl max-w-lg mx-auto">
        <div class="w-12 h-12 mx-auto mb-3 text-[#EF4444] flex items-center justify-center">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <p class="text-white font-semibold mb-4">{{ error }}</p>
        <button
          @click="getOffers"
          class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-5 py-2.5 rounded-xl transition cursor-pointer"
        >
          Reintentar carga
        </button>
      </div>

      <!-- Estado Vacío: Con ubicación activa -->
      <div
        v-else-if="filteredOffers.length === 0 && locationStore.isActive"
        class="empty-container text-center py-14 px-6 bg-[#2D2438] border border-[#4A4058] rounded-2xl max-w-xl mx-auto space-y-4"
      >
        <div class="w-14 h-14 mx-auto text-[#A78BFA] flex items-center justify-center">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="10" r="3" stroke-width="2"/>
          </svg>
        </div>
        <h3 class="text-lg font-bold text-white">No encontramos packs dentro de {{ locationStore.radiusKm }} km</h3>
        <p class="text-xs text-[#94A3B8]">
          Puedes ampliar tu radio de búsqueda o cambiar tu ubicación para explorar ofertas cercanas.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
          <button
            v-if="locationStore.radiusKm < 15"
            class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition cursor-pointer"
            @click="locationStore.setRadius(Math.min(locationStore.radiusKm * 2, 15)); handleLocationChanged()"
          >
            Ampliar radio a {{ Math.min(locationStore.radiusKm * 2, 15) }} km
          </button>
          <button
            class="bg-[#221C33] hover:bg-[#3D3450] border border-[#4A4058] text-[#E8EAF6] text-xs font-semibold px-4 py-2.5 rounded-xl transition cursor-pointer"
            @click="showLocationPicker = true"
          >
            Cambiar ubicación
          </button>
          <button
            class="text-[#94A3B8] hover:text-white text-xs underline px-2 py-1 cursor-pointer"
            @click="locationStore.clearLocation(); handleLocationChanged()"
          >
            Ver todo el catálogo
          </button>
        </div>
      </div>

      <!-- Estado Vacío: Sin filtros ni ubicación -->
      <div
        v-else-if="filteredOffers.length === 0"
        class="empty-container text-center py-14 px-6 bg-[#2D2438] border border-[#4A4058] rounded-2xl max-w-xl mx-auto space-y-3"
      >
        <div class="w-14 h-14 mx-auto text-[#7C3AED] flex items-center justify-center">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 6h18" stroke-width="2"/>
            <path d="M16 10a4 4 0 0 1-8 0" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h3 class="text-lg font-bold text-white">No hay packs disponibles en esta categoría</h3>
        <p class="text-xs text-[#94A3B8]">
          Prueba seleccionando otra categoría o limpiando los filtros de búsqueda.
        </p>
        <button
          v-if="activeCategory !== 'all'"
          class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-4 py-2 rounded-xl transition mt-2"
          @click="activeCategory = 'all'"
        >
          Ver todas las ofertas
        </button>
      </div>

      <!-- Cuadrícula de Tarjetas de Ofertas -->
      <div v-else>
        <div class="offers-grid">
          <CustomerCard
            v-for="offer in filteredOffers"
            :key="offer.id"
            :offer="offer"
            :distance="offer._distanceFormatted"
            @click="handleOfferClick(offer)"
            @quick-add="handleQuickAdd"
          />
        </div>

        <!-- Indicador de Carga Más Ofertas -->
        <div v-if="loadingMore" class="loading-more flex items-center justify-center gap-2 py-8 text-xs text-[#A78BFA]">
          <div class="w-4 h-4 border-2 border-[#7C3AED] border-t-transparent rounded-full animate-spin"></div>
          <span>Cargando más ofertas gastronómicas...</span>
        </div>

        <!-- Mensaje de Fin de Catálogo -->
        <div v-else-if="!hasMorePages && filteredOffers.length > 0" class="end-message text-center py-8 text-xs text-[#787596]">
          ✨ Has visto todas las ofertas disponibles en tu zona por el momento.
        </div>
      </div>
    </div>

    <!-- Modales -->
    <LocationPickerModal
      :isVisible="showLocationPicker"
      @close="showLocationPicker = false"
      @confirmed="handleLocationConfirmed"
    />

    <OfferModal
      :isVisible="isVisible"
      :offer="selectedOffer"
      @close="handleCloseOffer"
      @offerAction="addOfferToCart"
      @buyOffer="buyOffer"
    />

    <!-- Componente de Notificación Toast -->
    <Transition name="toast" appear>
      <div
        v-if="notification.show"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border backdrop-blur-md transition-all duration-300 max-w-sm"
        :class="[
          notification.type === 'error'
            ? 'bg-[#EF4444]/90 border-[#EF4444] text-white shadow-[#EF4444]/20'
            : notification.type === 'info'
            ? 'bg-[#3B82F6]/90 border-[#3B82F6] text-white shadow-[#3B82F6]/20'
            : 'bg-[#10B981]/90 border-[#10B981] text-white shadow-[#10B981]/20'
        ]"
      >
        <span class="shrink-0 flex items-center justify-center">
          <svg v-if="notification.type === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else-if="notification.type === 'info'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="16" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="22 4 12 14.01 9 11.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <p class="text-xs font-medium flex-1">{{ notification.message }}</p>
        <button
          type="button"
          @click="notification.show = false"
          class="text-white/70 hover:text-white text-xs ml-1"
          aria-label="Cerrar notificación"
        >
          ✕
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.offers-page-wrapper {
  padding: 16px 24px 64px;
  max-width: 1280px;
  margin: 0 auto;
  min-height: 100vh;
}

.offers-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

@media (max-width: 1080px) {
  .offers-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }
}

@media (max-width: 640px) {
  .offers-page-wrapper {
    padding: 12px 16px 56px;
  }

  .offers-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
}

.map-view-container {
  height: 600px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #4A4058;
}

/* Transición para el toast */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.95);
}
</style>
