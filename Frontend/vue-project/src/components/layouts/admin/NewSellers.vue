<script setup>
import { onMounted, shallowRef, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const router = useRouter();
const establishments = ref([]);
const loading = shallowRef(true);
const error = shallowRef(null);

// Filtros y búsqueda
const searchQuery = shallowRef('');
const statusFilter = shallowRef('pending'); // 'pending', 'approved', 'rejected', 'all'
const sortOrder = shallowRef('recent'); // 'recent', 'score_desc', 'score_asc'
let searchDebounceTimer = null;

// Paginación del servidor
const currentPage = shallowRef(1);
const totalPages = shallowRef(1);
const totalItems = shallowRef(0);
const fromItem = shallowRef(0);
const toItem = shallowRef(0);
const perPage = shallowRef(8);

// Estadísticas globales
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
    error.value = 'No se pudieron cargar las solicitudes de establecimientos.';
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

const getTrustScore = (est) => {
  if (typeof est.trust_score === 'number') return est.trust_score;
  const logs = est.trust_logs || [];
  return logs.filter((l) => l.is_active).reduce((sum, l) => sum + (Number(l.points) || 0), 0);
};

const sortedEstablishments = computed(() => {
  const list = [...establishments.value];
  if (sortOrder.value === 'score_desc') {
    return list.sort((a, b) => getTrustScore(b) - getTrustScore(a));
  }
  if (sortOrder.value === 'score_asc') {
    return list.sort((a, b) => getTrustScore(a) - getTrustScore(b));
  }
  return list;
});

const getTrustMeta = (est) => {
  const score = getTrustScore(est);
  const pct = Math.min(100, Math.round((score / 150) * 100));
  const hasGoogle = Boolean(est.google_place_id) || (est.trust_logs || []).some((l) => l.source_type === 'google_places_profile' && l.is_active);

  if (est.verification_status === 'rejected') {
    return {
      score,
      pct,
      textClass: 'text-content-secondary',
      barClass: 'bg-content-secondary',
      summary: est.rejection_reason ? `Motivo: ${est.rejection_reason}` : 'Documentación observada',
    };
  }
  if (score >= 70) {
    return {
      score,
      pct,
      textClass: 'text-success',
      barClass: 'bg-success',
      summary: `Nivel Verde • ${hasGoogle ? 'Google Maps verificado' : 'Umbral superado'}`,
    };
  }
  if (score >= 40) {
    return {
      score,
      pct,
      textClass: 'text-warning',
      barClass: 'bg-warning',
      summary: `Nivel Amarillo • ${hasGoogle ? 'Google Maps verificado' : 'Faltan validaciones'}`,
    };
  }
  return {
    score,
    pct,
    textClass: 'text-brand',
    barClass: 'bg-brand',
    summary: 'Nivel Rojo • Documentación incompleta',
  };
};

const getStatusBadge = (status) => {
  if (status === 'approved') {
    return { label: 'Aprobada', cls: 'bg-success/15 text-success' };
  }
  if (status === 'rejected') {
    return { label: 'Rechazada', cls: 'bg-danger/15 text-danger' };
  }
  return { label: 'Pendiente', cls: 'bg-warning/15 text-warning' };
};

const formatRelativeOrDate = (est) => {
  const dateStr = est.created_at;
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const diffHours = Math.floor((Date.now() - date.getTime()) / 3600000);
  if (est.verification_status === 'approved') {
    return `Aprobado el ${date.toLocaleDateString('es-AR')}`;
  }
  if (est.verification_status === 'rejected') {
    return `Rechazado el ${date.toLocaleDateString('es-AR')}`;
  }
  if (diffHours < 1) return 'Postulado hace minutos';
  if (diffHours < 24) return `Postulado hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
  if (diffHours < 48) return 'Postulado ayer';
  return `Postulado el ${date.toLocaleDateString('es-AR')}`;
};

const viewEstablishmentDetail = (establishmentId) => {
  router.push({ name: 'new-seller', params: { id: establishmentId } });
};

onMounted(() => {
  fetchEstablishments(1);
});
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-6">
    <!-- Header + Contadores Derecha -->
    <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight font-heading">
          Solicitudes de Comercios
        </h1>
        <p class="text-xs sm:text-sm text-content-secondary mt-1">
          Validación documental, ubicación y habilitación comercial de nuevos vendedores
        </p>
      </div>

      <div class="grid grid-cols-4 gap-4 sm:gap-7 text-right">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">PENDIENTES</span>
          <span class="text-xl sm:text-2xl font-extrabold text-brand font-heading tabular-nums">{{ stats.pending }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">ACEPTADAS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-success font-heading tabular-nums">{{ stats.approved }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">RECHAZADAS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-content-secondary font-heading tabular-nums">{{ stats.rejected }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">HISTÓRICO</span>
          <span class="text-xl sm:text-2xl font-extrabold text-content-strong font-heading tabular-nums">{{ stats.total }}</span>
        </div>
      </div>
    </header>

    <!-- Toolbar de Búsqueda, Chips y Ordenamiento -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Search Input Pill -->
        <div class="relative w-full sm:w-80">
          <svg
            class="w-4 h-4 text-content-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por comercio, titular, email o dirección..."
            class="w-full h-9 pl-10 pr-8 rounded-full bg-surface border border-border-subtle text-xs text-content-strong placeholder-content-muted focus:outline-none focus:border-brand transition-colors"
            @input="onSearchInput"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-content-muted hover:text-content-strong text-xs cursor-pointer"
            @click="clearSearch"
          >
            ✕
          </button>
        </div>

        <!-- Status Chips -->
        <button
          v-for="tab in [
            { key: 'pending', label: `Pendientes (${stats.pending})` },
            { key: 'approved', label: `Aceptadas (${stats.approved})` },
            { key: 'rejected', label: `Rechazadas (${stats.rejected})` },
            { key: 'all', label: `Todas (${stats.total})` },
          ]"
          :key="tab.key"
          type="button"
          class="h-9 px-4 rounded-full text-xs font-semibold font-heading transition-all cursor-pointer"
          :class="
            statusFilter === tab.key
              ? 'bg-brand text-surface shadow-xs'
              : 'bg-surface border border-border-subtle text-content-strong hover:border-content-muted'
          "
          @click="setStatusFilter(tab.key)"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Ordenamiento -->
      <div class="flex items-center gap-2 self-end lg:self-auto">
        <select
          v-model="sortOrder"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong focus:outline-none focus:border-brand cursor-pointer"
        >
          <option value="recent">Ordenar: Más recientes</option>
          <option value="score_desc">Ordenar: Mayor Trust Score</option>
          <option value="score_asc">Ordenar: Menor Trust Score</option>
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
        @click="fetchEstablishments(currentPage)"
      >
        Reintentar
      </button>
    </div>

    <!-- Skeletons -->
    <div v-if="loading" class="space-y-3.5">
      <div v-for="n in 4" :key="n" class="bg-surface border border-border-subtle rounded-card p-6 h-28 animate-pulse"></div>
    </div>

    <!-- Estado Vacío -->
    <div
      v-else-if="sortedEstablishments.length === 0"
      class="bg-surface border border-border-subtle rounded-card p-12 text-center space-y-2 shadow-card"
    >
      <h3 class="text-base font-bold text-content-strong font-heading">
        No hay solicitudes para mostrar
      </h3>
      <p class="text-xs text-content-secondary">
        Probá cambiando el filtro de estado o limpiando la búsqueda.
      </p>
    </div>

    <!-- Lista de Solicitudes -->
    <div v-else class="space-y-3.5">
      <article
        v-for="est in sortedEstablishments"
        :key="est.id"
        class="bg-surface border border-border-subtle hover:border-brand/40 rounded-card px-6 py-5 shadow-2xs transition-colors grid grid-cols-1 lg:grid-cols-[1.5fr_0.9fr_auto] items-center gap-5"
      >
        <!-- Info Izquierda -->
        <div class="min-w-0 space-y-1.5">
          <div class="flex flex-wrap items-center gap-2.5">
            <h2 class="text-base font-extrabold text-content-strong font-heading truncate">
              {{ est.name }}
            </h2>
            <span
              v-if="est.establishment_type?.name"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-surface-sunken text-content-secondary text-[11px] font-semibold"
            >
              {{ est.establishment_type.name }}
            </span>
          </div>

          <p class="text-xs text-content-secondary truncate">
            {{ est.address || 'Dirección no especificada' }}
            <span v-if="est.user">
              • Titular: {{ est.user.name }} {{ est.user.last_name || '' }} ({{ est.user.email }})
            </span>
          </p>

          <p class="text-[11.5px] text-content-muted">
            {{ formatRelativeOrDate(est) }} • ID #EST-{{ est.id }}
            <span v-if="typeof est.pending_files_count === 'number'">
              • {{ est.pending_files_count }} {{ est.pending_files_count === 1 ? 'documento pendiente' : 'documentos pendientes' }}
            </span>
          </p>
        </div>

        <!-- Centro: Trust Score -->
        <div class="space-y-1.5 min-w-[200px]">
          <div class="flex items-center justify-between gap-2 text-xs">
            <span class="text-[10.5px] font-bold uppercase tracking-wider text-content-secondary">
              TRUST SCORE
            </span>
            <span class="font-extrabold font-heading tabular-nums" :class="getTrustMeta(est).textClass">
              {{ getTrustMeta(est).score }} / 150 pts
            </span>
          </div>
          <div class="w-full h-1.5 rounded-full bg-surface-sunken overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-300"
              :class="getTrustMeta(est).barClass"
              :style="{ width: `${getTrustMeta(est).pct}%` }"
            ></div>
          </div>
          <p class="text-[11px] text-content-secondary truncate">
            {{ getTrustMeta(est).summary }}
          </p>
        </div>

        <!-- Derecha: Estado + CTA -->
        <div class="flex items-center justify-end gap-3 shrink-0">
          <span
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
            :class="getStatusBadge(est.verification_status).cls"
          >
            {{ getStatusBadge(est.verification_status).label }}
          </span>

          <button
            type="button"
            class="h-9 px-4 rounded-full text-xs font-bold font-heading transition-all cursor-pointer inline-flex items-center gap-1.5"
            :class="
              est.verification_status === 'pending'
                ? 'bg-brand hover:bg-brand-hover text-surface shadow-xs'
                : 'bg-surface-sunken hover:bg-border-subtle/50 text-content-strong'
            "
            @click="viewEstablishmentDetail(est.id)"
          >
            <span>{{ est.verification_status === 'pending' ? 'Auditar solicitud →' : 'Ver detalle' }}</span>
          </button>
        </div>
      </article>
    </div>

    <!-- Paginación Inferior -->
    <footer
      v-if="!loading && totalItems > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2"
    >
      <span class="text-xs text-content-secondary">
        Mostrando {{ fromItem }}–{{ toItem }} de {{ totalItems }} solicitudes registradas
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
