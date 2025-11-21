<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axiosInstance from '@/lib/axios.js';
import ReportCard from '@/components/layouts/admin/ReportCard.vue';

const reports = ref([]);
const loading = ref(true);
const error = ref(null);
const currentPage = ref(1);
const perPage = ref(15);
const total = ref(0);
const totalPages = ref(0);

// Filtros
const statusFilter = ref('all');
const typeFilter = ref('all');

const statusOptions = [
  { value: 'all', label: 'Todos los estados' },
  { value: 'pending', label: 'Pendientes' },
  { value: 'reviewing', label: 'En revisión' },
  { value: 'resolved', label: 'Resueltos' },
  { value: 'dismissed', label: 'Descartados' }
];

const typeOptions = [
  { value: 'all', label: 'Todos los tipos' },
  { value: 'App\\Models\\Offer', label: 'Ofertas' },
  { value: 'App\\Models\\FoodEstablishment', label: 'Establecimientos' },
  { value: 'App\\Models\\User', label: 'Usuarios' }
];

const getReports = async (page = 1) => {
  try {
    loading.value = true;
    error.value = null;

    const params = {
      page,
      per_page: perPage.value
    };

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value;
    }

    if (typeFilter.value !== 'all') {
      params.reportable_type = typeFilter.value;
    }

    const response = await axiosInstance.get('/adm/reports', { params });

    reports.value = response.data.data || [];
    currentPage.value = response.data.current_page || 1;
    total.value = response.data.total || 0;
    totalPages.value = response.data.last_page || 1;
    perPage.value = response.data.per_page || 15;

  } catch (err) {
    console.error('Error fetching reports:', err);
    error.value = 'No se pudieron cargar los reportes';
    reports.value = [];
  } finally {
    loading.value = false;
  }
};

const handleStatusUpdate = async (reportId, newStatus, adminNotes) => {
  try {
    await axiosInstance.patch(`/adm/reports/${reportId}/status`, {
      status: newStatus,
      admin_notes: adminNotes || undefined
    });

    // Recargar los reportes después de actualizar
    await getReports(currentPage.value);
  } catch (err) {
    console.error('Error updating report status:', err);
    throw err; // Re-lanzar para que el componente hijo lo maneje
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    getReports(page);
  }
};

const resetFilters = () => {
  statusFilter.value = 'all';
  typeFilter.value = 'all';
  currentPage.value = 1;
  getReports(1);
};

// Watch para recargar cuando cambien los filtros
watch([statusFilter, typeFilter], () => {
  currentPage.value = 1;
  getReports(1);
});

const paginationPages = computed(() => {
  const pages = [];
  const maxPages = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
  let end = Math.min(totalPages.value, start + maxPages - 1);

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

onMounted(() => {
  getReports();
});
</script>

<template>
  <div class="reports-page">
    <div class="page-header">
      <h1 class="page-title">Gestión de Reportes</h1>
      <p class="page-subtitle">
        Administra los reportes realizados por los usuarios
      </p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label for="status-filter">Estado:</label>
          <select
            id="status-filter"
            v-model="statusFilter"
            class="filter-select"
          >
            <option
              v-for="option in statusOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="type-filter">Tipo:</label>
          <select
            id="type-filter"
            v-model="typeFilter"
            class="filter-select"
          >
            <option
              v-for="option in typeOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>

        <button
          class="reset-filters-btn"
          @click="resetFilters"
        >
          Limpiar filtros
        </button>
      </div>

      <div class="results-info">
        <span v-if="!loading">
          Mostrando {{ reports.length }} de {{ total }} reportes
        </span>
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando reportes...</p>
    </div>

    <!-- Estado de error -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button class="retry-btn" @click="getReports(currentPage)">
        Reintentar
      </button>
    </div>

    <!-- Sin resultados -->
    <div v-else-if="reports.length === 0" class="empty-state">
      <p>No se encontraron reportes con los filtros seleccionados</p>
    </div>

    <!-- Lista de reportes -->
    <div v-else class="reports-list">
      <ReportCard
        v-for="report in reports"
        :key="report.id"
        :report="report"
        @status-updated="handleStatusUpdate"
      />
    </div>

    <!-- Paginación -->
    <div v-if="totalPages > 1 && !loading" class="pagination">
      <button
        class="pagination-btn"
        :disabled="currentPage === 1"
        @click="changePage(currentPage - 1)"
      >
        Anterior
      </button>

      <button
        v-if="paginationPages[0] > 1"
        class="pagination-btn"
        @click="changePage(1)"
      >
        1
      </button>

      <span v-if="paginationPages[0] > 2" class="pagination-ellipsis">...</span>

      <button
        v-for="page in paginationPages"
        :key="page"
        class="pagination-btn"
        :class="{ active: page === currentPage }"
        @click="changePage(page)"
      >
        {{ page }}
      </button>

      <span v-if="paginationPages[paginationPages.length - 1] < totalPages - 1" class="pagination-ellipsis">...</span>

      <button
        v-if="paginationPages[paginationPages.length - 1] < totalPages"
        class="pagination-btn"
        @click="changePage(totalPages)"
      >
        {{ totalPages }}
      </button>

      <button
        class="pagination-btn"
        :disabled="currentPage === totalPages"
        @click="changePage(currentPage + 1)"
      >
        Siguiente
      </button>
    </div>
  </div>
</template>

<style scoped>
.reports-page {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  font-size: 1rem;
  color: var(--color-text);
  opacity: 0.7;
  margin: 0;
}

/* Filtros */
.filters-section {
  background: var(--color-primary);
  padding: 1.5rem;
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
  margin-bottom: 1.5rem;
}

.filters-row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.filter-select {
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  color: var(--color-text);
  background-color: var(--color-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.filter-select:hover {
  border-color: var(--color-border-hover);
  background-color: var(--color-focus);
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

.reset-filters-btn {
  padding: 0.625rem 1.25rem;
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.reset-filters-btn:hover {
  background: var(--color-focus);
}

.results-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.8;
}

/* Estados */
.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  background: var(--color-primary);
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state p {
  color: var(--color-danger);
  margin-bottom: 1rem;
}

.retry-btn {
  padding: 0.625rem 1.25rem;
  background: var(--color-accent);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.retry-btn:hover {
  background: var(--color-accent-hover);
}

.empty-state p {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 1rem;
}

/* Lista de reportes */
.reports-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Paginación */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding: 1.5rem;
  background: var(--color-primary);
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  background: var(--color-secondary);
  color: var(--color-text);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 40px;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--color-focus);
  border-color: var(--color-border-hover);
}

.pagination-btn.active {
  background: var(--color-accent);
  color: var(--color-text);
  border-color: var(--color-accent);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-ellipsis {
  padding: 0.5rem;
  color: var(--color-text);
  opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
  .reports-page {
    padding: 1rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .filters-row {
    flex-direction: column;
  }

  .filter-group {
    width: 100%;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>

