<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const establishment = ref(null);
const establishmentTypes = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const successMessage = ref('');

// Form data
const formData = ref({
  name: '',
  establishment_type_id: ''
});

const fetchEstablishment = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get('/my-establishment');
    establishment.value = data || null;

    // Populate form with current data
    if (data) {
      formData.value.name = data.name || '';
      formData.value.establishment_type_id = data.establishment_type?.id || '';
    }

    error.value = null;
  } catch (err) {
    console.error('Error fetching establishment:', err);
    error.value = 'No se pudo cargar el establecimiento';
  } finally {
    loading.value = false;
  }
};

const fetchEstablishmentTypes = async () => {
  try {
    const { data } = await axiosInstance.get('/establishment-types');
    establishmentTypes.value = data || [];
  } catch (err) {
    console.error('Error fetching establishment types:', err);
  }
};

const updateEstablishment = async () => {
  if (!formData.value.name.trim()) {
    error.value = 'El nombre del establecimiento es requerido';
    return;
  }

  if (!formData.value.establishment_type_id) {
    error.value = 'Debe seleccionar un tipo de establecimiento';
    return;
  }

  try {
    saving.value = true;
    error.value = null;
    successMessage.value = '';

    await axiosInstance.put('/my-establishment', {
      name: formData.value.name.trim(),
      establishment_type_id: formData.value.establishment_type_id
    });

    successMessage.value = 'Establecimiento actualizado correctamente';

    setTimeout(() => {
      router.push('/ating-confirmation');
    }, 2000);

  } catch (err) {
    console.error('Error updating establishment:', err);
    error.value = err.response?.data?.message || 'Error al actualizar el establecimiento';
  } finally {
    saving.value = false;
  }
};

const cancelEdit = () => {
  router.push('/seller/establishment');
};

onMounted(async () => {
  await Promise.all([
    fetchEstablishment(),
    fetchEstablishmentTypes()
  ]);
});
</script>

<template>
  <div class="edit-establishment-page">
    <div class="edit-establishment-container">
      <div class="header">
        <button class="back-btn" @click="cancelEdit">
          ← Volver
        </button>
        <h1 class="title">Editar Establecimiento</h1>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando información del establecimiento...</p>
      </div>

      <div v-else-if="error && !establishment" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchEstablishment">Reintentar</button>
      </div>

      <div v-else class="content">
        <form @submit.prevent="updateEstablishment" class="edit-form">
          <div class="card">
            <h2 class="section-title">Información del Establecimiento</h2>

            <!-- Success Message -->
            <div v-if="successMessage" class="success-message">
              {{ successMessage }}
            </div>

            <!-- Error Message -->
            <div v-if="error" class="error-message">
              {{ error }}
            </div>

            <div class="form-group">
              <label for="establishment-name" class="label">
                Nombre del Establecimiento *
              </label>
              <input
                id="establishment-name"
                v-model="formData.name"
                type="text"
                class="form-input"
                placeholder="Ingresa el nombre del establecimiento"
                required
                :disabled="saving"
              />
            </div>

            <div class="form-group">
              <label for="establishment-type" class="label">
                Tipo de Establecimiento *
              </label>
              <select
                id="establishment-type"
                v-model="formData.establishment_type_id"
                class="form-select"
                required
                :disabled="saving"
              >
                <option value="">Selecciona un tipo de establecimiento</option>
                <option
                  v-for="type in establishmentTypes"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button
                type="button"
                class="cancel-btn"
                @click="cancelEdit"
                :disabled="saving"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="save-btn"
                :disabled="saving"
              >
                <span v-if="saving">Guardando...</span>
                <span v-else>Guardar Cambios</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.edit-establishment-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem;
}

.edit-establishment-container {
  max-width: 800px;
  margin: 0 auto;
}

.header {
  display: flex;
  align-items: center;
  margin-bottom: 2rem;
  color: var(--color-text);
  position: relative;
}

.back-btn {
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-right: 1rem;
}

.back-btn:hover {
  background: var(--color-focus);
}

.title {
  font-size: 2.2rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--color-text);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-radius: 50%;
  border-top-color: var(--color-primary);
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 2rem;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  color: var(--color-text);
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.retry-btn:hover {
  background: var(--color-focus);
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.edit-form {
  width: 100%;
}

.card {
  background: var(--color-background);
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(34, 32, 31, 0.2);
  border: 1px solid var(--color-border);
}

.section-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 1.5rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.success-message {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
  font-weight: 500;
}

.error-message {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
  font-weight: 500;
}

.form-group {
  margin-bottom: 1.5rem;
}

.label {
  display: block;
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.5rem;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-secondary);
  color: var(--color-text);
  font-size: 1rem;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
}

.form-input:disabled,
.form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select {
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.cancel-btn,
.save-btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  font-size: 1rem;
}

.cancel-btn {
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.cancel-btn:hover:not(:disabled) {
  background: var(--color-focus);
}

.save-btn {
  background: var(--color-primary);
  color: white;
  box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
}

.save-btn:hover:not(:disabled) {
  background: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 107, 0, 0.4);
}

.cancel-btn:disabled,
.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Responsive */
@media (max-width: 768px) {
  .edit-establishment-page {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .title {
    font-size: 1.8rem;
  }

  .card {
    padding: 1.5rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .cancel-btn,
  .save-btn {
    width: 100%;
  }
}
</style>