<template>
  <div class="establishment-management">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <header class="mb-8">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold">Gestión de Tipos de Establecimientos</h1>
          <button @click="openCreateModal" class="btn btn-accent">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Nuevo Tipo
          </button>
        </div>
        <p class="mt-2 text-muted">Crea, edita y administra los tipos de establecimientos de la plataforma.</p>
      </header>

      <!-- Tabs -->
      <div class="mb-6">
        <div class="tabs-nav">
          <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
              @click="activeTab = 'active'"
              :class="['tab-button', { 'tab-active': activeTab === 'active' }]"
            >
              Activos <span class="tab-count">{{ establishmentTypes.length }}</span>
            </button>
            <button
              @click="activeTab = 'trashed'; loadTrashedTypes()"
              :class="['tab-button', { 'tab-active': activeTab === 'trashed' }]"
            >
              Eliminados <span class="tab-count">{{ trashedTypes.length }}</span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="loader"></div>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Active Types -->
        <div v-if="activeTab === 'active'">
          <div v-if="establishmentTypes.length === 0" class="empty-state">
            <svg class="mx-auto h-12 w-12 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h4M8 7a2 2 0 012-2h4a2 2 0 012 2v8a2 2 0 01-2 2h-4a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold">No hay tipos de establecimientos</h3>
            <p class="mt-2 text-sm text-muted">Empieza por crear un nuevo tipo para que aparezca aquí.</p>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="type in establishmentTypes"
              :key="type.id"
              class="card"
            >
              <div class="p-6">
                <h3 class="text-xl font-bold truncate">{{ type.name }}</h3>
                <p class="text-sm text-muted mt-1 font-mono">{{ type.slug }}</p>
                <p v-if="type.description" class="text-sm h-10 overflow-hidden mt-3">{{ type.description }}</p>
                <div class="mt-6 flex justify-end items-center gap-3">
                  <button @click="editType(type)" class="icon-button" title="Editar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                  </button>
                  <button @click="confirmDelete(type)" class="icon-button-danger" title="Eliminar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m-1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Trashed Types -->
        <div v-if="activeTab === 'trashed'">
          <div v-if="trashedTypes.length === 0" class="empty-state">
            <svg class="mx-auto h-12 w-12 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold">No hay tipos eliminados</h3>
            <p class="mt-2 text-sm text-muted">La papelera de reciclaje está vacía.</p>
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="type in trashedTypes"
              :key="type.id"
              class="trashed-item"
            >
              <div>
                <h3 class="font-semibold">{{ type.name }}</h3>
                <p class="text-sm text-muted">{{ type.slug }}</p>
              </div>
              <button
                @click="restoreType(type)"
                class="btn-restore"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12A8 8 0 1013 5.2"></path></svg>
                Restaurar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content" :class="{ 'scale-100': showModal }">
        <div class="modal-header">
          <h3 class="text-xl font-bold">
            {{ isEditing ? 'Editar Tipo' : 'Nuevo Tipo de Establecimiento' }}
          </h3>
          <button @click="closeModal" class="icon-button">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <form @submit.prevent="submitForm" class="p-6">
          <div class="space-y-6">
            <div>
              <label for="name" class="form-label">Nombre *</label>
              <input
                v-model="form.name"
                id="name"
                type="text"
                required
                class="form-input"
                placeholder="Ej: Restaurante"
              >
              <p v-if="errors.name" class="form-error">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label for="description" class="form-label">Descripción</label>
              <textarea
                v-model="form.description"
                id="description"
                rows="4"
                class="form-input"
                placeholder="Una breve descripción del tipo de establecimiento."
              ></textarea>
              <p v-if="errors.description" class="form-error">{{ errors.description[0] }}</p>
            </div>
          </div>
          <div class="mt-8 flex justify-end gap-4">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="btn btn-accent"
            >
              <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-content max-w-sm w-full p-6 text-center">
        <div class="modal-icon-danger">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold mt-4">Confirmar Eliminación</h3>
        <p class="text-sm text-muted mt-2">
          ¿Estás seguro de que quieres eliminar <strong>"{{ typeToDelete?.name }}"</strong>? Podrás restaurarlo más tarde.
        </p>
        <div class="mt-6 flex justify-center gap-4">
          <button @click="showDeleteModal = false" class="btn btn-secondary w-24">
            Cancelar
          </button>
          <button
            @click="deleteType"
            :disabled="submitting"
            class="btn btn-danger w-24"
          >
            <svg v-if="submitting" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>Eliminar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Notificaciones -->
    <div aria-live="assertive" class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start z-50">
      <div class="w-full flex flex-col items-center space-y-4 sm:items-end">
        <transition
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="notification.show" class="notification">
            <div class="p-4">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg v-if="notification.type === 'success'" class="h-6 w-6 notification-icon-success" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <svg v-else class="h-6 w-6 notification-icon-error" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                  </svg>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                  <p class="text-sm font-medium">{{ notification.type === 'success' ? 'Éxito' : 'Error' }}</p>
                  <p class="mt-1 text-sm text-muted">{{ notification.message }}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                  <button @click="notification.show = false" class="icon-button">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </transition>
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
  loadTrashedTypes()
})
</script>

<style scoped>
.establishment-management {
  background-color: var(--color-bg);
  color: var(--color-text);
  padding: 2rem;
  min-height: 100vh;
}

.text-muted {
  color: rgba(232, 234, 246, 0.7);
}

.btn {
  font-weight: bold;
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  transition: all 0.2s ease-in-out;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-accent {
  background-color: var(--color-accent);
  color: var(--vt-c-white);
}
.btn-accent:hover {
  background-color: var(--color-accent-hover);
}

.btn-secondary {
  background-color: var(--color-secondary);
  color: var(--color-text);
}
.btn-secondary:hover {
  background-color: var(--color-focus);
}

.btn-danger {
  background-color: var(--color-danger);
  color: var(--vt-c-white);
  display: flex;
  justify-content: center;
  align-items: center;
}
.btn-danger:hover {
  background-color: var(--color-danger-hover);
}

.tabs-nav {
  border-bottom: 1px solid var(--color-secondary);
}

.tab-button {
  padding: 0.75rem 0.25rem;
  border-bottom: 2px solid transparent;
  font-weight: 500;
  font-size: 0.875rem;
  color: rgba(232, 234, 246, 0.7);
  transition: color 0.2s, border-color 0.2s;
  white-space: nowrap;
}
.tab-button:hover {
  color: var(--color-text);
  border-color: var(--color-secondary);
}
.tab-button.tab-active {
  border-color: var(--color-accent);
  color: var(--color-accent-light);
}

.tab-count {
  background-color: var(--color-secondary);
  color: rgba(232, 234, 246, 0.8);
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.5rem;
  padding: 2px 8px;
  border-radius: 9999px;
}

.loader {
  animation: spin 1s linear infinite;
  border-radius: 50%;
  height: 4rem;
  width: 4rem;
  border-top: 4px solid var(--color-accent);
  border-bottom: 4px solid var(--color-accent);
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-state {
  text-align: center;
  padding: 4rem 1.5rem;
  background-color: var(--color-primary);
  border-radius: var(--border-radius);
  box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.05);
}

.card {
  background-color: var(--color-primary);
  border-radius: 0.75rem;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  overflow: hidden;
  transform: translateY(0);
  transition: all 0.3s ease-in-out;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.icon-button {
  color: rgba(232, 234, 246, 0.7);
  transition: color 0.2s;
  background: none;
  border: none;
  cursor: pointer;
}
.icon-button:hover {
  color: var(--color-text);
}

.icon-button-danger {
  color: var(--color-danger);
  transition: color 0.2s;
  background: none;
  border: none;
  cursor: pointer;
}
.icon-button-danger:hover {
  color: var(--color-danger-hover);
}

.trashed-item {
  background-color: var(--color-primary);
  border-radius: var(--border-radius);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  padding: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.btn-restore {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--color-success);
  border: 1px solid rgba(16, 185, 129, 0.5);
  border-radius: 9999px;
  padding: 0.25rem 1rem;
  transition: all 0.2s;
  background: none;
  cursor: pointer;
}
.btn-restore:hover {
  color: #fff;
  background-color: var(--color-success);
  border-color: var(--color-success);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 13, 21, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 50;
  transition: opacity 0.3s ease;
}

.modal-content {
  background-color: var(--color-primary);
  border-radius: 0.75rem;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
  max-width: 28rem;
  width: 100%;
  transform: scale(0.95);
  transition: all 0.3s ease;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-secondary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  background-color: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: var(--border-radius);
  color: var(--color-text);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.form-input:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 2px var(--color-accent-light);
}

.form-error {
  color: var(--color-danger);
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.modal-icon-danger {
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 3rem;
  width: 3rem;
  border-radius: 50%;
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--color-danger);
}

.notification {
  max-width: 24rem;
  width: 100%;
  background-color: var(--color-primary);
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  border-radius: var(--border-radius);
  pointer-events: auto;
  ring: 1px solid rgba(0,0,0,0.05);
  overflow: hidden;
}

.notification-icon-success {
  color: var(--color-success);
}
.notification-icon-error {
  color: var(--color-danger);
}
</style>
