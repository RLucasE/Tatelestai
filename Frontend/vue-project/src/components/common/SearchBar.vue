<template>
  <div class="search-container">
    <div class="search-input-wrapper">
      <input
        v-model="searchQuery"
        @input="handleSearch"
        type="text"
        placeholder="Buscar ofertas..."
        class="search-input"
      />
      <div class="search-icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
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
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
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

    <div>
      hola xd
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
    const response = await axiosInstance.get('/offers', {
      params: {
        search: searchQuery.value.trim()
      }
    })

    const offers = response.data.data || response.data
    emit('search-results', offers)
  } catch (error) {
    console.error('Error searching offers:', error)
    emit('search-error', 'Error al buscar ofertas')
  } finally {
    isSearching.value = false
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
  margin-bottom: 20px;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 500px;
  margin: 0 auto;
}

.search-input {
  width: 100%;
  padding: 12px 45px 12px 45px;
  border: 2px solid var(--color-primary-light, #e0e0e0);
  border-radius: 25px;
  font-size: 16px;
  outline: none;
  transition: all 0.3s ease;
  background-color: var(--color-white, #ffffff);
  color: var(--color-darkest, #333);
}

.search-input:focus {
  border-color: var(--color-primary, #4CAF50);
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.search-input::placeholder {
  color: var(--color-text-light, #999);
}

.search-icon {
  position: absolute;
  left: 15px;
  color: var(--color-text-light, #999);
  pointer-events: none;
}

.clear-button {
  position: absolute;
  right: 15px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-light, #999);
  padding: 4px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.clear-button:hover {
  background-color: var(--color-danger-light, #ffebee);
  color: var(--color-danger, #f44336);
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
  color: var(--color-text-light, #999);
  font-size: 14px;
}

.search-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid var(--color-primary-light, #e0e0e0);
  border-top: 2px solid var(--color-primary, #4CAF50);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .search-input-wrapper {
    max-width: 100%;
  }

  .search-input {
    font-size: 14px;
    padding: 10px 40px 10px 40px;
  }
}
</style>
