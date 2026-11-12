<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Logo from '@/components/common/Logo.vue';
import NotificationBell from '@/components/common/NotificationBell.vue';
import BaseIcon from '@/components/ui/atoms/BaseIcon.vue';
import BaseAvatar from '@/components/ui/atoms/BaseAvatar.vue';

const authStore = useAuthStore();
const router = useRouter();

const user = computed(() => authStore.getUser());
const userName = computed(() => user.value?.name || user.value?.email || 'Usuario');
const userInitials = computed(() => {
  const name = userName.value;
  if (!name) return 'US';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
});

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

const navLinks = [
  {
    name: 'offers',
    label: 'ofertas',
    to: '/customer/offers',
    icon: 'compass',
  },
  {
    name: 'purchases',
    label: 'compras',
    to: '/customer/purchases',
    icon: 'shopping-bag',
  },
  {
    name: 'cart',
    label: 'carrito',
    to: '/customer/carrito',
    icon: 'shopping-cart',
  },
];
</script>

<template>
  <header class="sticky top-0 z-40 w-full bg-surface border-b border-border-subtle">
    <nav class="max-w-[1440px] mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-10 h-[70px]">
      <!-- 1. Logo de Tatelestai a la izquierda (Alineado con el contenido a 40px) -->
      <RouterLink
        to="/customer/offers"
        class="flex items-center gap-2 group focus:outline-none transition-transform active:scale-95 shrink-0"
        aria-label="Ir al inicio de ofertas"
      >
        <Logo size="md" />
      </RouterLink>

      <!-- 2. Navegación Principal y Perfil a la derecha (Figma: NavActions) -->
      <div class="flex items-center gap-2 sm:gap-3.5">
        <!-- Enlaces directos: ofertas, compras, carrito (Pill buttons con h=32px) -->
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="flex items-center gap-1.5 px-3.5 sm:px-4 h-8 rounded-full text-[13px] font-medium transition-all select-none active:scale-95 text-content hover:bg-canvas hover:text-brand [&.router-link-active]:text-surface [&.router-link-active]:bg-brand [&.router-link-active]:font-semibold"
        >
          <BaseIcon :name="link.icon" size="sm" class="text-current" />
          <span class="capitalize">{{ link.label }}</span>
        </RouterLink>

        <!-- Campanita de Notificaciones -->
        <div class="flex items-center">
          <NotificationBell />
        </div>

        <!-- Avatar del Usuario con Dropdown (Figma: UserAvatar 32x32 border 1px content) -->
        <div ref="menuRef" class="relative">
          <button
            type="button"
            class="w-8 h-8 rounded-full border border-content bg-surface text-content font-bold text-xs flex items-center justify-center transition-all cursor-pointer hover:border-brand hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand/40 active:scale-95"
            :class="{ 'border-brand ring-2 ring-brand/30': isMenuOpen }"
            aria-label="Abrir menú de usuario"
            :aria-expanded="isMenuOpen"
            @click="toggleMenu"
          >
            <span>{{ userInitials }}</span>
          </button>

          <!-- Dropdown Flotante -->
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
              class="absolute right-0 mt-2 w-56 rounded-[18px] bg-surface border border-border-subtle shadow-card py-2 z-50 text-content overflow-hidden"
            >
              <!-- Info del usuario logueado -->
              <div class="px-4 py-2.5 border-b border-border-subtle bg-canvas">
                <p class="text-xs font-bold text-content truncate">{{ userName }}</p>
                <p v-if="user?.email" class="text-[11px] text-content-secondary truncate mt-0.5">{{ user.email }}</p>
              </div>

              <!-- Enlaces secundarios del usuario -->
              <div class="py-1">
                <RouterLink
                  to="/customer/history"
                  class="flex items-center gap-2.5 px-4 py-2 text-sm text-content-secondary hover:text-content hover:bg-canvas transition-colors"
                  @click="isMenuOpen = false"
                >
                  <BaseIcon name="history" size="sm" />
                  <span>Historial</span>
                </RouterLink>

                <RouterLink
                  to="/customer/profile"
                  class="flex items-center gap-2.5 px-4 py-2 text-sm text-content-secondary hover:text-content hover:bg-canvas transition-colors"
                  @click="isMenuOpen = false"
                >
                  <BaseIcon name="user" size="sm" />
                  <span>Mi perfil</span>
                </RouterLink>
              </div>

              <!-- Separador y Botón Cerrar Sesión -->
              <div class="border-t border-border-subtle pt-1 mt-1">
                <button
                  type="button"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-brand hover:bg-brand/10 transition-colors text-left cursor-pointer font-medium"
                  @click="handleLogout"
                >
                  <BaseIcon name="log-out" size="sm" />
                  <span>Cerrar Sesión</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </nav>
  </header>
</template>
