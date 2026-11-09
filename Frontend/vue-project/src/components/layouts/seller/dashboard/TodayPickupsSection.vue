<script setup>
import { ref, computed, shallowRef } from 'vue';
import PickupCard from './PickupCard.vue';
import QrScannerModal from '@/components/layouts/seller/QrScannerModal.vue';
import ScannedOrderModal from './ScannedOrderModal.vue';
import { usePickupRedemption } from '@/composables/usePickupRedemption';

const props = defineProps({
  pickups: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['redeem', 'reload']);

const currentFilter = ref('all'); // 'all' | 'pending' | 'picked_up' | 'cancelled'
const searchOrCodeQuery = ref('');
const expandedPickupId = ref(null);
const showQrScanner = shallowRef(false);
const showScannedOrderModal = ref(false);
const selectedModalPickup = ref(null);

const {
  isVerifying,
  verifyError,
  verifiedOrder,
  isConfirming,
  successMessage,
  verifyCode,
  handleQrScanned,
  confirmDelivery,
  clearOrder,
  calculateTotal,
} = usePickupRedemption();

const countPending = computed(() => {
  return props.pickups.filter((p) => !p.is_picked_up && p.status !== 'picked_up' && p.status !== 'cancelled').length;
});

const countDelivered = computed(() => {
  return props.pickups.filter((p) => p.is_picked_up || p.status === 'picked_up').length;
});

const countCancelled = computed(() => {
  return props.pickups.filter((p) => p.status === 'cancelled').length;
});

const normalizedVerifiedPickup = computed(() => {
  if (!verifiedOrder.value) return null;
  const vo = verifiedOrder.value;
  const sellId = vo.sell_id ?? vo.id;
  const existing = props.pickups.find((p) => Number(p.id) === Number(sellId));
  if (existing) {
    return {
      ...existing,
      pickup_code: vo.pickup_code || existing.pickup_code,
      customer_email: vo.customer?.email || existing.customer_email,
    };
  }
  const packs = (vo.offers || []).map((item) => ({
    quantity: item.offer_quantity || 1,
    pack_name: item.pack_name || item.offer_title || 'Bolsa Sorpresa',
    description: item.pack_description || '',
    unit_price: item.pack_price || 0,
    subtotal: (item.offer_quantity || 1) * (item.pack_price || 0),
  }));
  return {
    id: sellId,
    customer_name: vo.customer?.name || 'Cliente',
    customer_email: vo.customer?.email || '',
    status: 'ready',
    is_picked_up: false,
    pickup_code: vo.pickup_code,
    total_price: calculateTotal(vo),
    packs,
  };
});

const filteredPickups = computed(() => {
  let list = props.pickups;

  if (currentFilter.value === 'pending') {
    list = list.filter((p) => !p.is_picked_up && p.status !== 'picked_up' && p.status !== 'cancelled');
  } else if (currentFilter.value === 'picked_up') {
    list = list.filter((p) => p.is_picked_up || p.status === 'picked_up');
  } else if (currentFilter.value === 'cancelled') {
    list = list.filter((p) => p.status === 'cancelled');
  }

  const q = searchOrCodeQuery.value.trim().toLowerCase();
  if (q && !q.includes('-')) {
    const matched = list.filter((p) => {
      const nameMatch = (p.customer_name || '').toLowerCase().includes(q);
      const idMatch = String(p.id).includes(q);
      return nameMatch || idMatch;
    });
    if (matched.length > 0) {
      return matched;
    }
  }

  return list;
});

const emptyStateMessage = computed(() => {
  if (currentFilter.value === 'cancelled') {
    return 'No hay pedidos cancelados registrados para el día de hoy.';
  }
  if (currentFilter.value === 'pending') {
    return 'No hay pedidos pendientes de retiro para el día de hoy.';
  }
  if (currentFilter.value === 'picked_up') {
    return 'Aún no se han completado entregas durante la jornada.';
  }
  return 'No se han registrado ventas para retirar en el día de hoy.';
});

const handleToolbarVerify = async () => {
  const raw = searchOrCodeQuery.value.trim();
  if (!raw) return;

  const ok = await verifyCode(raw.toUpperCase());
  if (ok && verifiedOrder.value) {
    const sellId = verifiedOrder.value.sell_id ?? verifiedOrder.value.id;
    expandedPickupId.value = Number(sellId);
    selectedModalPickup.value = normalizedVerifiedPickup.value;
    showScannedOrderModal.value = true;
  }
};

const handleQrSuccess = async (decodedText) => {
  const ok = await handleQrScanned(decodedText);
  if (ok && verifiedOrder.value) {
    const sellId = verifiedOrder.value.sell_id ?? verifiedOrder.value.id;
    expandedPickupId.value = Number(sellId);
    selectedModalPickup.value = normalizedVerifiedPickup.value;
    showScannedOrderModal.value = true;
  }
};

const handleCardRedeemClick = (pickup) => {
  if (
    verifiedOrder.value &&
    Number(verifiedOrder.value.sell_id ?? verifiedOrder.value.id) === Number(pickup.id)
  ) {
    expandedPickupId.value = Number(pickup.id);
    selectedModalPickup.value = normalizedVerifiedPickup.value;
    showScannedOrderModal.value = true;
    return;
  }

  clearOrder();
  selectedModalPickup.value = pickup;
  showScannedOrderModal.value = true;
};

const handleModalVerifyCode = async (codeStr) => {
  const ok = await verifyCode(codeStr);
  if (ok && verifiedOrder.value) {
    const sellId = verifiedOrder.value.sell_id ?? verifiedOrder.value.id;
    expandedPickupId.value = Number(sellId);
    selectedModalPickup.value = normalizedVerifiedPickup.value;
  }
};

const handleConfirmDeliveryAction = async () => {
  await confirmDelivery(() => {
    showScannedOrderModal.value = false;
    expandedPickupId.value = null;
    searchOrCodeQuery.value = '';
    emit('reload');
  });
};

const handleCollapseCard = () => {
  expandedPickupId.value = null;
  clearOrder();
};
</script>

<template>
  <section class="flex flex-col gap-5">
    <!-- PickupsSectionHeader -->
    <div class="flex flex-col gap-4">
      <!-- PickupsTitleAndTabsRow -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <h2 class="text-[22px] font-extrabold text-content leading-tight">
          Retiros de Hoy
        </h2>

        <!-- PickupFilterPills -->
        <div class="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'all'
                ? 'bg-brand text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'all'"
          >
            Todos ({{ pickups.length }})
          </button>

          <button
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'pending'
                ? 'bg-brand text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'pending'"
          >
            Pendientes ({{ countPending }})
          </button>

          <button
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'picked_up'
                ? 'bg-brand text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'picked_up'"
          >
            Entregados ({{ countDelivered }})
          </button>

          <button
            v-if="countCancelled > 0"
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'cancelled'
                ? 'bg-danger text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'cancelled'"
          >
            Cancelados ({{ countCancelled }})
          </button>
        </div>
      </div>

      <!-- IntegratedCodeAndQrToolbar (6005:2411) -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        <form
          class="flex-1 flex items-center justify-between gap-2 rounded-full bg-surface border border-border-subtle pl-4 pr-3.5 py-[9px] shadow-2xs focus-within:border-brand transition-colors"
          @submit.prevent="handleToolbarVerify"
        >
          <div class="flex items-center gap-2.5 flex-1 min-w-0">
            <svg class="w-4 h-4 text-content-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchOrCodeQuery"
              type="text"
              placeholder="Ingresar código de retiro (XXXX-XXXX-XXXX) o buscar cliente..."
              class="w-full bg-transparent text-[13px] text-content placeholder:text-content-secondary focus:outline-none"
            />
          </div>

          <button
            type="submit"
            class="text-[12px] font-bold text-brand hover:text-brand-hover shrink-0 cursor-pointer disabled:opacity-40"
            :disabled="isVerifying || !searchOrCodeQuery.trim()"
          >
            {{ isVerifying ? 'Verificando...' : 'Verificar ↵' }}
          </button>
        </form>

        <!-- Btn_EscanearQR -->
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 px-[18px] py-[9px] rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13px] font-bold shadow-xs transition-all active:scale-95 cursor-pointer shrink-0"
          @click="showQrScanner = true"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
            />
          </svg>
          <span>Escanear QR</span>
        </button>
      </div>

      <!-- Verification feedback alerts -->
      <div
        v-if="verifyError && !showScannedOrderModal"
        class="px-4 py-2.5 rounded-[12px] bg-danger/15 border border-danger/30 text-danger text-xs font-semibold flex items-center justify-between"
      >
        <span>{{ verifyError }}</span>
        <button type="button" class="text-xs font-bold underline cursor-pointer" @click="clearOrder">
          Cerrar
        </button>
      </div>

      <div
        v-if="successMessage"
        class="px-4 py-2.5 rounded-[12px] bg-success/15 border border-success/30 text-success text-xs font-bold"
      >
        {{ successMessage }}
      </div>
    </div>

    <!-- If a verified order wasn't in the current filtered list, show it expanded at the top -->
    <PickupCard
      v-if="
        normalizedVerifiedPickup &&
        expandedPickupId === Number(normalizedVerifiedPickup.id) &&
        !filteredPickups.some((p) => Number(p.id) === Number(normalizedVerifiedPickup.id))
      "
      :pickup="normalizedVerifiedPickup"
      :expanded="true"
      :verified-code="verifiedOrder?.pickup_code"
      :confirming="isConfirming"
      @collapse="handleCollapseCard"
      @confirm-delivery="handleConfirmDeliveryAction"
      @cancelled="emit('reload')"
    />

    <!-- Empty State -->
    <div
      v-if="filteredPickups.length === 0"
      class="p-10 rounded-[18px] bg-surface border border-dashed border-border-subtle text-center flex flex-col items-center justify-center gap-2 shadow-card"
    >
      <div class="w-11 h-11 rounded-[14px] bg-canvas text-content-secondary flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-content">Sin pedidos para mostrar</h3>
      <p class="text-xs text-content-secondary max-w-xs">
        {{ emptyStateMessage }}
      </p>
    </div>

    <!-- PickupsCardsList -->
    <div v-else class="flex flex-col gap-4">
      <PickupCard
        v-for="pickup in filteredPickups"
        :key="pickup.id"
        :pickup="
          normalizedVerifiedPickup && Number(normalizedVerifiedPickup.id) === Number(pickup.id)
            ? normalizedVerifiedPickup
            : pickup
        "
        :expanded="expandedPickupId === Number(pickup.id)"
        :verified-code="verifiedOrder?.pickup_code"
        :confirming="isConfirming"
        @redeem="handleCardRedeemClick"
        @collapse="handleCollapseCard"
        @confirm-delivery="handleConfirmDeliveryAction"
        @cancelled="emit('reload')"
      />
    </div>

    <!-- Modal de Escáner QR con Cámara -->
    <QrScannerModal
      :show="showQrScanner"
      @close="showQrScanner = false"
      @scanned="handleQrSuccess"
    />

    <!-- Modal Pedido Escaneado / Confirmar Entrega (6003:2159) -->
    <ScannedOrderModal
      v-model="showScannedOrderModal"
      :order="
        normalizedVerifiedPickup &&
        selectedModalPickup &&
        Number(normalizedVerifiedPickup.id) === Number(selectedModalPickup.id)
          ? normalizedVerifiedPickup
          : null
      "
      :pickup="selectedModalPickup"
      :loading="isVerifying"
      :confirming="isConfirming"
      :error-message="verifyError"
      @verify-code="handleModalVerifyCode"
      @confirm-delivery="handleConfirmDeliveryAction"
    />
  </section>
</template>
