<script setup>
import { computed } from "vue";

const props = defineProps({
  template: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["publish", "edit", "delete"]);

const allergensList = computed(() => {
  if (!props.template.allergens) return [];
  if (Array.isArray(props.template.allergens)) return props.template.allergens;
  return [];
});

const displayedAllergens = computed(() => {
  return allergensList.value.slice(0, 3);
});

const extraAllergensCount = computed(() => {
  return Math.max(0, allergensList.value.length - 3);
});

const formattedWeight = computed(() => {
  if (props.template.estimated_weight_kg == null) return null;
  return `${Number(props.template.estimated_weight_kg).toFixed(2)} kg`;
});
</script>

<template>
  <div
    class="flex flex-col justify-between rounded-2xl bg-[#2D2438] border border-white/[0.08] p-5 transition-all duration-200 hover:border-[#7C3AED]/40 hover:bg-[#322740]/90 shadow-sm"
  >
    <!-- Contenido Superior -->
    <div>
      <!-- Barra superior: Badge de plantilla y acciones discretas -->
      <div class="flex items-center justify-between gap-2">
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-[#7C3AED]/15 border border-[#7C3AED]/25 text-[#C4B5FD]"
        >
          <svg class="w-3 h-3 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
          Plantilla
        </span>

        <!-- Botones de Acción -->
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="p-1.5 rounded-lg text-[#A5A8C2] hover:text-white hover:bg-white/[0.08] transition cursor-pointer"
            title="Editar plantilla"
            @click="emit('edit', template)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
              />
            </svg>
          </button>
          <button
            type="button"
            class="p-1.5 rounded-lg text-[#A5A8C2] hover:text-[#EF4444] hover:bg-[#EF4444]/10 transition cursor-pointer"
            title="Eliminar plantilla"
            @click="emit('delete', template)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Título y Descripción -->
      <h3
        class="text-base font-bold text-white tracking-tight line-clamp-1 mt-3 mb-1.5"
        :title="template.title"
      >
        {{ template.title }}
      </h3>
      <p class="text-xs text-[#A5A8C2] line-clamp-2 leading-relaxed mb-3.5">
        {{ template.description }}
      </p>

      <!-- Fila de Metadatos (Peso y Alérgenos) -->
      <div class="flex flex-wrap items-center gap-1.5 pt-1 mb-4">
        <!-- Peso -->
        <span
          v-if="formattedWeight"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-white/[0.04] text-[#E8EAF6] border border-white/[0.06]"
        >
          <svg class="w-3 h-3 text-[#A5A8C2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
            />
          </svg>
          {{ formattedWeight }}
        </span>

        <!-- Alérgenos -->
        <template v-if="allergensList.length > 0">
          <span
            v-for="(alg, idx) in displayedAllergens"
            :key="idx"
            class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#7C3AED]/10 text-[#C4B5FD] border border-[#7C3AED]/20"
          >
            {{ alg }}
          </span>
          <span
            v-if="extraAllergensCount > 0"
            class="px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-white/[0.04] text-[#A5A8C2] border border-white/[0.06]"
            :title="allergensList.slice(3).join(', ')"
          >
            +{{ extraAllergensCount }}
          </span>
        </template>
        <span v-else class="text-[11px] text-[#787596] italic">
          Sin alérgenos declarados
        </span>
      </div>
    </div>

    <!-- Botón de Lanzamiento Rápido en 1 Clic -->
    <div class="pt-3 border-t border-white/[0.06]">
      <button
        type="button"
        class="w-full inline-flex items-center justify-center gap-2 py-2 px-3.5 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] transition-all hover:scale-[1.01] active:scale-95 shadow-sm shadow-purple-900/30 cursor-pointer"
        @click="emit('publish', template)"
      >
        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <span>Lanzar Pack Hoy</span>
      </button>
    </div>
  </div>
</template>
