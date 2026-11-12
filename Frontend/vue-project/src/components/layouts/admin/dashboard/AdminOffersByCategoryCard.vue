<script setup>
import { computed } from 'vue';

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const barColors = [
  'bg-brand',
  'bg-brand-light',
  'bg-rescue',
  'bg-surface-darkest',
  'bg-success',
];

const totalPacks = computed(() => {
  return props.categories.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
});

const normalizedCategories = computed(() => {
  const total = totalPacks.value > 0 ? totalPacks.value : 1;
  return [...props.categories]
    .sort((a, b) => (Number(b.count) || 0) - (Number(a.count) || 0))
    .slice(0, 5)
    .map((item, idx) => {
      const count = Number(item.count) || 0;
      const pct = totalPacks.value > 0 ? Math.round((count / total) * 100) : 0;
      return {
        name: item.establishment_type || 'Otros rubros',
        count,
        pct,
        colorClass: barColors[idx % barColors.length],
      };
    });
});
</script>

<template>
  <article class="bg-surface border border-border-subtle rounded-card p-6 shadow-card flex flex-col justify-between gap-4">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-[15px] font-bold text-content-strong font-heading">
        Ofertas Activas por Rubro
      </h3>
      <span class="text-xs font-semibold text-content-secondary tabular-nums">
        {{ totalPacks.toLocaleString('es-AR') }} packs
      </span>
    </div>

    <div v-if="loading" class="space-y-3 animate-pulse">
      <div v-for="n in 4" :key="n" class="space-y-1.5">
        <div class="h-3 bg-surface-sunken rounded w-2/3"></div>
        <div class="h-1.5 bg-surface-sunken rounded w-full"></div>
      </div>
    </div>

    <div v-else-if="normalizedCategories.length === 0" class="py-4 text-xs text-content-secondary text-center">
      No hay ofertas activas registradas en los últimos 7 días.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="cat in normalizedCategories"
        :key="cat.name"
        class="space-y-1.5"
      >
        <div class="flex items-center justify-between gap-2 text-[12.5px]">
          <span class="font-semibold text-content-strong truncate">{{ cat.name }}</span>
          <span class="text-[11.5px] text-content-secondary shrink-0 tabular-nums">
            {{ cat.count }} {{ cat.count === 1 ? 'pack' : 'packs' }} ({{ cat.pct }}%)
          </span>
        </div>
        <div class="w-full h-1.5 rounded-full bg-surface-sunken overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="cat.colorClass"
            :style="{ width: `${Math.max(4, cat.pct)}%` }"
          ></div>
        </div>
      </div>
    </div>
  </article>
</template>
