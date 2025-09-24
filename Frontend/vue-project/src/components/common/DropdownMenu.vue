<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  items: {
    type: Array,
    required: true
    // Formato: [{ text: 'Texto del enlace', to: '/ruta' }, ...]
  }
});

const isOpen = ref(false);

function toggleDropdown() {
  isOpen.value = !isOpen.value;
}
</script>

<template>
  <li class="dropdown-container">
    <button
        class="menu-link dropdown-toggle"
        @click="toggleDropdown"
        :aria-expanded="isOpen"
    >
      {{ title }}
      <span class="dropdown-icon" :class="{ open: isOpen }">▼</span>
    </button>

    <transition name="dropdown">
      <ul v-if="isOpen" class="submenu">
        <li v-for="(item, index) in items" :key="index">
          <RouterLink :to="item.to" class="submenu-link" @click="isOpen = false">
            {{ item.text }}
          </RouterLink>
        </li>
      </ul>
    </transition>
  </li>
</template>

<style scoped>
.dropdown-container {
  position: relative;
}

.dropdown-toggle {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  text-align: left;
  cursor: pointer;
  background: transparent;
  border: none;
  font-family: inherit;
  font-size: inherit;
}

.dropdown-icon {
  font-size: 10px;
  transition: transform 0.2s ease;
}

.dropdown-icon.open {
  transform: rotate(-180deg);
}

.submenu {
  list-style: none;
  padding: 0;
  margin: 4px 0 4px 12px;
  display: grid;
  gap: 2px;
}

.submenu-link {
  display: block;
  padding: 6px 10px;
  border-radius: 6px;
  color: var(--color-text);
  text-decoration: none;
  font-size: 0.95em;
}

.submenu-link:hover {
  background: color-mix(in oklab, var(--color-darkest), white 6%);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: max-height 0.3s ease, opacity 0.2s ease;
  max-height: 500px;
  overflow: hidden;
}

.dropdown-enter-from,
.dropdown-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>