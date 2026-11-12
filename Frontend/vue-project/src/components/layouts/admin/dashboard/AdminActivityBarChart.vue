<script setup>
import { computed } from 'vue';

const props = defineProps({
  bars: {
    type: Array,
    default: () => [],
  },
  subtitle: {
    type: String,
    default: 'Actividad transaccional diaria de la plataforma (última semana)',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const maxCount = computed(() => {
  const maxVal = props.bars.reduce((acc, item) => Math.max(acc, Number(item.count) || 0), 0);
  return maxVal > 0 ? maxVal : 1;
});

const peakCount = computed(() => {
  return props.bars.reduce((acc, item) => Math.max(acc, Number(item.count) || 0), 0);
});

const getBarHeightPx = (count) => {
  const num = Number(count) || 0;
  if (num === 0) return 10;
  return Math.max(18, Math.round((num / maxCount.value) * 180));
};

const isHighlightedBar = (bar, index) => {
  const isLast = index === props.bars.length - 1;
  const isPeak = peakCount.value > 0 && Number(bar.count) === peakCount.value;
  return isLast || isPeak;
};
</script>

<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-[17px] font-bold text-content-strong font-heading leading-snug">
          Evolución de Pedidos y Bolsas Rescatadas
        </h2>
        <p class="text-[12.5px] text-content-secondary">
          {{ subtitle }}
        </p>
      </div>

      <div class="flex items-center gap-4 text-[11.5px] text-content-secondary font-medium">
        <div class="inline-flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-brand inline-block"></span>
          <span>Día pico / Hoy</span>
        </div>
        <div class="inline-flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-surface-darkest inline-block"></span>
          <span>Días previos</span>
        </div>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="bg-surface border border-border-subtle rounded-card p-6 h-[272px] flex items-end justify-between gap-4 animate-pulse">
      <div v-for="n in 7" :key="n" class="flex-1 flex flex-col items-center gap-2">
        <div class="w-11 bg-surface-sunken rounded-t-lg" :style="{ height: `${40 + n * 18}px` }"></div>
        <div class="w-10 h-3 bg-surface-sunken rounded"></div>
      </div>
    </div>

    <!-- Bar Plot -->
    <div
      v-else
      class="bg-surface border border-border-subtle rounded-card px-5 sm:px-7 py-6 shadow-card"
    >
      <div v-if="bars.length === 0" class="h-[220px] flex items-center justify-center text-xs text-content-secondary">
        Sin actividad registrada en el período seleccionado.
      </div>
      <div v-else class="grid gap-3 sm:gap-5 items-end min-h-[224px]" :style="{ gridTemplateColumns: `repeat(${bars.length}, minmax(0, 1fr))` }">
        <div
          v-for="(bar, idx) in bars"
          :key="bar.key || bar.label"
          class="flex flex-col items-center justify-end gap-2 group"
        >
          <span
            class="text-xs font-bold tabular-nums font-heading transition-colors"
            :class="isHighlightedBar(bar, idx) ? 'text-brand' : 'text-content-strong'"
          >
            {{ bar.count }}
          </span>
          <div
            class="w-full max-w-[44px] rounded-t-[10px] transition-all duration-300 group-hover:opacity-90"
            :class="
              Number(bar.count) === 0
                ? 'bg-surface-sunken'
                : isHighlightedBar(bar, idx)
                  ? 'bg-brand'
                  : 'bg-surface-darkest'
            "
            :style="{ height: `${getBarHeightPx(bar.count)}px` }"
            :title="`${bar.label}: ${bar.count} pedidos (${bar.packs || 0} packs)`"
          ></div>
          <div class="text-center leading-tight pt-1">
            <span class="block text-[11.5px] font-semibold text-content-strong truncate">
              {{ bar.label }}
            </span>
            <span class="block text-[10.5px] text-content-secondary tabular-nums">
              {{ bar.count }} ped.
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
