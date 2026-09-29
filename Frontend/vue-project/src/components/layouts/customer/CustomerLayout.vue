<script setup>
import Logo from '@/components/common/Logo.vue';

const navItems = [
  {
    name: 'offers',
    label: 'Explorar Packs',
    to: '/customer/offers',
    icon: 'compass',
  },
  {
    name: 'purchases',
    label: 'Mis Reservas',
    to: '/customer/purchases',
    icon: 'ticket',
  },
  {
    name: 'history',
    label: 'Historial de Rescates',
    to: '/customer/history',
    icon: 'history',
  },
  {
    name: 'cart',
    label: 'Mi Carrito',
    to: '/customer/carrito',
    icon: 'cart',
  },
  {
    name: 'profile',
    label: 'Mi Perfil',
    to: '/customer/profile',
    icon: 'user',
  },
];
</script>

<template>
  <header class="sticky top-0 z-40 w-full bg-[#0F0D15]/95 backdrop-blur-md border-b border-[#3D3450]/60 shadow-md">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-6 h-16">
      <!-- Logo de Tatelestai -->
      <RouterLink
        to="/customer/offers"
        class="flex items-center gap-2 group focus:outline-none transition-transform active:scale-95"
        aria-label="Ir a Explorar Packs"
      >
        <Logo />
      </RouterLink>

      <!-- Navegación Atómica con Tooltips -->
      <div class="flex items-center gap-1.5 sm:gap-2">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="relative group flex items-center justify-center w-10 h-10 sm:w-11 sm:h-11 rounded-xl transition-all duration-200 select-none border border-transparent text-[#A5A8C2] hover:text-white hover:bg-white/[0.06] hover:border-white/[0.08] active:scale-95 [&.router-link-active]:bg-[#7C3AED]/20 [&.router-link-active]:text-[#A78BFA] [&.router-link-active]:border-[#7C3AED]/40 [&.router-link-active]:shadow-sm"
          :aria-label="item.label"
        >
          <!-- Icono: Explorar Packs (Compass) -->
          <svg
            v-if="item.icon === 'compass'"
            class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <circle cx="12" cy="12" r="10" stroke-width="2" />
            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" stroke-width="2" stroke-linejoin="round" />
          </svg>

          <!-- Icono: Mis Reservas (Ticket / Pack con reloj) -->
          <svg
            v-else-if="item.icon === 'ticket'"
            class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
          </svg>

          <!-- Icono: Historial de Rescates (Check / Clock History) -->
          <svg
            v-else-if="item.icon === 'history'"
            class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
          </svg>

          <!-- Icono: Mi Carrito (Shopping Bag) -->
          <svg
            v-else-if="item.icon === 'cart'"
            class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>

          <!-- Icono: Mi Perfil (User) -->
          <svg
            v-else-if="item.icon === 'user'"
            class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>

          <!-- Tooltip Flotante al Hover -->
          <div
            class="pointer-events-none absolute top-full mt-2 left-1/2 -translate-x-1/2 px-2.5 py-1 text-[11px] font-semibold text-white bg-[#2D2438] border border-[#3D3450] rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-all duration-200 whitespace-nowrap z-50 transform translate-y-1 group-hover:translate-y-0"
          >
            {{ item.label }}
            <!-- Flecha del tooltip -->
            <span class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[#2D2438] border-t border-l border-[#3D3450] rotate-45"></span>
          </div>
        </RouterLink>
      </div>
    </nav>
  </header>

  <!-- Contenedor Principal -->
  <main class="min-h-[calc(100vh-4rem)] w-full bg-[#1A1625] text-[#E8EAF6]">
    <RouterView></RouterView>
  </main>
</template>
