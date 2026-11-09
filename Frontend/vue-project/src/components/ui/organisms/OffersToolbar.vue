<script setup>
import SearchBar from '@/components/common/SearchBar.vue';
import LocationBar from '@/components/common/LocationBar.vue';
import BaseButton from '../atoms/BaseButton.vue';

const activeCategory = defineModel('activeCategory', { type: String, default: 'all' });

defineProps({
  categories: {
    type: Array,
    required: true,
  },
  isLocationActive: {
    type: Boolean,
    default: false,
  },
  radiusKm: {
    type: Number,
    default: 3,
  },
});

const emit = defineEmits([
  'search-results',
  'search-error',
  'search-clear',
  'search-leading',
  'location-changed',
  'open-picker',
]);
</script>

<template>
  <div class="pt-6 pb-2 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 lg:gap-4 w-full">
    <!-- Izquierda (LeftFilters): Pastilla de Ubicación + Barra de Búsqueda -->
    <div class="flex items-center gap-3 w-full sm:w-auto shrink-0">
      <div class="shrink-0">
        <LocationBar
          @location-changed="emit('location-changed')"
          @open-picker="emit('open-picker')"
        />
      </div>

      <div class="flex-1 sm:w-[260px] sm:flex-none min-w-0">
        <SearchBar
          placeholder="Buscar..."
          @search-results="emit('search-results', $event)"
          @search-error="emit('search-error', $event)"
          @search-clear="emit('search-clear')"
          @search-leading="emit('search-leading', $event)"
        />
      </div>
    </div>

    <!-- Derecha (CategoryChips): Botones de Categoría (Figma: 34px de altura, gap 8px, rounded-full) -->
    <div class="flex items-center gap-2 overflow-x-auto scrollbar-none max-w-full lg:max-w-none justify-start lg:justify-end pb-1 lg:pb-0">
      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        class="h-[34px] px-4 rounded-full text-[13px] transition-all select-none whitespace-nowrap shrink-0 cursor-pointer active:scale-95 border"
        :class="[
          activeCategory === cat.id
            ? 'bg-brand text-surface font-semibold border-brand shadow-sm'
            : 'bg-surface text-content font-medium border-border-subtle hover:border-content-secondary hover:text-brand',
        ]"
        @click="activeCategory = cat.id"
      >
        {{ cat.label }}
      </button>
    </div>
  </div>
</template>
