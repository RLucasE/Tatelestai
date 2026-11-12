<script setup>
import { ref, computed } from 'vue';
import { RouterLink } from 'vue-router';
import StockCard from './StockCard.vue';

const props = defineProps({
  stock: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['create-offer', 'edit-offer', 'view-all']);

const currentFilter = ref('all'); // 'all' | 'in_stock' | 'sold_out'

const inStockOffers = computed(() => {
  return props.stock.filter((offer) => !offer.is_sold_out && (offer.stock_remaining ?? offer.quantity ?? 0) > 0);
});

const soldOutOffers = computed(() => {
  return props.stock.filter((offer) => offer.is_sold_out || (offer.stock_remaining ?? offer.quantity ?? 0) <= 0);
});

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
  <section class="flex flex-col gap-5">
    <!-- StockSectionHeader (6002:2009) -->
    <div class="flex flex-col gap-4">
      <!-- Title and Filter Pills Row (6005:2422) -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <h2 class="text-[22px] font-extrabold text-content leading-tight">
          Stock en Vivo
        </h2>

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
            Todas ({{ stock.length }})
          </button>

          <button
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'in_stock'
                ? 'bg-brand text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'in_stock'"
          >
            Con stock ({{ inStockOffers.length }})
          </button>

          <button
            type="button"
            class="px-3.5 py-[7px] rounded-full text-[12.5px] transition-all cursor-pointer"
            :class="
              currentFilter === 'sold_out'
                ? 'bg-brand text-surface font-bold shadow-xs'
                : 'text-content-secondary font-semibold hover:text-content hover:bg-surface/60'
            "
            @click="currentFilter = 'sold_out'"
          >
            Agotadas ({{ soldOutOffers.length }})
          </button>
        </div>
      </div>

      <!-- Subtitle and + Publicar Bolsa Button Row (6005:2431) -->
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <span class="text-[13.5px] text-content-secondary">
          {{ stock.length }} {{ stock.length === 1 ? 'bolsa publicada hoy en el local' : 'bolsas publicadas hoy en el local' }}
        </span>

        <button
          type="button"
          class="inline-flex items-center justify-center px-[18px] py-[9px] rounded-[10px] bg-surface-darkest hover:bg-surface-darkest/90 text-surface text-[13px] font-bold transition-all active:scale-95 cursor-pointer shrink-0"
          @click="emit('create-offer')"
        >
          + Publicar Bolsa
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="stock.length === 0"
      class="p-10 rounded-[18px] bg-surface border border-dashed border-border-subtle text-center flex flex-col items-center justify-center gap-3 shadow-card"
    >
      <div class="w-11 h-11 rounded-[14px] bg-canvas text-content-secondary flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-content">Sin ofertas activas</h3>
        <p class="text-xs text-content-secondary max-w-xs mt-0.5">
          Publicá tus excedentes del día para comenzar a recibir pedidos.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[10px] text-xs font-bold bg-brand hover:bg-brand-hover text-surface transition-all cursor-pointer"
        @click="emit('create-offer')"
      >
        <span>+ Publicar Bolsa</span>
      </button>
    </div>

    <div
      v-else-if="filteredStock.length === 0"
      class="p-6 rounded-[18px] bg-surface border border-border-subtle text-center text-xs text-content-secondary shadow-card"
    >
      No hay ofertas que coincidan con este filtro.
    </div>

    <!-- StockCardsList (6002:2020) -->
    <div v-else class="flex flex-col gap-4">
      <StockCard
        v-for="offer in filteredStock"
        :key="offer.offer_id ?? offer.id"
        :offer="offer"
        @edit="(offerId, offerObj) => emit('edit-offer', offerId, offerObj)"
      />
    </div>

    <!-- ManageCatalogLinkRow (6002:2051) -->
    <div class="flex items-center justify-between gap-3 pt-2 flex-wrap">
      <span class="text-[13px] font-medium text-content-secondary">
        ¿Querés lanzar otro lote o editar tus plantillas?
      </span>
      <RouterLink
        to="/seller/templates"
        class="text-[13.5px] font-bold text-brand hover:text-brand-hover transition-colors"
      >
        Ir a Packs y Plantillas →
      </RouterLink>
    </div>
  </section>
</template>
