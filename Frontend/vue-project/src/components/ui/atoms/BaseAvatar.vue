<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: {
    type: String,
    default: '',
  },
  initials: {
    type: String,
    default: '',
  },
  src: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v),
  },
});

const computedInitials = computed(() => {
  if (props.initials) return props.initials.toUpperCase();
  if (!props.name || typeof props.name !== 'string') return 'TA';
  const clean = props.name.trim();
  const words = clean.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return clean.slice(0, 2).toUpperCase();
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'w-6 h-6 text-[10px]';
    case 'md':
      return 'w-10 h-10 text-sm';
    case 'lg':
      return 'w-12 h-12 text-base';
    case 'sm':
    default:
      return 'w-8 h-8 text-xs';
  }
});
</script>

<template>
  <div
    class="relative rounded-full overflow-hidden shrink-0 flex items-center justify-center select-none bg-surface border border-content text-content font-bold"
    :class="sizeClasses"
    :title="name"
  >
    <img
      v-if="src"
      :src="src"
      :alt="name || 'Avatar'"
      class="w-full h-full object-cover"
      loading="lazy"
    />
    <span v-else>{{ computedInitials }}</span>
  </div>
</template>
