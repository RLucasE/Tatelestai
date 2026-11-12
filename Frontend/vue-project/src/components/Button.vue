<script setup>
import BaseButton from '@/components/ui/atoms/BaseButton.vue';

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
  buttonType: {
    type: String,
    default: null,
  },
  buttonText: {
    type: String,
    default: '',
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
</script>

<template>
  <BaseButton
    :variant="variant"
    :size="size"
    :type="buttonType || type"
    :loading="loading"
    :disabled="disabled"
    :block="block"
    :pill="pill"
    @click="emit('click', $event)"
  >
    <template #icon-left>
      <slot name="icon-left" />
    </template>

    <slot>
      {{ buttonText }}
    </slot>

    <template #icon-right>
      <slot name="icon-right" />
    </template>
  </BaseButton>
</template>
