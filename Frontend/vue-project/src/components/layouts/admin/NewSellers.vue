<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const establishments = ref([]);
const loading = ref(true);
const error = ref(null);

// Filtros y búsqueda
const searchQuery = ref('');
const statusFilter = ref('pending'); // 'pending', 'approved', 'rejected', 'all'
let searchDebounceTimer = null;

// Paginación del servidor
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);
const fromItem = ref(0);
const toItem = ref(0);
const perPage = ref(8);

// Estadísticas globales (independientes de la página actual)
const stats = ref({
  pending: 0,
  approved: 0,
  rejected: 0,
  total: 0,
});

const fetchEstablishments = async (page = 1) => {
  try {
    loading.value = true;
    const params = {
      page,
      per_page: perPage.value,
    };

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value;
    }

    const query = searchQuery.value.trim();
    if (query) {
      params.search = query;
    }

    const response = await axiosInstance.get('/adm/pending-establishments', { params });
    const resData = response.data;

    establishments.value = Array.isArray(resData?.data) ? resData.data : [];
    currentPage.value = Number(resData?.current_page) || 1;
    totalPages.value = Number(resData?.last_page) || 1;
    totalItems.value = Number(resData?.total) || 0;
    fromItem.value = Number(resData?.from) || 0;
    toItem.value = Number(resData?.to) || 0;

    if (resData?.stats) {
      stats.value = {
        pending: Number(resData.stats.pending) || 0,
        approved: Number(resData.stats.approved) || 0,
        rejected: Number(resData.stats.rejected) || 0,
        total: Number(resData.stats.total) || 0,
      };
    }
    error.value = null;
  } catch (err) {
    console.error('Error fetching establishments:', err);
    error.value = 'No se pudieron cargar las solicitudes de establecimientos';
    establishments.value = [];
  } finally {
    loading.value = false;
  }
};

const setStatusFilter = (newStatus) => {
  if (statusFilter.value === newStatus) return;
  statusFilter.value = newStatus;
  currentPage.value = 1;
  fetchEstablishments(1);
};

const onSearchInput = () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1;
    fetchEstablishments(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  currentPage.value = 1;
  fetchEstablishments(1);
};

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page;
    fetchEstablishments(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const paginationPages = computed(() => {
  const pages = [];
  const maxPages = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
  let end = Math.min(totalPages.value, start + maxPages - 1);

  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

const viewEstablishmentDetail = (establishmentId) => {
  router.push({ name: 'new-seller', params: { id: establishmentId } });
};

const getInitials = (name) => {
  if (!name) return 'CO';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'approved':
      return 'Aceptada';
    case 'rejected':
      return 'Rechazada';
    case 'pending':
    default:
      return 'Pendiente';
  }
};

onMounted(() => {
  fetchEstablishments(1);
});
</script>

<template>
  <div class="new-sellers-page">
    <div class="new-sellers-container">
      <!-- Encabezado Principal -->
      <header class="page-header">
        <div>
          <h1 class="page-title">Solicitudes de Establecimientos</h1>
          <p class="page-subtitle">
            Gestión y clasificación de solicitudes comerciales clasificadas por estado: pendientes de revisión, aceptadas y rechazadas.
          </p>
        </div>

        <button
          type="button"
          @click="fetchEstablishments(currentPage)"
          class="btn-refresh"
          :disabled="loading"
          title="Actualizar listado de solicitudes"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span class="hidden sm:inline">Actualizar</span>
        </button>
      </header>

      <!-- Métricas / Clasificación Superior por Estado -->
      <div class="metrics-grid">
        <!-- Pendientes -->
        <div
          class="metric-card metric-card--pending"
          @click="setStatusFilter('pending')"
          :class="{ 'metric-card--active': statusFilter === 'pending' }"
          role="button"
          tabindex="0"
          @keydown.enter="setStatusFilter('pending')"
        >
          <div class="flex items-center justify-between">
            <span class="metric-label">Pendientes</span>
            <span class="metric-badge-indicator metric-badge-indicator--pending">
              <span class="status-pulse-dot"></span>
              En espera
            </span>
          </div>
          <span class="metric-value text-amber-400">{{ stats.pending }}</span>
          <span class="metric-helper">Por revisar y verificar</span>
        </div>

        <!-- Aceptadas -->
        <div
          class="metric-card metric-card--approved"
          @click="setStatusFilter('approved')"
          :class="{ 'metric-card--active': statusFilter === 'approved' }"
          role="button"
          tabindex="0"
          @keydown.enter="setStatusFilter('approved')"
        >
          <div class="flex items-center justify-between">
            <span class="metric-label">Aceptadas</span>
            <span class="metric-badge-indicator metric-badge-indicator--approved">
              <svg class="w-3 h-3 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              Aprobadas
            </span>
          </div>
          <span class="metric-value text-[#10B981]">{{ stats.approved }}</span>
          <span class="metric-helper">Verificadas y activas</span>
        </div>

        <!-- Rechazadas -->
        <div
          class="metric-card metric-card--rejected"
          @click="setStatusFilter('rejected')"
          :class="{ 'metric-card--active': statusFilter === 'rejected' }"
          role="button"
          tabindex="0"
          @keydown.enter="setStatusFilter('rejected')"
        >
          <div class="flex items-center justify-between">
            <span class="metric-label">Rechazadas</span>
            <span class="metric-badge-indicator metric-badge-indicator--rejected">
              <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Denegadas
            </span>
          </div>
          <span class="metric-value text-red-400">{{ stats.rejected }}</span>
          <span class="metric-helper">Solicitudes no admitidas</span>
        </div>

        <!-- Total -->
        <div
          class="metric-card metric-card--all"
          @click="setStatusFilter('all')"
          :class="{ 'metric-card--active': statusFilter === 'all' }"
          role="button"
          tabindex="0"
          @keydown.enter="setStatusFilter('all')"
        >
          <div class="flex items-center justify-between">
            <span class="metric-label">Total Solicitudes</span>
            <span class="metric-badge-indicator metric-badge-indicator--all">Histórico</span>
          </div>
          <span class="metric-value text-white">{{ stats.total }}</span>
          <span class="metric-helper">Todos los registros</span>
        </div>
      </div>

      <!-- Barra de Filtros, Pestañas y Búsqueda -->
      <div class="filters-bar">
        <!-- Segmented Control de Estados -->
        <div class="status-tabs-nav" role="tablist">
          <button
            type="button"
            role="tab"
            :aria-selected="statusFilter === 'pending'"
            class="status-tab-btn"
            :class="{ 'status-tab-btn--active': statusFilter === 'pending' }"
            @click="setStatusFilter('pending')"
          >
            <span class="status-tab-dot status-tab-dot--pending"></span>
            <span>Pendientes</span>
            <span class="status-tab-count">{{ stats.pending }}</span>
          </button>

          <button
            type="button"
            role="tab"
            :aria-selected="statusFilter === 'approved'"
            class="status-tab-btn"
            :class="{ 'status-tab-btn--active': statusFilter === 'approved' }"
            @click="setStatusFilter('approved')"
          >
            <span class="status-tab-dot status-tab-dot--approved"></span>
            <span>Aceptadas</span>
            <span class="status-tab-count">{{ stats.approved }}</span>
          </button>

          <button
            type="button"
            role="tab"
            :aria-selected="statusFilter === 'rejected'"
            class="status-tab-btn"
            :class="{ 'status-tab-btn--active': statusFilter === 'rejected' }"
            @click="setStatusFilter('rejected')"
          >
            <span class="status-tab-dot status-tab-dot--rejected"></span>
            <span>Rechazadas</span>
            <span class="status-tab-count">{{ stats.rejected }}</span>
          </button>

          <button
            type="button"
            role="tab"
            :aria-selected="statusFilter === 'all'"
            class="status-tab-btn"
            :class="{ 'status-tab-btn--active': statusFilter === 'all' }"
            @click="setStatusFilter('all')"
          >
            <span>Todas</span>
            <span class="status-tab-count">{{ stats.total }}</span>
          </button>
        </div>

        <!-- Input de Búsqueda -->
        <div class="search-input-wrap">
          <svg class="w-4 h-4 text-[#A5A8C2]/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="searchQuery"
            @input="onSearchInput"
            @keydown.enter="fetchEstablishments(1)"
            placeholder="Buscar por comercio, titular, email o rubro..."
            class="search-input"
          />
          <button v-if="searchQuery" type="button" @click="clearSearch" class="text-xs text-[#A5A8C2]/60 hover:text-white">
            Limpiar
          </button>
        </div>
      </div>

      <!-- Resumen de Resultados / Paginación Info -->
      <div class="results-meta-bar" v-if="!loading && totalItems > 0">
        <span class="text-xs text-[#A5A8C2]/70 font-medium">
          Mostrando <strong class="text-white">{{ fromItem }} - {{ toItem }}</strong> de <strong class="text-white">{{ totalItems }}</strong> solicitudes
          <span v-if="statusFilter !== 'all'"> (filtradas por {{ getStatusLabel(statusFilter) }})</span>
        </span>
      </div>

      <!-- Estado: Cargando -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p class="text-xs text-[#A5A8C2]/70 font-medium">Cargando solicitudes...</p>
      </div>

      <!-- Estado: Error -->
      <div v-else-if="error" class="error-box">
        <svg class="w-8 h-8 text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-sm text-red-300 font-medium mb-3">{{ error }}</p>
        <button class="btn-retry" @click="fetchEstablishments(currentPage)">Reintentar</button>
      </div>

      <!-- Estado: Contenido -->
      <div v-else>
        <!-- Estado Vacío Total -->
        <div v-if="totalItems === 0 && !searchQuery && statusFilter === 'all'" class="empty-box">
          <div class="empty-icon-wrapper">
            <svg class="w-8 h-8 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-white mb-1">Sin solicitudes registradas</h3>
          <p class="text-xs text-[#A5A8C2]/70">No hay solicitudes de comercios en el sistema en este momento.</p>
        </div>

        <!-- Estado Vacío con Filtro de Estado o Búsqueda -->
        <div v-else-if="establishments.length === 0" class="empty-box">
          <svg class="w-8 h-8 text-[#A5A8C2]/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="text-base font-bold text-white mb-1">
            <span v-if="searchQuery">Sin resultados para "{{ searchQuery }}"</span>
            <span v-else-if="statusFilter === 'pending'">No hay solicitudes pendientes</span>
            <span v-else-if="statusFilter === 'approved'">No hay solicitudes aceptadas</span>
            <span v-else-if="statusFilter === 'rejected'">No hay solicitudes rechazadas</span>
            <span v-else>Sin solicitudes</span>
          </h3>
          <p class="text-xs text-[#A5A8C2]/70 mb-3">
            <span v-if="searchQuery">Intenta ajustar los términos de búsqueda o cambiar de pestaña.</span>
            <span v-else-if="statusFilter === 'pending'">Todas las solicitudes han sido revisadas y resueltas.</span>
            <span v-else-if="statusFilter === 'approved'">Aún no se ha aprobado ningún establecimiento en este filtro.</span>
            <span v-else-if="statusFilter === 'rejected'">No existen solicitudes rechazadas actualmente.</span>
            <span v-else>No se encontraron registros.</span>
          </p>
          <button
            v-if="searchQuery || statusFilter !== 'pending'"
            class="btn-clear-filters"
            @click="clearSearch(); setStatusFilter('pending')"
          >
            Ver solicitudes pendientes
          </button>
        </div>

        <!-- LISTA DE ESTABLECIMIENTOS CLASIFICADOS -->
        <div v-else class="sellers-list-stack">
          <article
            v-for="establishment in establishments"
            :key="establishment.id"
            class="seller-list-item group"
            :class="'seller-list-item--' + (establishment.verification_status || 'pending')"
            @click="viewEstablishmentDetail(establishment.id)"
            role="button"
            tabindex="0"
            :aria-label="`Ver solicitud de ${establishment.name}`"
            @keydown.enter="viewEstablishmentDetail(establishment.id)"
          >
            <div class="seller-list-item-main">
              <!-- Bloque 1: Comercio & Rubro -->
              <div class="seller-col-commerce">
                <div class="commerce-avatar shrink-0" :class="'commerce-avatar--' + (establishment.verification_status || 'pending')">
                  {{ getInitials(establishment.name) }}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="commerce-name truncate">{{ establishment.name }}</h3>
                    <span
                      v-if="establishment.establishment_type?.name || establishment.establishmentType?.name"
                      class="badge-rubro"
                    >
                      {{ establishment.establishment_type?.name || establishment.establishmentType?.name }}
                    </span>
                    <span
                      v-if="establishment.google_place_id"
                      class="badge-google-maps"
                      title="Local vinculado en Google Maps"
                    >
                      <svg class="w-3 h-3 text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>Google Maps</span>
                    </span>
                  </div>

                  <div class="flex items-center gap-3 text-xs text-[#A5A8C2]/70 mt-1 flex-wrap">
                    <span class="flex items-center gap-1 truncate max-w-sm" v-if="establishment.address">
                      <svg class="w-3.5 h-3.5 text-[#A5A8C2]/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span class="truncate">{{ establishment.address }}</span>
                    </span>
                    <span class="flex items-center gap-1" v-if="establishment.created_at">
                      <svg class="w-3.5 h-3.5 text-[#A5A8C2]/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>Ingresó {{ formatDate(establishment.created_at) }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Bloque 2: Titular -->
              <div class="seller-col-owner">
                <div class="flex items-center gap-1.5 text-xs text-[#CBD5E1] font-semibold">
                  <svg class="w-3.5 h-3.5 text-[#A78BFA] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="truncate">{{ establishment.user?.name }} {{ establishment.user?.last_name }}</span>
                </div>
                <p class="text-[11px] text-[#A5A8C2]/60 truncate mt-0.5">
                  {{ establishment.user?.email }}
                </p>
              </div>

              <!-- Bloque 3: Clasificación / Estado de Solicitud (PROTAGONISTA) -->
              <div class="seller-col-status">
                <!-- Badge de Estado de Solicitud -->
                <div class="status-pill" :class="'status-pill--' + (establishment.verification_status || 'pending')">
                  <!-- Icono según estado -->
                  <span v-if="(establishment.verification_status || 'pending') === 'pending'" class="status-pill__dot status-pill__dot--pending"></span>
                  <svg v-else-if="establishment.verification_status === 'approved'" class="w-3 h-3 shrink-0 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else-if="establishment.verification_status === 'rejected'" class="w-3 h-3 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>

                  <span class="font-bold">{{ getStatusLabel(establishment.verification_status) }}</span>
                </div>

                <!-- Detalle contextual del estado -->
                <div class="text-[11px] mt-1 flex items-center gap-1.5">
                  <template v-if="(establishment.verification_status || 'pending') === 'pending'">
                    <span v-if="establishment.pending_files_count > 0" class="text-amber-400/90 font-medium">
                      {{ establishment.pending_files_count }} doc{{ establishment.pending_files_count !== 1 ? 's' : '' }} por revisar
                    </span>
                    <span v-else class="text-[#10B981] font-medium">
                      Docs listos
                    </span>
                  </template>
                  <template v-else-if="establishment.verification_status === 'approved'">
                    <span class="text-[#10B981]/90 font-medium">
                      Comercio habilitado
                    </span>
                  </template>
                  <template v-else-if="establishment.verification_status === 'rejected'">
                    <span class="text-red-400/90 font-medium">
                      Revisión denegada
                    </span>
                  </template>
                </div>
              </div>

              <!-- Bloque 4: Nivel de Confiabilidad (Score acumulado) -->
              <div class="seller-col-trust">
                <div class="trust-mini-pill" :class="'trust-mini-pill--' + (establishment.trust_level || 'red')" :title="`Nivel de confianza: ${establishment.trust_level || 'red'}`">
                  <span class="trust-mini-pill__dot"></span>
                  <span>{{ establishment.trust_score || 0 }} pts</span>
                </div>
                <span class="text-[10px] text-[#A5A8C2]/50 uppercase tracking-wider font-semibold mt-0.5">Confianza</span>
              </div>

              <!-- Bloque 5: Botón Acción -->
              <div class="seller-col-action shrink-0">
                <button
                  type="button"
                  class="btn-action-seller"
                  :class="{
                    'btn-action-seller--primary': (establishment.verification_status || 'pending') === 'pending',
                    'btn-action-seller--secondary': establishment.verification_status !== 'pending'
                  }"
                  @click.stop="viewEstablishmentDetail(establishment.id)"
                  :title="(establishment.verification_status || 'pending') === 'pending' ? 'Revisar solicitud y documentación' : 'Ver detalle del expediente'"
                >
                  <span v-if="(establishment.verification_status || 'pending') === 'pending'">Revisar</span>
                  <span v-else>Ver detalle</span>
                  <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </article>
        </div>

        <!-- Barra de Paginación -->
        <nav v-if="totalPages > 1 && !loading" class="pagination-bar" aria-label="Navegación de páginas">
          <div class="pagination-controls">
            <!-- Botón Anterior -->
            <button
              type="button"
              class="pagination-btn pagination-btn--prev"
              :disabled="currentPage === 1"
              @click="changePage(currentPage - 1)"
              aria-label="Página anterior"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              <span class="hidden sm:inline">Anterior</span>
            </button>

            <!-- Primera página fija si está fuera del rango -->
            <button
              v-if="paginationPages[0] > 1"
              type="button"
              class="pagination-btn"
              @click="changePage(1)"
            >
              1
            </button>

            <span v-if="paginationPages[0] > 2" class="pagination-ellipsis">&hellip;</span>

            <!-- Páginas intermedias -->
            <button
              v-for="page in paginationPages"
              :key="page"
              type="button"
              class="pagination-btn"
              :class="{ 'pagination-btn--active': page === currentPage }"
              :aria-current="page === currentPage ? 'page' : null"
              @click="changePage(page)"
            >
              {{ page }}
            </button>

            <span v-if="paginationPages[paginationPages.length - 1] < totalPages - 1" class="pagination-ellipsis">&hellip;</span>

            <!-- Última página fija si está fuera del rango -->
            <button
              v-if="paginationPages[paginationPages.length - 1] < totalPages"
              type="button"
              class="pagination-btn"
              @click="changePage(totalPages)"
            >
              {{ totalPages }}
            </button>

            <!-- Botón Siguiente -->
            <button
              type="button"
              class="pagination-btn pagination-btn--next"
              :disabled="currentPage === totalPages"
              @click="changePage(currentPage + 1)"
              aria-label="Página siguiente"
            >
              <span class="hidden sm:inline">Siguiente</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </nav>
      </div>
    </div>
  </div>
</template>

<style scoped>
.new-sellers-page {
  min-height: 100vh;
  background-color: #1A1625;
  color: #E8EAF6;
  padding: 2rem 1.5rem 3.5rem;
}

.new-sellers-container {
  max-width: 1120px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #FFFFFF;
  margin: 0 0 0.35rem;
  letter-spacing: -0.025em;
}

.page-subtitle {
  font-size: 0.875rem;
  color: rgba(165, 168, 194, 0.75);
  margin: 0;
  max-width: 680px;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.85rem;
  background: #2D2438;
  border: 1px solid #3D3450;
  color: #CBD5E1;
  border-radius: 10px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(124, 58, 237, 0.15);
  border-color: #7C3AED;
  color: #FFFFFF;
}

.btn-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Tarjetas Superiores de Clasificación por Estado */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}

.metric-card {
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 12px;
  padding: 0.95rem 1.15rem;
  cursor: pointer;
  transition: all 0.18s ease;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  position: relative;
  overflow: hidden;
}

.metric-card:hover {
  transform: translateY(-2px);
  border-color: rgba(165, 168, 194, 0.4);
}

.metric-card--pending:hover {
  border-color: rgba(245, 158, 11, 0.5);
}

.metric-card--approved:hover {
  border-color: rgba(16, 185, 129, 0.5);
}

.metric-card--rejected:hover {
  border-color: rgba(239, 68, 68, 0.5);
}

.metric-card--all:hover {
  border-color: rgba(124, 58, 237, 0.5);
}

/* Estado activo de las tarjetas */
.metric-card--active {
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.metric-card--pending.metric-card--active {
  border-color: #F59E0B;
  background: color-mix(in srgb, #F59E0B 8%, #2D2438);
}

.metric-card--approved.metric-card--active {
  border-color: #10B981;
  background: color-mix(in srgb, #10B981 8%, #2D2438);
}

.metric-card--rejected.metric-card--active {
  border-color: #EF4444;
  background: color-mix(in srgb, #EF4444 8%, #2D2438);
}

.metric-card--all.metric-card--active {
  border-color: #7C3AED;
  background: color-mix(in srgb, #7C3AED 8%, #2D2438);
}

.metric-label {
  font-size: 0.75rem;
  color: rgba(165, 168, 194, 0.8);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.metric-badge-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 9999px;
}

.metric-badge-indicator--pending {
  background: rgba(245, 158, 11, 0.12);
  color: #FBBF24;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.metric-badge-indicator--approved {
  background: rgba(16, 185, 129, 0.12);
  color: #34D399;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.metric-badge-indicator--rejected {
  background: rgba(239, 68, 68, 0.12);
  color: #F87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.metric-badge-indicator--all {
  background: rgba(255, 255, 255, 0.06);
  color: #CBD5E1;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.metric-value {
  font-size: 1.65rem;
  font-weight: 800;
  line-height: 1;
}

.metric-helper {
  font-size: 0.75rem;
  color: rgba(165, 168, 194, 0.6);
  margin-top: 0.1rem;
}

/* Punto parpadeante para pendientes */
.status-pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #F59E0B;
  box-shadow: 0 0 6px #F59E0B;
  animation: pulse-dot 1.8s infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.85); }
}

/* Barra de Filtros y Tabs */
.filters-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

/* Segmented Control de Estados */
.status-tabs-nav {
  display: inline-flex;
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 10px;
  padding: 0.25rem;
  gap: 0.2rem;
  flex-wrap: wrap;
}

.status-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.4rem 0.75rem;
  border-radius: 7px;
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(165, 168, 194, 0.8);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}

.status-tab-btn:hover:not(.status-tab-btn--active) {
  color: #FFFFFF;
  background: rgba(255, 255, 255, 0.04);
}

.status-tab-btn--active {
  background: #7C3AED;
  color: #FFFFFF;
  box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35);
}

.status-tab-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.status-tab-dot--pending {
  background: #F59E0B;
}

.status-tab-dot--approved {
  background: #10B981;
}

.status-tab-dot--rejected {
  background: #EF4444;
}

.status-tab-count {
  font-size: 0.6875rem;
  padding: 0.1rem 0.4rem;
  border-radius: 9999px;
  background: rgba(0, 0, 0, 0.25);
  font-weight: 700;
}

.status-tab-btn--active .status-tab-count {
  background: rgba(255, 255, 255, 0.2);
  color: #FFFFFF;
}

/* Buscador */
.search-input-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 10px;
  padding: 0.45rem 0.85rem;
  min-width: 280px;
  flex: 1 1 300px;
  max-width: 440px;
  transition: border-color 0.15s ease;
}

.search-input-wrap:focus-within {
  border-color: #7C3AED;
}

.search-input {
  background: transparent;
  border: none;
  outline: none;
  color: #FFFFFF;
  font-size: 0.8125rem;
  width: 100%;
}

.search-input::placeholder {
  color: rgba(165, 168, 194, 0.5);
}

/* Barra de metadatos de resultados */
.results-meta-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.85rem;
  padding: 0 0.25rem;
}

/* Contenedor tipo Lista */
.sellers-list-stack {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

/* Fila / Elemento de la lista */
.seller-list-item {
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  position: relative;
}

.seller-list-item:hover {
  background: color-mix(in srgb, #7C3AED 5%, #2D2438);
  border-color: rgba(124, 58, 237, 0.5);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.seller-list-item:focus-visible {
  outline: 2px solid #7C3AED;
  outline-offset: 2px;
}

.seller-list-item--approved {
  border-left: 3px solid #10B981;
}

.seller-list-item--rejected {
  border-left: 3px solid #EF4444;
}

.seller-list-item--pending {
  border-left: 3px solid #F59E0B;
}

.seller-list-item-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
}

/* Bloque 1: Comercio */
.seller-col-commerce {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
  flex: 3 1 320px;
}

.commerce-avatar {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: color-mix(in srgb, #7C3AED 18%, #3D3450);
  border: 1px solid rgba(124, 58, 237, 0.4);
  color: #FFFFFF;
  font-weight: 700;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: -0.02em;
}

.commerce-avatar--approved {
  background: color-mix(in srgb, #10B981 18%, #2D2438);
  border-color: rgba(16, 185, 129, 0.4);
}

.commerce-avatar--rejected {
  background: color-mix(in srgb, #EF4444 18%, #2D2438);
  border-color: rgba(239, 68, 68, 0.4);
}

.commerce-avatar--pending {
  background: color-mix(in srgb, #F59E0B 18%, #2D2438);
  border-color: rgba(245, 158, 11, 0.4);
}

.commerce-name {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #FFFFFF;
  margin: 0;
  line-height: 1.3;
}

.badge-rubro {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #CBD5E1;
}

.badge-google-maps {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #93C5FD;
}

/* Bloque 2: Titular */
.seller-col-owner {
  min-width: 0;
  flex: 2 1 180px;
}

/* Bloque 3: Estado de la Solicitud */
.seller-col-status {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 140px;
  flex: 1.5 1 140px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
}

.status-pill--pending {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #FBBF24;
}

.status-pill--approved {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #34D399;
}

.status-pill--rejected {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #F87171;
}

.status-pill__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-pill__dot--pending {
  background: #F59E0B;
  box-shadow: 0 0 5px #F59E0B;
}

/* Bloque 4: Confiabilidad */
.seller-col-trust {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 80px;
  flex: 0 0 80px;
}

.trust-mini-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.trust-mini-pill--green {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #34D399;
}

.trust-mini-pill--yellow {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #FBBF24;
}

.trust-mini-pill--red {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #F87171;
}

.trust-mini-pill__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

/* Bloque 5: Botón Acción */
.seller-col-action {
  display: flex;
  justify-content: flex-end;
}

.btn-action-seller {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.9rem;
  border-radius: 9px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
  white-space: nowrap;
}

.btn-action-seller--primary {
  background: #7C3AED;
  border: 1px solid #7C3AED;
  color: #FFFFFF;
}

.btn-action-seller--primary:hover {
  background: #6D28D9;
  border-color: #6D28D9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35);
}

.btn-action-seller--secondary {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid #3D3450;
  color: #CBD5E1;
}

.btn-action-seller--secondary:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(165, 168, 194, 0.4);
  color: #FFFFFF;
}

/* Barra de Paginación */
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(61, 52, 80, 0.6);
}

.pagination-controls {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 12px;
  padding: 0.35rem;
}

.pagination-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  min-width: 34px;
  height: 34px;
  padding: 0 0.6rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(203, 213, 225, 0.85);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}

.pagination-btn:hover:not(:disabled):not(.pagination-btn--active) {
  background: rgba(255, 255, 255, 0.08);
  color: #FFFFFF;
}

.pagination-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.pagination-btn--active {
  background: #7C3AED;
  color: #FFFFFF;
  box-shadow: 0 2px 8px rgba(124, 58, 237, 0.4);
}

.pagination-ellipsis {
  padding: 0 0.35rem;
  color: rgba(165, 168, 194, 0.5);
  font-size: 0.875rem;
  user-select: none;
}

/* Estados de Carga, Error y Vacío */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1.5rem;
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 14px;
}

.error-box,
.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3.5rem 1.5rem;
  background: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 14px;
  text-align: center;
}

.empty-icon-wrapper {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 2.5px solid rgba(124, 58, 237, 0.2);
  border-radius: 50%;
  border-top-color: #7C3AED;
  animation: spin 0.8s linear infinite;
  margin-bottom: 0.85rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-retry,
.btn-clear-filters {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid #3D3450;
  color: #CBD5E1;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-retry:hover,
.btn-clear-filters:hover {
  background: #7C3AED;
  border-color: #7C3AED;
  color: #FFFFFF;
}

@media (max-width: 900px) {
  .seller-list-item-main {
    flex-wrap: wrap;
    gap: 1rem;
  }

  .seller-col-commerce {
    flex: 1 1 100%;
  }

  .seller-col-owner {
    flex: 1 1 45%;
  }

  .seller-col-status {
    flex: 1 1 45%;
  }

  .seller-col-trust {
    flex: 0 0 auto;
  }

  .seller-col-action {
    flex: 1 1 100%;
    justify-content: flex-end;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(61, 52, 80, 0.6);
  }
}

@media (max-width: 640px) {
  .new-sellers-page {
    padding: 1.25rem 1rem 2.5rem;
  }

  .status-tabs-nav {
    width: 100%;
  }

  .status-tab-btn {
    flex: 1 1 auto;
    justify-content: center;
  }

  .search-input-wrap {
    max-width: 100%;
    width: 100%;
  }

  .pagination-controls {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
