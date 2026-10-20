<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import QRCode from 'qrcode';
import { buildPickupQrPayload } from '@/utils/qrPayload';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const verificationSuccess = ref(false);
const orderData = ref(null);
const errorMessage = ref('');
const qrDataUrl = ref('');

const verifyPaymentReturn = async () => {
  loading.value = true;
  errorMessage.value = '';

  const sellId = route.query.external_reference || route.query.sell_id;
  const paymentId = route.query.payment_id || route.query.collection_id;
  const status = route.query.status || route.query.collection_status || 'approved';
  const preferenceId = route.query.preference_id;

  if (!sellId) {
    errorMessage.value = 'No se encontró la referencia del pedido en el retorno de Mercado Pago.';
    loading.value = false;
    return;
  }

  try {
    const response = await axiosInstance.post('/customer/payment/verify', {
      sell_id: Number(sellId),
      payment_id: paymentId ? String(paymentId) : null,
      status: status,
      preference_id: preferenceId ? String(preferenceId) : null,
    });

    orderData.value = response.data?.data || null;
    verificationSuccess.value = true;
    sessionStorage.removeItem('purchaseConfirmation');

    if (orderData.value?.pickup_code) {
      try {
        const payload = buildPickupQrPayload(orderData.value.pickup_code, orderData.value.sell_id);
        qrDataUrl.value = await QRCode.toDataURL(payload, {
          width: 260,
          margin: 2,
          color: { dark: '#000000', light: '#FFFFFF' },
        });
      } catch (qrErr) {
        console.error('Error generando QR:', qrErr);
      }
    }
  } catch (error) {
    console.error('Error al verificar pago:', error);
    errorMessage.value = error.response?.data?.error || 'No se pudo verificar el estado del pago con Mercado Pago.';
    verificationSuccess.value = false;
  } finally {
    loading.value = false;
  }
};

const goToPurchases = () => {
  router.push({ name: 'customer-purchases' });
};

const goToOffers = () => {
  router.push({ name: 'customer-offers' });
};

onMounted(() => {
  verifyPaymentReturn();
});
</script>

<template>
  <div class="min-h-screen bg-[#1A1625] text-[#E8EAF6] flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-[#2D2438] border border-white/[0.08] rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
      
      <!-- Estado de Carga -->
      <div v-if="loading" class="text-center py-12 space-y-4">
        <div class="w-14 h-14 border-4 border-[#7C3AED] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <h3 class="text-lg font-bold text-white">Verificando pago con Mercado Pago...</h3>
        <p class="text-xs text-[#A5A8C2]">Estamos confirmando la acreditación de tu dinero y reservando tu pack.</p>
      </div>

      <!-- Pago Aprobado -->
      <div v-else-if="verificationSuccess" class="text-center space-y-6">
        <div class="w-16 h-16 bg-[#10B981]/15 text-[#10B981] border border-[#10B981]/30 rounded-2xl flex items-center justify-center mx-auto">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <div class="space-y-1.5">
          <h2 class="text-2xl font-black text-white tracking-tight">¡Pago Aprobado!</h2>
          <p class="text-sm text-[#A5A8C2]">
            Tu pack sorpresa ha sido reservado exitosamente.
          </p>
        </div>

        <!-- Código de Retiro & QR -->
        <div v-if="orderData?.pickup_code" class="p-5 rounded-3xl bg-[#1A1625] border border-white/[0.08] space-y-4">
          <span class="text-xs font-semibold text-[#A5A8C2] uppercase tracking-wider block">Tu Ticket de Retiro</span>

          <!-- QR en Tarjeta Blanca de Alto Contraste -->
          <div v-if="qrDataUrl" class="flex justify-center">
            <div class="p-3 bg-white rounded-2xl shadow-xl border border-white/20 inline-block">
              <img
                :src="qrDataUrl"
                alt="Código QR de retiro"
                class="w-48 h-48 sm:w-52 sm:h-52 object-contain rounded-lg"
              />
            </div>
          </div>

          <div class="text-2xl sm:text-3xl font-black tracking-widest text-[#10B981] font-mono">
            {{ orderData.pickup_code }}
          </div>
          <p class="text-[11px] text-[#A5A8C2]">
            Presentá este código o mostrá el QR directamente en el mostrador durante la ventana de retiro.
          </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            class="flex-1 py-3 px-4 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-bold text-sm transition-colors cursor-pointer shadow-lg shadow-[#7C3AED]/20"
            @click="goToPurchases"
          >
            Ver en Mis Reservas
          </button>
          <button
            type="button"
            class="py-3 px-4 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-white font-semibold text-sm transition-colors cursor-pointer"
            @click="goToOffers"
          >
            Seguir explorando
          </button>
        </div>
      </div>

      <!-- Pago Rechazado o Error -->
      <div v-else class="text-center space-y-6">
        <div class="w-16 h-16 bg-[#EF4444]/15 text-[#EF4444] border border-[#EF4444]/30 rounded-2xl flex items-center justify-center mx-auto">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>

        <div class="space-y-1.5">
          <h2 class="text-2xl font-black text-white tracking-tight">No pudimos procesar el pago</h2>
          <p class="text-sm text-[#F87171]">
            {{ errorMessage || 'El pago fue rechazado o cancelado en Mercado Pago.' }}
          </p>
          <p class="text-xs text-[#A5A8C2]">
            No te preocupes: los cupos fueron liberados y no se debitó dinero de tu cuenta.
          </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            class="flex-1 py-3 px-4 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-bold text-sm transition-colors cursor-pointer"
            @click="goToOffers"
          >
            Volver a Explorar Packs
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
