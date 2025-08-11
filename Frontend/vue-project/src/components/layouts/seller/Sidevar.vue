<template>
  <div class="sidebar-wrapper">
    <div v-if="isOpen" class="backdrop" @click="closeSidebar" aria-hidden="true"></div>

    <aside class="sidebar" :class="{ open: isOpen }" role="navigation" aria-label="Menú del vendedor">
      <header class="sidebar__header">
        <span class="sidebar__brand">Vendedor</span>
        <button class="sidebar__close" @click="closeSidebar" aria-label="Cerrar menú">
          ×
        </button>
      </header>
      <nav class="sidebar__nav">
        <RouterLink to="/seller/create-offer" class="nav-link">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2h6z"></path>
            </svg>
          </span>
          <span>Nueva Oferta</span>
        </RouterLink>

        <RouterLink to="/seller/my-offers" class="nav-link">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"></path>
            </svg>
          </span>
          <span>Mis Ofertas</span>
        </RouterLink>

        <RouterLink to="/seller/sells" class="nav-link">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM17 18c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.16 14h9.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49a1 1 0 0 0-.88-1.48H6.21L5.27 2H2v2h2l3.6 7.59-1.35 2.45A2 2 0 0 0 6 16a2 2 0 0 0 2 2h12v-2H8.42a.25.25 0 0 1-.22-.37l.96-1.63z"></path>
            </svg>
          </span>
          <span>Mis Pedidos</span>
        </RouterLink>

        <RouterLink :to="{ name: 'create-product' }" class="nav-link">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16zM5 9.2 12 13l7-3.8V16l-7 4-7-4V9.2z"></path>
            </svg>
          </span>
          <span>Agregar Productos</span>
        </RouterLink>

        <RouterLink :to="{ name: 'my-products' }" class="nav-link">
          <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path d="M16 16H8V8h8v8zm-6-6v4h4v-4H10zM3 3h6v2H5v4H3V3zm16 0v6h-2V5h-4V3h6zm0 18h-6v-2h4v-4h2v6zM3 21v-6h2v4h4v2H3z"></path>
            </svg>
          </span>
          <span>Mis Productos</span>
        </RouterLink>
      </nav>
    </aside>
  </div>
 </template>

<script>
export default {
  name: "SidebarMenu",
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    isOpen: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
  },
  mounted() {
    window.addEventListener("keydown", this.handleKeydown);
  },
  beforeUnmount() {
    window.removeEventListener("keydown", this.handleKeydown);
  },
  watch: {
    isOpen(newValue) {
      document.body.style.overflow = newValue ? "hidden" : "";
    },
  },
  methods: {
    toggleSidebar() {
      this.isOpen = !this.isOpen;
    },
    closeSidebar() {
      this.isOpen = false;
    },
    handleKeydown(event) {
      if (event.key === "Escape" && this.isOpen) {
        this.closeSidebar();
      }
    },
  },
};
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.sidebar-wrapper {
  position: relative;
}

.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(1px);
  z-index: 999;
}

.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;
  height: 100vh;
  background: var(--color-darkest);
  color: var(--color-text);
  transform: translateX(-100%);
  transition: transform 0.3s ease;
  z-index: 1000;
  border-right: 1px solid var(--color-border);
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.02), 0 10px 30px rgba(0, 0, 0, 0.35);
}

.sidebar.open {
  transform: translateX(0);
}

.sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1rem 0.5rem 1rem;
}

.sidebar__brand {
  color: var(--color-text);
  font-weight: 600;
  letter-spacing: 0.3px;
}

.sidebar__close {
  background: transparent;
  border: none;
  color: var(--color-text);
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  border-radius: var(--border-radius);
}
.sidebar__close:hover {
  color: var(--color-primary);
}

.sidebar__nav {
  padding: 0.5rem 0.5rem 1rem 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.75rem;
  margin: 0.25rem 0.5rem;
  color: var(--color-text);
  text-decoration: none;
  border-radius: var(--border-radius);
  transition: background-color 0.2s ease, color 0.2s ease, transform 0.12s ease;
}

.nav-link:hover {
  background: var(--color-secondary);
}

.nav-link:active {
  transform: translateY(1px);
}

.nav-link .icon {
  display: inline-flex;
  width: 20px;
  height: 20px;
  color: var(--color-primary);
}

/* Estado activo de RouterLink */
.nav-link.router-link-exact-active,
.nav-link.router-link-active {
  background: var(--color-primary);
  color: var(--color-text);
}
.nav-link.router-link-exact-active .icon,
.nav-link.router-link-active .icon {
  color: var(--color-text);
}

</style>
