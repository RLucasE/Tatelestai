<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const route = useRoute();
const router = useRouter();
const establishmentId = route.params.id;

// URL del backend para recursos estáticos (obtiene la base URL de axios sin el /api)
const BACKEND_URL = axiosInstance.defaults.baseURL.replace('/api', '');

const establishment = ref(null);
const loading = ref(true);
const error = ref(null);
const verifying = ref(false);
const rejecting = ref(false);
const rejectReason = ref('');
const showRejectModal = ref(false);

// Helpers para file_type del enum VerificationFileType: 'jpg', 'png', 'pdf'
const isImageFile = (fileType) => ['jpg', 'jpeg', 'png'].includes(fileType);
const isPdfFile = (fileType) => fileType === 'pdf';

const getFileTypeLabel = (fileType) => {
  if (isImageFile(fileType)) return 'Imagen';
  if (isPdfFile(fileType)) return 'PDF';
  return 'Documento';
};

const getFileIcon = (fileType) => {
  if (isPdfFile(fileType)) return '📄';
  if (isImageFile(fileType)) return '🖼️';
  return '📎';
};

const fetchEstablishment = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get(`/adm/pending-establishments/${establishmentId}`);
    establishment.value = response.data.data || null;
    error.value = null;
  } catch (err) {
    console.error('Error fetching establishment detail:', err);
    error.value = 'No se pudo cargar el establecimiento';
    establishment.value = null;
  } finally {
    loading.value = false;
  }
};

const verifyEstablishment = async () => {
  if (!establishment.value || verifying.value) return;
  if (!confirm('¿Seguro que deseas aprobar este establecimiento?')) return;

  try {
    verifying.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/verify`);
    alert('Establecimiento verificado y aprobado correctamente');
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error verifying establishment:', err);
    const message = err.response?.data?.message || 'Error al verificar el establecimiento';
    alert(message);
  } finally {
    verifying.value = false;
  }
};

const openRejectModal = () => {
  showRejectModal.value = true;
  rejectReason.value = '';
};

const closeRejectModal = () => {
  showRejectModal.value = false;
  rejectReason.value = '';
};

const rejectEstablishment = async () => {
  if (!establishment.value || rejecting.value) return;
  if (!rejectReason.value || rejectReason.value.trim().length < 10) {
    alert('Por favor ingresa una razón de al menos 10 caracteres');
    return;
  }

  try {
    rejecting.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/reject`, {
      reason: rejectReason.value.trim()
    });

    alert('Establecimiento rechazado correctamente');
    closeRejectModal();
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error rejecting establishment:', err);
    const message = err.response?.data?.message || 'Error al rechazar el establecimiento';
    alert(message);
  } finally {
    rejecting.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'new-sellers' });
};

const getFileUrl = (fileId) => {
  return `${BACKEND_URL}/api/admin/verification-files/${fileId}`;
};

const openFile = (file) => {
  window.open(getFileUrl(file.id), '_blank');
};

const handleImageError = (event) => {
  event.target.style.display = 'none';
  const parent = event.target.parentElement;
  if (parent && !parent.querySelector('.image-error-text')) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'image-error-text';
    errorDiv.textContent = 'Archivo no disponible';
    parent.appendChild(errorDiv);
  }
};

onMounted(fetchEstablishment);
</script>

<template>
  <div class="new-seller-page">
    <div class="new-seller-container">
      <div class="top-bar">
        <button class="back-btn" @click="goBack">
          <span class="back-icon">&larr;</span>
          <span>Volver</span>
        </button>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando información...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchEstablishment">Reintentar</button>
      </div>

      <div v-else-if="establishment" class="content">
        <div class="header">
          <h1 class="title">Revisión de Solicitud</h1>
          <div class="status-chip" :class="establishment.user?.state">
            {{ establishment.user?.state === 'waiting_for_confirmation' ? 'Pendiente' : establishment.user?.state }}
          </div>
        </div>

        <!-- Información del Usuario -->
        <section class="section">
          <h2 class="section-title">Usuario</h2>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">ID</span>
              <span class="value">#{{ establishment.user?.id }}</span>
            </div>
            <div class="info-item">
              <span class="label">Nombre completo</span>
              <span class="value">{{ establishment.user?.name }} {{ establishment.user?.last_name }}</span>
            </div>
            <div class="info-item span-full">
              <span class="label">Email</span>
              <span class="value">{{ establishment.user?.email }}</span>
            </div>
            <div class="info-item" v-if="establishment.user?.roles && establishment.user.roles.length > 0">
              <span class="label">Rol</span>
              <div class="role-badges">
                <span v-for="role in establishment.user.roles" :key="role" class="role-badge">
                  {{ role }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Información del Establecimiento -->
        <section class="section">
          <h2 class="section-title">Establecimiento</h2>
          <div class="info-grid">
            <div class="info-item span-full">
              <span class="label">Nombre</span>
              <span class="value">{{ establishment.name }}</span>
            </div>
            <div class="info-item" v-if="establishment.establishment_type">
              <span class="label">Tipo</span>
              <span class="value">{{ establishment.establishment_type.name }}</span>
            </div>
            <div class="info-item span-full">
              <span class="label">Dirección</span>
              <span class="value">{{ establishment.address || 'No especificada' }}</span>
            </div>
            <div class="info-item" v-if="establishment.phone">
              <span class="label">Teléfono</span>
              <span class="value">{{ establishment.phone }}</span>
            </div>
            <div class="info-item" v-if="establishment.verification_status">
              <span class="label">Estado de verificación</span>
              <span class="value verification-badge" :class="establishment.verification_status">
                {{ establishment.verification_status === 'pending' ? 'Pendiente' :
                  establishment.verification_status === 'approved' ? 'Aprobado' : 'Rechazado' }}
              </span>
            </div>
            <div class="info-item span-full" v-if="establishment.google_place_id">
              <span class="label">Google Place ID</span>
              <span class="value small-text">{{ establishment.google_place_id }}</span>
            </div>
            <div class="info-item" v-if="establishment.latitude">
              <span class="label">Coordenadas</span>
              <span class="value small-text">{{ establishment.latitude }}, {{ establishment.longitude }}</span>
            </div>
          </div>

          <!-- Google Places Data -->
          <div v-if="establishment.google_place_data" class="google-data-section">
            <h3 class="subsection-title">Información de Google Places</h3>
            <div class="info-grid">
              <div class="info-item" v-if="establishment.google_place_data.rating">
                <span class="label">Calificación</span>
                <span class="value">{{ establishment.google_place_data.rating }}</span>
              </div>
              <div class="info-item" v-if="establishment.google_place_data.user_ratings_total">
                <span class="label">Reseñas totales</span>
                <span class="value">{{ establishment.google_place_data.user_ratings_total }}</span>
              </div>
              <div class="info-item" v-if="establishment.google_place_data.business_status">
                <span class="label">Estado del negocio</span>
                <span class="value">{{ establishment.google_place_data.business_status }}</span>
              </div>
              <div class="info-item span-full" v-if="establishment.google_place_data.website">
                <span class="label">Sitio web</span>
                <a :href="establishment.google_place_data.website" target="_blank" class="value link">
                  {{ establishment.google_place_data.website }}
                </a>
              </div>
              <div class="info-item span-full" v-if="establishment.google_place_data.types">
                <span class="label">Categorías</span>
                <div class="tags-container">
                  <span v-for="(type, index) in establishment.google_place_data.types.slice(0, 5)"
                    :key="index" class="tag">
                    {{ type.replace(/_/g, ' ') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Archivos de Verificación -->
          <div class="files-section" v-if="establishment.verification_files && establishment.verification_files.length > 0">
            <h3 class="subsection-title">Archivos de Verificación</h3>
            <div class="files-grid">
              <div
                v-for="file in establishment.verification_files"
                :key="file.id"
                class="file-card"
                @click="openFile(file)"
              >
                <div class="file-header">
                  <span class="file-icon">{{ getFileIcon(file.file_type) }}</span>
                  <span class="file-type-label">{{ getFileTypeLabel(file.file_type) }}</span>
                </div>
                <div class="file-preview">
                  <img
                    v-if="isImageFile(file.file_type)"
                    :src="getFileUrl(file.id)"
                    alt="Archivo de verificación"
                    class="preview-img"
                    @error="handleImageError"
                  />
                  <div v-else class="pdf-preview">
                    <div class="pdf-icon">📄</div>
                    <p class="pdf-text">Documento PDF</p>
                  </div>
                </div>
                <div class="file-footer">
                  <span class="file-action">{{ isPdfFile(file.file_type) ? 'Abrir PDF' : 'Ver archivo' }} &rarr;</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Mapa (si hay coordenadas) -->
          <div v-if="establishment.latitude && establishment.longitude" class="map-section">
            <h3 class="subsection-title">Ubicación</h3>
            <div class="map-container">
              <iframe
                :src="`https://www.google.com/maps?q=${establishment.latitude},${establishment.longitude}&z=15&output=embed`"
                width="100%" height="300" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
            <a :href="`https://www.google.com/maps?q=${establishment.latitude},${establishment.longitude}`"
              target="_blank" class="map-link">
              Ver en Google Maps &rarr;
            </a>
          </div>

          <!-- Notas de Verificación (si existen) -->
          <div v-if="establishment.verification_notes" class="notes-section">
            <h3 class="subsection-title">Notas de Verificación</h3>
            <p class="notes-text">{{ establishment.verification_notes }}</p>
          </div>
        </section>

        <!-- Acciones -->
        <section class="section actions-section" v-if="establishment.user?.state === 'waiting_for_confirmation' && establishment.verification_status === 'pending'">
          <h2 class="section-title">Acciones</h2>
          <div class="actions-grid">
            <button class="action-btn approve-btn" @click="verifyEstablishment" :disabled="verifying || rejecting">
              <span v-if="verifying" class="btn-loader"></span>
              <span v-else>
                <span class="btn-icon">✓</span>
                <span>Aprobar Establecimiento</span>
              </span>
            </button>
            <button class="action-btn reject-btn" @click="openRejectModal" :disabled="rejecting || verifying">
              <span class="btn-icon">✕</span>
              <span>Rechazar Solicitud</span>
            </button>
          </div>
          <p class="action-hint">
            Al aprobar, el vendedor recibirá un correo de notificación y podrá acceder a su panel.
          </p>
        </section>

        <div v-else class="info-message">
          Esta solicitud ya ha sido procesada.
        </div>
      </div>

      <!-- Modal de Rechazo -->
      <div v-if="showRejectModal" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Rechazar Establecimiento</h3>
            <button class="modal-close" @click="closeRejectModal">✕</button>
          </div>
          <div class="modal-body">
            <label class="input-label">Razón del rechazo (mínimo 10 caracteres)</label>
            <textarea
              v-model="rejectReason"
              class="reject-textarea"
              placeholder="Explica la razón del rechazo..."
              rows="5"
              :disabled="rejecting"
            ></textarea>
            <p class="char-count" :class="{ 'valid': rejectReason.length >= 10 }">
              {{ rejectReason.length }} / 10 caracteres mínimo
            </p>
          </div>
          <div class="modal-footer">
            <button class="modal-btn cancel-btn" @click="closeRejectModal" :disabled="rejecting">
              Cancelar
            </button>
            <button class="modal-btn confirm-btn" @click="rejectEstablishment" :disabled="rejecting || rejectReason.length < 10">
              <span v-if="rejecting" class="btn-loader"></span>
              <span v-else>Rechazar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.new-seller-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem 1rem;
}

.new-seller-container {
  max-width: 800px;
  margin: 0 auto;
}

.top-bar {
  margin-bottom: 2rem;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: transparent;
  color: var(--color-text);
  border: none;
  padding: 0.5rem 0;
  cursor: pointer;
  font-size: 0.9375rem;
  font-weight: 500;
  transition: all 0.2s;
}

.back-btn:hover {
  color: var(--color-heading);
}

.back-icon {
  font-size: 1.25rem;
  transition: transform 0.2s;
}

.back-btn:hover .back-icon {
  transform: translateX(-4px);
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 2px solid var(--color-border);
  border-radius: 50%;
  border-top-color: var(--color-heading);
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-state p,
.error-state p {
  color: var(--color-text);
  margin: 0;
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.625rem 1.25rem;
  background: var(--color-background);
  color: var(--color-heading);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}

.retry-btn:hover {
  background: var(--color-background-soft);
  border-color: var(--color-heading);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
}

.title {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0;
  letter-spacing: -0.025em;
}

.status-chip {
  background: var(--color-background-soft);
  color: var(--color-text);
  padding: 0.375rem 0.875rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid var(--color-border);
  text-transform: capitalize;
  white-space: nowrap;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 1.5rem;
}

.subsection-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.8;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.info-item.span-full {
  grid-column: 1 / -1;
}

.label {
  font-size: 0.8125rem;
  color: var(--color-text);
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
}

.value {
  font-size: 0.9375rem;
  color: var(--color-heading);
  font-weight: 500;
}

.role-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.role-badge {
  background: var(--color-background-soft);
  color: var(--color-heading);
  padding: 0.25rem 0.625rem;
  border-radius: 4px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid var(--color-border);
}

.actions-section {
  border: 2px solid var(--color-border);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem 1.5rem;
  border-radius: 6px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.approve-btn {
  background: var(--color-accent);
  color: white;
}

.approve-btn:hover:not(:disabled) {
  background: var(--color-accent-hover);
  transform: translateY(-1px);
}

.reject-btn {
  background: transparent;
  color: var(--color-heading);
  border: 1px solid var(--color-border);
}

.reject-btn:hover:not(:disabled) {
  background: var(--color-background-soft);
  border-color: var(--color-heading);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-icon {
  font-size: 1.125rem;
}

.btn-loader {
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spin 0.6s linear infinite;
}

.action-hint {
  font-size: 0.8125rem;
  color: var(--color-text);
  opacity: 0.6;
  margin: 0;
  font-style: italic;
}

.info-message {
  padding: 1rem;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  text-align: center;
  color: var(--color-text);
  font-size: 0.9375rem;
}

.verification-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.8125rem;
  font-weight: 600;
}

.verification-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.verification-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.verification-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.small-text {
  font-size: 0.8125rem;
  font-family: monospace;
}

.link {
  color: var(--color-accent);
  text-decoration: none;
  transition: color 0.2s;
}

.link:hover {
  color: var(--color-accent-hover);
  text-decoration: underline;
}

.google-data-section,
.files-section,
.map-section,
.notes-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag {
  background: var(--color-background-soft);
  color: var(--color-heading);
  padding: 0.375rem 0.75rem;
  border-radius: 4px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid var(--color-border);
  text-transform: capitalize;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.file-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.2s;
  cursor: pointer;
}

.file-card:hover {
  border-color: var(--color-heading);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.file-header {
  padding: 0.75rem 1rem;
  background: var(--color-background);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.file-icon {
  font-size: 1.25rem;
}

.file-type-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-heading);
}

.preview-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
}

.file-preview {
  position: relative;
  width: 100%;
  height: 300px;
}

.pdf-preview {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--color-background-soft);
  gap: 0.5rem;
}

.pdf-icon {
  font-size: 4rem;
  opacity: 0.5;
}

.pdf-text {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
}

.file-footer {
  padding: 0.75rem 1rem;
  background: var(--color-background);
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
}

.file-action {
  font-size: 0.875rem;
  color: var(--color-accent);
  font-weight: 500;
  transition: all 0.2s;
}

.file-card:hover .file-action {
  transform: translateX(4px);
}

.image-error-text {
  width: 100%;
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background-soft);
  color: var(--color-text);
  font-size: 0.875rem;
  opacity: 0.6;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0;
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: var(--color-text);
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.modal-close:hover {
  background: var(--color-background-soft);
  color: var(--color-heading);
}

.modal-body {
  padding: 1.5rem;
}

.input-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-heading);
  margin-bottom: 0.5rem;
}

.reject-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: 0.9375rem;
  font-family: inherit;
  color: var(--color-heading);
  background: var(--color-background-soft);
  resize: vertical;
  transition: border-color 0.2s;
}

.reject-textarea:focus {
  outline: none;
  border-color: var(--color-accent);
}

.reject-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.char-count {
  font-size: 0.8125rem;
  color: var(--color-text);
  opacity: 0.6;
  margin: 0.5rem 0 0;
  text-align: right;
}

.char-count.valid {
  color: var(--color-accent);
  opacity: 1;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--color-border);
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.modal-btn {
  padding: 0.625rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 100px;
}

.cancel-btn {
  background: transparent;
  color: var(--color-heading);
  border: 1px solid var(--color-border);
}

.cancel-btn:hover:not(:disabled) {
  background: var(--color-background-soft);
  border-color: var(--color-heading);
}

.confirm-btn {
  background: #dc2626;
  color: white;
}

.confirm-btn:hover:not(:disabled) {
  background: #b91c1c;
}

.modal-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.map-container {
  margin-bottom: 0.75rem;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.map-link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: var(--color-accent);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.map-link:hover {
  color: var(--color-accent-hover);
  text-decoration: underline;
}

.notes-section {
  background: var(--color-background-soft);
  padding: 1rem;
  border-radius: 6px;
  margin-top: 1.5rem;
}

.notes-text {
  font-size: 0.9375rem;
  color: var(--color-text);
  margin: 0;
  line-height: 1.6;
  white-space: pre-wrap;
}

@media (max-width: 640px) {
  .new-seller-page {
    padding: 1.5rem 1rem;
  }

  .header {
    flex-direction: column;
    align-items: flex-start;
  }

  .title {
    font-size: 1.5rem;
  }

  .info-grid,
  .actions-grid {
    grid-template-columns: 1fr;
  }

  .info-item.span-full {
    grid-column: 1;
  }

  .files-grid {
    grid-template-columns: 1fr;
  }

  .preview-img {
    height: 250px;
  }

  .file-preview {
    height: 250px;
  }

  .modal-content {
    margin: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .modal-btn {
    width: 100%;
  }
}
</style>

