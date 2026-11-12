<script setup>
defineProps({
  shape: {
    type: String,
    default: 'rect',
    validator: (v) => ['rect', 'circle', 'pill'].includes(v),
  },
});
</script>

<template>
  <div
    class="bg-surface-elevated/70 animate-pulse"
    :class="[
      shape === 'circle' ? 'rounded-full' : shape === 'pill' ? 'rounded-full' : 'rounded-md',
    ]"
  />
</template>
