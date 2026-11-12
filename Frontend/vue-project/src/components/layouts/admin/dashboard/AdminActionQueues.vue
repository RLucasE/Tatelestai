<script setup>
import { RouterLink } from 'vue-router';

defineProps({
  pendingEstablishments: {
    type: Array,
    default: () => [],
  },
  pendingEstablishmentsCount: {
    type: Number,
    default: 0,
  },
  openReports: {
    type: Array,
    default: () => [],
  },
  openReportsCount: {
    type: Number,
    default: 0,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const getTrustPillClass = (score) => {
  const s = Number(score) || 0;
  if (s >= 70) return 'bg-success/15 text-success';
  if (s >= 40) return 'bg-warning/15 text-warning';
  return 'bg-danger/15 text-danger';
};

const getReportTypeLabel = (type = '') => {
  if (type.includes('Offer')) return 'Oferta';
  if (type.includes('FoodEstablishment')) return 'Establecimiento';
  if (type.includes('User')) return 'Usuario';
  return 'Reporte';
};

const getReportReasonLabel = (reason = '') => {
  const labels = {
    inappropriate: 'Contenido inapropiado',
    fraud: 'Fraude',
    false_information: 'Información falsa',
    spam: 'Spam reiterado',
    offensive: 'Ofensivo',
    other: 'Otro motivo',
  };
  return labels[reason] || reason;
};

const getReportStatusInfo = (status = '') => {
  if (status === 'reviewing') {
    return { label: 'En revisión', pillClass: 'bg-surface-sunken text-content-strong' };
  }
  if (status === 'resolved') {
    return { label: 'Resuelto', pillClass: 'bg-success/15 text-success' };
  }
  if (status === 'dismissed') {
    return { label: 'Descartado', pillClass: 'bg-surface-sunken text-content-secondary' };
  }
  return { label: 'Pendiente', pillClass: 'bg-warning/15 text-warning' };
};

const formatRelativeTime = (dateString) => {
  if (!dateString) return 'reciente';
  const diffMs = Date.now() - new Date(dateString).getTime();
  const diffMin = Math.max(1, Math.floor(diffMs / 60000));
  if (diffMin < 60) return `hace ${diffMin} min`;
  const diffHours = Math.floor(diffMin / 60);
  if (diffHours < 24) return `hace ${diffHours} hs`;
  const diffDays = Math.floor(diffHours / 24);
  if (diffDays === 1) return 'ayer';
  return `hace ${diffDays} días`;
};

const getReportTargetTitle = (report) => {
  const r = report.reportable;
  if (!r) return `Elemento #${report.reportable_id}`;
  return r.title || r.name || (r.email ? `${r.name || 'Usuario'} (${r.email})` : `Elemento #${report.reportable_id}`);
};
</script>

<template>
  <aside class="space-y-7">
    <!-- Cola 1: Solicitudes por revisar -->
    <section class="space-y-3.5">
      <div class="flex items-center justify-between gap-2">
        <h2 class="text-[17px] font-bold text-content-strong font-heading">
          Solicitudes por revisar
        </h2>
        <RouterLink
          to="/adm/new-sellers"
          class="text-[12.5px] font-semibold text-brand hover:text-brand-hover transition-colors"
        >
          Ver todas ({{ pendingEstablishmentsCount }}) →
        </RouterLink>
      </div>

      <div v-if="loading" class="space-y-3 animate-pulse">
        <div v-for="n in 3" :key="n" class="h-[70px] bg-surface border border-border-subtle rounded-card"></div>
      </div>

      <div
        v-else-if="pendingEstablishments.length === 0"
        class="bg-surface border border-border-subtle rounded-card p-5 text-center text-xs text-content-secondary"
      >
        No hay solicitudes pendientes de revisión en este momento.
      </div>

      <div v-else class="space-y-3">
        <article
          v-for="est in pendingEstablishments"
          :key="est.id"
          class="bg-surface border border-border-subtle hover:border-brand/40 rounded-card px-5 py-4 flex items-center justify-between gap-3 shadow-2xs transition-colors"
        >
          <div class="min-w-0 space-y-0.5">
            <h3 class="text-sm font-bold text-content-strong truncate font-heading">
              {{ est.name }}
            </h3>
            <p class="text-xs text-content-secondary truncate">
              {{ est.address || 'Sin dirección' }} • {{ formatRelativeTime(est.created_at) }}
            </p>
          </div>

          <div class="flex items-center gap-2.5 shrink-0">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold tabular-nums"
              :class="getTrustPillClass(est.trust_score ?? 0)"
            >
              {{ est.trust_score ?? 0 }} pts
            </span>
            <RouterLink
              :to="`/adm/new-seller/${est.id}`"
              class="text-[12.5px] font-semibold text-brand hover:text-brand-hover whitespace-nowrap"
            >
              Auditar →
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <!-- Cola 2: Reportes recientes sin resolver -->
    <section class="space-y-3.5">
      <div class="flex items-center justify-between gap-2">
        <h2 class="text-[17px] font-bold text-content-strong font-heading">
          Reportes recientes sin resolver
        </h2>
        <RouterLink
          to="/adm/reports"
          class="text-[12.5px] font-semibold text-brand hover:text-brand-hover transition-colors"
        >
          Ir a Reportes ({{ openReportsCount }}) →
        </RouterLink>
      </div>

      <div v-if="loading" class="space-y-3 animate-pulse">
        <div v-for="n in 3" :key="n" class="h-[70px] bg-surface border border-border-subtle rounded-card"></div>
      </div>

      <div
        v-else-if="openReports.length === 0"
        class="bg-surface border border-border-subtle rounded-card p-5 text-center text-xs text-content-secondary"
      >
        No hay reportes abiertos sin resolver.
      </div>

      <div v-else class="space-y-3">
        <article
          v-for="report in openReports"
          :key="report.id"
          class="bg-surface border border-border-subtle hover:border-brand/40 rounded-card px-5 py-4 flex items-center justify-between gap-3 shadow-2xs transition-colors"
        >
          <div class="min-w-0 space-y-0.5">
            <h3 class="text-sm font-bold text-content-strong truncate font-heading">
              #REP-{{ report.id }} • {{ getReportTargetTitle(report) }}
            </h3>
            <p class="text-xs text-content-secondary truncate">
              {{ getReportTypeLabel(report.reportable_type) }} • {{ getReportReasonLabel(report.reason) }} • {{ formatRelativeTime(report.created_at) }}
            </p>
          </div>

          <div class="flex items-center gap-2.5 shrink-0">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
              :class="getReportStatusInfo(report.status).pillClass"
            >
              {{ getReportStatusInfo(report.status).label }}
            </span>
            <RouterLink
              to="/adm/reports"
              class="text-[12.5px] font-semibold text-brand hover:text-brand-hover whitespace-nowrap"
            >
              Revisar →
            </RouterLink>
          </div>
        </article>
      </div>
    </section>
  </aside>
</template>
