<template>
  <div>
    <div class="hamburger" @click="toggleSidebar">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <aside class="sidebar" :class="{ open: isOpen }">
      <ul>
        <li>
          <RouterLink to="/seller/create-offer">Nueva Oferta</RouterLink>
        </li>
        <li>
          <RouterLink to="/seller/my-offers">Mis Ofertas</RouterLink>
        </li>
        <li>
          <RouterLink to="/seller/sells">Mis Pedidos</RouterLink>
        </li>
        <li>
          <RouterLink :to="{ name: 'create-product' }"
            >Agregar Productos</RouterLink
          >
        </li>
        <li>
          <RouterLink :to="{ name: 'my-products' }">Mis Productos</RouterLink>
        </li>
      </ul>
    </aside>
  </div>
</template>

<script>
export default {
  name: "SidebarMenu",
  data() {
    return {
      isOpen: false,
    };
  },
  methods: {
    toggleSidebar() {
      this.isOpen = !this.isOpen;
    },
  },
};
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.hamburger {
  position: absolute;
  top: 1rem;
  left: 1rem;
  cursor: pointer;
  z-index: 1001;
  width: 30px;
  height: 22px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.hamburger span {
  display: block;
  height: 4px;
  background: var(--color-primary);
  border-radius: 2px;
}

.sidebar {
  position: fixed;
  top: 0;
  left: -250px;
  width: 250px;
  height: 100vh;
  background: var(--color-darkest);
  color: #ecf0f1;
  padding-top: 4rem;
  padding-bottom: 2rem;
  padding-right: 1rem;
  padding-left: 1rem;
  transition: left 0.3s ease;
  z-index: 1000;
}

.sidebar.open {
  left: 0;
}

.sidebar ul {
  list-style: none;
}

.sidebar ul li {
  margin: 1rem 0;
}
</style>
