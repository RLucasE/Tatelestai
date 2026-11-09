<script setup>
import { computed } from 'vue';
import BaseBadge from '../atoms/BaseBadge.vue';

const props = defineProps({
  price: {
    type: [Number, String],
    required: true,
  },
  originalPrice: {
    type: [Number, String],
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  showSavingsBadge: {
    type: Boolean,
    default: false,
  },
  unitLabel: {
    type: String,
    default: '',
  },
});

const numPrice = computed(() => Number(props.price || 0));
const numOriginal = computed(() => (props.originalPrice != null ? Number(props.originalPrice) : null));

const formattedPrice = computed(() => {
  return numPrice.value.toLocaleString('es-AR');
});

const formattedOriginal = computed(() => {
  return numOriginal.value != null ? numOriginal.value.toLocaleString('es-AR') : null;
});

const hasDiscount = computed(() => {
  return numOriginal.value != null && numOriginal.value > numPrice.value;
});

const discountPercent = computed(() => {
  if (!hasDiscount.value || numOriginal.value === 0) return 0;
  return Math.round(((numOriginal.value - numPrice.value) / numOriginal.value) * 100);
});

const priceSizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-base font-bold';
    case 'lg':
      return 'type-display';
    case 'md':
    default:
      return 'type-price-hero';
  }
});
</script>

<template>
  <div class="flex items-baseline gap-2">
    <div class="flex flex-col">
      <!-- Precio Original Tachado -->
      <span
        v-if="hasDiscount"
        class="text-xs text-content-muted line-through font-medium leading-none tabular-nums"
      >
        ${{ formattedOriginal }}
      </span>

      <!-- Precio de Rescate -->
      <div class="flex items-baseline gap-1">
        <span class="text-content-strong tabular-nums tracking-tight leading-tight" :class="priceSizeClasses">
          ${{ formattedPrice }}
        </span>
        <span v-if="unitLabel" class="text-xs text-content-secondary font-normal">
          {{ unitLabel }}
        </span>
      </div>
    </div>

    <!-- Badge de Descuento (Ahorro) -->
    <BaseBadge
      v-if="showSavingsBadge && hasDiscount"
      variant="emerald"
      size="xs"
      pill
    >
      -{{ discountPercent }}%
    </BaseBadge>
  </div>
</template>
