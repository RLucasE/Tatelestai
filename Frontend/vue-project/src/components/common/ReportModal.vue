<template>
  <div v-if="isVisible" class="report-modal-overlay" @click="closeModal">
    <div class="report-modal-container" @click.stop>
      <div class="report-modal-header">
        <h3>Reportar {{ getReportableTypeLabel }}</h3>
        <button class="close-button" @click="closeModal">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitReport" class="report-form">
        <div class="form-group">
          <label for="report-reason">Motivo del reporte *</label>
          <select
            id="report-reason"
            v-model="reportData.reason"
            required
            class="form-control"
          >
            <option value="">Selecciona un motivo</option>
            <option value="inappropriate">Contenido inapropiado</option>
            <option value="fraud">Fraude o estafa</option>
            <option value="false_information">Información falsa</option>
            <option value="spam">Spam</option>
            <option value="expired_product">Producto vencido</option>
            <option value="poor_quality">Mala calidad</option>
            <option value="other">Otro</option>
          </select>
        </div>

        <div class="form-group">
          <label for="report-description">Descripción *</label>
          <textarea
            id="report-description"
            v-model="reportData.description"
            placeholder="Por favor, describe el problema con detalle (mínimo 10 caracteres)"
            required
            minlength="10"
            maxlength="1000"
            rows="5"
            class="form-control"
          ></textarea>
          <small class="char-count">{{ reportData.description.length }}/1000 caracteres</small>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <div v-if="success" class="success-message">
          {{ success }}
        </div>

        <div class="form-actions">
          <button
            type="button"
            @click="closeModal"
            class="btn-cancel"
            :disabled="isSubmitting"
          >
            Cancelar
          </button>
          <button
            type="submit"
            class="btn-submit"
            :disabled="isSubmitting || !isFormValid"
          >
            {{ isSubmitting ? 'Enviando...' : 'Enviar Reporte' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  reportableType: {
    type: String,
    required: true,
    validator: (value) => ['offer', 'establishment', 'user'].includes(value)
  },
  reportableId: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(['close', 'success']);

const reportData = ref({
  reason: '',
  description: ''
});

const isSubmitting = ref(false);
const error = ref('');
const success = ref('');

const getReportableTypeLabel = computed(() => {
  const labels = {
    offer: 'Oferta',
    establishment: 'Establecimiento',
    user: 'Usuario'
  };
  return labels[props.reportableType] || 'Elemento';
});

const isFormValid = computed(() => {
  return reportData.value.reason &&
         reportData.value.description &&
         reportData.value.description.length >= 10 &&
         reportData.value.description.length <= 1000;
});

const resetForm = () => {
  reportData.value = {
    reason: '',
    description: ''
  };
  error.value = '';
  success.value = '';
  isSubmitting.value = false;
};

const closeModal = () => {
  if (!isSubmitting.value) {
    resetForm();
    emit('close');
  }
};

const submitReport = async () => {
  if (!isFormValid.value) {
    error.value = 'Por favor completa todos los campos correctamente';
    return;
  }

  isSubmitting.value = true;
  error.value = '';
  success.value = '';

  try {
    await axiosInstance.post('/customer/reports', {
      reportable_type: props.reportableType,
      reportable_id: props.reportableId,
      reason: reportData.value.reason,
      description: reportData.value.description
    });

    success.value = 'Reporte enviado exitosamente. Gracias por tu colaboración.';
    emit('success');

    // Cerrar el modal después de 2 segundos
    setTimeout(() => {
      closeModal();
    }, 2000);
  } catch (err) {
    console.error('Error al enviar reporte:', err);
    if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat();
      error.value = errors.join(', ');
    } else {
      error.value = 'Error al enviar el reporte. Por favor intenta de nuevo.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

// Resetear el formulario cuando se abre el modal
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    resetForm();
  }
});
</script>

<style scoped>
.report-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.75);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  padding: 20px;
  box-sizing: border-box;
}

.report-modal-container {
  background: var(--color-primary);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  color: var(--color-text);
}

.report-modal-header {
  padding: 20px 24px;
  background: var(--color-secondary);
  border-bottom: 2px solid var(--color-focus);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.report-modal-header h3 {
  margin: 0;
  font-size: 1.3em;
  font-weight: 600;
  color: var(--color-text);
}

.close-button {
  background: none;
  border: none;
  color: var(--color-text);
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-button:hover {
  background: var(--color-focus);
  transform: scale(1.1);
}

.report-form {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--color-text);
  font-size: 0.95em;
}

.form-control {
  width: 100%;
  padding: 12px;
  border: 2px solid var(--color-focus);
  border-radius: 8px;
  background: var(--color-secondary);
  color: var(--color-text);
  font-size: 1em;
  font-family: inherit;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-text);
  background: var(--color-darkest);
}

select.form-control {
  cursor: pointer;
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

.char-count {
  display: block;
  margin-top: 4px;
  font-size: 0.85em;
  color: var(--color-text);
  opacity: 0.7;
  text-align: right;
}

.error-message {
  padding: 12px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid #ef4444;
  border-radius: 8px;
  color: #ef4444;
  margin-bottom: 16px;
  font-size: 0.9em;
}

.success-message {
  padding: 12px;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid #22c55e;
  border-radius: 8px;
  color: #22c55e;
  margin-bottom: 16px;
  font-size: 0.9em;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.btn-cancel,
.btn-submit {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1em;
}

.btn-cancel {
  background: var(--color-focus);
  color: var(--color-text);
}

.btn-cancel:hover:not(:disabled) {
  background: var(--color-darkest);
  transform: translateY(-2px);
}

.btn-submit {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 2px solid var(--color-focus);
}

.btn-submit:hover:not(:disabled) {
  background: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-cancel:disabled,
.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .report-modal-container {
    max-height: 95vh;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-submit {
    width: 100%;
  }
}
</style>

