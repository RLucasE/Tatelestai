<script setup>
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();

const getBorderColor = (type) => {
  switch (type) {
    case 'success':
      return 'border-[#10B981]/60 bg-[#10B981]/10 text-[#34D399]';
    case 'error':
      return 'border-[#EF4444]/60 bg-[#EF4444]/10 text-[#F87171]';
    case 'warning':
      return 'border-[#F59E0B]/60 bg-[#F59E0B]/10 text-[#FBBF24]';
    default:
      return 'border-[#7C3AED]/60 bg-[#7C3AED]/10 text-[#A78BFA]';
  }
};
</script>

<template>
  <div
    class="fixed z-50 bottom-4 right-4 sm:top-20 sm:bottom-auto flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
  >
    <TransitionGroup
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-for="toast in notificationStore.toasts"
        :key="toast.id"
        class="pointer-events-auto p-4 rounded-2xl bg-[#2D2438] border shadow-2xl backdrop-blur-md flex items-start gap-3 transition-all"
        :class="getBorderColor(toast.type)"
      >
        <!-- Icono según tipo -->
        <div class="shrink-0 mt-0.5">
          <svg
            v-if="toast.type === 'success'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg
            v-else-if="toast.type === 'error'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <svg
            v-else-if="toast.type === 'warning'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg
            v-else
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <!-- Contenido del Toast -->
        <div class="flex-1 min-w-0">
          <h4 class="text-xs font-bold text-white tracking-wide">{{ toast.title }}</h4>
          <p class="text-xs text-[#E8EAF6]/90 mt-0.5 leading-relaxed break-words">{{ toast.message }}</p>
          <RouterLink
            v-if="toast.actionUrl"
            :to="toast.actionUrl"
            class="inline-block mt-2 text-[11px] font-bold text-[#A78BFA] hover:text-white transition-colors underline"
            @click="notificationStore.removeToast(toast.id)"
          >
            Ver detalles →
          </RouterLink>
        </div>

        <!-- Botón de Cerrar -->
        <button
          type="button"
          class="shrink-0 p-1 text-[#A5A8C2] hover:text-white rounded-lg hover:bg-white/[0.06] transition-colors cursor-pointer"
          @click="notificationStore.removeToast(toast.id)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
