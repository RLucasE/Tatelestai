<script setup>
import { shallowRef, ref, onMounted, computed, watch } from 'vue';
import axiosInstance from '@/lib/axios.js';
import ReportCard from '@/components/layouts/admin/ReportCard.vue';

const reports = ref([]);
const loading = shallowRef(true);
const error = shallowRef(null);
const currentPage = shallowRef(1);
const perPage = shallowRef(15);
const total = shallowRef(0);
const fromItem = shallowRef(0);
const toItem = shallowRef(0);
const totalPages = shallowRef(0);

// Contadores globales por estado
const counts = ref({
  all: 0,
  pending: 0,
  reviewing: 0,
  resolved: 0,
  dismissed: 0,
});

// Filtros
const statusFilter = shallowRef('all');
const typeFilter = shallowRef('all');
const reasonFilter = shallowRef('all');

const typeOptions = [
  { value: 'all', label: 'Tipo: Todos los tipos' },
  { value: 'App\\Models\\Offer', label: 'Tipo: Ofertas' },
  { value: 'App\\Models\\FoodEstablishment', label: 'Tipo: Establecimientos' },
  { value: 'App\\Models\\User', label: 'Tipo: Usuarios' },
];

const reasonOptions = [
  { value: 'all', label: 'Motivo: Todos los motivos' },
  { value: 'false_information', label: 'Motivo: Información falsa' },
  { value: 'inappropriate', label: 'Motivo: Contenido inapropiado' },
  { value: 'fraud', label: 'Motivo: Fraude' },
  { value: 'spam', label: 'Motivo: Spam' },
  { value: 'offensive', label: 'Motivo: Ofensivo' },
  { value: 'other', label: 'Motivo: Otro' },
];

const fetchStatusCounts = async () => {
  try {
    const [allRes, pendRes, revRes, resRes, disRes] = await Promise.allSettled([
      axiosInstance.get('/adm/reports', { params: { per_page: 1 } }),
      axiosInstance.get('/adm/reports', { params: { status: 'pending', per_page: 1 } }),
      axiosInstance.get('/adm/reports', { params: { status: 'reviewing', per_page: 1 } }),
      axiosInstance.get('/adm/reports', { params: { status: 'resolved', per_page: 1 } }),
      axiosInstance.get('/adm/reports', { params: { status: 'dismissed', per_page: 1 } }),
    ]);

    counts.value = {
      all: allRes.status === 'fulfilled' ? Number(allRes.value.data?.total || 0) : 0,
      pending: pendRes.status === 'fulfilled' ? Number(pendRes.value.data?.total || 0) : 0,
      reviewing: revRes.status === 'fulfilled' ? Number(revRes.value.data?.total || 0) : 0,
      resolved: resRes.status === 'fulfilled' ? Number(resRes.value.data?.total || 0) : 0,
      dismissed: disRes.status === 'fulfilled' ? Number(disRes.value.data?.total || 0) : 0,
    };
  } catch (err) {
    console.warn('No se pudieron cargar los contadores de reportes:', err);
  }
};

const getReports = async (page = 1) => {
  try {
    loading.value = true;
    error.value = null;

    const params = {
      page,
      per_page: perPage.value,
    };

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value;
    }

    if (typeFilter.value !== 'all') {
      params.reportable_type = typeFilter.value;
    }

    const response = await axiosInstance.get('/adm/reports', { params });
    const data = response.data;

    reports.value = Array.isArray(data?.data) ? data.data : [];
    currentPage.value = Number(data?.current_page) || 1;
    total.value = Number(data?.total) || 0;
    fromItem.value = Number(data?.from) || 0;
    toItem.value = Number(data?.to) || 0;
    totalPages.value = Number(data?.last_page) || 1;
  } catch (err) {
    console.error('Error fetching reports:', err);
    error.value = 'No se pudieron cargar los reportes.';
    reports.value = [];
  } finally {
    loading.value = false;
  }
};

const filteredReports = computed(() => {
  if (reasonFilter.value === 'all') return reports.value;
  return reports.value.filter((r) => r.reason === reasonFilter.value);
});

const handleStatusUpdate = async (reportId, newStatus, adminNotes) => {
  await axiosInstance.patch(`/adm/reports/${reportId}/status`, {
    status: newStatus,
    admin_notes: adminNotes || undefined,
  });
  await Promise.all([getReports(currentPage.value), fetchStatusCounts()]);
};

const handleActionTaken = async (reportId) => {
  await axiosInstance.post(`/adm/reports/${reportId}/take-action`);
  await Promise.all([getReports(currentPage.value), fetchStatusCounts()]);
};

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page;
    getReports(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

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
  fetchStatusCounts();
});
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-6">
    <!-- Header + Contadores -->
    <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight font-heading">
          Reportes y Moderación
        </h1>
        <p class="text-xs sm:text-sm text-content-secondary mt-1">
          Supervisión de denuncias sobre ofertas, establecimientos y usuarios de la plataforma
        </p>
      </div>

      <div class="grid grid-cols-4 gap-4 sm:gap-7 text-right">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">PENDIENTES</span>
          <span class="text-xl sm:text-2xl font-extrabold text-brand font-heading tabular-nums">{{ counts.pending }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">EN REVISIÓN</span>
          <span class="text-xl sm:text-2xl font-extrabold text-content-strong font-heading tabular-nums">{{ counts.reviewing }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">RESUELTOS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-success font-heading tabular-nums">{{ counts.resolved }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">DESCARTADOS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-content-secondary font-heading tabular-nums">{{ counts.dismissed }}</span>
        </div>
      </div>
    </header>

    <!-- Toolbar de Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="tab in [
            { value: 'all', label: `Todos (${counts.all})` },
            { value: 'pending', label: `Pendientes (${counts.pending})` },
            { value: 'reviewing', label: `En revisión (${counts.reviewing})` },
            { value: 'resolved', label: `Resueltos (${counts.resolved})` },
            { value: 'dismissed', label: `Descartados (${counts.dismissed})` },
          ]"
          :key="tab.value"
          type="button"
          class="h-9 px-4 rounded-full text-xs font-semibold font-heading transition-all cursor-pointer"
          :class="
            statusFilter === tab.value
              ? 'bg-brand text-surface shadow-xs'
              : 'bg-surface border border-border-subtle text-content-strong hover:border-content-muted'
          "
          @click="statusFilter = tab.value"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="typeFilter"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong focus:outline-none focus:border-brand cursor-pointer"
        >
          <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <select
          v-model="reasonFilter"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong focus:outline-none focus:border-brand cursor-pointer"
        >
          <option v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="bg-danger/10 border border-danger/30 text-danger rounded-card px-4 py-3 text-xs sm:text-sm font-medium flex items-center justify-between gap-3"
    >
      <span>{{ error }}</span>
      <button
        type="button"
        class="px-3 py-1 rounded-lg bg-danger/15 hover:bg-danger/25 text-xs font-bold cursor-pointer"
        @click="getReports(currentPage)"
      >
        Reintentar
      </button>
    </div>

    <!-- Skeletons -->
    <div v-if="loading" class="space-y-3.5">
      <div v-for="n in 4" :key="n" class="bg-surface border border-border-subtle rounded-card p-6 h-28 animate-pulse"></div>
    </div>

    <!-- Vacío -->
    <div
      v-else-if="filteredReports.length === 0"
      class="bg-surface border border-border-subtle rounded-card p-12 text-center space-y-2 shadow-card"
    >
      <h3 class="text-base font-bold text-content-strong font-heading">
        No hay reportes que coincidan con los filtros
      </h3>
      <p class="text-xs text-content-secondary">
        Probá seleccionando otro estado, tipo o motivo.
      </p>
    </div>

    <!-- Listado de Reportes -->
    <div v-else class="space-y-3.5">
      <ReportCard
        v-for="report in filteredReports"
        :key="report.id"
        :report="report"
        @status-updated="handleStatusUpdate"
        @action-taken="handleActionTaken"
      />
    </div>

    <!-- Paginación -->
    <footer
      v-if="!loading && total > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2"
    >
      <span class="text-xs text-content-secondary">
        Mostrando {{ fromItem }}–{{ toItem }} de {{ total }} reportes registrados
      </span>

      <div v-if="totalPages > 1" class="flex items-center gap-1.5">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="h-8 px-3 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong disabled:opacity-40 hover:border-brand transition-colors cursor-pointer"
          @click="changePage(currentPage - 1)"
        >
          ← Anterior
        </button>

        <button
          v-for="page in paginationPages"
          :key="page"
          type="button"
          class="w-8 h-8 rounded-full text-xs font-bold transition-colors cursor-pointer"
          :class="
            page === currentPage
              ? 'bg-brand text-surface shadow-xs'
              : 'bg-surface border border-border-subtle text-content-strong hover:border-brand'
          "
          @click="changePage(page)"
        >
          {{ page }}
        </button>

        <button
          type="button"
          :disabled="currentPage >= totalPages"
          class="h-8 px-3 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong disabled:opacity-40 hover:border-brand transition-colors cursor-pointer"
          @click="changePage(currentPage + 1)"
        >
          Siguiente →
        </button>
      </div>
    </footer>
  </div>
</template>
