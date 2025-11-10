<script setup>
import { ref } from "vue";
import Sidevar from "./seller/Sidevar.vue";
import Logo from "@/components/common/Logo.vue";

const isSidebarOpen = ref(false);
</script>

<template>
  <nav class="flex justify-between items-center pl-4 pr-4 h-16">
    <div class="flex items-center gap-3">
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
      <RouterLink to="/seller/dashboard" class="logo-link">
        <Logo />
      </RouterLink>
    </div>
    <div>
      <!-- Espacio para navegación adicional -->
    </div>
  </nav>
  <main class="customer-cards-box">
    <RouterView />
    <Sidevar v-model="isSidebarOpen" />
  </main>
</template>

<style scoped>
.customer-cards-box {
  display: flex;
  flex-wrap: wrap;
  background-color: var(--color-bg);
  justify-content: center;
  align-items: center;
  padding: 4.5rem 16px 16px;
  min-height: 100vh;
  height: 100%;
}

nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1100;
  background-color: var(--color-darkest);
  border-bottom: 1px solid var(--color-border);
}

.logo-link {
  text-decoration: none;
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

.nav-toggle.open span:nth-child(1) {
  transform: translateY(8px) rotate(45deg);
}
.nav-toggle.open span:nth-child(2) {
  opacity: 0;
}
.nav-toggle.open span:nth-child(3) {
  transform: translateY(-8px) rotate(-45deg);
}
</style>
