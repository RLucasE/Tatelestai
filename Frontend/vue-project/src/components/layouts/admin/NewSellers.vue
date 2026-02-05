<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const pendingEstablishments = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchPendingEstablishments = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get('/adm/pending-establishments');
    // La API devuelve { message, data }
    pendingEstablishments.value = Array.isArray(response.data.data) ? response.data.data : [];
    error.value = null;
  } catch (err) {
    console.error('Error fetching pending establishments:', err);
    error.value = 'No se pudieron cargar las solicitudes de vendedores';
    pendingEstablishments.value = [];
  } finally {
    loading.value = false;
  }
};

const viewEstablishmentDetail = (establishmentId) => {
  router.push({ name: 'new-seller', params: { id: establishmentId } });
};

onMounted(fetchPendingEstablishments);
</script>

<template>
  <div class="new-sellers-page">
    <div class="new-sellers-container">
      <div class="header">
        <h1 class="title">Solicitudes Pendientes</h1>
        <p class="subtitle">{{ pendingEstablishments.length }} establecimiento{{ pendingEstablishments.length !== 1 ? 's' : '' }} esperando aprobación</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando solicitudes...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchPendingEstablishments">Reintentar</button>
      </div>

      <div v-else class="content">
        <div v-if="pendingEstablishments.length === 0" class="empty-state">
          <div class="empty-icon">✓</div>
          <p class="empty-title">Todo al día</p>
          <p class="empty-text">No hay solicitudes pendientes en este momento</p>
        </div>

        <div v-else class="sellers-list">
          <div
            v-for="establishment in pendingEstablishments"
            :key="establishment.id"
            class="seller-card"
            @click="viewEstablishmentDetail(establishment.id)"
          >
            <div class="card-header">
              <div class="user-info">
                <h3 class="name">{{ establishment.user?.name }} {{ establishment.user?.last_name }}</h3>
                <p class="email">{{ establishment.user?.email }}</p>
              </div>
              <div class="status-badge">Pendiente</div>
            </div>

            <div class="card-body">
              <div class="info-row">
                <span class="info-label">Establecimiento</span>
                <span class="info-value">{{ establishment.name }}</span>
              </div>
              <div class="info-row" v-if="establishment.address">
                <span class="info-label">Dirección</span>
                <span class="info-value">{{ establishment.address }}</span>
              </div>
              <div class="info-row" v-if="establishment.establishment_type">
                <span class="info-label">Tipo</span>
                <span class="info-value">{{ establishment.establishment_type.name }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Verificación</span>
                <span class="verification-badge" :class="establishment.verification_status">
                  {{ establishment.verification_status === 'pending' ? 'Pendiente' :
                     establishment.verification_status === 'approved' ? 'Aprobado' : 'Rechazado' }}
                </span>
              </div>
              <div class="info-row" v-if="establishment.google_place_id">
                <span class="info-label">Google Places</span>
                <span class="info-value">Verificado</span>
              </div>
            </div>

            <div class="card-footer">
              <span class="view-link">Ver detalles →</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.new-sellers-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem 1rem;
}

.new-sellers-container {
  max-width: 1000px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2.5rem;
}

.title {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 0.5rem;
  letter-spacing: -0.025em;
}

.subtitle {
  font-size: 0.9375rem;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: var(--color-text);
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
  to { transform: rotate(360deg); }
}

.error-state {
  text-align: center;
  padding: 3rem 2rem;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.error-state p {
  color: var(--color-text);
  margin: 0 0 1rem;
}

.retry-btn {
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

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.empty-icon {
  font-size: 3rem;
  opacity: 0.3;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 0.5rem;
}

.empty-text {
  font-size: 0.9375rem;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
}

.sellers-list {
  display: grid;
  gap: 1rem;
}

.seller-card {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.2s;
}

.seller-card:hover {
  border-color: var(--color-heading);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 0.25rem;
}

.email {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-badge {
  background: var(--color-background-soft);
  color: var(--color-text);
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid var(--color-border);
  white-space: nowrap;
}

.card-body {
  padding: 1rem 0;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.info-label {
  font-size: 0.8125rem;
  color: var(--color-text);
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
}

.info-value {
  font-size: 0.9375rem;
  color: var(--color-heading);
  font-weight: 500;
  text-align: right;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  justify-content: flex-end;
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

.google-verified {
  color: #10b981;
  font-weight: 600;
}

.card-footer {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}

.view-link {
  font-size: 0.875rem;
  color: var(--color-heading);
  font-weight: 500;
  transition: all 0.2s;
}

.seller-card:hover .view-link {
  transform: translateX(4px);
}

@media (max-width: 640px) {
  .new-sellers-page {
    padding: 1.5rem 1rem;
  }

  .title {
    font-size: 1.5rem;
  }

  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .info-value {
    text-align: left;
  }
}
</style>
