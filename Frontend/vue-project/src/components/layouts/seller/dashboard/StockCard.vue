<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

defineEmits(['edit']);

const { formatCurrency, formatTime } = useFormatters();

const isSoldOut = computed(() => Boolean(props.offer.is_sold_out || props.offer.stock_remaining <= 0));

const statusBadgeClass = computed(() => {
  if (isSoldOut.value) {
    return 'bg-[#EF4444]/15 border-[#EF4444]/30 text-[#F87171]';
  }
  return 'bg-[#10B981]/15 border-[#10B981]/30 text-[#34D399]';
});
</script>

<template>
  <div
    class="p-3 sm:p-3.5 rounded-xl bg-[#2D2438] border border-white/[0.08] flex items-center justify-between gap-3 hover:bg-[#3D3450]/70 hover:border-white/[0.14] transition-all group"
    :class="{ 'opacity-70 bg-[#2D2438]/60': isSoldOut }"
  >
    <!-- Contenido Principal de la Oferta -->
    <div class="min-w-0 flex-1 space-y-1">
      <!-- Línea 1: Título, Precio y Estado -->
      <div class="flex items-center gap-2 flex-wrap">
        <h4 class="text-xs sm:text-sm font-bold text-white truncate max-w-[200px] sm:max-w-xs">
          {{ offer.title }}
        </h4>

        <span class="text-xs font-bold text-[#A78BFA] shrink-0">
          ${{ formatCurrency(offer.price) }}
        </span>

        <span
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0"
          :class="statusBadgeClass"
        >
          <span class="w-1 h-1 rounded-full" :class="isSoldOut ? 'bg-[#EF4444]' : 'bg-[#10B981]'"></span>
          <span>{{ isSoldOut ? 'Agotado' : 'Disponible' }}</span>
        </span>
      </div>

      <!-- Línea 2: Métricas en línea y Horario -->
      <div class="flex items-center gap-1.5 text-[11px] text-[#A5A8C2] flex-wrap">
        <span
          class="font-semibold"
          :class="isSoldOut ? 'text-[#EF4444]' : 'text-[#34D399]'"
        >
          {{ offer.stock_remaining }} {{ offer.stock_remaining === 1 ? 'disponible' : 'disponibles' }}
        </span>

        <span class="text-white/20">•</span>

        <span class="text-[#C4B5FD]">
          {{ offer.stock_sold_today }} {{ offer.stock_sold_today === 1 ? 'vendido hoy' : 'vendidos hoy' }}
        </span>

        <template v-if="offer.pickup_start_datetime || offer.expiration_datetime">
          <span class="text-white/20">•</span>
          <span class="inline-flex items-center gap-1">
            <svg class="w-3 h-3 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ formatTime(offer.pickup_start_datetime) }} a {{ formatTime(offer.expiration_datetime) }} hs
          </span>
        </template>
      </div>
    </div>

    <!-- Botón Editar Oferta -->
    <button
      type="button"
      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-[#A78BFA] hover:text-white bg-white/[0.04] hover:bg-[#7C3AED]/20 border border-white/[0.06] hover:border-[#7C3AED]/40 transition-all cursor-pointer shrink-0"
      title="Editar oferta"
      @click="$emit('edit', offer.offer_id)"
    >
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
      </svg>
      <span class="hidden sm:inline">Editar</span>
    </button>
  </div>
</template>
