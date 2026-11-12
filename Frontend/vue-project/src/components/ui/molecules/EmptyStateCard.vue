<script setup>
import BaseIcon from '../atoms/BaseIcon.vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  variant: {
    type: String,
    default: 'empty',
    validator: (v) => ['empty', 'location', 'error'].includes(v),
  },
});
</script>

<template>
  <div
    class="text-center py-12 px-6 bg-surface border rounded-modal max-w-xl mx-auto space-y-4 shadow-card"
    :class="variant === 'error' ? 'border-danger/40' : 'border-border-subtle'"
  >
    <!-- Icon Container con Lucide -->
    <div
      class="w-14 h-14 mx-auto flex items-center justify-center rounded-full"
      :class="[
        variant === 'error'
          ? 'bg-danger/15 text-danger'
          : 'bg-brand/15 text-brand-light',
      ]"
    >
      <slot name="icon">
        <BaseIcon v-if="variant === 'location'" name="pin" size="lg" />
        <BaseIcon v-else-if="variant === 'error'" name="alert" size="lg" />
        <BaseIcon v-else name="bag" size="lg" />
      </slot>
    </div>

    <!-- Content -->
    <div class="space-y-1">
      <h3 class="type-h1 text-content-strong">{{ title }}</h3>
      <p v-if="description" class="type-body-sm text-content-secondary max-w-md mx-auto">
        {{ description }}
      </p>
    </div>

    <!-- Action Slot -->
    <div v-if="$slots.actions" class="flex flex-wrap items-center justify-center gap-3 pt-2">
      <slot name="actions" />
    </div>
  </div>
</template>
