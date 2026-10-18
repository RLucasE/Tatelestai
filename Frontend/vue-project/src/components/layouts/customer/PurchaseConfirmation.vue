<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const purchaseData = ref(null);
const errorMessage = ref('');
const processingPayment = ref(false);
const paymentErrorMessage = ref('');
const showErrorNotification = ref(false);

const purchaseToken = route.params.token;

onMounted(() => {
  if (!purchaseToken) {
    errorMessage.value = 'Token de compra no válido o inexistente.';
    loading.value = false;
    return;
  }

  const sessionData = sessionStorage.getItem('purchaseConfirmation');
  if (sessionData) {
    try {
      purchaseData.value = JSON.parse(sessionData);
      loading.value = false;
    } catch (error) {
      errorMessage.value = 'Error al leer los datos de confirmación de tu reserva.';
      loading.value = false;
    }
  } else {
    errorMessage.value = 'No se encontraron datos de reserva en la sesión activa.';
    loading.value = false;
  }
});

const establishmentName = computed(() => {
  return purchaseData.value?.establishment?.name || `Establecimiento #${purchaseData.value?.food_establishment_id || ''}`;
});

const establishmentAddress = computed(() => {
  return purchaseData.value?.establishment?.address || '';
});

const offersList = computed(() => {
  return purchaseData.value?.offers?.offers || [];
});

const calculateOfferTotal = (offer) => {
  return (Number(offer.price || 0) * Number(offer.quantity || 1)).toFixed(2);
};

const grandTotal = computed(() => {
  if (!offersList.value.length) return '0.00';
  return offersList.value.reduce((total, offer) => {
    return total + (Number(offer.price || 0) * Number(offer.quantity || 1));
  }, 0).toFixed(2);
});

const formatExpirationDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const payWithMercadoPago = async () => {
  processingPayment.value = true;
  showErrorNotification.value = false;

  try {
    const response = await axiosInstance.post('/customer/payment/create-preference', {
      purchase_token: purchaseToken,
    });

    const data = response.data?.data;

    if (!data?.init_point) {
      throw new Error('No se recibió la URL de pago de Mercado Pago');
    }

    // Redirigir a Mercado Pago Checkout Pro (o modo Sandbox)
    const targetUrl = data.sandbox_init_point || data.init_point;
    window.location.href = targetUrl;

  } catch (error) {
    processingPayment.value = false;
    showErrorNotification.value = true;
    paymentErrorMessage.value = error.response?.data?.error || error.message || 'No se pudo iniciar el pago con Mercado Pago.';
  }
};

const goBack = () => {
  router.push({ name: 'cart' });
};
</script>

<template>
  <div class="min-h-screen bg-[#1A1625] text-[#E8EAF6] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto space-y-6">
      
      <!-- Encabezado -->
      <div class="space-y-1">
        <button
          type="button"
          class="inline-flex items-center gap-2 text-xs font-semibold text-[#A78BFA] hover:text-white transition-colors cursor-pointer mb-2"
          @click="goBack"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver al carrito
        </button>
        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Confirmar y Abonar</h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2]">Revisá el resumen de tus bolsas sorpresa antes de pagar.</p>
      </div>

      <!-- Estado de Carga -->
      <div v-if="loading" class="p-12 rounded-3xl bg-[#2D2438] border border-white/[0.08] text-center space-y-3">
        <div class="w-10 h-10 border-3 border-[#7C3AED] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs text-[#A5A8C2]">Cargando detalles de tu reserva...</p>
      </div>

      <!-- Error de Datos de Sesión -->
      <div v-else-if="errorMessage" class="p-8 rounded-3xl bg-[#2D2438] border border-[#EF4444]/30 text-center space-y-4">
        <div class="w-12 h-12 rounded-2xl bg-[#EF4444]/15 text-[#EF4444] flex items-center justify-center mx-auto">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-semibold text-[#F87171]">{{ errorMessage }}</p>
        <button
          type="button"
          class="px-4 py-2.5 rounded-xl bg-[#7C3AED] text-white font-bold text-xs cursor-pointer hover:bg-[#6D28D9] transition-colors"
          @click="goBack"
        >
          Regresar al Carrito
        </button>
      </div>

      <!-- Contenido Principal -->
      <div v-else class="space-y-4">
        
        <!-- Tarjeta de Comercio y Expiración -->
        <div class="p-5 rounded-3xl bg-[#2D2438] border border-white/[0.08] space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <span class="text-[11px] font-bold text-[#A78BFA] uppercase tracking-wider">Establecimiento</span>
              <h2 class="text-lg font-bold text-white mt-0.5">{{ establishmentName }}</h2>
              <p v-if="establishmentAddress" class="text-xs text-[#A5A8C2] mt-0.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-[#A78BFA] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ establishmentAddress }}
              </p>
            </div>
            
            <div class="px-3 py-1.5 rounded-xl bg-[#F59E0B]/10 border border-[#F59E0B]/25 text-right shrink-0">
              <span class="text-[10px] font-bold text-[#FBBF24] block uppercase">Reserva Temporal</span>
              <span class="text-xs font-semibold text-white">
                Expira a las {{ formatExpirationDate(purchaseData?.expires_at) }} hs
              </span>
            </div>
          </div>
        </div>

        <!-- Lista de Packs Sorpresa -->
        <div class="p-5 rounded-3xl bg-[#2D2438] border border-white/[0.08] space-y-3">
          <h3 class="text-xs font-bold text-[#A5A8C2] uppercase tracking-wider">Bolsas Sorpresa en este pedido</h3>
          
          <div class="divide-y divide-white/[0.06] space-y-2">
            <div
              v-for="offer in offersList"
              :key="offer.id"
              class="pt-2 first:pt-0 flex items-center justify-between gap-4"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-md bg-[#7C3AED]/20 text-[#C4B5FD] font-bold text-xs">
                    x{{ offer.quantity }}
                  </span>
                  <h4 class="text-sm font-bold text-white truncate">{{ offer.title }}</h4>
                </div>
                <p v-if="offer.description" class="text-xs text-[#A5A8C2] truncate mt-0.5 max-w-sm">
                  {{ offer.description }}
                </p>
              </div>

              <div class="text-right shrink-0">
                <span class="text-sm font-black text-white">${{ calculateOfferTotal(offer) }}</span>
                <span class="block text-[10px] text-[#A5A8C2]">
                  ${{ Number(offer.price).toLocaleString('es-AR') }} c/u
                </span>
              </div>
            </div>
          </div>

          <!-- Total a Pagar -->
          <div class="pt-4 border-t border-white/[0.08] flex items-center justify-between">
            <div>
              <span class="text-xs font-semibold text-[#A5A8C2] uppercase tracking-wide">Total Final</span>
              <p class="text-[11px] text-[#10B981] font-medium">Incluye garantía de rescate Tatelestai</p>
            </div>
            <div class="text-right">
              <span class="text-2xl font-black text-white">${{ grandTotal }}</span>
            </div>
          </div>
        </div>

        <!-- Botón de Pago con Mercado Pago -->
        <div class="p-5 rounded-3xl bg-[#2D2438] border border-white/[0.08] space-y-4">
          <div class="flex items-center justify-between gap-2 text-xs text-[#A5A8C2]">
            <span class="flex items-center gap-1.5 font-semibold text-white">
              <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Pago seguro y protegido
            </span>
            <span class="text-[11px] text-[#A5A8C2]">Mercado Pago Checkout Pro</span>
          </div>

          <button
            type="button"
            class="w-full py-3.5 px-6 rounded-2xl bg-[#009EE3] hover:bg-[#0086C3] text-white font-extrabold text-base flex items-center justify-center gap-2.5 transition-all shadow-lg shadow-[#009EE3]/25 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
            :disabled="processingPayment"
            @click="payWithMercadoPago"
          >
            <svg v-if="processingPayment" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
            <span>{{ processingPayment ? 'Conectando con Mercado Pago...' : 'Pagar con Mercado Pago' }}</span>
          </button>

          <p class="text-[11px] text-[#A5A8C2] text-center">
            Podrás abonar con dinero en cuenta de Mercado Pago, tarjeta de débito/crédito o transferencia.
          </p>
        </div>

        <!-- Alerta de Error -->
        <div
          v-if="showErrorNotification"
          class="p-4 rounded-2xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#F87171] flex items-center justify-between gap-3"
        >
          <span>{{ paymentErrorMessage }}</span>
          <button type="button" class="font-bold underline cursor-pointer" @click="showErrorNotification = false">Cerrar</button>
        </div>

      </div>

    </div>
  </div>
</template>
