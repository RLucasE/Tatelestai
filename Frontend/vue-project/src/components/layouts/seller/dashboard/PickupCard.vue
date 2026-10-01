<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  pickup: {
    type: Object,
    required: true,
  },
});

defineEmits(['redeem']);

const { formatCurrency, formatTime, getInitials } = useFormatters();

const isPickedUp = computed(() => Boolean(props.pickup.is_picked_up) || props.pickup.status === 'picked_up');
const isCancelled = computed(() => props.pickup.status === 'cancelled');

const isExpired = computed(() => {
  if (isPickedUp.value || isCancelled.value) return false;
  if (props.pickup.status === 'expired') return true;
  if (!props.pickup.max_pickup_datetime) return false;
  return new Date() > new Date(props.pickup.max_pickup_datetime);
});

const statusLabel = computed(() => {
  if (isCancelled.value) return 'Cancelado';
  if (isPickedUp.value) return 'Retirado';
  if (isExpired.value) return 'Expirado';
  if (props.pickup.status === 'ready') return 'Listo para retirar';
  if (props.pickup.status === 'confirmed') return 'Confirmado';
  return 'Pendiente';
});

const statusPillClass = computed(() => {
  if (isCancelled.value) {
    return 'bg-[#EF4444]/15 border-[#EF4444]/30 text-[#F87171]';
  }
  if (isPickedUp.value) {
    return 'bg-[#10B981]/15 border-[#10B981]/30 text-[#34D399]';
  }
  if (isExpired.value) {
    return 'bg-[#EF4444]/15 border-[#EF4444]/30 text-[#F87171]';
  }
  if (props.pickup.status === 'ready') {
    return 'bg-[#10B981]/15 border-[#10B981]/30 text-[#34D399]';
  }
  if (props.pickup.status === 'confirmed') {
    return 'bg-[#7C3AED]/15 border-[#7C3AED]/30 text-[#C4B5FD]';
  }
  return 'bg-[#F59E0B]/15 border-[#F59E0B]/30 text-[#FBBF24]';
});

const canRedeem = computed(() => {
  return props.pickup.status === 'pending' && !isPickedUp.value && !isExpired.value;
});
</script>

<template>
  <div
    class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] space-y-3 transition-all hover:bg-[#3D3450]/60"
    :class="{ 'opacity-70 border-white/[0.04]': isPickedUp }"
  >
    <!-- Fila Superior: Cliente y Estado -->
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-8 h-8 rounded-full bg-[#1A1625] border border-white/[0.1] text-xs font-bold text-[#A78BFA] flex items-center justify-center shrink-0">
          {{ getInitials(pickup.customer_name) }}
        </div>
        <div class="min-w-0">
          <h4 class="text-sm font-bold text-white truncate">
            {{ pickup.customer_name || 'Cliente' }}
          </h4>
          <span class="text-[11px] font-medium text-[#A5A8C2]">
            Pedido #{{ pickup.id }}
          </span>
        </div>
      </div>

      <span
        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shrink-0"
        :class="statusPillClass"
      >
        <svg v-if="isPickedUp" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else-if="isExpired" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ statusLabel }}</span>
      </span>
    </div>

    <!-- Franja Horaria de Retiro -->
    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#1A1625] border border-white/[0.04] text-xs text-[#E8EAF6]">
      <svg class="w-3.5 h-3.5 text-[#A78BFA] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="text-[#A5A8C2]">Horario:</span>
      <strong class="font-semibold text-white">
        {{ formatTime(pickup.pickup_start_datetime) }} a {{ formatTime(pickup.max_pickup_datetime) }} hs
      </strong>
    </div>

    <!-- Lista de Packs -->
    <div class="space-y-1 text-xs pt-1">
      <div
        v-for="(pack, idx) in pickup.packs"
        :key="idx"
        class="flex items-center justify-between text-[#A5A8C2]"
      >
        <div class="flex items-center gap-1.5 truncate">
          <span class="font-bold text-[#A78BFA]">x{{ pack.quantity }}</span>
          <span class="truncate text-[#E8EAF6]">{{ pack.pack_name }}</span>
        </div>
        <span class="font-medium text-white shrink-0 ml-2">
          ${{ formatCurrency(pack.subtotal) }}
        </span>
      </div>
    </div>

    <!-- Total y Acción -->
    <div class="flex items-center justify-between pt-2.5 border-t border-white/[0.06]">
      <div class="flex items-baseline gap-1.5">
        <span class="text-[11px] text-[#A5A8C2]">Total:</span>
        <strong class="text-sm font-bold text-white">
          ${{ formatCurrency(pickup.total_price) }}
        </strong>
      </div>

      <button
        v-if="canRedeem"
        type="button"
        class="text-xs font-semibold text-[#A78BFA] hover:text-white transition-colors cursor-pointer"
        @click="$emit('redeem', pickup)"
      >
        Verificar código &rarr;
      </button>
    </div>
  </div>
</template>
