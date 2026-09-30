<script setup>
import { ref, computed } from 'vue';
import PickupCard from './PickupCard.vue';

const props = defineProps({
  pickups: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['redeem']);

const currentFilter = ref('all'); // 'all', 'pending', 'picked_up'

const countPending = computed(() => {
  return props.pickups.filter((p) => !p.is_picked_up).length;
});

const countDelivered = computed(() => {
  return props.pickups.filter((p) => p.is_picked_up).length;
});

const filteredPickups = computed(() => {
  if (currentFilter.value === 'pending') {
    return props.pickups.filter((p) => !p.is_picked_up);
  }
  if (currentFilter.value === 'picked_up') {
    return props.pickups.filter((p) => p.is_picked_up);
  }
  return props.pickups;
});
</script>

<template>
  <section class="space-y-4">
    <!-- Header de Sección y Filtros -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
          Retiros de Hoy
        </h2>
        <p class="text-xs text-[#A5A8C2]">
          Gestión de pedidos agendados para entrega física durante la jornada.
        </p>
      </div>

      <!-- Segmented Buttons / Tabs -->
      <div class="inline-flex p-1 rounded-xl bg-[#2D2438] border border-white/[0.08] self-start sm:self-center">
        <button
          type="button"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
          :class="currentFilter === 'all' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-[#A5A8C2] hover:text-white'"
          @click="currentFilter = 'all'"
        >
          Todos ({{ pickups.length }})
        </button>
        <button
          type="button"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
          :class="currentFilter === 'pending' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-[#A5A8C2] hover:text-white'"
          @click="currentFilter = 'pending'"
        >
          Pendientes ({{ countPending }})
        </button>
        <button
          type="button"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
          :class="currentFilter === 'picked_up' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-[#A5A8C2] hover:text-white'"
          @click="currentFilter = 'picked_up'"
        >
          Entregados ({{ countDelivered }})
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="filteredPickups.length === 0"
      class="p-8 rounded-2xl bg-[#2D2438] border border-dashed border-white/[0.1] text-center space-y-2 flex flex-col items-center justify-center"
    >
      <div class="w-10 h-10 rounded-xl bg-white/[0.04] text-[#A5A8C2] flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-white">Sin pedidos para mostrar</h3>
      <p class="text-xs text-[#A5A8C2] max-w-xs">
        {{ currentFilter === 'all' ? 'No se han registrado ventas para retirar en el día de hoy.' : 'No hay pedidos con el filtro seleccionado.' }}
      </p>
    </div>

    <!-- Lista de Retiros con scroll contenido estilizado -->
    <div v-else class="max-h-[460px] overflow-y-auto pr-1 space-y-2.5 custom-scrollbar">
      <PickupCard
        v-for="pickup in filteredPickups"
        :key="pickup.id"
        :pickup="pickup"
        @redeem="$emit('redeem', $event)"
      />
    </div>
  </section>
</template>
