<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const route = useRoute();
const router = useRouter();
const sellerId = route.params.id;

// URL del backend para recursos estáticos (obtiene la base URL de axios sin el /api)
const BACKEND_URL = axiosInstance.defaults.baseURL.replace('/api', '');

const seller = ref(null);
const loading = ref(true);
const error = ref(null);
const activating = ref(false);
const rejecting = ref(false);

const fetchSeller = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get(`/new-sellers/${sellerId}`);
    seller.value = data || null;
    error.value = null;
  } catch (err) {
    console.error('Error fetching seller detail:', err);
    error.value = 'No se pudo cargar el vendedor';
    seller.value = null;
  } finally {
    loading.value = false;
  }
};

const activateSeller = async () => {
  if (!seller.value || activating.value) return;

  try {
    activating.value = true;
    await axiosInstance.patch(`/users/${sellerId}/activate-seller`);

    // Mostrar mensaje de éxito
    alert('Vendedor activado correctamente');

    // Redirigir a la lista de nuevos vendedores
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error activating seller:', err);
    const message = err.response?.data?.message || 'Error al activar el vendedor';
    alert(message);
  } finally {
    activating.value = false;
  }
};

const denySeller = async () => {
  if (!seller.value || rejecting.value) return;
  if (!confirm('¿Seguro que deseas rechazar este establecimiento?')) return;
  try {
    rejecting.value = true;
    await axiosInstance.patch(`/users/${sellerId}/denie-seller`);
    alert('Establecimiento rechazado correctamente');
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error denying seller:', err);
    const message = err.response?.data?.message || 'Error al rechazar el establecimiento';
    alert(message);
  } finally {
    rejecting.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'new-sellers' });
};

const handleImageError = (event) => {
  event.target.style.display = 'none';
  const parent = event.target.parentElement;
  if (parent && !parent.querySelector('.image-error-text')) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'image-error-text';
    errorDiv.textContent = 'Imagen no disponible';
    parent.appendChild(errorDiv);
  }
};

onMounted(fetchSeller);
</script>

<template>
  <div class="new-seller-page">
    <div class="new-seller-container">
      <div class="top-bar">
        <button class="back-btn" @click="goBack">
          <span class="back-icon">←</span>
          <span>Volver</span>
        </button>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando información...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchSeller">Reintentar</button>
      </div>

      <div v-else-if="seller" class="content">
        <div class="header">
          <h1 class="title">Revisión de Solicitud</h1>
          <div class="status-chip" :class="seller.state">
            {{ seller.state === 'waiting_for_confirmation' ? 'Pendiente' : seller.state }}
          </div>
        </div>

        <!-- Información del Usuario -->
        <section class="section">
          <h2 class="section-title">Usuario</h2>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">ID</span>
              <span class="value">#{{ seller.id }}</span>
            </div>
            <div class="info-item">
              <span class="label">Nombre completo</span>
              <span class="value">{{ seller.name }} {{ seller.last_name }}</span>
            </div>
            <div class="info-item span-full">
              <span class="label">Email</span>
              <span class="value">{{ seller.email }}</span>
            </div>
            <div class="info-item" v-if="seller.roles && seller.roles.length > 0">
              <span class="label">Rol</span>
              <div class="role-badges">
                <span v-for="role in seller.roles" :key="role" class="role-badge">
                  {{ role.name }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Información del Establecimiento -->
        <section class="section" v-if="seller.food_establishment">
          <h2 class="section-title">Establecimiento</h2>
          <div class="info-grid">
            <div class="info-item span-full">
              <span class="label">Nombre</span>
              <span class="value">{{ seller.food_establishment.name }}</span>
            </div>
            <div class="info-item" v-if="seller.food_establishment.establishment_type">
              <span class="label">Tipo</span>
              <span class="value">{{ seller.food_establishment.establishment_type.name }}</span>
            </div>
            <div class="info-item span-full">
              <span class="label">Dirección</span>
              <span class="value">{{ seller.food_establishment.address || 'No especificada' }}</span>
            </div>
            <div class="info-item" v-if="seller.food_establishment.phone">
              <span class="label">Teléfono</span>
              <span class="value">{{ seller.food_establishment.phone }}</span>
            </div>
            <div class="info-item" v-if="seller.food_establishment.verification_status">
              <span class="label">Estado de verificación</span>
              <span class="value verification-badge" :class="seller.food_establishment.verification_status">
                {{ seller.food_establishment.verification_status === 'pending' ? 'Pendiente' :
                  seller.food_establishment.verification_status === 'approved' ? 'Aprobado' : 'Rechazado' }}
              </span>
            </div>
            <div class="info-item span-full" v-if="seller.food_establishment.google_place_id">
              <span class="label">Google Place ID</span>
              <span class="value small-text">{{ seller.food_establishment.google_place_id }}</span>
            </div>
            <div class="info-item" v-if="seller.food_establishment.latitude">
              <span class="label">Coordenadas</span>
              <span class="value small-text">{{ seller.food_establishment.latitude }}, {{
                seller.food_establishment.longitude }}</span>
            </div>
          </div>

          <!-- Google Places Data -->
          <div v-if="seller.food_establishment.google_place_data" class="google-data-section">
            <h3 class="subsection-title">Información de Google Places</h3>
            <div class="info-grid">
              <div class="info-item" v-if="seller.food_establishment.google_place_data.rating">
                <span class="label">Calificación</span>
                <span class="value">⭐ {{ seller.food_establishment.google_place_data.rating }}</span>
              </div>
              <div class="info-item" v-if="seller.food_establishment.google_place_data.user_ratings_total">
                <span class="label">Reseñas totales</span>
                <span class="value">{{ seller.food_establishment.google_place_data.user_ratings_total }}</span>
              </div>
              <div class="info-item" v-if="seller.food_establishment.google_place_data.business_status">
                <span class="label">Estado del negocio</span>
                <span class="value">{{ seller.food_establishment.google_place_data.business_status }}</span>
              </div>
              <div class="info-item span-full" v-if="seller.food_establishment.google_place_data.website">
                <span class="label">Sitio web</span>
                <a :href="seller.food_establishment.google_place_data.website" target="_blank" class="value link">
                  {{ seller.food_establishment.google_place_data.website }}
                </a>
              </div>
              <div class="info-item span-full" v-if="seller.food_establishment.google_place_data.types">
                <span class="label">Categorías</span>
                <div class="tags-container">
                  <span v-for="(type, index) in seller.food_establishment.google_place_data.types.slice(0, 5)"
                    :key="index" class="tag">
                    {{ type.replace(/_/g, ' ') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Fotos de Verificación -->
          <div class="photos-section">
            <h3 class="subsection-title">Fotos de Verificación</h3>
            <div class="photos-grid">
              <div class="photo-card" v-if="seller.food_establishment.establishment_photo">
                <div class="photo-header">
                  <span class="photo-label">📷 Foto del Establecimiento</span>
                </div>
                <img :src="`${BACKEND_URL}/storage/${seller.food_establishment.establishment_photo}`"
                  alt="Foto del establecimiento" class="photo-img" @error="handleImageError" />
              </div>
              <div class="photo-card" v-if="seller.food_establishment.owner_selfie">
                <div class="photo-header">
                  <span class="photo-label">🤳 Selfie del Propietario</span>
                </div>
                <img :src="`${BACKEND_URL}/storage/${seller.food_establishment.owner_selfie}`"
                  alt="Selfie del propietario" class="photo-img" @error="handleImageError" />
              </div>
            </div>
          </div>

          <!-- Mapa (si hay coordenadas) -->
          <div v-if="seller.food_establishment.latitude && seller.food_establishment.longitude" class="map-section">
            <h3 class="subsection-title">Ubicación</h3>
            <div class="map-container">
              <iframe
                :src="`https://www.google.com/maps?q=${seller.food_establishment.latitude},${seller.food_establishment.longitude}&z=15&output=embed`"
                width="100%" height="300" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
            <a :href="`https://www.google.com/maps?q=${seller.food_establishment.latitude},${seller.food_establishment.longitude}`"
              target="_blank" class="map-link">
              Ver en Google Maps →
            </a>
          </div>

          <!-- Notas de Verificación (si existen) -->
          <div v-if="seller.food_establishment.verification_notes" class="notes-section">
            <h3 class="subsection-title">Notas de Verificación</h3>
            <p class="notes-text">{{ seller.food_establishment.verification_notes }}</p>
          </div>
        </section>

        <!-- Acciones -->
        <section class="section actions-section" v-if="seller.state === 'waiting_for_confirmation'">
          <h2 class="section-title">Acciones</h2>
          <div class="actions-grid">
            <button class="action-btn approve-btn" @click="activateSeller" :disabled="activating || rejecting">
              <span v-if="activating" class="btn-loader"></span>
              <span v-else>
                <span class="btn-icon">✓</span>
                <span>Aprobar Vendedor</span>
              </span>
            </button>
            <button class="action-btn reject-btn" @click="denySeller" :disabled="rejecting || activating">
              <span v-if="rejecting" class="btn-loader"></span>
              <span v-else>
                <span class="btn-icon">✕</span>
                <span>Rechazar Solicitud</span>
              </span>
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
.photos-section,
.map-section,
.notes-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
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

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.photo-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  overflow: hidden;
}

.photo-header {
  padding: 0.75rem 1rem;
  background: var(--color-background);
  border-bottom: 1px solid var(--color-border);
}

.photo-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-heading);
}

.photo-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
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

  .photos-grid {
    grid-template-columns: 1fr;
  }

  .photo-img {
    height: 250px;
  }
}
</style>
