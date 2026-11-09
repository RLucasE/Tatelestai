<script setup>
import { ref, watch } from 'vue';
import BaseIcon from '@/components/ui/atoms/BaseIcon.vue';

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Buscar ofertas...',
  },
});

const emit = defineEmits(['search-results', 'search-error', 'search-clear', 'search-leading']);

const searchQuery = ref('');
const isSearching = ref(false);
let searchTimeout = null;

const handleSearch = () => {
  clearTimeout(searchTimeout);

  if (searchQuery.value.trim() === '') {
    emit('search-clear');
    return;
  }

  searchTimeout = setTimeout(async () => {
    await performSearch();
  }, 400);
};

const performSearch = async () => {
  if (searchQuery.value.trim() === '') return;

  try {
    isSearching.value = true;
    emit('search-leading', true);
    emit('search-results', searchQuery.value.trim());
  } catch (error) {
    console.error('Error searching offers:', error);
    emit('search-error', 'Error al buscar ofertas');
  } finally {
    isSearching.value = false;
    emit('search-leading', false);
  }
};

const clearSearch = () => {
  searchQuery.value = '';
  emit('search-clear');
};

watch(() => searchQuery.value, (newValue) => {
  if (newValue === '') {
    emit('search-clear');
  }
});
</script>

<template>
  <div class="relative flex items-center w-full">
    <input
      v-model="searchQuery"
      type="text"
      :placeholder="placeholder"
      class="w-full h-9 pl-9 pr-9 bg-surface border border-border-subtle hover:border-content-secondary rounded-full text-[13px] text-content placeholder:text-content-muted transition-all duration-150 focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20"
      @input="handleSearch"
    />

    <!-- Ícono de Lupa (Figma: text-content) -->
    <div class="absolute left-3.5 text-content pointer-events-none flex items-center justify-center">
      <BaseIcon name="search" size="sm" />
    </div>

    <!-- Botón Limpiar Búsqueda -->
    <button
      v-if="searchQuery"
      type="button"
      class="absolute right-3 p-1 rounded-full text-content-secondary hover:text-brand hover:bg-canvas transition-all cursor-pointer flex items-center justify-center"
      aria-label="Limpiar búsqueda"
      @click="clearSearch"
    >
      <BaseIcon name="close" size="sm" />
    </button>
  </div>
</template>
