<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Logo from '@/components/common/Logo.vue';

defineProps({
  badgeText: {
    type: String,
    default: 'CONFIGURACIÓN DE CUENTA',
  },
});

const router = useRouter();
const authStore = useAuthStore();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<template>
  <header class="sticky top-0 z-40 w-full bg-surface border-b border-border-subtle">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-6 sm:px-10 lg:px-12 h-[70px]">
      <div class="flex items-center gap-2">
        <Logo variant="imagotipo" :height="28" />
      </div>

      <div class="flex items-center gap-3.5">
        <span
          class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-brand-light/10 border border-brand-light/40 text-[11px] font-bold uppercase tracking-wider text-brand"
        >
          {{ badgeText }}
        </span>

        <!-- Header button: standard rounded-[10px] secondary button -->
        <button
          type="button"
          class="inline-flex items-center justify-center h-[40px] px-4 rounded-[10px] border border-border-subtle bg-surface hover:bg-canvas text-sm font-semibold text-content transition-colors cursor-pointer select-none"
          @click="handleLogout"
        >
          <span>Cerrar sesión</span>
        </button>
      </div>
    </nav>
  </header>
</template>

<style scoped>
</style>
