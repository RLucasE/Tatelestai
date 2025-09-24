<template>
  <div class="min-h-screen custom-bg p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="custom-card rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold custom-text">Gestión de Tipos de Establecimientos</h1>
          <button
            @click="openCreateModal"
            class="custom-btn-primary px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Tipo
          </button>
        </div>
      </div>

      <!-- Tabs para activos y eliminados -->
      <div class="custom-card rounded-lg shadow-md mb-6">
        <div class="border-b custom-border">
          <nav class="flex space-x-8 px-6">
            <button
              @click="activeTab = 'active'"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                activeTab === 'active'
                  ? 'border-white custom-text'
                  : 'border-transparent text-gray-300 hover:text-white hover:border-gray-300'
              ]"
            >
              Activos ({{ establishmentTypes.length }})
            </button>
            <button
              @click="activeTab = 'trashed'; loadTrashedTypes()"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                activeTab === 'trashed'
                  ? 'border-white custom-text'
                  : 'border-transparent text-gray-300 hover:text-white hover:border-gray-300'
              ]"
            >
              Eliminados ({{ trashedTypes.length }})
            </button>
          </nav>
        </div>
      </div>

      <!-- Loading indicator -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
      </div>

      <!-- Lista de tipos activos -->
      <div v-else-if="activeTab === 'active'" class="custom-card rounded-lg shadow-md overflow-hidden">
        <div v-if="establishmentTypes.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <h3 class="mt-2 text-sm font-medium custom-text">No hay tipos de establecimientos</h3>
          <p class="mt-1 text-sm text-gray-300">Comienza creando un nuevo tipo de establecimiento.</p>
        </div>

        <div v-else>
          <div
            v-for="(type, index) in establishmentTypes"
            :key="type.id"
            :class="[
              'p-6 hover:bg-opacity-80 hover:custom-secondary transition-colors duration-200',
              { 'border-t custom-border': index > 0 }
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-semibold custom-text">{{ type.name }}</h3>
                <p class="text-sm text-gray-300 mt-1">Slug: {{ type.slug }}</p>
                <p v-if="type.description" class="text-sm text-gray-200 mt-2">{{ type.description }}</p>
              </div>
              <div class="flex gap-2 ml-4">
                <button
                  @click="editType(type)"
                  class="custom-text hover:text-gray-300 p-2 rounded-lg hover:bg-opacity-50 hover:custom-focus transition-colors duration-200"
                  title="Editar"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button
                  @click="confirmDelete(type)"
                  class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-red-900 hover:bg-opacity-20 transition-colors duration-200"
                  title="Eliminar"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de tipos eliminados -->
      <div v-else-if="activeTab === 'trashed'" class="custom-card rounded-lg shadow-md overflow-hidden">
        <div v-if="trashedTypes.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <h3 class="mt-2 text-sm font-medium custom-text">No hay tipos eliminados</h3>
          <p class="mt-1 text-sm text-gray-300">Los tipos eliminados aparecerán aquí.</p>
        </div>

        <div v-else>
          <div
            v-for="(type, index) in trashedTypes"
            :key="type.id"
            :class="[
              'p-6 custom-secondary',
              { 'border-t custom-border': index > 0 }
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-300">{{ type.name }}</h3>
                <p class="text-sm text-gray-400 mt-1">Slug: {{ type.slug }}</p>
                <p v-if="type.description" class="text-sm text-gray-400 mt-2">{{ type.description }}</p>
              </div>
              <div class="flex gap-2 ml-4">
                <button
                  @click="restoreType(type)"
                  class="text-green-400 hover:text-green-300 px-3 py-1 border border-green-400 rounded-lg hover:bg-green-900 hover:bg-opacity-20 transition-colors duration-200"
                >
                  Restaurar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="custom-card rounded-lg max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold custom-text">
            {{ isEditing ? 'Editar Tipo de Establecimiento' : 'Nuevo Tipo de Establecimiento' }}
          </h3>
          <button
            @click="closeModal"
            class="text-gray-300 hover:text-white transition-colors duration-200"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="mb-4">
            <label class="block text-sm font-medium custom-text mb-2">
              Nombre *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border custom-input rounded-lg focus:outline-none focus:ring-2 focus:ring-white focus:border-transparent"
              placeholder="Ej: Restaurante, Cafetería, etc."
            >
            <p v-if="errors.name" class="text-red-400 text-sm mt-1">{{ errors.name[0] }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium custom-text mb-2">
              Descripción
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border custom-input rounded-lg focus:outline-none focus:ring-2 focus:ring-white focus:border-transparent"
              placeholder="Descripción del tipo de establecimiento"
            ></textarea>
            <p v-if="errors.description" class="text-red-400 text-sm mt-1">{{ errors.description[0] }}</p>
          </div>

          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-gray-300 border border-gray-500 rounded-lg hover:bg-opacity-50 hover:custom-focus transition-colors duration-200"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 custom-btn-primary rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
              {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="custom-card rounded-lg max-w-md w-full p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0 w-10 h-10 mx-auto bg-red-900 bg-opacity-20 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
        </div>
        <div class="text-center">
          <h3 class="text-lg font-medium custom-text mb-2">Confirmar Eliminación</h3>
          <p class="text-sm text-gray-300 mb-4">
            ¿Estás seguro de que deseas eliminar el tipo "{{ typeToDelete?.name }}"? Esta acción se puede revertir desde la pestaña de eliminados.
          </p>
          <div class="flex justify-center gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-gray-300 border border-gray-500 rounded-lg hover:bg-opacity-50 hover:custom-focus transition-colors duration-200"
            >
              Cancelar
            </button>
            <button
              @click="deleteType"
              :disabled="submitting"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
              {{ submitting ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notificaciones -->
    <div v-if="notification.show" class="fixed bottom-4 right-4 z-50">
      <div
        :class="[
          'px-6 py-4 rounded-lg shadow-lg text-white',
          notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'
        ]"
      >
        {{ notification.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/lib/axios'

// Estado reactivo
const loading = ref(false)
const submitting = ref(false)
const activeTab = ref('active')
const establishmentTypes = ref([])
const trashedTypes = ref([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const typeToDelete = ref(null)
const errors = ref({})

const form = ref({
  name: '',
  description: ''
})

const notification = ref({
  show: false,
  message: '',
  type: 'success'
})

// Cargar tipos de establecimientos activos
const loadTypes = async () => {
  loading.value = true
  try {
    const response = await axios.get('/adm-establishments/types')
    if (response.data.success) {
      establishmentTypes.value = response.data.data
    }
  } catch (error) {
    showNotification('Error al cargar los tipos de establecimientos', 'error')
    console.error('Error loading types:', error)
  } finally {
    loading.value = false
  }
}

// Cargar tipos eliminados
const loadTrashedTypes = async () => {
  loading.value = true
  try {
    const response = await axios.get('/adm-establishments/types-trashed')
    if (response.data.success) {
      trashedTypes.value = response.data.data
    }
  } catch (error) {
    showNotification('Error al cargar los tipos eliminados', 'error')
    console.error('Error loading trashed types:', error)
  } finally {
    loading.value = false
  }
}

// Abrir modal para crear
const openCreateModal = () => {
  isEditing.value = false
  form.value = { name: '', description: '' }
  errors.value = {}
  showModal.value = true
}

// Editar tipo
const editType = (type) => {
  isEditing.value = true
  form.value = {
    id: type.id,
    name: type.name,
    description: type.description || ''
  }
  errors.value = {}
  showModal.value = true
}

// Cerrar modal
const closeModal = () => {
  showModal.value = false
  form.value = { name: '', description: '' }
  errors.value = {}
}

// Enviar formulario
const submitForm = async () => {
  submitting.value = true
  errors.value = {}

  try {
    let response
    if (isEditing.value) {
      response = await axios.patch(`/adm-establishments/types/${form.value.id}`, {
        name: form.value.name,
        description: form.value.description
      })
    } else {
      response = await axios.post('/adm-establishments/types', {
        name: form.value.name,
        description: form.value.description
      })
    }

    if (response.data.success) {
      showNotification(response.data.message, 'success')
      closeModal()
      loadTypes()
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      showNotification('Error al procesar la solicitud', 'error')
    }
    console.error('Error submitting form:', error)
  } finally {
    submitting.value = false
  }
}

// Confirmar eliminación
const confirmDelete = (type) => {
  typeToDelete.value = type
  showDeleteModal.value = true
}

// Eliminar tipo
const deleteType = async () => {
  submitting.value = true
  try {
    const response = await axios.delete(`/adm-establishments/types/${typeToDelete.value.id}`)
    if (response.data.success) {
      showNotification(response.data.message, 'success')
      showDeleteModal.value = false
      loadTypes()
    }
  } catch (error) {
    showNotification('Error al eliminar el tipo', 'error')
    console.error('Error deleting type:', error)
  } finally {
    submitting.value = false
  }
}

// Restaurar tipo
const restoreType = async (type) => {
  try {
    const response = await axios.patch(`/adm-establishments/types/${type.id}/restore`)
    if (response.data.success) {
      showNotification(response.data.message, 'success')
      loadTrashedTypes()
      loadTypes()
    }
  } catch (error) {
    showNotification('Error al restaurar el tipo', 'error')
    console.error('Error restoring type:', error)
  }
}

// Mostrar notificación
const showNotification = (message, type = 'success') => {
  notification.value = {
    show: true,
    message,
    type
  }
  setTimeout(() => {
    notification.value.show = false
  }, 3000)
}

// Cargar datos al montar el componente
onMounted(() => {
  loadTypes()
})
</script>

<style scoped>
:root {
  --color-text: #ffffff;
  --color-bg: #afadab;
  --color-primary: #6f6c69;
  --color-secondary: #5f5c59;
  --color-focus: #3e3b39;
  --color-darkest: #22201f;
}

.custom-bg {
  background-color: var(--color-bg);
}

.custom-card {
  background-color: var(--color-primary);
}

.custom-text {
  color: var(--color-text);
}

.custom-secondary {
  background-color: var(--color-secondary);
}

.custom-focus {
  background-color: var(--color-focus);
}

.custom-border {
  border-color: var(--color-secondary);
}

.custom-divide > :not([hidden]) ~ :not([hidden]) {
  border-color: var(--color-secondary);
}

.custom-btn-primary {
  background-color: var(--color-darkest);
  color: var(--color-text);
}

.custom-btn-primary:hover {
  background-color: var(--color-focus);
}

.custom-input {
  background-color: var(--color-secondary);
  border-color: var(--color-focus);
  color: var(--color-text);
}

.custom-input::placeholder {
  color: #9ca3af;
}
</style>
