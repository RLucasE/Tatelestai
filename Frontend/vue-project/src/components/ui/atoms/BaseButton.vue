<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'emerald', 'outline', 'ghost', 'danger'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg', 'icon'].includes(v),
  },
  type: {
    type: String,
    default: 'button',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
  pill: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['click']);

const handleClick = (e) => {
  if (props.disabled || props.loading) {
    e.preventDefault();
    return;
  }
  emit('click', e);
};

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-brand hover:bg-brand-hover text-surface shadow-sm shadow-brand/20 border border-transparent';
    case 'secondary':
      return 'bg-surface-sunken hover:bg-border-subtle/60 text-content-strong border border-border-subtle hover:border-brand/40';
    case 'emerald':
      return 'bg-rescue hover:bg-rescue-hover text-content-strong font-bold shadow-sm shadow-rescue/25 border border-transparent';
    case 'outline':
      return 'bg-surface hover:bg-surface-sunken text-content border border-border-subtle hover:border-brand/40';
    case 'ghost':
      return 'bg-transparent hover:bg-surface-sunken text-content-secondary hover:text-content-strong border border-transparent';
    case 'danger':
      return 'bg-danger/12 hover:bg-danger/20 text-danger border border-danger/30';
    default:
      return 'bg-brand text-surface';
  }
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'control-xs px-2.5 text-xs';
    case 'sm':
      return 'control-sm px-4 text-xs font-semibold';
    case 'md':
      return 'control-md px-5 text-sm font-semibold';
    case 'lg':
      return 'control-lg px-6 text-sm font-bold';
    case 'icon':
      return 'w-10 h-10 p-0 flex items-center justify-center';
    default:
      return 'control-md px-5 text-sm';
  }
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 select-none cursor-pointer transition-all duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
    :class="[
      variantClasses,
      sizeClasses,
      pill ? 'rounded-full' : 'rounded-[10px]',
      block ? 'w-full' : '',
    ]"
    @click="handleClick"
  >
    <!-- Spinner de Carga -->
    <svg
      v-if="loading"
      class="animate-spin w-4 h-4 text-current"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>

    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </button>
</template>
