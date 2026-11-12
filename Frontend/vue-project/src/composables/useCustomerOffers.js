import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import { useLocationStore } from '@/stores/location';
import { calculateDistance, formatDistance } from '@/lib/helpers/geo';

export function useCustomerOffers() {
  const router = useRouter();
  const locationStore = useLocationStore();

  // Estado de datos
  const offers = ref([]);
  const originalOffers = ref([]);
  const loading = ref(true);
  const loadingMore = ref(false);
  const error = ref(null);
  const currentPage = ref(1);
  const hasMorePages = ref(true);
  const searchQuery = ref('');
  const isSearchActive = ref(false);

  // Filtros y modos
  const activeCategory = ref('all');
  const sortBy = ref('distance');
  const viewMode = ref('list'); // 'list' | 'map'
  const showLocationPicker = ref(false);

  // Modal de detalle
  const selectedOffer = ref({});
  const isModalOpen = ref(false);

  // Toast de feedback
  const notification = ref({
    show: false,
    message: '',
    type: 'success',
  });
  let toastTimer = null;

  const showNotification = (message, type = 'success') => {
    if (toastTimer) clearTimeout(toastTimer);
    notification.value = {
      show: true,
      message,
      type,
    };
    toastTimer = setTimeout(() => {
      notification.value.show = false;
    }, 3200);
  };

  const closeNotification = () => {
    notification.value.show = false;
  };

  const categories = [
    { id: 'all', label: 'Todos' },
    { id: 'bakery', label: 'Panaderías' },
    { id: 'restaurants', label: 'Restaurantes' },
    { id: 'groceries', label: 'Verdulerías' },
    { id: 'kiosks', label: 'Kioscos' },
  ];

  const sortOptions = computed(() => {
    const opts = [];
    if (locationStore.isActive) {
      opts.push({ value: 'distance', label: 'Más cercanas' });
    }
    opts.push(
      { value: 'discount', label: 'Mayor ahorro (%)' },
      { value: 'expiration', label: 'Retiro más próximo' },
      { value: 'price_asc', label: 'Menor precio' }
    );
    return opts;
  });

  // Ofertas con cálculo de distancia
  const offersWithDistance = computed(() => {
    if (!locationStore.isActive) return offers.value;
    return offers.value.map((offer) => {
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

    if (activeCategory.value !== 'all') {
      list = list.filter((o) => {
        const cat = (o.category || o.title || '').toLowerCase();
        switch (activeCategory.value) {
          case 'bakery':
            return cat.includes('panad') || cat.includes('pan') || cat.includes('factura') || cat.includes('medialuna');
          case 'restaurants':
            return cat.includes('restauran') || cat.includes('plato') || cat.includes('almuerzo') || cat.includes('comida') || cat.includes('pizza') || cat.includes('bistr') || cat.includes('rotiser');
          case 'groceries':
            return cat.includes('verdul') || cat.includes('fruta') || cat.includes('vegetal') || cat.includes('huerta');
          case 'kiosks':
            return cat.includes('kiosco') || cat.includes('golos') || cat.includes('snack') || cat.includes('almac') || cat.includes('bebida');
          default:
            return true;
        }
      });
    }

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

  // Carga de ofertas
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

      const response = await axiosInstance.get('/packs', { params });
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
      console.error('Error fetching offers:', err);
      error.value = 'Error al cargar las ofertas gastronómicas';
      if (!isLoadMore) {
        offers.value = [];
      }
    } finally {
      loading.value = false;
      loadingMore.value = false;
    }
  };

  const loadMoreOffers = async () => {
    if (loadingMore.value || !hasMorePages.value) return;
    await getOffers(currentPage.value + 1, true);
  };

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

  // Acciones de búsqueda
  const handleSearchResults = (query) => {
    searchQuery.value = query;
    isSearchActive.value = true;
    currentPage.value = 1;
    getOffers(1, false);
  };

  const handleSearchError = (msg) => {
    error.value = msg;
  };

  const handleSearchClear = () => {
    searchQuery.value = '';
    isSearchActive.value = false;
    currentPage.value = 1;
    getOffers(1, false);
  };

  // Carrito y compra directa
  const handleQuickAdd = async (offer) => {
    await addOfferToCart({ id: offer.id, quantity: 1 });
  };

  const addOfferToCart = async ({ id, quantity }) => {
    const offerId = id || selectedOffer.value?.id;
    if (!offerId) return;

    try {
      await axiosInstance.post('/add-to-cart', {
        offer_id: offerId,
        quantity: quantity || 1,
      });
      showNotification('¡Bolsa agregada a tu carrito con éxito!', 'success');
    } catch (err) {
      console.error('Error al agregar al carrito:', err);
      if (err.status === 400) {
        showNotification('Ya alcanzaste el límite de unidades disponibles para este pack', 'error');
      } else {
        showNotification('No se pudo agregar al carrito. Intenta nuevamente', 'error');
      }
    }
  };

  const buyOffer = async ({ id, quantity, food_establishment_id }) => {
    const estId = food_establishment_id || selectedOffer.value?.establishment?.id || selectedOffer.value?.food_establishment_id;
    const offerId = id || selectedOffer.value?.id;
    if (!estId || !offerId) {
      showNotification('No se pudo identificar el comercio para esta reserva', 'error');
      return;
    }

    try {
      showNotification('Preparando tu reserva...', 'info');
      const prepareResponse = await axiosInstance.post('/customer/pack-reservations/prepare', {
        food_establishment_id: estId,
        offers: [{ id: offerId, quantity: quantity || 1 }],
      });
      sessionStorage.setItem('purchaseConfirmation', JSON.stringify(prepareResponse.data.data));
      isModalOpen.value = false;

      router.push({
        name: 'purchase-confirmation',
        params: {
          token: prepareResponse.data.data.purchase_token,
        },
      });
    } catch (err) {
      console.error(err);
      showNotification('Error al procesar la reserva. Verifica los datos', 'error');
    }
  };

  // Modal helpers
  const openOfferModal = (offer) => {
    selectedOffer.value = offer;
    isModalOpen.value = true;
  };

  const closeOfferModal = () => {
    isModalOpen.value = false;
  };

  // Watchers y ciclo de vida
  watch(
    () => locationStore.radiusKm,
    () => {
      if (locationStore.isActive) {
        currentPage.value = 1;
        getOffers(1, false);
      }
    }
  );

  const resetFilters = () => {
    activeCategory.value = 'all';
    sortBy.value = locationStore.isActive ? 'distance' : 'discount';
    searchQuery.value = '';
    isSearchActive.value = false;
    getOffers(1, false);
  };

  onMounted(() => {
    getOffers();
    window.addEventListener('scroll', handleScroll);
  });

  onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (toastTimer) clearTimeout(toastTimer);
  });

  return {
    offers,
    filteredOffers,
    loading,
    loadingMore,
    error,
    currentPage,
    hasMorePages,
    searchQuery,
    activeCategory,
    sortBy,
    viewMode,
    categories,
    sortOptions,
    selectedOffer,
    isModalOpen,
    showLocationPicker,
    notification,
    locationStore,
    getOffers,
    handleSearchResults,
    handleSearchError,
    handleSearchClear,
    handleQuickAdd,
    addOfferToCart,
    buyOffer,
    openOfferModal,
    closeOfferModal,
    closeNotification,
    resetFilters,
  };
}
