<script setup>
import { computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  template: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['publish', 'edit', 'delete']);

const { formatCurrency } = useFormatters();

const allergensText = computed(() => {
  const list = Array.isArray(props.template.allergens) ? props.template.allergens : [];
  if (list.length === 0) return 'Sin alérgenos declarados';
  return `Alérgenos declarados: ${list.join(' • ')}`;
});

const metaText = computed(() => {
  const parts = [];
  if (props.template.estimated_weight_kg != null) {
    parts.push(`Peso estimado: ${Number(props.template.estimated_weight_kg).toFixed(1)} kg`);
  }
  const count = props.template.offers_count ?? props.template.published_count;
  if (count != null) {
    parts.push(`${count} lotes publicados con esta plantilla`);
  } else {
    parts.push('Plantilla reutilizable en 1 clic');
  }
  return parts.join(' • ');
});

const suggestedPriceText = computed(() => {
  const price = props.template.suggested_price ?? props.template.price;
  if (price != null && Number(price) > 0) {
    return `$${formatCurrency(price)} sugerido`;
  }
  return '1 Clic';
});
</script>

<template>
  <div
    class="rounded-[18px] bg-surface border border-border-subtle px-6 py-[22px] shadow-card flex flex-col gap-4 transition-all hover:border-brand/40"
  >
    <!-- TemplateHeaderRow -->
    <div class="flex items-start justify-between gap-3">
      <div class="flex flex-col gap-0.5 min-w-0">
        <h3
          class="text-[16px] font-bold text-content truncate"
          :title="template.title"
        >
          {{ template.title }}
        </h3>
        <span class="text-[12px] font-medium text-content-secondary">
          {{ metaText }}
        </span>
      </div>

      <span class="text-[13.5px] font-bold text-brand shrink-0 tabular-nums">
        {{ suggestedPriceText }}
      </span>
    </div>

    <!-- TemplateBodyCol -->
    <div class="flex flex-col gap-1.5">
      <p class="text-[13.5px] text-content leading-relaxed line-clamp-2">
        {{ template.description }}
      </p>
      <p class="text-[12px] font-semibold text-content-secondary">
        {{ allergensText }}
      </p>
    </div>

    <!-- TemplateActionsRow -->
    <div class="flex items-center justify-between gap-3 pt-0.5 flex-wrap">
      <div class="flex items-center gap-[18px]">
        <button
          type="button"
          class="text-[12.5px] font-semibold text-content-secondary hover:text-content transition-colors cursor-pointer"
          @click="emit('edit', template)"
        >
          Editar plantilla
        </button>
        <button
          type="button"
          class="text-[12.5px] font-semibold text-danger hover:underline transition-colors cursor-pointer"
          @click="emit('delete', template)"
        >
          Eliminar
        </button>
      </div>

      <button
        type="button"
        class="inline-flex items-center justify-center px-[18px] py-2 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[12.5px] font-bold transition-all active:scale-95 cursor-pointer"
        @click="emit('publish', template)"
      >
        Publicar en 1 Clic →
      </button>
    </div>
  </div>
</template>
