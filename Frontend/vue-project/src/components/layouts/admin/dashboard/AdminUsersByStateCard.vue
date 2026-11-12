<script setup>
import { computed } from 'vue';

const props = defineProps({
  total: {
    type: Number,
    default: 0,
  },
  states: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const stateMap = computed(() => {
  const map = {};
  props.states.forEach((item) => {
    map[item.state] = Number(item.count) || 0;
  });
  return map;
});

const rows = computed(() => {
  const totalSafe = props.total > 0 ? props.total : 1;
  const defs = [
    { key: 'active', label: 'Activos', dotClass: 'bg-success' },
    { key: 'waiting_for_confirmation', label: 'Esperando aprobación', dotClass: 'bg-warning' },
    { key: 'inactive', label: 'Inactivos / Suspendidos', dotClass: 'bg-content-secondary' },
    { key: 'denied', label: 'Solicitudes denegadas', dotClass: 'bg-danger' },
  ];

  return defs.map((def) => {
    const count = stateMap.value[def.key] || 0;
    const pct = props.total > 0 ? ((count / totalSafe) * 100).toFixed(1) : '0.0';
    return {
      ...def,
      count,
      pct,
    };
  });
});
</script>

<template>
  <article class="bg-surface border border-border-subtle rounded-card p-6 shadow-card flex flex-col justify-between gap-4">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-[15px] font-bold text-content-strong font-heading">
        Usuarios por Estado
      </h3>
      <span class="text-xs font-semibold text-content-secondary tabular-nums">
        {{ total.toLocaleString('es-AR') }} total
      </span>
    </div>

    <div v-if="loading" class="space-y-3 animate-pulse">
      <div v-for="n in 4" :key="n" class="h-4 bg-surface-sunken rounded"></div>
    </div>

    <ul v-else class="space-y-3">
      <li
        v-for="row in rows"
        :key="row.key"
        class="flex items-center justify-between gap-3 text-[13px]"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="row.dotClass"></span>
          <span class="text-content-strong font-medium truncate">{{ row.label }}</span>
        </div>
        <div class="flex items-center gap-3 shrink-0 tabular-nums">
          <span class="font-bold text-content-strong">{{ row.count.toLocaleString('es-AR') }}</span>
          <span class="text-xs text-content-secondary w-11 text-right">{{ row.pct }}%</span>
        </div>
      </li>
    </ul>
  </article>
</template>
