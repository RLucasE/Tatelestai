<script setup>
const model = defineModel({
  type: String,
  required: true,
});

defineProps({
  options: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <div class="inline-flex items-center bg-surface-sunken border border-border-subtle p-1 rounded-full text-xs font-medium shrink-0 control-sm">
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      class="px-3 h-full rounded-full transition-all duration-150 flex items-center gap-1.5 cursor-pointer select-none active:scale-95"
      :class="[
        model === opt.value
          ? 'bg-brand text-surface font-semibold shadow-xs'
          : 'text-content-secondary hover:text-content',
      ]"
      @click="model = opt.value"
    >
      <!-- Ícono opcional -->
      <slot :name="`icon-${opt.value}`" />
      <span>{{ opt.label }}</span>
    </button>
  </div>
</template>
