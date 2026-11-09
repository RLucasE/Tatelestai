<script setup>
defineProps({
  establishmentName: {
    type: String,
    default: '',
  },
  currentDate: {
    type: String,
    default: '',
  },
  refreshing: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['refresh', 'create-offer']);
</script>

<template>
  <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-border-subtle">
    <div>
      <div class="flex items-center gap-2 mb-1.5 flex-wrap">
        <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
          Operativa Diaria
        </span>
        <span
          v-if="establishmentName"
          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand/10 border border-brand/25 text-brand"
        >
          <svg class="w-3 h-3 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <span>{{ establishmentName }}</span>
        </span>
        <span v-if="currentDate" class="text-xs text-content-secondary capitalize">
          • {{ currentDate }}
        </span>
      </div>

      <h1 class="text-2xl sm:text-3xl font-extrabold text-content tracking-tight">
        Panel de Control
      </h1>
      <p class="text-xs sm:text-sm text-content-secondary mt-1">
        Supervisa las ventas del día, el inventario restante y los retiros programados en tiempo real.
      </p>
    </div>

    <!-- Acciones de Cabecera -->
    <div class="flex items-center gap-2.5 self-start sm:self-center shrink-0">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-[10px] text-xs font-semibold bg-surface hover:bg-canvas text-content border border-border-subtle transition-all hover:scale-[1.02] active:scale-95 shadow-sm disabled:opacity-50 cursor-pointer"
        :disabled="loading || refreshing"
        @click="$emit('refresh')"
      >
        <svg
          class="w-3.5 h-3.5 text-content-secondary"
          :class="{ 'animate-spin': refreshing }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
        <span>{{ refreshing ? 'Actualizando...' : 'Actualizar' }}</span>
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-[10px] text-xs font-bold bg-brand hover:bg-brand-hover text-surface shadow-xs transition-all hover:scale-[1.02] active:scale-95 cursor-pointer"
        @click="$emit('create-offer')"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Publicar Oferta</span>
      </button>
    </div>
  </header>
</template>
