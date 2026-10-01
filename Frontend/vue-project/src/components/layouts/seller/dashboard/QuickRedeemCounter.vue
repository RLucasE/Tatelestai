<script setup>
import { ref } from 'vue';
import axiosInstance from '@/lib/axios';
import { useFormatters } from '@/composables/useFormatters';

defineProps({
  initialCode: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['delivery-confirmed']);

const { formatCurrency } = useFormatters();

const pickupCode = ref('');
const isVerifying = ref(false);
const verifyError = ref('');
const verifiedOrder = ref(null);
const isConfirming = ref(false);
const successMessage = ref('');

const formatCode = (event) => {
  let val = event.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
  if (val.length > 4) {
    val = val.slice(0, 4) + '-' + val.slice(4);
  }
  if (val.length > 9) {
    val = val.slice(0, 9) + '-' + val.slice(9);
  }
  pickupCode.value = val;
  verifyError.value = '';
};

const verifyCode = async () => {
  const cleanCode = pickupCode.value.trim();
  if (!cleanCode || cleanCode.length < 6) {
    verifyError.value = 'Ingresa un código de pickup válido';
    return;
  }

  isVerifying.value = true;
  verifyError.value = '';
  successMessage.value = '';
  verifiedOrder.value = null;

  try {
    const response = await axiosInstance.post('/check-customer-code', {
      pickup_code: cleanCode,
    });
    verifiedOrder.value = response.data?.data;
  } catch (err) {
    console.error('Error al verificar código:', err);
    if (err.response?.status === 404) {
      verifyError.value = 'Código de pickup no encontrado';
    } else if (err.response?.status === 403) {
      verifyError.value = 'Este código corresponde a otro establecimiento';
    } else if (err.response?.status === 410) {
      verifyError.value = 'El tiempo para recoger este pedido ha expirado';
    } else {
      verifyError.value = err.response?.data?.error || 'Error al validar el código';
    }
  } finally {
    isVerifying.value = false;
  }
};

const confirmDelivery = async () => {
  if (!verifiedOrder.value?.sell_id) return;

  isConfirming.value = true;
  successMessage.value = '';
  verifyError.value = '';

  try {
    await axiosInstance.post(`/complete-sell/${verifiedOrder.value.sell_id}`, {
      pick_up_code: verifiedOrder.value.pickup_code,
    });

    successMessage.value = `Pedido #${verifiedOrder.value.sell_id} entregado con éxito`;
    emit('delivery-confirmed', verifiedOrder.value.sell_id);

    // Limpiar estado tras confirmar
    setTimeout(() => {
      clearOrder();
    }, 2500);
  } catch (err) {
    console.error('Error al confirmar entrega:', err);
    verifyError.value = err.response?.data?.error || 'Error al confirmar la entrega del pedido';
  } finally {
    isConfirming.value = false;
  }
};

const clearOrder = () => {
  verifiedOrder.value = null;
  pickupCode.value = '';
  verifyError.value = '';
  successMessage.value = '';
};

const calculateTotal = (order) => {
  if (!order) return 0;
  if (order.total_price != null) return Number(order.total_price);
  if (!order.offers) return 0;
  return order.offers.reduce(
    (sum, o) => sum + (Number(o.offer_quantity || 1) * Number(o.pack_price || 0)),
    0
  );
};
</script>

<template>
  <section class="p-5 sm:p-6 rounded-2xl bg-[#2D2438] border border-white/[0.08] shadow-sm">
    <!-- Encabezado del Módulo -->
    <div class="flex items-start justify-between gap-4 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#7C3AED]/20 border border-[#7C3AED]/35 text-[#A78BFA] flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
        </div>
        <div>
          <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">
            Canje Rápido de Código
          </h2>
          <p class="text-xs text-[#A5A8C2]">
            Verifica el código de pickup del cliente y confirma la entrega sin salir del panel.
          </p>
        </div>
      </div>
    </div>

    <!-- Formulario de Entrada -->
    <form @submit.prevent="verifyCode" class="space-y-3">
      <div class="flex flex-col sm:flex-row gap-2.5 max-w-xl">
        <div class="relative flex-1">
          <input
            type="text"
            v-model="pickupCode"
            placeholder="XXXX-XXXX-XXXX"
            maxlength="14"
            class="w-full px-4 py-2.5 rounded-xl bg-[#1A1625] border border-white/[0.12] text-white font-mono text-sm sm:text-base font-bold tracking-widest text-center uppercase focus:outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-[#7C3AED] transition-colors placeholder:text-white/20 placeholder:font-sans placeholder:tracking-normal disabled:opacity-50"
            :disabled="isVerifying || isConfirming"
            @input="formatCode"
          />
        </div>

        <button
          type="submit"
          class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer shrink-0"
          :disabled="isVerifying || isConfirming || !pickupCode.trim()"
        >
          <svg
            v-if="isVerifying"
            class="w-3.5 h-3.5 animate-spin"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <svg
            v-else
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span>{{ isVerifying ? 'Verificando...' : 'Verificar' }}</span>
        </button>
      </div>

      <!-- Mensaje de Error -->
      <p v-if="verifyError" class="text-xs text-[#F87171] font-medium flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ verifyError }}</span>
      </p>

      <!-- Mensaje de Éxito -->
      <div
        v-if="successMessage"
        class="p-3 rounded-xl bg-[#10B981]/15 border border-[#10B981]/30 text-xs text-[#34D399] font-semibold flex items-center gap-2"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ successMessage }}</span>
      </div>
    </form>

    <!-- Detalle del Pedido Verificado -->
    <div
      v-if="verifiedOrder"
      class="mt-4 p-4 rounded-xl bg-[#1A1625] border border-white/[0.1] space-y-3.5 animate-fadeIn"
    >
      <div class="flex items-center justify-between pb-3 border-b border-white/[0.08] flex-wrap gap-2">
        <div>
          <span class="text-[11px] font-bold uppercase tracking-wider text-[#A78BFA]">
            Pedido #{{ verifiedOrder.sell_id }}
          </span>
          <h3 class="text-sm sm:text-base font-bold text-white mt-0.5">
            {{ verifiedOrder.customer?.name || 'Cliente' }}
          </h3>
        </div>
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-[#7C3AED]/20 border border-[#7C3AED]/35 text-white">
          {{ verifiedOrder.pickup_code }}
        </span>
      </div>

      <!-- Items del pedido -->
      <div class="space-y-1.5 text-xs">
        <div
          v-for="(item, idx) in verifiedOrder.offers"
          :key="idx"
          class="flex items-center justify-between py-1 border-b border-white/[0.04] last:border-none"
        >
          <div class="flex items-center gap-2">
            <span class="px-1.5 py-0.5 rounded bg-white/[0.06] text-white font-bold">
              x{{ item.offer_quantity }}
            </span>
            <span class="text-[#E8EAF6]">{{ item.pack_name || item.offer_title }}</span>
          </div>
          <span class="font-semibold text-white">
            ${{ formatCurrency(item.pack_price * item.offer_quantity) }}
          </span>
        </div>
      </div>

      <!-- Footer del Pedido -->
      <div class="flex items-center justify-between pt-3 border-t border-white/[0.08] flex-wrap gap-3">
        <div class="flex items-baseline gap-2">
          <span class="text-xs text-[#A5A8C2]">Total:</span>
          <span class="text-base font-extrabold text-white">
            ${{ formatCurrency(calculateTotal(verifiedOrder)) }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-[#A5A8C2] hover:text-white hover:bg-white/[0.06] transition-colors cursor-pointer"
            :disabled="isConfirming"
            @click="clearOrder"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold bg-[#10B981] hover:bg-[#059669] text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer"
            :disabled="isConfirming"
            @click="confirmDelivery"
          >
            <svg
              v-if="isConfirming"
              class="w-3 h-3 animate-spin"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg
              v-else
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ isConfirming ? 'Confirmando...' : 'Confirmar Entrega' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
