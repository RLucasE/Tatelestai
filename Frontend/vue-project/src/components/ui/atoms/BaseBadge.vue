<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['brand', 'emerald', 'warning', 'danger', 'neutral', 'outline'].includes(v),
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md'].includes(v),
  },
  dot: {
    type: Boolean,
    default: false,
  },
  dotColor: {
    type: String,
    default: null,
  },
  pulse: {
    type: Boolean,
    default: false,
  },
  pill: {
    type: Boolean,
    default: true,
  },
});

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'brand':
      return 'bg-brand/14 border-brand/30 text-brand';
    case 'emerald':
      return 'bg-success/15 border-success/30 text-success';
    case 'warning':
      return 'bg-warning/15 border-warning/35 text-brand';
    case 'danger':
      return 'bg-danger/15 border-danger/30 text-danger';
    case 'outline':
      return 'bg-surface border-border-subtle text-content';
    case 'neutral':
    default:
      return 'bg-surface-sunken border-border-subtle text-content-secondary';
  }
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'px-1.5 py-0.5 text-[11px] gap-1';
    case 'md':
      return 'px-2.5 py-1 text-xs font-bold gap-1.5';
    case 'sm':
    default:
      return 'px-2 py-0.5 text-xs font-semibold gap-1.5';
  }
});

const dotColorClass = computed(() => {
  if (props.dotColor) return props.dotColor;
  switch (props.variant) {
    case 'brand':
      return 'bg-brand';
    case 'emerald':
      return 'bg-success';
    case 'warning':
      return 'bg-warning';
    case 'danger':
      return 'bg-danger';
    default:
      return 'bg-content-muted';
  }
});
</script>

<template>
  <span
    class="inline-flex items-center border select-none tabular-nums transition-colors"
    :class="[
      variantClasses,
      sizeClasses,
      pill ? 'rounded-full' : 'rounded-md',
      pulse ? 'animate-pulse' : '',
    ]"
  >
    <span
      v-if="dot"
      class="w-1.5 h-1.5 rounded-full shrink-0"
      :class="dotColorClass"
    />
    <slot />
  </span>
</template>
