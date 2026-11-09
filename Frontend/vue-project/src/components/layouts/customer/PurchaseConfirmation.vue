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

const establishmentCategory = computed(() => {
  return purchaseData.value?.establishment?.category || 'Panadería & Gastronomía';
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'TA';
});

const pickupWindow = computed(() => {
  return purchaseData.value?.establishment?.pickup_window || 'Hoy en horario de retiro';
});

const offersList = computed(() => {
  if (!purchaseData.value) return [];
  if (Array.isArray(purchaseData.value.offersDetails) && purchaseData.value.offersDetails.length > 0) {
    return purchaseData.value.offersDetails;
  }
  if (Array.isArray(purchaseData.value.offers?.offers)) {
    return purchaseData.value.offers.offers;
  }
  if (Array.isArray(purchaseData.value.offers)) {
    return purchaseData.value.offers;
  }
  return [];
});

const totalPacksCount = computed(() => {
  return offersList.value.reduce((total, offer) => total + Number(offer.quantity || 1), 0);
});

const getOriginalUnitPrice = (offer) => {
  if (offer.original_price != null && Number(offer.original_price) > 0) {
    return Number(offer.original_price);
  }
  if (offer.minimum_value != null && Number(offer.minimum_value) > 0) {
    return Number(offer.minimum_value);
  }
  const price = Number(offer.price || 0);
  return price > 0 ? Math.round(price * 2.5) : 0;
};

const grandTotalNumber = computed(() => {
  if (!offersList.value.length) return 0;
  return offersList.value.reduce((total, offer) => {
    return total + (Number(offer.price || 0) * Number(offer.quantity || 1));
  }, 0);
});

const grandTotal = computed(() => {
  return grandTotalNumber.value.toLocaleString('es-AR');
});

// Estimación del valor original total de los packs
const originalTotalNumber = computed(() => {
  if (!offersList.value.length) return 0;
  return offersList.value.reduce((total, offer) => {
    return total + (getOriginalUnitPrice(offer) * Number(offer.quantity || 1));
  }, 0);
});

const originalTotal = computed(() => {
  return originalTotalNumber.value.toLocaleString('es-AR');
});

const totalSavingsNumber = computed(() => {
  return Math.max(0, originalTotalNumber.value - grandTotalNumber.value);
});

const totalSavings = computed(() => {
  return totalSavingsNumber.value.toLocaleString('es-AR');
});

const formatExpirationDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleTimeString('es-AR', {
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
    paymentErrorMessage.value =
      error.response?.data?.error ||
      error.response?.data?.message ||
      error.message ||
      'No se pudo iniciar el pago con Mercado Pago.';
  }
};

const goBack = () => {
  router.push({ name: 'cart' });
};
</script>

<template>
  <div class="confirm-page-wrapper w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 min-h-[calc(100vh-4rem)] bg-canvas text-content">
    
    <!-- Encabezado y Botón Regresar: Figma node 4021:2022 (BackToCartLink) -->
    <header class="confirm-header-section mb-6">
      <button
        type="button"
        class="inline-flex items-center gap-2 border border-border-subtle bg-surface hover:border-brand/40 text-content rounded-[10px] px-3.5 py-1.5 text-xs font-medium transition-colors shadow-2xs cursor-pointer mb-3"
        @click="goBack"
      >
        <svg class="w-3.5 h-3.5 text-content" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Regresar</span>
      </button>

      <div class="border-b border-border-subtle pb-4">
        <!-- Título: Figma node 4021:2026 -->
        <h1 class="text-2xl sm:text-3xl font-bold text-content-strong tracking-tight flex items-center gap-3">
          <span>Confirmar y Abonar Reserva</span>
          <span
            v-if="totalPacksCount > 0"
            class="bg-brand text-surface text-xs font-black px-2.5 py-0.5 rounded-full shadow-2xs"
          >
            {{ totalPacksCount }} {{ totalPacksCount === 1 ? 'pack' : 'packs' }}
          </span>
        </h1>
        <p class="text-xs sm:text-sm text-content-secondary mt-1">
          Revisá los detalles de tu pedido y completá el pago seguro para asegurar tus bolsas sorpresa.
        </p>
      </div>
    </header>

    <!-- Estado de Carga -->
    <div v-if="loading" class="p-12 rounded-2xl bg-surface border border-border-subtle text-center space-y-4 shadow-card max-w-lg mx-auto my-8">
      <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-sm font-semibold text-content-secondary">Cargando detalles de tu reserva...</p>
    </div>

    <!-- Error de Datos de Sesión -->
    <div
      v-else-if="errorMessage"
      class="p-8 sm:p-10 rounded-2xl bg-surface border border-danger/30 text-center space-y-4 shadow-card max-w-lg mx-auto my-8"
    >
      <div class="w-14 h-14 rounded-2xl bg-danger/10 text-danger flex items-center justify-center mx-auto shadow-2xs">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke-width="2" />
          <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round" />
          <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round" />
        </svg>
      </div>
      <div class="space-y-1">
        <h3 class="text-base font-bold text-content-strong">Reserva no encontrada</h3>
        <p class="text-xs sm:text-sm text-danger font-medium">{{ errorMessage }}</p>
      </div>
      <button
        type="button"
        class="px-6 py-2.5 rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-xs cursor-pointer transition-colors shadow-sm"
        @click="goBack"
      >
        Regresar al Carrito
      </button>
    </div>

    <!-- Layout de 2 Columnas: Figma node 4021:2044 (ConfirmationSplitLayout) -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      
      <!-- Columna Izquierda: Información del Comercio y Packs (Figma node 4021:2138, OrderDetails) -->
      <div class="lg:col-span-7 xl:col-span-8 space-y-6">
        
        <!-- Ficha del Comercio: Figma node 4023:2208 (Establishment_SanJose) -->
        <article class="bg-surface border border-border-subtle rounded-2xl p-6 shadow-card space-y-5">
          <!-- Sección Encabezado y Retiro: Figma node 4021:2139 (StoreAndPickupSection) -->
          <div class="space-y-3">
            <!-- Fila Superior: StoreTopRow (Figma node 4021:2140) -->
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span
                  class="w-10 h-10 rounded-full bg-surface-sunken border border-border-subtle text-brand text-xs font-black flex items-center justify-center shrink-0 shadow-2xs"
                >
                  {{ establishmentInitials }}
                </span>
                
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-content-strong truncate">
                      {{ establishmentName }}
                    </h2>
                    <span
                      class="hidden sm:inline-flex items-center gap-1 bg-surface-sunken border border-border-subtle text-content-secondary text-[11px] font-semibold px-2.5 py-0.5 rounded-full shrink-0"
                    >
                      <svg class="w-3 h-3 text-success shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fill-rule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      <span>Comercio verificado</span>
                    </span>
                  </div>

                  <p v-if="establishmentAddress" class="text-xs text-content-secondary truncate mt-0.5">
                    {{ establishmentAddress }}
                  </p>
                </div>
              </div>

              <!-- Badge de Expiración de Reserva -->
              <div
                v-if="purchaseData?.expires_at"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-warning/15 border border-warning/30 text-warning text-xs font-bold shrink-0"
              >
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke-width="2" />
                  <polyline points="12 6 12 12 16 14" stroke-width="2" />
                </svg>
                <span>Reserva válida hasta las {{ formatExpirationDate(purchaseData.expires_at) }} hs</span>
              </div>
            </div>

            <!-- Fila de Retiro: PickupMetaRow (Figma node 4021:2145) -->
            <div class="flex items-center gap-2 pt-1 text-xs text-content font-medium">
              <svg class="w-4 h-4 text-content-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2" />
                <polyline points="12 6 12 12 16 14" stroke-width="2" />
              </svg>
              <span>Retiro: {{ pickupWindow }}</span>
            </div>
          </div>

          <!-- Lista de Productos en la Reserva: Figma node 4021:2150 (ProductsListSection) -->
          <div class="border-t border-border-subtle pt-4 space-y-4">
            <div
              v-for="offer in offersList"
              :key="offer.id"
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-2 border-b border-border-subtle/50 last:border-0"
            >
              <!-- ItemDetails: Figma node 4021:2152 -->
              <div class="min-w-0 flex-1 space-y-1">
                <div class="flex items-baseline gap-2">
                  <span class="text-base font-bold text-content-strong tabular-nums shrink-0">
                    {{ offer.quantity }}x
                  </span>
                  <h3 class="text-sm sm:text-base font-bold text-content-strong">
                    {{ offer.title }}
                  </h3>
                </div>

                <p v-if="offer.description" class="text-xs text-content-secondary line-clamp-2 max-w-lg">
                  {{ offer.description }}
                </p>
              </div>

              <!-- PriceGroup: Figma node 4021:2155 -->
              <div class="flex items-center gap-3 shrink-0 text-right sm:justify-end">
                <span class="text-xs text-content-muted line-through tabular-nums">
                  ${{ (getOriginalUnitPrice(offer) * Number(offer.quantity || 1)).toLocaleString('es-AR') }}
                </span>
                <span class="text-base font-bold text-content-strong tabular-nums">
                  ${{ (Number(offer.price || 0) * Number(offer.quantity || 1)).toLocaleString('es-AR') }}
                </span>
              </div>
            </div>
          </div>
        </article>

      </div>

      <!-- Columna Derecha: Sidebar Resumen de tu Reserva (Figma node 4021:2165, CheckoutSummary) -->
      <aside class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-8 space-y-4">
        <div class="bg-surface border border-border-subtle rounded-2xl p-6 shadow-card space-y-5">
          
          <!-- Título: Figma node 4021:2166 -->
          <h2 class="text-lg font-bold text-content-strong tracking-tight">
            Resumen de tu Reserva
          </h2>

          <!-- Información del Medio de Pago: Figma node 4021:2167 (PaymentMethodInfo) -->
          <div class="space-y-1">
            <span class="text-[11px] font-bold text-content-muted uppercase tracking-wider block">
              MEDIO DE PAGO
            </span>
            <div class="text-sm font-bold text-content-strong">
              Mercado Pago
            </div>
            <p class="text-xs text-content-secondary">
              Tarjetas de débito, crédito, dinero en cuenta.
            </p>
          </div>

          <!-- Desglose Económico: Figma node 4021:2171 (PriceBreakdown) -->
          <div class="space-y-2 py-3 border-y border-border-subtle text-xs">
            <!-- Fila 1: Valor original -->
            <div class="flex items-center justify-between text-content-secondary">
              <span>Valor original de los {{ totalPacksCount }} packs</span>
              <span class="tabular-nums font-medium line-through">
                ${{ originalTotal }}
              </span>
            </div>

            <!-- Fila 2: Descuento por rescate gastronómico -->
            <div v-if="totalSavingsNumber > 0" class="flex items-center justify-between text-content-secondary">
              <span>Descuento por rescate gastronómico</span>
              <span class="tabular-nums font-semibold text-brand">
                -${{ totalSavings }}
              </span>
            </div>

            <!-- Fila 3: Cargo de servicio Tatelestai -->
            <div class="flex items-center justify-between text-content-secondary">
              <span>Cargo de servicio Tatelestai</span>
              <div class="flex items-center gap-1.5">
                <span class="text-content-muted line-through tabular-nums">$ 450</span>
                <span class="text-content-strong font-medium">$ 0 (Bonificado)</span>
              </div>
            </div>
          </div>

          <!-- Total a Abonar: Figma node 4021:2182 (TotalRow) -->
          <div class="flex items-center justify-between pt-1">
            <div class="space-y-0.5">
              <span class="text-xs font-bold text-content-strong uppercase tracking-wider block">
                TOTAL A ABONAR
              </span>
              <span class="text-[11px] text-content-secondary">
                Recibirás 1 código QR y PIN de retiro
              </span>
            </div>
            <div class="text-right">
              <span class="text-2xl font-bold text-content-strong tracking-tight tabular-nums">
                ${{ grandTotal }}
              </span>
            </div>
          </div>

          <!-- Botón de Pago Principal: Figma node 4021:2187 (PayWithMercadoPagoButton) -->
          <div class="pt-1 space-y-3">
            <button
              type="button"
              class="w-full h-12 rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-[15px] shadow-sm shadow-brand/20 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer select-none disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="processingPayment"
              @click="payWithMercadoPago"
            >
              <svg
                v-if="processingPayment"
                class="w-5 h-5 animate-spin text-surface"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>Pagar con Mercado Pago</span>
            </button>

            <!-- Cláusula de Confianza y Garantía -->
            <p class="text-[11px] text-content-secondary text-center leading-relaxed">
              Cancelación gratuita hasta 2 horas antes del inicio del retiro. Garantía Tatelestai: 100% reembolso si el local no dispone de tu pack.
            </p>
          </div>

        </div>
      </aside>

    </div>

    <!-- Alerta Flotante de Error de Pago -->
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
          v-if="showErrorNotification"
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
            <h4 class="text-sm font-bold text-content-strong">Error en el proceso de pago</h4>
            <p class="text-xs text-content-secondary mt-0.5">{{ paymentErrorMessage }}</p>
          </div>
          <button
            type="button"
            class="text-content-secondary hover:text-content-strong p-1 rounded-lg transition-colors cursor-pointer"
            @click="showErrorNotification = false"
          >
            &times;
          </button>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
