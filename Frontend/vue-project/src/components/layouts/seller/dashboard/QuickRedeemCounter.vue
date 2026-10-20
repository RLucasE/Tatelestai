<script setup>
import { shallowRef } from 'vue';
import { useFormatters } from '@/composables/useFormatters';
import { usePickupRedemption } from '@/composables/usePickupRedemption';
import QrScannerModal from '@/components/layouts/seller/QrScannerModal.vue';

defineProps({
  initialCode: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['delivery-confirmed']);

const { formatCurrency } = useFormatters();

const {
  pickupCode,
  isVerifying,
  verifyError,
  verifiedOrder,
  isConfirming,
  successMessage,
  setAndFormatCode,
  verifyCode,
  handleQrScanned,
  confirmDelivery,
  clearOrder,
  calculateTotal,
} = usePickupRedemption();

const showScannerModal = shallowRef(false);

const onInputCode = (event) => {
  setAndFormatCode(event.target.value);
};

const onQrScanned = async (decodedText) => {
  await handleQrScanned(decodedText);
};

const onConfirmDelivery = () => {
  confirmDelivery((sellId) => {
    emit('delivery-confirmed', sellId);
  });
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
            Escaneá el QR del cliente con la cámara o ingresá su código alfanumérico para verificar y confirmar la entrega.
          </p>
        </div>
      </div>
    </div>

    <!-- Formulario de Entrada y Botón Escáner QR -->
    <form @submit.prevent="verifyCode()" class="space-y-3">
      <div class="flex flex-col sm:flex-row gap-2.5 max-w-xl">
        <div class="relative flex-1">
          <input
            type="text"
            v-model="pickupCode"
            placeholder="XXXX-XXXX-XXXX"
            maxlength="14"
            class="w-full px-4 py-2.5 rounded-xl bg-[#1A1625] border border-white/[0.12] text-white font-mono text-sm sm:text-base font-bold tracking-widest text-center uppercase focus:outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-[#7C3AED] transition-colors placeholder:text-white/20 placeholder:font-sans placeholder:tracking-normal disabled:opacity-50"
            :disabled="isVerifying || isConfirming"
            @input="onInputCode"
          />
        </div>

        <button
          type="submit"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer shrink-0"
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

        <!-- Botón Escanear QR con Cámara -->
        <button
          type="button"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#10B981] hover:bg-[#059669] text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer shrink-0"
          :disabled="isVerifying || isConfirming"
          @click="showScannerModal = true"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>Escanear QR</span>
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

    <!-- Detalle del Pedido Verificado (Paso 1 completado -> Paso 2 listo) -->
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
          <span v-if="verifiedOrder.customer?.email" class="text-[11px] text-[#A5A8C2]">
            {{ verifiedOrder.customer.email }}
          </span>
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
          class="flex items-center justify-between py-1.5 border-b border-white/[0.04] last:border-none"
        >
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-md bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-white font-bold text-xs">
              x{{ item.offer_quantity }}
            </span>
            <div>
              <span class="text-[#E8EAF6] font-semibold block">{{ item.pack_name || item.offer_title }}</span>
              <span v-if="item.pack_description" class="text-[11px] text-[#A5A8C2] line-clamp-1">
                {{ item.pack_description }}
              </span>
            </div>
          </div>
          <span class="font-bold text-white ml-2 shrink-0">
            ${{ formatCurrency(item.pack_price * item.offer_quantity) }}
          </span>
        </div>
      </div>

      <!-- Footer del Pedido con Botón de Confirmar Entrega (Paso 2) -->
      <div class="flex items-center justify-between pt-3 border-t border-white/[0.08] flex-wrap gap-3">
        <div class="flex items-baseline gap-2">
          <span class="text-xs text-[#A5A8C2]">Total:</span>
          <span class="text-lg font-extrabold text-white">
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
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-[#10B981] hover:bg-[#059669] text-white shadow-md transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer"
            :disabled="isConfirming"
            @click="onConfirmDelivery"
          >
            <svg
              v-if="isConfirming"
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ isConfirming ? 'Confirmando...' : 'Confirmar Entrega' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de Escáner QR con Cámara -->
    <QrScannerModal
      :show="showScannerModal"
      @close="showScannerModal = false"
      @scanned="onQrScanned"
    />
  </section>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
