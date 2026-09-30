<script setup>
import { ref, computed } from 'vue';
import StockCard from './StockCard.vue';

const props = defineProps({
  stock: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['create-offer', 'edit-offer', 'view-all']);

// Filtro de estado: 'all' | 'in_stock' | 'sold_out'
const currentFilter = ref('all');

// Ofertas con stock vs agotadas
const inStockOffers = computed(() => {
  return props.stock.filter((offer) => !offer.is_sold_out && offer.stock_remaining > 0);
});

const soldOutOffers = computed(() => {
  return props.stock.filter((offer) => offer.is_sold_out || offer.stock_remaining <= 0);
});

// Total de unidades disponibles acumuladas
const totalUnitsAvailable = computed(() => {
  return props.stock.reduce((acc, curr) => acc + (Number(curr.stock_remaining) || 0), 0);
});

// Lista filtrada
const filteredStock = computed(() => {
  if (currentFilter.value === 'in_stock') {
    return inStockOffers.value;
  }
  if (currentFilter.value === 'sold_out') {
    return soldOutOffers.value;
  }
  return props.stock;
});
</script>

<template>
  <section class="space-y-3.5">
    <!-- Header de Sección -->
    <div class="flex items-start sm:items-center justify-between gap-3 flex-wrap">
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
            Stock en Vivo
          </h2>
          <span
            v-if="stock.length > 0"
            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#7C3AED]/20 text-[#A78BFA] border border-[#7C3AED]/30"
          >
            {{ totalUnitsAvailable }} {{ totalUnitsAvailable === 1 ? 'unidad' : 'unidades' }}
          </span>
        </div>
        <p class="text-xs text-[#A5A8C2] mt-0.5">
          {{ stock.length }} {{ stock.length === 1 ? 'bolsa publicada' : 'bolsas publicadas' }} en el local
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="text-xs font-semibold text-[#A78BFA] hover:text-white transition-colors cursor-pointer"
          @click="$emit('view-all')"
        >
          Gestionar ofertas &rarr;
        </button>
      </div>
    </div>

    <!-- Pestañas de Filtro (solo si hay más de 1 oferta) -->
    <div
      v-if="stock.length > 1"
      class="flex items-center gap-1.5 p-1 rounded-xl bg-[#2D2438]/80 border border-white/[0.06] overflow-x-auto"
    >
      <button
        type="button"
        class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer whitespace-nowrap"
        :class="
          currentFilter === 'all'
            ? 'bg-[#7C3AED] text-white shadow-sm'
            : 'text-[#A5A8C2] hover:text-white hover:bg-white/[0.04]'
        "
        @click="currentFilter = 'all'"
      >
        Todas ({{ stock.length }})
      </button>

      <button
        type="button"
        class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer whitespace-nowrap"
        :class="
          currentFilter === 'in_stock'
            ? 'bg-[#7C3AED] text-white shadow-sm'
            : 'text-[#A5A8C2] hover:text-white hover:bg-white/[0.04]'
        "
        @click="currentFilter = 'in_stock'"
      >
        Con stock ({{ inStockOffers.length }})
      </button>

      <button
        type="button"
        class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer whitespace-nowrap"
        :class="
          currentFilter === 'sold_out'
            ? 'bg-[#7C3AED] text-white shadow-sm'
            : 'text-[#A5A8C2] hover:text-white hover:bg-white/[0.04]'
        "
        @click="currentFilter = 'sold_out'"
      >
        Agotadas ({{ soldOutOffers.length }})
      </button>
    </div>

    <!-- Empty State General (sin ofertas creadas) -->
    <div
      v-if="stock.length === 0"
      class="p-8 rounded-2xl bg-[#2D2438] border border-dashed border-white/[0.1] text-center space-y-3 flex flex-col items-center justify-center"
    >
      <div class="w-10 h-10 rounded-xl bg-white/[0.04] text-[#A5A8C2] flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-white">Sin ofertas activas</h3>
        <p class="text-xs text-[#A5A8C2] max-w-xs mt-0.5">
          Publica tus excedentes del día para comenzar a recibir pedidos.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-sm transition-all hover:scale-[1.02] active:scale-95 cursor-pointer"
        @click="$emit('create-offer')"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Publicar Bolsa</span>
      </button>
    </div>

    <!-- Empty State del Filtro -->
    <div
      v-else-if="filteredStock.length === 0"
      class="p-6 rounded-xl bg-[#2D2438]/50 border border-white/[0.06] text-center text-xs text-[#A5A8C2]"
    >
      No hay ofertas que coincidan con este filtro.
    </div>

    <!-- Lista de Ofertas con scroll contenido estilizado -->
    <div
      v-else
      class="max-h-[460px] overflow-y-auto pr-1 space-y-2 custom-scrollbar"
    >
      <StockCard
        v-for="offer in filteredStock"
        :key="offer.offer_id"
        :offer="offer"
        @edit="$emit('edit-offer', $event)"
      />
    </div>
  </section>
</template>
