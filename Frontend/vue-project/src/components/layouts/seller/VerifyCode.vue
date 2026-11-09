<script setup>
import { shallowRef } from "vue";
import { useFormatters } from "@/composables/useFormatters";
import { usePickupRedemption } from "@/composables/usePickupRedemption";
import QrScannerModal from "@/components/layouts/seller/QrScannerModal.vue";

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
  if (!dateString) return "";
  const date = new Date(dateString);
  return date.toLocaleDateString("es-AR", {
    day: "numeric",
    month: "long",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 space-y-6 text-content">
    <!-- Encabezado de la Sección -->
    <div class="space-y-1">
      <span class="text-[11px] font-bold uppercase tracking-wider text-brand block">
        Mostrador de Entrega
      </span>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-content tracking-tight">
        Verificar y Canjear Pedido
      </h1>
      <p class="text-xs sm:text-sm text-content-secondary">
        Escaneá el código QR del cliente o ingresá su código alfanumérico para revisar los packs y confirmar la entrega.
      </p>
    </div>

    <!-- Tarjeta Principal de Validación -->
    <div class="p-6 sm:p-8 rounded-[22px] bg-surface border border-border-subtle shadow-xs space-y-6">
      <!-- Opciones Rápidas: Escáner QR o Teclado -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 border-b border-border-subtle">
        <!-- Opción A: Escanear con Cámara -->
        <button
          type="button"
          class="p-5 rounded-2xl bg-canvas border border-border-subtle hover:border-brand text-left transition-all cursor-pointer group flex items-start gap-4"
          @click="showScannerModal = true"
        >
          <div class="w-12 h-12 rounded-2xl bg-brand/12 text-brand border border-brand/25 flex items-center justify-center shrink-0 group-hover:bg-brand/20 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="space-y-1 min-w-0">
            <span class="text-sm font-bold text-content group-hover:text-brand transition-colors block">
              Escanear con Cámara
            </span>
            <p class="text-xs text-content-secondary leading-relaxed">
              Lectura automática del código QR que el cliente exhibe en su pantalla.
            </p>
          </div>
        </button>

        <!-- Opción B: Ingreso Manual -->
        <div class="p-5 rounded-2xl bg-canvas border border-border-subtle space-y-2">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-xs font-bold text-content">Ingreso por Código</span>
          </div>
          <p class="text-[11px] text-content-secondary">
            Si la cámara no está disponible, ingresá los 12 caracteres alfanuméricos.
          </p>
        </div>
      </div>

      <!-- Formulario de Entrada Manual -->
      <form class="space-y-3" @submit.prevent="verifyCode()">
        <label for="pickup-code-input" class="text-xs font-semibold text-content block">
          Código de Retiro del Cliente
        </label>

        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <input
              id="pickup-code-input"
              v-model="pickupCode"
              type="text"
              placeholder="XXXX-XXXX-XXXX"
              maxlength="14"
              class="w-full px-4 py-3 rounded-[10px] bg-canvas border border-border-subtle text-content font-mono text-lg font-extrabold tracking-widest text-center uppercase focus:outline-none focus:border-brand transition-colors placeholder:text-content-secondary/50 placeholder:font-sans placeholder:tracking-normal disabled:opacity-50"
              :disabled="isVerifying || isConfirming"
              @input="onInputCode"
            />
          </div>

          <button
            type="submit"
            class="px-6 py-3 rounded-[10px] text-xs font-bold bg-brand hover:bg-brand-hover text-surface transition-all disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2 shrink-0"
            :disabled="isVerifying || isConfirming || !pickupCode.trim()"
          >
            <span>{{ isVerifying ? "Verificando..." : "Verificar Código" }}</span>
          </button>
        </div>

        <!-- Alerta de Error -->
        <div
          v-if="verifyError"
          class="p-3.5 rounded-2xl bg-danger/10 border border-danger/30 text-xs text-danger font-semibold flex items-center gap-2"
        >
          <span>{{ verifyError }}</span>
        </div>

        <!-- Alerta de Éxito -->
        <div
          v-if="successMessage"
          class="p-3.5 rounded-2xl bg-success/12 border border-success/30 text-xs text-success font-bold flex items-center gap-2"
        >
          <span>{{ successMessage }}</span>
        </div>
      </form>

      <!-- Ficha del Pedido Verificado -->
      <div
        v-if="verifiedOrder"
        class="pt-6 border-t border-border-subtle space-y-6"
      >
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand block">
              Detalles de la Reserva
            </span>
            <h2 class="text-xl font-extrabold text-content mt-0.5">
              Pedido #{{ verifiedOrder.sell_id }}
            </h2>
            <span class="text-xs text-content-secondary block">
              Emitido el {{ formatDate(verifiedOrder.created_at) }} hs
            </span>
          </div>

          <span class="px-3.5 py-1.5 rounded-xl font-mono text-sm font-bold bg-brand/12 border border-brand/25 text-brand">
            {{ verifiedOrder.pickup_code }}
          </span>
        </div>

        <!-- Información del Cliente -->
        <div class="p-4 rounded-2xl bg-canvas border border-border-subtle space-y-2">
          <span class="text-[10px] font-bold text-content-secondary uppercase tracking-wider block">
            Datos del Comprador
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <span class="text-content-secondary block text-[11px]">Nombre y Apellido</span>
              <strong class="text-content font-bold text-sm">{{ verifiedOrder.customer?.name || "Cliente" }}</strong>
            </div>
            <div>
              <span class="text-content-secondary block text-[11px]">Email</span>
              <span class="text-content">{{ verifiedOrder.customer?.email }}</span>
            </div>
          </div>
        </div>

        <!-- Bolsas Sorpresa a Entregar -->
        <div class="space-y-2.5">
          <span class="text-xs font-bold text-content-secondary uppercase tracking-wider block">
            Bolsas sorpresa incluidas en este pedido
          </span>

          <div class="space-y-2">
            <div
              v-for="(offer, idx) in verifiedOrder.offers"
              :key="idx"
              class="p-4 rounded-2xl bg-canvas border border-border-subtle flex items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span class="px-2.5 py-1 rounded-lg bg-brand/12 text-brand font-bold text-xs shrink-0">
                  x{{ offer.offer_quantity }}
                </span>
                <div class="min-w-0">
                  <h4 class="text-sm font-bold text-content truncate">
                    {{ offer.pack_name || offer.offer_title }}
                  </h4>
                  <p v-if="offer.pack_description" class="text-xs text-content-secondary line-clamp-1 mt-0.5">
                    {{ offer.pack_description }}
                  </p>
                </div>
              </div>

              <div class="text-right shrink-0">
                <span class="text-sm font-extrabold text-content">
                  ${{ formatCurrency(offer.pack_price * offer.offer_quantity) }}
                </span>
                <span class="block text-[10px] text-content-secondary">
                  ${{ formatCurrency(offer.pack_price) }} c/u
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Total y Acciones Finales -->
        <div class="pt-4 border-t border-border-subtle flex items-center justify-between flex-wrap gap-4">
          <div>
            <span class="text-xs text-content-secondary uppercase font-semibold block">Total Abonado</span>
            <span class="text-2xl font-extrabold text-content tracking-tight">
              ${{ formatCurrency(calculateTotal(verifiedOrder)) }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              class="px-4 py-2.5 rounded-[10px] text-xs font-semibold text-content bg-surface-sunken hover:bg-canvas transition-colors cursor-pointer"
              :disabled="isConfirming"
              @click="clearOrder"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-[10px] text-xs font-bold bg-brand hover:bg-brand-hover text-surface transition-all disabled:opacity-50 cursor-pointer"
              :disabled="isConfirming"
              @click="onConfirmDelivery"
            >
              <span>{{ isConfirming ? "Confirmando entrega..." : "✓ Confirmar Entrega" }}</span>
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
