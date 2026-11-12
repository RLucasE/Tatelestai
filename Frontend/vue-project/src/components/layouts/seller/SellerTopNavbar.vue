<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Logo from '@/components/common/Logo.vue';
import NotificationBell from '@/components/common/NotificationBell.vue';
import BaseAvatar from '@/components/ui/atoms/BaseAvatar.vue';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const user = computed(() => authStore.getUser());
const userName = computed(() => user.value?.name || user.value?.email || 'Vendedor');
const establishmentName = computed(
  () => user.value?.establishment?.name || 'Comercio Asociado'
);

const isMenuOpen = ref(false);
const menuRef = ref(null);

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = (e) => {
  if (menuRef.value && !menuRef.value.contains(e.target)) {
    isMenuOpen.value = false;
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && isMenuOpen.value) {
    isMenuOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeMenu);
  document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeMenu);
  document.removeEventListener('keydown', handleKeyDown);
});

const handleLogout = async () => {
  isMenuOpen.value = false;
  await authStore.logout();
  router.push('/login');
};

const navModules = [
  {
    key: 'panel',
    label: 'Panel',
    to: '/seller/dashboard',
    matchPaths: ['/seller/dashboard', '/seller/verify', '/seller/verify-code'],
  },
  {
    key: 'packs',
    label: 'Packs',
    to: '/seller/templates',
    matchPaths: ['/seller/templates', '/seller/offers', '/seller/my-offers', '/seller/create-offer', '/seller/edit-offer'],
  },
  {
    key: 'ventas',
    label: 'Ventas',
    to: '/seller/sells',
    matchPaths: ['/seller/sells', '/seller/ventas'],
  },
  {
    key: 'comercio',
    label: 'Mi Comercio',
    to: '/seller/establishment',
    matchPaths: ['/seller/establishment'],
  },
];

const isModuleActive = (mod) => {
  const path = route.path;
  if (mod.key === 'panel' && (path === '/seller' || path === '/seller/')) {
    return true;
  }
  return mod.matchPaths.some((prefix) => path.startsWith(prefix));
};
</script>

<template>
  <header class="sticky top-0 z-40 w-full bg-surface border-b border-border-subtle">
    <nav class="seller-content-container w-full max-w-[1420px] mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 h-[70px]">
      <!-- Left: Brand Logo -->
      <RouterLink
        to="/seller/dashboard"
        class="flex items-center gap-2 focus:outline-none transition-transform active:scale-95"
        aria-label="Ir al panel del vendedor"
      >
        <Logo variant="imagotipo" size="medium" />
      </RouterLink>

      <!-- Right: Module Switcher + Notifications + User Avatar -->
      <div class="flex items-center gap-1.5 sm:gap-3">
        <!-- Module Switcher Pills -->
        <div class="flex items-center gap-1 sm:gap-2">
          <RouterLink
            v-for="mod in navModules"
            :key="mod.key"
            :to="mod.to"
            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-full text-[13px] transition-all duration-150 select-none active:scale-95"
            :class="
              isModuleActive(mod)
                ? 'bg-brand text-surface font-semibold shadow-xs'
                : 'text-content font-normal hover:bg-canvas'
            "
          >
            <!-- Panel Icon -->
            <svg
              v-if="mod.key === 'panel'"
              class="w-4 h-4 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
              />
            </svg>

            <!-- Packs Icon -->
            <svg
              v-else-if="mod.key === 'packs'"
              class="w-4 h-4 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
              />
            </svg>

            <!-- Ventas Icon -->
            <svg
              v-else-if="mod.key === 'ventas'"
              class="w-4 h-4 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>

            <!-- Mi Comercio Icon -->
            <svg
              v-else-if="mod.key === 'comercio'"
              class="w-4 h-4 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
              />
            </svg>

            <span>{{ mod.label }}</span>
          </RouterLink>
        </div>

        <!-- Notification Bell -->
        <NotificationBell />

        <!-- User Avatar Dropdown -->
        <div ref="menuRef" class="relative">
          <button
            type="button"
            class="flex items-center justify-center rounded-full p-0.5 transition-all cursor-pointer focus:outline-none active:scale-95"
            aria-label="Abrir menú de cuenta"
            :aria-expanded="isMenuOpen"
            @click="toggleMenu"
          >
            <BaseAvatar :name="establishmentName || userName" size="sm" />
          </button>

          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0 translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 translate-y-1"
          >
            <div
              v-if="isMenuOpen"
              class="absolute right-0 mt-2 w-60 rounded-[18px] bg-surface border border-border-subtle shadow-card py-2 z-50 text-content overflow-hidden"
            >
              <div class="px-4 py-2.5 border-b border-border-subtle bg-canvas">
                <p class="text-xs font-bold text-content truncate">{{ establishmentName }}</p>
                <p class="text-[11px] text-content-secondary truncate mt-0.5">{{ user?.email || userName }}</p>
              </div>

              <div class="py-1">
                <RouterLink
                  to="/seller/establishment"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-content hover:bg-canvas transition-colors"
                  @click="isMenuOpen = false"
                >
                  <span>Configuración de Mi Comercio</span>
                </RouterLink>
                <RouterLink
                  to="/seller/templates"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-content hover:bg-canvas transition-colors"
                  @click="isMenuOpen = false"
                >
                  <span>Plantillas y Bolsas</span>
                </RouterLink>
              </div>

              <div class="border-t border-border-subtle pt-1 mt-1">
                <button
                  type="button"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-danger hover:bg-danger/10 transition-colors text-left cursor-pointer"
                  @click="handleLogout"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  <span>Cerrar sesión</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </nav>
  </header>
</template>
