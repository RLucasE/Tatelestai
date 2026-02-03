<script setup>
import { ref } from "vue";
import { RouterLink, RouterView, useRouter } from "vue-router";
import DropdownMenu from "@/components/common/DropdownMenu.vue";
import Logo from "@/components/common/Logo.vue";
import { useAuthStore } from '@/stores/auth.js';

const isSidebarOpen = ref(false);
const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  try {
    await authStore.logout();
    isSidebarOpen.value = false;
    router.push('/login');
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
  }
};
</script>

<template>
  <div class="admin-layout">
    <header class="admin-header">
      <nav class="nav">
        <button
          class="nav-toggle"
          :class="{ open: isSidebarOpen }"
          @click="isSidebarOpen = !isSidebarOpen"
          :aria-expanded="String(isSidebarOpen)"
          aria-label="Abrir menú"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
        <RouterLink to="/adm/dashboard" class="logo-link">
          <Logo />
        </RouterLink>
      </nav>
    </header>

    <aside class="sidebar" :class="{ open: isSidebarOpen }" @click.self="isSidebarOpen = false">
      <div class="sidebar-panel">
        <h2 class="sidebar-title">Panel</h2>
        <ul class="menu">
          <li><RouterLink to="/adm/dashboard" class="menu-link">Estadísticas</RouterLink></li>
          <li><RouterLink to="/adm/users" class="menu-link">Usuarios</RouterLink></li>
          <li><RouterLink to="/adm/sells" class="menu-link">Ventas</RouterLink></li>
          <li><RouterLink to="/adm/offers" class="menu-link">Ofertas</RouterLink></li>
          <li><RouterLink to="/adm/pending-offers" class="menu-link">Verificar Ofertas</RouterLink></li>
          <li><RouterLink to="/adm/reports" class="menu-link">Reportes</RouterLink></li>
          <li>
            <DropdownMenu
                title="Establecimientos"
                class="menu-dropdown"
                :items="[
              { text: 'Solicitudes', to: '/adm/new-sellers' },
              { text: 'Categorías', to: '/adm/establishment-types' },
            ]"
            />
          </li>
          <li>
            <button @click="handleLogout" class="menu-link logout-button">
              <span class="logout-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18">
                  <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h8v-2H4V5z"></path>
                </svg>
              </span>
              Cerrar Sesión
            </button>
          </li>
        </ul>
      </div>
    </aside>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  background: var(--color-bg);
  color: var(--color-text);
  min-height: 100vh;
}

.admin-header {
  position: sticky;
  top: 0;
  z-index: 1100;
  background: var(--color-darkest);
  border-bottom: 1px solid var(--color-border);
}

.nav {
  display: flex;
  align-items: center;
  gap: 12px;
  height: 56px;
  padding: 0 16px;
}

.nav-toggle {
  cursor: pointer;
  width: 36px;
  height: 28px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  padding: 4px 6px;
  border-radius: var(--border-radius);
  background: transparent;
  border: 1px solid var(--color-border);
}

.nav-toggle:hover {
  background-color: color-mix(in oklab, var(--color-darkest), white 6%);
}

.nav-toggle span {
  display: block;
  width: 100%;
  height: 3px;
  background: var(--color-primary);
  border-radius: 2px;
  transition: transform 0.25s ease, opacity 0.25s ease, width 0.25s ease;
}

.nav-toggle.open span:nth-child(1) { transform: translateY(8px) rotate(45deg); }
.nav-toggle.open span:nth-child(2) { opacity: 0; }
.nav-toggle.open span:nth-child(3) { transform: translateY(-8px) rotate(-45deg); }

/* Overlay que cubre pantalla (debajo del header), no desplaza contenido */
.sidebar {
  position: fixed;
  top: 56px;         /* debajo del header para mantener accesible el botón */
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1200;     /* por encima del contenido */
  background: transparent;
  pointer-events: none;           /* deshabilitado cuando está cerrado */
  overflow: hidden;
  transition: background 0.25s ease;
}
.sidebar.open {
  background: rgba(0, 0, 0, 0.35); /* backdrop */
  pointer-events: auto;
}

/* Panel deslizante */
.sidebar-panel {
  width: 260px;
  height: 100%;
  padding: 16px;
  background: color-mix(in oklab, var(--color-darkest), black 6%);
  border-right: 1px solid var(--color-border);
  box-shadow: 2px 0 12px rgba(0,0,0,0.2);
  transform: translateX(-100%);
  transition: transform 0.25s ease;
}
.sidebar.open .sidebar-panel {
  transform: translateX(0);
}

.sidebar-title {
  margin: 0 0 8px;
  font-size: 12px;
  color: var(--color-primary);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.menu {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 4px;
}
.menu-link {
  display: block;
  padding: 8px 10px;
  border-radius: 8px;
  color: var(--color-text);
  text-decoration: none;
}
.menu-link:hover {
  background: color-mix(in oklab, var(--color-darkest), white 6%);
}

.logout-button {
  background: transparent;
  border: none;
  cursor: pointer;
  font-family: inherit;
  font-size: inherit;
  width: 100%;
  text-align: left;
  margin-top: auto;
  border-top: 1px solid var(--color-border);
  padding-top: 12px;
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logout-button:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.logout-icon {
  display: inline-flex;
  width: 18px;
  height: 18px;
  fill: currentColor;
}

.logout-button:hover .logout-icon {
  fill: #ef4444;
}

.content {

  padding: 16px;
  max-width: 100%;
  margin: 0 auto;
}

@media (min-width: 1024px) {
  .content { padding: 24px; margin-left: 0; }
}

.logo-link {
  text-decoration: none;
  margin-left: 1rem;
}
</style>
