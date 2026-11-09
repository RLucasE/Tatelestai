<script setup>
import { computed } from 'vue';
import BaseIcon from '../atoms/BaseIcon.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'success',
    validator: (v) => ['success', 'error', 'info'].includes(v),
  },
});

const emit = defineEmits(['close']);

const typeClasses = computed(() => {
  switch (props.type) {
    case 'error':
      return 'bg-danger/90 border-danger text-surface shadow-danger/20';
    case 'info':
      return 'bg-info/90 border-info text-surface shadow-info/20';
    case 'success':
    default:
      return 'bg-rescue/90 border-rescue text-surface shadow-rescue/20';
  }
});
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform translate-y-4 opacity-0 scale-95"
    enter-to-class="transform translate-y-0 opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-y-0 opacity-100 scale-100"
    leave-to-class="transform translate-y-4 opacity-0 scale-95"
  >
    <div
      v-if="show"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-full shadow-xl border backdrop-blur-md max-w-sm"
      :class="typeClasses"
    >
      <span class="shrink-0 flex items-center justify-center">
        <BaseIcon v-if="type === 'error'" name="alert" size="sm" />
        <BaseIcon v-else-if="type === 'info'" name="info" size="sm" />
        <BaseIcon v-else name="check" size="sm" />
      </span>

      <p class="text-xs font-semibold flex-1 leading-snug">{{ message }}</p>

      <button
        type="button"
        class="text-surface/70 hover:text-surface p-1 rounded-full hover:bg-surface/10 transition-colors cursor-pointer flex items-center justify-center"
        aria-label="Cerrar notificación"
        @click="emit('close')"
      >
        <BaseIcon name="close" size="xs" />
      </button>
    </div>
  </Transition>
</template>
