<script setup>
import BaseIcon from './BaseIcon.vue';

const model = defineModel();

defineProps({
  options: {
    type: Array,
    required: true,
  },
  id: {
    type: String,
    default: null,
  },
  ariaLabel: {
    type: String,
    default: 'Seleccionar opción',
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md'].includes(v),
  },
});
</script>

<template>
  <div class="relative inline-flex items-center">
    <select
      :id="id"
      v-model="model"
      :aria-label="ariaLabel"
      class="appearance-none bg-surface-sunken border border-border-subtle text-content rounded-full text-xs font-medium cursor-pointer transition-colors hover:border-brand-light/40 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand"
      :class="{
        'h-[30px] pl-3 pr-7': size === 'xs',
        'control-sm pl-3.5 pr-8': size === 'sm',
        'control-md pl-4 pr-9': size === 'md',
      }"
    >
      <option
        v-for="opt in options"
        :key="opt.value"
        :value="opt.value"
        :disabled="opt.disabled"
        class="bg-surface text-content py-1"
      >
        {{ opt.label }}
      </option>
    </select>
    <!-- Chevron Icon (Lucide) -->
    <BaseIcon
      name="chevron-down"
      size="xs"
      class="text-content-secondary pointer-events-none absolute right-3"
    />
  </div>
</template>
