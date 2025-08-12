<script setup>
import { ref } from "vue";
import { RouterLink, RouterView } from "vue-router";

const isSidebarOpen = ref(false);
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
      </nav>
    </header>

    <aside class="sidebar" :class="{ open: isSidebarOpen }" @click.self="isSidebarOpen = false">
      <div class="sidebar-panel">
        <h2 class="sidebar-title">Panel</h2>
        <ul class="menu">
          <li><RouterLink to="/adm/users" class="menu-link">Usuarios</RouterLink></li>
          <li><RouterLink to="/adm/offers" class="menu-link">Ofertas</RouterLink></li>
          <li><RouterLink to="/adm/sells" class="menu-link">Ventas</RouterLink></li>
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

.sidebar {
  position: fixed;
  top: 56px;
  left: 0;
  bottom: 0;
  width: 0;
  overflow: hidden;
  background: color-mix(in oklab, var(--color-darkest), black 6%);
  transition: width 0.25s ease;
}
.sidebar.open {
  width: 260px;
  box-shadow: 2px 0 12px rgba(0,0,0,0.2);
}
.sidebar-panel {
  width: 260px;
  height: 100%;
  padding: 16px;
  border-right: 1px solid var(--color-border);
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

.content {
  padding: 16px;
  max-width: 1200px;
  margin: 0 auto;
}

@media (min-width: 1024px) {
  .content { padding: 24px; margin-left: 0; }
  .sidebar.open + .content { margin-left: 260px; }
}
</style>
