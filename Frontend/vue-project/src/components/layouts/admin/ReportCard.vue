<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  report: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['status-updated']);

const showDetails = ref(false);
const isUpdating = ref(false);
const showUpdateModal = ref(false);
const selectedStatus = ref('');
const adminNotes = ref('');
const updateError = ref(null);

const reportableTypeName = computed(() => {
  const type = props.report.reportable_type;
  if (type.includes('Offer')) return 'Oferta';
  if (type.includes('FoodEstablishment')) return 'Establecimiento';
  if (type.includes('User')) return 'Usuario';
  return 'Desconocido';
});

const statusLabel = computed(() => {
  const labels = {
    'pending': 'Pendiente',
    'reviewing': 'En revisión',
    'resolved': 'Resuelto',
    'dismissed': 'Descartado'
  };
  return labels[props.report.status] || props.report.status;
});

const statusBadgeClass = computed(() => {
  const classes = {
    'pending': 'badge-pending',
    'reviewing': 'badge-reviewing',
    'resolved': 'badge-resolved',
    'dismissed': 'badge-dismissed'
  };
  return classes[props.report.status] || 'badge-default';
});

const reasonLabel = computed(() => {
  const labels = {
    'inappropriate': 'Contenido inapropiado',
    'fraud': 'Fraude',
    'false_information': 'Información falsa',
    'spam': 'Spam',
    'offensive': 'Ofensivo',
    'other': 'Otro'
  };
  return labels[props.report.reason] || props.report.reason;
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const openUpdateModal = () => {
  selectedStatus.value = props.report.status;
  adminNotes.value = props.report.admin_notes || '';
  updateError.value = null;
  showUpdateModal.value = true;
};

const closeUpdateModal = () => {
  showUpdateModal.value = false;
  selectedStatus.value = '';
  adminNotes.value = '';
  updateError.value = null;
};

const updateStatus = async () => {
  if (!selectedStatus.value) {
    updateError.value = 'Debes seleccionar un estado';
    return;
  }

  try {
    isUpdating.value = true;
    updateError.value = null;

    await emit('status-updated', props.report.id, selectedStatus.value, adminNotes.value);

    closeUpdateModal();
  } catch (err) {
    console.error('Error updating status:', err);
    updateError.value = err.response?.data?.message || 'Error al actualizar el estado';
  } finally {
    isUpdating.value = false;
  }
};

const toggleDetails = () => {
  showDetails.value = !showDetails.value;
};
</script>

<template>
  <div class="report-card">
    <div class="report-header">
      <div class="report-info">
        <div class="report-id">
          <span class="label">ID:</span>
          <span class="value">#{{ report.id }}</span>
        </div>
        <div class="report-type">
          <span class="type-badge">{{ reportableTypeName }}</span>
        </div>
        <div class="report-status">
          <span class="status-badge" :class="statusBadgeClass">
            {{ statusLabel }}
          </span>
        </div>
      </div>

      <div class="report-actions">
        <button
          class="action-btn"
          @click="toggleDetails"
        >
          {{ showDetails ? 'Ocultar' : 'Ver detalles' }}
        </button>
        <button
          class="action-btn primary"
          @click="openUpdateModal"
        >
          Actualizar estado
        </button>
      </div>
    </div>

    <div class="report-summary">
      <div class="summary-item">
        <span class="summary-label">Razón:</span>
        <span class="summary-value">{{ reasonLabel }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Reportado por:</span>
        <span class="summary-value">{{ report.reporter?.name || 'N/A' }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Fecha:</span>
        <span class="summary-value">{{ formatDate(report.created_at) }}</span>
      </div>
    </div>

    <div v-if="report.description" class="report-description">
      <p>{{ report.description }}</p>
    </div>

    <!-- Detalles expandidos -->
    <div v-if="showDetails" class="report-details">
      <div class="details-section">
        <h4 class="section-title">Información del Reportable</h4>
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Tipo:</span>
            <span class="detail-value">{{ report.reportable_type }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">ID:</span>
            <span class="detail-value">{{ report.reportable_id }}</span>
          </div>
          <div v-if="report.reportable" class="detail-item full-width">
            <span class="detail-label">Detalles:</span>
            <pre class="detail-value code">{{ JSON.stringify(report.reportable, null, 2) }}</pre>
          </div>
        </div>
      </div>

      <div class="details-section">
        <h4 class="section-title">Información del Reportador</h4>
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Nombre:</span>
            <span class="detail-value">{{ report.reporter?.name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value">{{ report.reporter?.email || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">ID:</span>
            <span class="detail-value">{{ report.reported_by }}</span>
          </div>
        </div>
      </div>

      <div v-if="report.reviewer" class="details-section">
        <h4 class="section-title">Información de Revisión</h4>
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Revisado por:</span>
            <span class="detail-value">{{ report.reviewer?.name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Fecha de revisión:</span>
            <span class="detail-value">{{ formatDate(report.reviewed_at) }}</span>
          </div>
          <div v-if="report.admin_notes" class="detail-item full-width">
            <span class="detail-label">Notas del administrador:</span>
            <p class="detail-value">{{ report.admin_notes }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de actualización de estado -->
    <Teleport to="body">
      <div v-if="showUpdateModal" class="modal-overlay" @click.self="closeUpdateModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Actualizar estado del reporte #{{ report.id }}</h3>
            <button class="close-btn" @click="closeUpdateModal">&times;</button>
          </div>

          <div class="modal-body">
            <div v-if="updateError" class="error-message">
              {{ updateError }}
            </div>

            <div class="form-group">
              <label for="status-select">Nuevo estado:</label>
              <select
                id="status-select"
                v-model="selectedStatus"
                class="form-select"
              >
                <option value="pending">Pendiente</option>
                <option value="reviewing">En revisión</option>
                <option value="resolved">Resuelto</option>
                <option value="dismissed">Descartado</option>
              </select>
            </div>

            <div class="form-group">
              <label for="admin-notes">Notas del administrador:</label>
              <textarea
                id="admin-notes"
                v-model="adminNotes"
                class="form-textarea"
                rows="4"
                maxlength="1000"
                placeholder="Escribe aquí las notas sobre este reporte (opcional)..."
              ></textarea>
              <span class="char-count">{{ adminNotes.length }}/1000</span>
            </div>
          </div>

          <div class="modal-footer">
            <button
              class="btn btn-secondary"
              @click="closeUpdateModal"
              :disabled="isUpdating"
            >
              Cancelar
            </button>
            <button
              class="btn btn-primary"
              @click="updateStatus"
              :disabled="isUpdating"
            >
              {{ isUpdating ? 'Actualizando...' : 'Actualizar' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.report-card {
  background: var(--color-primary);
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
  overflow: hidden;
  transition: all 0.2s;
}

.report-card:hover {
  border-color: var(--color-border-hover);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.report-header {
  padding: 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border);
  gap: 1rem;
  flex-wrap: wrap;
}

.report-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.report-id {
  font-weight: 600;
  color: var(--color-text);
}

.report-id .label {
  color: var(--color-text);
  opacity: 0.7;
  margin-right: 0.25rem;
}

.type-badge {
  padding: 0.25rem 0.75rem;
  background: color-mix(in oklab, var(--color-info), transparent 70%);
  color: var(--color-info);
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-pending {
  background: color-mix(in oklab, var(--color-warning), transparent 70%);
  color: var(--color-warning);
}

.badge-reviewing {
  background: color-mix(in oklab, var(--color-info), transparent 70%);
  color: var(--color-info);
}

.badge-resolved {
  background: color-mix(in oklab, var(--color-success), transparent 70%);
  color: var(--color-success);
}

.badge-dismissed {
  background: color-mix(in oklab, var(--color-danger), transparent 70%);
  color: var(--color-danger);
}

.report-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  background: var(--color-secondary);
  color: var(--color-text);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--color-focus);
}

.action-btn.primary {
  background: var(--color-accent);
  color: var(--color-text);
  border-color: var(--color-accent);
}

.action-btn.primary:hover {
  background: var(--color-accent-hover);
}

.report-summary {
  padding: 1rem 1.25rem;
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
  background: var(--color-secondary);
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-label {
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.7;
  font-weight: 500;
}

.summary-value {
  font-size: 0.875rem;
  color: var(--color-text);
  font-weight: 500;
}

.report-description {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border);
}

.report-description p {
  margin: 0;
  color: var(--color-text);
  opacity: 0.9;
  line-height: 1.6;
}

.report-details {
  padding: 1.25rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-secondary);
}

.details-section {
  margin-bottom: 1.5rem;
}

.details-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 1rem 0;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.7;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.detail-value {
  font-size: 0.875rem;
  color: var(--color-text);
}

.detail-value.code {
  background: var(--color-darkest);
  color: var(--color-text);
  padding: 0.75rem;
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
  font-family: 'Courier New', monospace;
  font-size: 0.75rem;
  overflow-x: auto;
  margin: 0.5rem 0 0 0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--color-text);
  opacity: 0.7;
  cursor: pointer;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--color-secondary);
  opacity: 1;
}

.modal-body {
  padding: 1.5rem;
}

.error-message {
  padding: 0.75rem 1rem;
  background: color-mix(in oklab, var(--color-danger), transparent 70%);
  color: var(--color-danger);
  border-radius: var(--border-radius);
  border: 1px solid var(--color-danger);
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.form-select,
.form-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  color: var(--color-text);
  background: var(--color-secondary);
  transition: all 0.2s;
}

.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.char-count {
  display: block;
  text-align: right;
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.7;
  margin-top: 0.25rem;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-focus);
}

.btn-primary {
  background: var(--color-accent);
  color: var(--color-text);
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-accent-hover);
}

/* Responsive */
@media (max-width: 768px) {
  .report-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .report-info {
    width: 100%;
  }

  .report-actions {
    width: 100%;
  }

  .action-btn {
    flex: 1;
  }

  .report-summary {
    flex-direction: column;
    gap: 1rem;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>

