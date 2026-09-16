<template>
  <div class="search-container">
    <div class="search-input-wrapper">
      <input
        v-model="searchQuery"
        @input="handleSearch"
        type="text"
        :placeholder="placeholder"
        class="search-input"
      />
      <div class="search-icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
      </div>
      <button
        v-if="searchQuery"
        @click="clearSearch"
        class="clear-button"
        type="button"
        aria-label="Limpiar búsqueda"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axiosInstance from '@/lib/axios'

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Buscar ofertas...'
  }
})

const emit = defineEmits(['search-results', 'search-error', 'search-clear','search-leading'])

const searchQuery = ref('')
const isSearching = ref(false)
let searchTimeout = null

const handleSearch = () => {
  // Debounce search to avoid too many API calls
  clearTimeout(searchTimeout)

  if (searchQuery.value.trim() === '') {
    emit('search-clear')
    return
  }

  searchTimeout = setTimeout(async () => {
    await performSearch()
  }, 500) // Wait 500ms after user stops typing
}

const performSearch = async () => {
  if (searchQuery.value.trim() === '') return

  try {
    isSearching.value = true
    emit('search-leading', true)

    // Ahora emitimos la query en lugar de los resultados
    // para que el componente padre gestione la paginación
    emit('search-results', searchQuery.value.trim())
  } catch (error) {
    console.error('Error searching offers:', error)
    emit('search-error', 'Error al buscar ofertas')
  } finally {
    isSearching.value = false
    emit('search-leading', false)
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  emit('search-clear')
}

// Watch for external changes to search query
watch(() => searchQuery.value, (newValue) => {
  if (newValue === '') {
    emit('search-clear')
  }
})

watch(() => isSearching.value, (newValue) => {
  if (newValue === false){
    emit('search-leading', false);
  }else {
    emit('search-leading',true);
  }
})
</script>

<style scoped>
.search-container {
  width: 100%;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.search-input {
  width: 100%;
  height: 44px;
  padding: 0 42px 0 44px;
  border: 1px solid #3D3450;
  border-radius: 9999px;
  font-size: 0.875rem;
  outline: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  background-color: #221C30;
  color: #E8EAF6;
  box-sizing: border-box;
}

.search-input:hover {
  border-color: #4E4264;
  background-color: #261F36;
}

.search-input:focus {
  border-color: #7C3AED;
  background-color: #261F36;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
}

.search-input::placeholder {
  color: #7E7896;
}

.search-icon {
  position: absolute;
  left: 15px;
  color: #7E7896;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s ease;
}

.search-input:focus ~ .search-icon {
  color: #A78BFA;
}

.clear-button {
  position: absolute;
  right: 12px;
  background: rgba(255, 255, 255, 0.08);
  border: none;
  cursor: pointer;
  color: #9DA1BF;
  padding: 5px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.clear-button:hover {
  background-color: rgba(239, 68, 68, 0.2);
  color: #EF4444;
}

@media (max-width: 768px) {
  .search-input {
    font-size: 0.85rem;
    padding-left: 40px;
    height: 42px;
  }
}
</style>
