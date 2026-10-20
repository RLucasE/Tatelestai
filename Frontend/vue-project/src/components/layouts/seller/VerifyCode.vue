<script setup>
import { shallowRef } from 'vue';
import { useFormatters } from '@/composables/useFormatters';
import { usePickupRedemption } from '@/composables/usePickupRedemption';
import QrScannerModal from '@/components/layouts/seller/QrScannerModal.vue';

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
  confirmDelivery();
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 text-[#E8EAF6]">
    
    <!-- Encabezado de la Sección -->
    <div class="space-y-1">
      <span class="text-[11px] font-bold uppercase tracking-wider text-[#A78BFA] block">
        Mostrador de Entrega
      </span>
      <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
        Verificar y Canjear Pedido
      </h1>
      <p class="text-xs sm:text-sm text-[#A5A8C2]">
        Escaneá el código QR del cliente o ingresá su código alfanumérico para revisar los packs y confirmar la entrega.
      </p>
    </div>

    <!-- Tarjeta Principal de Validación -->
    <div class="p-6 sm:p-8 rounded-3xl bg-[#2D2438] border border-white/[0.08] shadow-xl space-y-6">
      
      <!-- Opciones Rápidas: Escáner QR o Teclado -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 border-b border-white/[0.08]">
        
        <!-- Opción A: Escanear con Cámara -->
        <button
          type="button"
          class="p-5 rounded-2xl bg-[#1A1625] border border-white/[0.08] hover:border-[#10B981]/50 text-left transition-all hover:scale-[1.01] active:scale-98 cursor-pointer group flex items-start gap-4"
          @click="showScannerModal = true"
        >
          <div class="w-12 h-12 rounded-2xl bg-[#10B981]/15 text-[#10B981] border border-[#10B981]/30 flex items-center justify-center shrink-0 group-hover:bg-[#10B981]/25 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="space-y-1 min-w-0">
            <span class="text-sm font-bold text-white group-hover:text-[#34D399] transition-colors block">
              Escanear con Cámara
            </span>
            <p class="text-xs text-[#A5A8C2] leading-relaxed">
              Lectura automática del código QR que el cliente exhibe en su pantalla.
            </p>
          </div>
        </button>

        <!-- Opción B: Ingreso Manual -->
        <div class="p-5 rounded-2xl bg-[#1A1625] border border-white/[0.08] space-y-2">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-xs font-bold text-white">Ingreso por Código</span>
          </div>
          <p class="text-[11px] text-[#A5A8C2]">
            Si la cámara no está disponible, ingresá los 12 caracteres alfanuméricos.
          </p>
        </div>
      </div>

      <!-- Formulario de Entrada Manual -->
      <form @submit.prevent="verifyCode()" class="space-y-3">
        <label for="pickup-code-input" class="text-xs font-semibold text-[#A5A8C2] block">
          Código de Retiro del Cliente
        </label>
        
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <input
              id="pickup-code-input"
              type="text"
              v-model="pickupCode"
              placeholder="XXXX-XXXX-XXXX"
              maxlength="14"
              class="w-full px-4 py-3 rounded-2xl bg-[#1A1625] border border-white/[0.12] text-white font-mono text-lg font-black tracking-widest text-center uppercase focus:outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-[#7C3AED] transition-colors placeholder:text-white/20 placeholder:font-sans placeholder:tracking-normal disabled:opacity-50"
              :disabled="isVerifying || isConfirming"
              @input="onInputCode"
            />
          </div>

          <button
            type="submit"
            class="px-6 py-3 rounded-2xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-lg shadow-[#7C3AED]/25 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2 shrink-0"
            :disabled="isVerifying || isConfirming || !pickupCode.trim()"
          >
            <svg
              v-if="isVerifying"
              class="w-4 h-4 animate-spin"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg
              v-else
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span>{{ isVerifying ? 'Verificando...' : 'Verificar Código' }}</span>
          </button>
        </div>

        <!-- Alerta de Error -->
        <div
          v-if="verifyError"
          class="p-3.5 rounded-2xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#F87171] font-semibold flex items-center gap-2"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ verifyError }}</span>
        </div>

        <!-- Alerta de Éxito -->
        <div
          v-if="successMessage"
          class="p-3.5 rounded-2xl bg-[#10B981]/15 border border-[#10B981]/30 text-xs text-[#34D399] font-bold flex items-center gap-2"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ successMessage }}</span>
        </div>
      </form>

      <!-- Ficha del Pedido Verificado (Paso 1 -> Paso 2) -->
      <div
        v-if="verifiedOrder"
        class="pt-6 border-t border-white/[0.08] space-y-6 animate-fadeIn"
      >
        <!-- Encabezado del Pedido -->
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#A78BFA] block">
              Detalles de la Reserva
            </span>
            <h2 class="text-xl font-black text-white mt-0.5">
              Pedido #{{ verifiedOrder.sell_id }}
            </h2>
            <span class="text-xs text-[#A5A8C2] block">
              Emitido el {{ formatDate(verifiedOrder.created_at) }} hs
            </span>
          </div>

          <span class="px-3.5 py-1.5 rounded-xl font-mono text-sm font-black bg-[#7C3AED]/20 border border-[#7C3AED]/35 text-[#C4B5FD]">
            {{ verifiedOrder.pickup_code }}
          </span>
        </div>

        <!-- Información del Cliente -->
        <div class="p-4 rounded-2xl bg-[#1A1625] border border-white/[0.06] space-y-2">
          <span class="text-[10px] font-bold text-[#A5A8C2] uppercase tracking-wider block">
            Datos del Comprador
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <span class="text-[#A5A8C2] block text-[11px]">Nombre y Apellido</span>
              <strong class="text-white font-bold text-sm">{{ verifiedOrder.customer?.name || 'Cliente' }}</strong>
            </div>
            <div>
              <span class="text-[#A5A8C2] block text-[11px]">Email</span>
              <span class="text-[#E8EAF6]">{{ verifiedOrder.customer?.email }}</span>
            </div>
          </div>
        </div>

        <!-- Bolsas Sorpresa a Entregar -->
        <div class="space-y-2.5">
          <span class="text-xs font-bold text-[#A5A8C2] uppercase tracking-wider block">
            Bolsas sorpresa incluidas en este pedido
          </span>

          <div class="space-y-2">
            <div
              v-for="(offer, idx) in verifiedOrder.offers"
              :key="idx"
              class="p-4 rounded-2xl bg-[#1A1625] border border-white/[0.06] flex items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span class="px-2.5 py-1 rounded-lg bg-[#7C3AED]/25 border border-[#7C3AED]/35 text-white font-bold text-xs shrink-0">
                  x{{ offer.offer_quantity }}
                </span>
                <div class="min-w-0">
                  <h4 class="text-sm font-bold text-white truncate">
                    {{ offer.pack_name || offer.offer_title }}
                  </h4>
                  <p v-if="offer.pack_description" class="text-xs text-[#A5A8C2] line-clamp-1 mt-0.5">
                    {{ offer.pack_description }}
                  </p>
                </div>
              </div>

              <div class="text-right shrink-0">
                <span class="text-sm font-black text-white">
                  ${{ formatCurrency(offer.pack_price * offer.offer_quantity) }}
                </span>
                <span class="block text-[10px] text-[#A5A8C2]">
                  ${{ formatCurrency(offer.pack_price) }} c/u
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Total y Acciones Finales (Paso 2) -->
        <div class="pt-4 border-t border-white/[0.08] flex items-center justify-between flex-wrap gap-4">
          <div>
            <span class="text-xs text-[#A5A8C2] uppercase font-semibold block">Total Abonado</span>
            <span class="text-2xl font-black text-white tracking-tight">
              ${{ formatCurrency(calculateTotal(verifiedOrder)) }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              class="px-4 py-2.5 rounded-xl text-xs font-semibold text-[#A5A8C2] hover:text-white bg-white/[0.06] hover:bg-white/[0.1] transition-colors cursor-pointer"
              :disabled="isConfirming"
              @click="clearOrder"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl text-xs font-bold bg-[#10B981] hover:bg-[#059669] text-white shadow-lg shadow-[#10B981]/25 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 cursor-pointer"
              :disabled="isConfirming"
              @click="onConfirmDelivery"
            >
              <svg
                v-if="isConfirming"
                class="w-4 h-4 animate-spin"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg
                v-else
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ isConfirming ? 'Confirmando entrega...' : 'Confirmar Entrega' }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal de Escáner QR con Cámara -->
    <QrScannerModal
      :show="showScannerModal"
      @close="showScannerModal = false"
      @scanned="onQrScanned"
    />
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
