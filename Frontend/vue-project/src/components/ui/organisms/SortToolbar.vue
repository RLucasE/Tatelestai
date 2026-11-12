<script setup>
import BaseSelect from '../atoms/BaseSelect.vue';
import BaseIcon from '../atoms/BaseIcon.vue';

const sortBy = defineModel('sortBy', { type: String, default: 'distance' });

defineProps({
  totalOffers: {
    type: Number,
    default: 0,
  },
  sortOptions: {
    type: Array,
    default: () => [
      { value: 'distance', label: 'Más cercanas' },
      { value: 'discount', label: 'Mayor ahorro (%)' },
      { value: 'expiration', label: 'Retiro más próximo' },
      { value: 'price_asc', label: 'Menor precio' },
    ],
  },
});

const emit = defineEmits(['reset-filters']);
</script>

<template>
  <div class="pt-2 pb-4 flex items-center justify-between text-[13px] text-content-secondary flex-wrap gap-3 w-full">
    <!-- Izquierda (SortLeftActions): Botón circular de Reset + Grupo Dropdown (ordenar por [Select]) -->
    <div class="flex items-center gap-3">
      <!-- Botón Circular Refresh (Figma: 34x34) -->
      <button
        type="button"
        class="w-[34px] h-[34px] rounded-full border border-border-subtle hover:border-content-secondary bg-surface text-content-secondary hover:text-brand flex items-center justify-center transition-all cursor-pointer active:scale-95 shrink-0"
        title="Restablecer filtros"
        aria-label="Restablecer filtros"
        @click="emit('reset-filters')"
      >
        <BaseIcon name="refresh" size="xs" />
      </button>

      <!-- Grupo Ordenar Por (Figma: SortDropdownGroup) -->
      <div class="flex items-center gap-2">
        <label for="sort-select" class="text-xs text-content-secondary font-normal select-none">
          ordenar por
        </label>
        <div class="relative inline-flex items-center">
          <select
            id="sort-select"
            v-model="sortBy"
            aria-label="Ordenar ofertas"
            class="appearance-none bg-surface border border-border-subtle hover:border-content-secondary text-content rounded-full text-xs font-medium cursor-pointer transition-colors h-[30px] pl-3 pr-7 focus:outline-none focus:border-brand"
          >
            <option
              v-for="opt in sortOptions"
              :key="opt.value"
              :value="opt.value"
              class="bg-surface text-content py-1"
            >
              {{ opt.label }}
            </option>
          </select>
          <BaseIcon
            name="chevron-down"
            size="xs"
            class="text-content-secondary pointer-events-none absolute right-2.5"
          />
        </div>
      </div>
    </div>

    <!-- Derecha: Conteo exacto (Figma: "60 packs disponibles") -->
    <div class="text-xs text-content-secondary tabular-nums font-normal">
      <span>{{ totalOffers }} packs disponibles</span>
    </div>
  </div>
</template>
