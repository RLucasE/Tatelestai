<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import OfferModal from '@/components/common/OfferModal.vue';
import CustomerCard from './CustomerCard.vue';
import ReportModal from '@/components/common/ReportModal.vue';

const route = useRoute();
const router = useRouter();

const establishment = ref(null);
const offers = ref([]);
const loading = ref(true);
const error = ref(null);
const selectedOffer = ref(null);
const showOfferModal = ref(false);
const showReportModal = ref(false);

const establishmentId = computed(() => route.params.id);

// Iniciales del establecimiento para el avatar
const establishmentInitials = computed(() => {
  const name = establishment.value?.name?.trim() || '';
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'CA';
});

// Sistema de notificaciones
const notification = ref({
  show: false,
  message: '',
  type: 'success', // 'success', 'error', 'info'
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

    // 2. Obtener packs activos de este establecimiento específico
    const packsResponse = await axiosInstance.get('/packs', {
      params: {
        food_establishment_id: establishmentId.value,
        per_page: 50,
      },
    });

    const activePacks = packsResponse.data?.data || [];
    offers.value = activePacks;

    // Si establishment no cargó por la ruta directa y hay packs, obtener datos del primer pack
    if (!establishment.value && activePacks.length > 0) {
      const first = activePacks[0];
      establishment.value = {
        id: first.food_establishment_id,
        name: first.establishment?.name || first.establishment_name || 'Comercio adherido',
        address: first.establishment?.address || first.establishment_address || '',
        establishment_type: first.establishment?.type || first.establishment_type,
        phone_number: first.establishment?.phone || first.establishment_phone,
        email: first.establishment?.email || first.establishment_email,
        description: first.establishment?.description || first.establishment_description,
      };
    } else if (!establishment.value) {
      establishment.value = {
        id: parseInt(establishmentId.value, 10),
        name: 'Comercio adherido',
      };
    }
  } catch (err) {
    console.error('Error al cargar datos del establecimiento:', err);
    error.value = 'No se pudieron cargar las ofertas del establecimiento.';
  } finally {
    loading.value = false;
  }
};

const openOfferModal = (offer) => {
  selectedOffer.value = offer;
  showOfferModal.value = true;
};

const closeOfferModal = () => {
  showOfferModal.value = false;
  selectedOffer.value = null;
};

// Agregar al carrito desde el botón comprar de CustomerCard
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

    const prepareResponse = await axiosInstance.post('/prepare-purchase', offerPayload);
    sessionStorage.setItem('purchaseConfirmation', JSON.stringify(prepareResponse.data.data));

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
    <div class="establishment-container max-w-6xl mx-auto px-4 py-6">
      <!-- Navegación Superior -->
      <nav class="mb-4">
        <button
          type="button"
          class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-[#A5A8C2] hover:text-white transition-colors cursor-pointer group"
          @click="goBack"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Volver al Catálogo</span>
        </button>
      </nav>

      <!-- Panel de Información del Establecimiento -->
      <header
        v-if="establishment"
        class="bg-[#221C33] border border-[#3D3450] rounded-2xl p-5 sm:p-6 mb-8 shadow-lg relative overflow-hidden"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <!-- Datos del comercio con avatar -->
          <div class="flex items-start gap-4 min-w-0 flex-1">
            <span class="w-12 h-12 rounded-2xl bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-[#A78BFA] font-bold text-lg flex items-center justify-center shrink-0 shadow-inner">
              {{ establishmentInitials }}
            </span>

            <div class="space-y-1.5 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                  {{ establishment.name }}
                </h1>
                <span
                  v-if="establishment.establishment_type"
                  class="bg-white/[0.05] border border-white/[0.1] text-xs text-[#CBD5E1] px-2.5 py-0.5 rounded-full font-medium"
                >
                  {{ establishment.establishment_type }}
                </span>
                <span
                  v-if="offers.length > 0"
                  class="bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-[#A78BFA] text-xs font-semibold px-2 py-0.5 rounded-md"
                >
                  {{ offers.length }} {{ offers.length === 1 ? 'pack activo' : 'packs activos' }}
                </span>
              </div>

              <!-- Dirección física -->
              <p v-if="establishment.address" class="text-xs sm:text-sm text-[#A5A8C2] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ establishment.address }}</span>
              </p>

              <!-- Teléfono o Email si existen -->
              <div v-if="establishment.phone_number || establishment.phone || establishment.email" class="flex flex-wrap items-center gap-3 text-xs text-[#787596] pt-0.5">
                <span v-if="establishment.phone_number || establishment.phone" class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  <span>{{ establishment.phone_number || establishment.phone }}</span>
                </span>
                <span v-if="establishment.email" class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  <span>{{ establishment.email }}</span>
                </span>
              </div>

              <!-- Descripción -->
              <p v-if="establishment.description" class="text-xs text-[#94A3B8] pt-1 leading-relaxed max-w-2xl">
                {{ establishment.description }}
              </p>
            </div>
          </div>

          <!-- Acciones de cabecera -->
          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="border border-[#F59E0B]/30 hover:bg-[#F59E0B]/15 text-[#F59E0B] p-2 rounded-xl transition-colors cursor-pointer"
              @click="openReportModal"
              title="Reportar este establecimiento"
              aria-label="Reportar establecimiento"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </button>
          </div>
        </div>
      </header>

      <!-- Estado de Carga -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4.5">
        <div
          v-for="i in 3"
          :key="i"
          class="bg-[#2D2438] border border-[#3D3450] rounded-xl p-4 animate-pulse space-y-3"
        >
          <div class="h-4 bg-[#3D3450] rounded w-2/3"></div>
          <div class="h-3 bg-[#3D3450]/60 rounded w-full"></div>
          <div class="h-6 bg-[#3D3450]/40 rounded w-1/3"></div>
          <div class="flex items-center justify-between pt-2 border-t border-[#3D3450]/40">
            <div class="h-5 bg-[#3D3450] rounded w-16"></div>
            <div class="h-7 bg-[#7C3AED]/30 rounded-xl w-20"></div>
          </div>
        </div>
      </div>

      <!-- Estado de Error -->
      <div
        v-else-if="error"
        class="bg-[#2D2438] border border-[#EF4444]/40 rounded-2xl p-8 text-center text-white my-6 max-w-lg mx-auto space-y-3"
      >
        <div class="w-10 h-10 mx-auto text-[#EF4444] flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <p class="text-sm font-semibold">{{ error }}</p>
        <button
          type="button"
          class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-5 py-2.5 rounded-xl transition cursor-pointer"
          @click="fetchEstablishmentData"
        >
          Reintentar
        </button>
      </div>

      <!-- Lista de Ofertas Activas -->
      <section v-else-if="offers.length > 0" class="space-y-4">
        <h2 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center gap-2">
          <span>Bolsas Sorpresa Disponibles</span>
          <span class="text-xs font-normal text-[#A5A8C2]">({{ offers.length }})</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4.5">
          <CustomerCard
            v-for="offer in offers"
            :key="offer.id"
            :offer="offer"
            @click="openOfferModal(offer)"
            @quick-add="handleQuickAdd"
          />
        </div>
      </section>

      <!-- Estado Sin Ofertas -->
      <div
        v-else
        class="bg-[#2D2438] border border-[#3D3450] rounded-2xl p-8 sm:p-12 text-center my-6 max-w-lg mx-auto shadow-xl space-y-4"
      >
        <div class="w-16 h-16 mx-auto rounded-2xl bg-[#7C3AED]/15 border border-[#7C3AED]/30 text-[#A78BFA] flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="space-y-1">
          <h2 class="text-lg sm:text-xl font-bold text-white">No hay bolsas activas en este momento</h2>
          <p class="text-xs sm:text-sm text-[#A5A8C2] leading-relaxed max-w-sm mx-auto">
            Este local actualmente no tiene excedentes disponibles para rescatar hoy. Te recomendamos explorar otros comercios cercanos.
          </p>
        </div>
        <div class="pt-2">
          <RouterLink
            to="/customer/offers"
            class="inline-flex items-center justify-center gap-2 bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs sm:text-sm font-bold px-6 py-3 rounded-xl shadow-lg shadow-[#7C3AED]/25 transition-all duration-200 active:scale-95 cursor-pointer"
          >
            <span>Explorar otros comercios</span>
            <span class="text-base leading-none">→</span>
          </RouterLink>
        </div>
      </div>
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
        class="fixed bottom-6 right-6 z-50 max-w-md bg-[#2D2438] border rounded-2xl p-4 shadow-2xl flex items-start gap-3"
        :class="notification.type === 'error' ? 'border-[#EF4444]' : 'border-[#10B981]'"
      >
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
          :class="notification.type === 'error' ? 'bg-[#EF4444]/20 text-[#EF4444]' : 'bg-[#10B981]/20 text-[#10B981]'"
        >
          <svg v-if="notification.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm font-semibold text-white">{{ notification.message }}</p>
        </div>
        <button
          type="button"
          class="text-[#A5A8C2] hover:text-white p-1 rounded-lg transition-colors cursor-pointer"
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
  background-color: #1A1625;
  color: #E8EAF6;
  width: 100%;
}
</style>
