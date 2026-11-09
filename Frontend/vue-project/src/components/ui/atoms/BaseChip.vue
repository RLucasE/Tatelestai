<script setup>
import BaseButton from './BaseButton.vue';

defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['sm', 'md'].includes(v),
  },
});

const emit = defineEmits(['click']);
</script>

<template>
  <BaseButton
    :variant="active ? 'primary' : 'secondary'"
    :size="size"
    :disabled="disabled"
    pill
    @click="emit('click', $event)"
  >
    <template #icon-left>
      <slot name="icon" />
    </template>
    <slot />
  </BaseButton>
</template>
