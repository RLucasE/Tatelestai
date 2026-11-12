<script setup>
import { shallowRef, ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';
import Logo from '@/components/common/Logo.vue';
import NotificationBell from '@/components/common/NotificationBell.vue';
import RealtimeToastContainer from '@/components/common/RealtimeToastContainer.vue';
import { useAuthStore } from '@/stores/auth.js';
import { useNotificationStore } from '@/stores/notifications';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const isMobileMenuOpen = shallowRef(false);
const isManagementOpen = shallowRef(false);
const isProfileOpen = shallowRef(false);
const managementRef = ref(null);
const profileRef = ref(null);

const pendingRequestsCount = shallowRef(0);
const openReportsCount = shallowRef(0);
const openClaimsCount = shallowRef(0);

const currentUser = computed(() => authStore.getUser() || {});

const adminInitials = computed(() => {
  const name = (currentUser.value.name || '').trim();
  const last = (currentUser.value.last_name || '').trim();
  if (name && last) return `${name[0]}${last[0]}`.toUpperCase();
  if (name.length >= 2) return name.slice(0, 2).toUpperCase();
  return 'AD';
});

const isRouteActive = (names) => names.includes(route.name);

const fetchHeaderBadges = async () => {
  try {
    const [estRes, repRes, claimsRes] = await Promise.allSettled([
      axiosInstance.get('/adm/pending-establishments', { params: { status: 'pending', per_page: 1 } }),
      axiosInstance.get('/adm/reports', { params: { status: 'pending', per_page: 1 } }),
      axiosInstance.get('/adm/claims', { params: { status: 'pending', per_page: 1 } }),
    ]);

    if (estRes.status === 'fulfilled') {
      const stats = estRes.value.data?.stats;
      pendingRequestsCount.value = Number(stats?.pending ?? estRes.value.data?.total ?? 0);
    }
    if (repRes.status === 'fulfilled') {
      openReportsCount.value = Number(repRes.value.data?.total ?? 0);
    }
    if (claimsRes.status === 'fulfilled') {
      const claimsData = claimsRes.value.data;
      openClaimsCount.value = Number(
        claimsData?.counts?.pending ??
        claimsData?.meta?.total ??
        (Array.isArray(claimsData?.data) ? claimsData.data.length : 0)
      );
    }
  } catch (err) {
    console.warn('No se pudieron cargar los contadores de barra superior:', err);
  }
};

const handleClickOutside = (event) => {
  if (managementRef.value && !managementRef.value.contains(event.target)) {
    isManagementOpen.value = false;
  }
  if (profileRef.value && !profileRef.value.contains(event.target)) {
    isProfileOpen.value = false;
  }
};

const handleLogout = async () => {
  try {
    await authStore.logout();
    isMobileMenuOpen.value = false;
    isProfileOpen.value = false;
    router.push('/login');
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
  }
};

onMounted(() => {
  const user = authStore.getUser();
  if (user?.id) {
    notificationStore.fetchNotifications();
    notificationStore.connectRealtime(user.id);
  }
  fetchHeaderBadges();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  notificationStore.disconnect();
  document.removeEventListener('click', handleClickOutside);
});

const managementLinks = [
  { to: '/adm/users', label: 'Usuarios', routeName: 'admin-users' },
  { to: '/adm/sells', label: 'Ventas', routeName: 'admin-sells' },
  { to: '/adm/offers', label: 'Ofertas', routeName: 'admin-offers' },
  { to: '/adm/pending-offers', label: 'Verificar Ofertas', routeName: 'admin-pending-offers' },
  { to: '/adm/establishment-types', label: 'Categorías', routeName: 'admin-establishment-types' },
];
</script>

<template>
  <div class="min-h-screen bg-canvas text-content flex flex-col">
    <!-- Top Navbar (Figma AdminTopNavbar: 70px, blanco, limpio) -->
    <header class="sticky top-0 z-40 h-[70px] bg-surface border-b border-border-subtle">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 h-full flex items-center justify-between gap-4">
        <!-- Izquierda: Logo + Badge ADMIN -->
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="lg:hidden p-2 rounded-lg border border-border-subtle text-content-strong hover:bg-surface-sunken transition-colors cursor-pointer"
            :aria-expanded="String(isMobileMenuOpen)"
            aria-label="Abrir menú"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                v-if="!isMobileMenuOpen"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>

          <RouterLink to="/adm/dashboard" class="flex items-center gap-3 no-underline">
            <Logo />
            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full bg-surface-sunken text-content-strong text-[11px] font-bold tracking-wider uppercase font-heading">
              ADMIN
            </span>
          </RouterLink>
        </div>

        <!-- Derecha: Navegación Principal + Notificaciones + Avatar -->
        <div class="flex items-center gap-2 sm:gap-3">
          <nav class="hidden lg:flex items-center gap-1.5">
            <RouterLink
              to="/adm/dashboard"
              class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[13px] font-heading font-semibold transition-all duration-150"
              :class="
                isRouteActive(['admin-dashboard'])
                  ? 'bg-brand text-surface shadow-xs'
                  : 'text-content-strong hover:bg-surface-sunken'
              "
            >
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <span>Resumen</span>
            </RouterLink>

            <RouterLink
              to="/adm/new-sellers"
              class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[13px] font-heading font-semibold transition-all duration-150"
              :class="
                isRouteActive(['new-sellers', 'new-seller'])
                  ? 'bg-brand text-surface shadow-xs'
                  : 'text-content-strong hover:bg-surface-sunken'
              "
            >
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l1.664-3.328A1 1 0 015.558 3h12.884a1 1 0 01.894.672L21 7M3 7h18M5 7v11a2 2 0 002 2h10a2 2 0 002-2V7m-9 4h4" />
              </svg>
              <span>Solicitudes</span>
              <span
                v-if="pendingRequestsCount > 0"
                class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold leading-none"
                :class="
                  isRouteActive(['new-sellers', 'new-seller'])
                    ? 'bg-surface text-brand'
                    : 'bg-brand/15 text-brand'
                "
              >
                {{ pendingRequestsCount }}
              </span>
            </RouterLink>

            <RouterLink
              to="/adm/reports"
              class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[13px] font-heading font-semibold transition-all duration-150"
              :class="
                isRouteActive(['admin-reports'])
                  ? 'bg-brand text-surface shadow-xs'
                  : 'text-content-strong hover:bg-surface-sunken'
              "
            >
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
              </svg>
              <span>Reportes</span>
              <span
                v-if="openReportsCount > 0"
                class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold leading-none"
                :class="
                  isRouteActive(['admin-reports'])
                    ? 'bg-surface text-brand'
                    : 'bg-brand/15 text-brand'
                "
              >
                {{ openReportsCount }}
              </span>
            </RouterLink>

            <RouterLink
              to="/adm/claims"
              class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[13px] font-heading font-semibold transition-all duration-150"
              :class="
                isRouteActive(['admin-claims'])
                  ? 'bg-brand text-surface shadow-xs'
                  : 'text-content-strong hover:bg-surface-sunken'
              "
            >
              <span>Reclamos</span>
              <span
                v-if="openClaimsCount > 0"
                class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold leading-none"
                :class="
                  isRouteActive(['admin-claims'])
                    ? 'bg-surface text-brand'
                    : 'bg-warning/15 text-warning'
                "
              >
                {{ openClaimsCount }}
              </span>
            </RouterLink>

            <!-- Dropdown Gestión Complementaria -->
            <div ref="managementRef" class="relative">
              <button
                type="button"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[13px] font-heading font-semibold transition-colors cursor-pointer"
                :class="
                  isRouteActive(['admin-users', 'admin-user-detail', 'admin-sells', 'adm-seller-sells', 'adm-customer-purchases', 'admin-offers', 'admin-pending-offers', 'admin-seller-offers', 'admin-establishment-types'])
                    ? 'bg-surface-sunken text-brand'
                    : 'text-content-secondary hover:text-content-strong hover:bg-surface-sunken'
                "
                @click.stop="isManagementOpen = !isManagementOpen"
              >
                <span>Más</span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': isManagementOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div
                v-if="isManagementOpen"
                class="absolute right-0 mt-2 w-48 bg-surface border border-border-subtle rounded-card shadow-card py-1.5 z-50"
              >
                <RouterLink
                  v-for="item in managementLinks"
                  :key="item.to"
                  :to="item.to"
                  class="block px-4 py-2 text-xs font-semibold text-content-strong hover:bg-surface-sunken hover:text-brand transition-colors"
                  @click="isManagementOpen = false"
                >
                  {{ item.label }}
                </RouterLink>
              </div>
            </div>
          </nav>

          <NotificationBell />

          <!-- Avatar Admin -->
          <div ref="profileRef" class="relative">
            <button
              type="button"
              class="w-9 h-9 rounded-full bg-surface-darkest text-surface text-xs font-bold font-heading flex items-center justify-center shadow-2xs hover:opacity-90 transition cursor-pointer"
              aria-label="Menú de administrador"
              @click.stop="isProfileOpen = !isProfileOpen"
            >
              {{ adminInitials }}
            </button>

            <div
              v-if="isProfileOpen"
              class="absolute right-0 mt-2 w-56 bg-surface border border-border-subtle rounded-card shadow-card p-3 z-50 space-y-2.5"
            >
              <div class="border-b border-border-subtle pb-2">
                <p class="text-xs font-bold text-content-strong truncate">
                  {{ currentUser.name || 'Administrador' }} {{ currentUser.last_name || '' }}
                </p>
                <p class="text-[11px] text-content-secondary truncate">
                  {{ currentUser.email || 'admin@tatelestai.com' }}
                </p>
              </div>
              <button
                type="button"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-danger hover:bg-danger/10 transition-colors cursor-pointer"
                @click="handleLogout"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Cerrar sesión</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Menú móvil -->
    <div
      v-if="isMobileMenuOpen"
      class="lg:hidden fixed inset-0 top-[70px] z-30 bg-surface-darkest/40 backdrop-blur-[1px]"
      @click.self="isMobileMenuOpen = false"
    >
      <aside class="w-72 max-w-[85vw] h-full bg-surface border-r border-border-subtle p-4 space-y-2 overflow-y-auto">
        <p class="text-[11px] font-bold uppercase tracking-wider text-content-secondary px-3 pt-1">
          Navegación Admin
        </p>
        <RouterLink
          to="/adm/dashboard"
          class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-content-strong hover:bg-surface-sunken"
          @click="isMobileMenuOpen = false"
        >
          <span>Resumen Operativo</span>
        </RouterLink>
        <RouterLink
          to="/adm/new-sellers"
          class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-content-strong hover:bg-surface-sunken"
          @click="isMobileMenuOpen = false"
        >
          <span>Solicitudes</span>
          <span v-if="pendingRequestsCount > 0" class="px-2 py-0.5 rounded-full bg-brand/15 text-brand text-xs font-bold">
            {{ pendingRequestsCount }}
          </span>
        </RouterLink>
        <RouterLink
          to="/adm/reports"
          class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-content-strong hover:bg-surface-sunken"
          @click="isMobileMenuOpen = false"
        >
          <span>Reportes</span>
          <span v-if="openReportsCount > 0" class="px-2 py-0.5 rounded-full bg-brand/15 text-brand text-xs font-bold">
            {{ openReportsCount }}
          </span>
        </RouterLink>
        <RouterLink
          to="/adm/claims"
          class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-content-strong hover:bg-surface-sunken"
          @click="isMobileMenuOpen = false"
        >
          <span>Reclamos</span>
          <span v-if="openClaimsCount > 0" class="px-2 py-0.5 rounded-full bg-warning/15 text-warning text-xs font-bold">
            {{ openClaimsCount }}
          </span>
        </RouterLink>

        <div class="pt-2 border-t border-border-subtle space-y-1">
          <p class="text-[11px] font-bold uppercase tracking-wider text-content-secondary px-3 py-1">
            Gestión
          </p>
          <RouterLink
            v-for="item in managementLinks"
            :key="item.to"
            :to="item.to"
            class="block px-3 py-2 rounded-xl text-sm font-medium text-content-strong hover:bg-surface-sunken"
            @click="isMobileMenuOpen = false"
          >
            {{ item.label }}
          </RouterLink>
        </div>

        <div class="pt-3 border-t border-border-subtle">
          <button
            type="button"
            class="w-full flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold text-danger hover:bg-danger/10 cursor-pointer"
            @click="handleLogout"
          >
            <span>Cerrar sesión</span>
          </button>
        </div>
      </aside>
    </div>

    <main class="flex-1 w-full">
      <RouterView />
    </main>
    <RealtimeToastContainer />
  </div>
</template>
