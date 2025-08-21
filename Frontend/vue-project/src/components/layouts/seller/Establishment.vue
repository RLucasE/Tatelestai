<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const establishment = ref(null);
const loading = ref(true);
const error = ref(null);

const fetchEstablishment = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get('/my-establishment');
    establishment.value = data || null;
    error.value = null;
  } catch (err) {
    console.error('Error fetching establishment:', err);
    error.value = 'No se pudo cargar el establecimiento';
    establishment.value = null;
  } finally {
    loading.value = false;
  }
};

const editEstablishment = () => {
  router.push('/seller/establishment/edit');
};

onMounted(fetchEstablishment);
</script>

<template>
  <div class="establishment-page">
    <div class="establishment-container">
      <div class="header">
        <h1 class="title">Mi Establecimiento</h1>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando información del establecimiento...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchEstablishment">Reintentar</button>
      </div>

      <div v-else-if="establishment" class="content">
        <!-- Información del establecimiento -->
        <section class="card">
          <h2 class="section-title">Información del Establecimiento</h2>
          <div class="data-grid">
            <div class="data-item">
              <span class="label">ID del Establecimiento</span>
              <span class="value">{{ establishment.id }}</span>
            </div>
            <div class="data-item full">
              <span class="label">Nombre del Establecimiento</span>
              <span class="value">{{ establishment.name }}</span>
            </div>
            <div class="data-item" v-if="establishment.establishment_type">
              <span class="label">Tipo de Establecimiento</span>
              <span class="value">{{ establishment.establishment_type.name }}</span>
            </div>
            <div class="data-item">
              <span class="label">Fecha de Creación</span>
              <span class="value">{{ new Date(establishment.created_at).toLocaleDateString('es-ES') }}</span>
            </div>
            <div class="data-item">
              <span class="label">Última Actualización</span>
              <span class="value">{{ new Date(establishment.updated_at).toLocaleDateString('es-ES') }}</span>
            </div>
          </div>
        </section>

        <!-- Botón de editar establecimiento -->
        <div class="action-section">
          <button class="edit-btn" @click="editEstablishment">
            Editar establecimiento
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.establishment-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem;
}

.establishment-container {
  max-width: 800px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--color-text);
  position: relative;
}

.back-btn {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.title {
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.edit-btn {
  position: relative;
  right: 0;
  top: 0;
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  margin-top: 1.5rem;
}

.edit-btn:hover {
  background: var(--color-focus);
  transform: translateY(-2px);
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

.card {
  background: var(--color-background);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 8px 32px rgba(34, 32, 31, 0.2);
  border: 1px solid var(--color-border);
}

.section-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.data-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.data-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.data-item.full {
  grid-column: 1 / -1;
}

.label {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.value {
  color: var(--color-heading);
  font-size: 1.1rem;
}

.action-section {
  display: flex;
  justify-content: center;
  padding: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .establishment-page {
    padding: 1rem;
  }

  .data-grid {
    grid-template-columns: 1fr;
  }

  .header {
    margin-bottom: 1rem;
  }

  .title {
    font-size: 1.8rem;
  }
}
</style>