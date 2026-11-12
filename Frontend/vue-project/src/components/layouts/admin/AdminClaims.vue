<script setup>
import { shallowRef, ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import axiosInstance from '@/lib/axios';
import { useFormatters } from '@/composables/useFormatters';
import { useNotificationStore } from '@/stores/notifications';

const { formatCurrency } = useFormatters();
const notificationStore = useNotificationStore();

const claims = ref([]);
const counts = ref({ pending: 0, under_review: 0, approved: 0, rejected: 0 });
const loading = shallowRef(true);
const error = shallowRef(null);
const filterStatus = shallowRef('');
const filterReason = shallowRef('');
const currentPage = shallowRef(1);
const lastPage = shallowRef(1);
const totalItems = shallowRef(0);
const fromItem = shallowRef(0);
const toItem = shallowRef(0);

const selectedClaim = ref(null);
const loadingDetail = shallowRef(false);
const detailError = shallowRef(null);

// Evidencias privadas cargadas vía blob URL
const evidenceBlobUrls = ref({});
const evidenceErrors = ref({});
const enlargedImageUrl = shallowRef(null);
let evidenceRequestToken = 0;

// Formulario de resolución
const refundAmountInput = shallowRef('');
const resolutionNotes = shallowRef('');
const isAbusive = shallowRef(false);
const deactivateOffer = shallowRef(false);
const applyTrustPenalty = shallowRef(false);
const resolving = shallowRef(false);
const actionError = shallowRef(null);
const actionSuccess = shallowRef(null);

const reasonOptions = [
  { value: '', label: 'Motivo: Todos los motivos' },
  { value: 'spoiled_food', label: 'Motivo: Comida en mal estado' },
  { value: 'not_as_described', label: 'Motivo: No coincide con lo publicado' },
  { value: 'undeclared_allergen', label: 'Motivo: Alérgeno no declarado' },
  { value: 'missing_items', label: 'Motivo: Faltantes en el pack' },
  { value: 'pickup_issue', label: 'Motivo: Problema en el retiro' },
  { value: 'other', label: 'Motivo: Otro motivo' },
];

const totalClaimsCount = computed(() => {
  return (
    Number(counts.value.pending || 0) +
    Number(counts.value.under_review || 0) +
    Number(counts.value.approved || 0) +
    Number(counts.value.rejected || 0)
  );
});

const statusTabs = computed(() => [
  { value: '', label: `Todos (${totalClaimsCount.value})` },
  { value: 'pending', label: `Pendientes (${counts.value.pending || 0})` },
  { value: 'under_review', label: `En revisión (${counts.value.under_review || 0})` },
  { value: 'approved', label: `Aprobados (${counts.value.approved || 0})` },
  { value: 'rejected', label: `Rechazados (${counts.value.rejected || 0})` },
]);

const paginationPages = computed(() => {
  const pages = [];
  const maxPages = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
  const end = Math.min(lastPage.value, start + maxPages - 1);

  if (end - start < maxPages - 1) {
    start = Math.max(1, end - maxPages + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

const revokeEvidenceUrls = () => {
  evidenceRequestToken++;
  Object.values(evidenceBlobUrls.value).forEach((url) => {
    if (url) URL.revokeObjectURL(url);
  });
  evidenceBlobUrls.value = {};
  evidenceErrors.value = {};
  enlargedImageUrl.value = null;
};

onBeforeUnmount(() => {
  revokeEvidenceUrls();
});

const fetchClaims = async (page = 1, silent = false) => {
  try {
    if (!silent) loading.value = true;
    error.value = null;

    const params = { page };
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterReason.value) params.reason = filterReason.value;

    const response = await axiosInstance.get('/adm/claims', { params });
    claims.value = Array.isArray(response.data?.data) ? response.data.data : [];

    if (response.data?.counts) {
      counts.value = response.data.counts;
    }

    const meta = response.data?.meta;
    if (meta) {
      currentPage.value = Number(meta.current_page) || 1;
      lastPage.value = Number(meta.last_page) || 1;
      totalItems.value = Number(meta.total) || claims.value.length;
      fromItem.value = Number(meta.from) || (claims.value.length > 0 ? 1 : 0);
      toItem.value = Number(meta.to) || claims.value.length;
    } else {
      currentPage.value = 1;
      lastPage.value = 1;
      totalItems.value = claims.value.length;
      fromItem.value = claims.value.length > 0 ? 1 : 0;
      toItem.value = claims.value.length;
    }
  } catch (err) {
    console.error('Error al cargar reclamos:', err);
    error.value = 'No se pudieron cargar los reclamos.';
  } finally {
    if (!silent) loading.value = false;
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    fetchClaims(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const loadPrivateEvidences = async (evidences = [], claimId = null) => {
  revokeEvidenceUrls();
  const requestToken = evidenceRequestToken;

  for (const ev of evidences) {
    if (requestToken !== evidenceRequestToken || (claimId !== null && selectedClaim.value?.id !== claimId)) {
      return;
    }
    try {
      const resp = await axiosInstance.get(ev.url, { responseType: 'blob' });
      const blobUrl = URL.createObjectURL(resp.data);
      if (requestToken !== evidenceRequestToken || (claimId !== null && selectedClaim.value?.id !== claimId)) {
        URL.revokeObjectURL(blobUrl);
        return;
      }
      evidenceBlobUrls.value = {
        ...evidenceBlobUrls.value,
        [ev.id]: blobUrl,
      };
    } catch (e) {
      console.error(`Error cargando evidencia ${ev.id}:`, e);
      evidenceErrors.value = {
        ...evidenceErrors.value,
        [ev.id]: true,
      };
    }
  }
};

const openClaimDetail = async (claimId) => {
  loadingDetail.value = true;
  detailError.value = null;
  actionError.value = null;
  actionSuccess.value = null;
  selectedClaim.value = null;
  revokeEvidenceUrls();

  try {
    const response = await axiosInstance.get(`/adm/claims/${claimId}`);
    const detail = response.data?.data || response.data;
    selectedClaim.value = detail;

    const gross = Number(detail.sell?.gross_amount || 0);
    refundAmountInput.value = gross > 0 ? String(gross) : '';
    resolutionNotes.value = detail.resolution_notes || '';
    isAbusive.value = Boolean(detail.is_abusive);
    deactivateOffer.value = false;
    applyTrustPenalty.value = ['spoiled_food', 'undeclared_allergen'].includes(detail.reason);

    if (detail.evidences?.length) {
      await loadPrivateEvidences(detail.evidences, detail.id);
    }
  } catch (err) {
    console.error('Error al obtener detalle del reclamo:', err);
    detailError.value = err.response?.data?.message || 'No se pudo cargar el detalle del reclamo.';
  } finally {
    loadingDetail.value = false;
  }
};

const closeDetail = () => {
  if (resolving.value) return;
  selectedClaim.value = null;
  detailError.value = null;
  actionError.value = null;
  actionSuccess.value = null;
  revokeEvidenceUrls();
};

const markUnderReview = async () => {
  if (!selectedClaim.value || resolving.value) return;
  resolving.value = true;
  actionError.value = null;
  actionSuccess.value = null;

  try {
    const response = await axiosInstance.patch(`/adm/claims/${selectedClaim.value.id}/under-review`);
    const updated = response.data?.data;
    selectedClaim.value = {
      ...selectedClaim.value,
      ...updated,
      customer_risk_summary: selectedClaim.value.customer_risk_summary,
    };
    actionSuccess.value = 'El reclamo se marcó como "En revisión".';
    await fetchClaims(currentPage.value, true);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'No se pudo pasar a revisión.';
  } finally {
    resolving.value = false;
  }
};

const isResolvable = computed(() => {
  return Boolean(selectedClaim.value && ['pending', 'under_review'].includes(selectedClaim.value.status));
});

const grossAmountSelected = computed(() => {
  return Number(selectedClaim.value?.sell?.gross_amount || 0);
});

const isPartialRefundPreview = computed(() => {
  const amt = Number(refundAmountInput.value);
  return amt > 0 && amt < grossAmountSelected.value;
});

const resolveClaim = async (decision) => {
  if (!selectedClaim.value || resolving.value) return;
  actionError.value = null;
  actionSuccess.value = null;

  if (decision === 'reject' && resolutionNotes.value.trim().length < 3) {
    actionError.value = 'Debés ingresar una nota explicando el motivo del rechazo.';
    return;
  }

  const payload = {
    decision,
    resolution_notes: resolutionNotes.value.trim() || null,
  };

  if (decision === 'approve') {
    const parsedAmt = Number(refundAmountInput.value);
    if (!parsedAmt || parsedAmt <= 0 || parsedAmt > grossAmountSelected.value) {
      actionError.value = `El monto a reembolsar debe estar entre $0.01 y $${formatCurrency(grossAmountSelected.value)}.`;
      return;
    }
    payload.refund_amount = parsedAmt;
    payload.deactivate_offer = deactivateOffer.value;
    payload.apply_trust_penalty = applyTrustPenalty.value;
  } else {
    payload.is_abusive = isAbusive.value;
  }

  resolving.value = true;
  try {
    const response = await axiosInstance.post(`/adm/claims/${selectedClaim.value.id}/resolve`, payload);
    const resolved = response.data?.data;
    selectedClaim.value = {
      ...selectedClaim.value,
      ...resolved,
      customer_risk_summary: selectedClaim.value.customer_risk_summary,
    };
    actionSuccess.value = response.data?.message || 'Resolución aplicada correctamente.';
    await fetchClaims(currentPage.value, true);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Ocurrió un error al resolver el reclamo.';
  } finally {
    resolving.value = false;
  }
};

const statusBadgeClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-warning/15 text-warning';
    case 'under_review':
      return 'bg-brand/12 text-brand';
    case 'approved':
      return 'bg-success/15 text-success';
    case 'rejected':
      return 'bg-danger/12 text-danger';
    default:
      return 'bg-surface-sunken text-content-secondary';
  }
};

const riskBadgeClass = (level) => {
  switch (level) {
    case 'high':
      return 'bg-danger/12 text-danger';
    case 'medium':
      return 'bg-warning/15 text-warning';
    default:
      return 'bg-success/15 text-success';
  }
};

const riskLabel = (level) => {
  switch (level) {
    case 'high':
      return 'Riesgo Alto';
    case 'medium':
      return 'Riesgo Medio';
    default:
      return 'Riesgo Bajo';
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

watch([filterStatus, filterReason], () => {
  fetchClaims(1);
});

watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (event?.event_type === 'claim_created') {
      fetchClaims(currentPage.value, true);
    }
  }
);

onMounted(() => {
  fetchClaims(1);
});
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-6">
    <!-- Header + Contadores -->
    <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight font-heading">
          Moderación de Reclamos y Reembolsos
        </h1>
        <p class="text-xs sm:text-sm text-content-secondary mt-1">
          Evaluá reclamos dentro de la ventana de 14 días, revisá alérgenos congelados, evidencias y señales anti-abuso
        </p>
      </div>

      <div class="grid grid-cols-4 gap-4 sm:gap-7 text-right">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">PENDIENTES</span>
          <span class="text-xl sm:text-2xl font-extrabold text-warning font-heading tabular-nums">{{ counts.pending }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">EN REVISIÓN</span>
          <span class="text-xl sm:text-2xl font-extrabold text-brand font-heading tabular-nums">{{ counts.under_review }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">APROBADOS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-success font-heading tabular-nums">{{ counts.approved }}</span>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-content-secondary block">RECHAZADOS</span>
          <span class="text-xl sm:text-2xl font-extrabold text-danger font-heading tabular-nums">{{ counts.rejected }}</span>
        </div>
      </div>
    </header>

    <!-- Toolbar de Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          type="button"
          class="h-9 px-4 rounded-full text-xs font-semibold font-heading transition-all cursor-pointer"
          :class="
            filterStatus === tab.value
              ? 'bg-brand text-surface shadow-xs'
              : 'bg-surface border border-border-subtle text-content-strong hover:border-content-muted'
          "
          @click="filterStatus = tab.value"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="filterReason"
          aria-label="Filtrar por motivo"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong focus:outline-none focus:border-brand cursor-pointer"
        >
          <option v-for="r in reasonOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>

        <button
          type="button"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle hover:border-brand text-xs font-semibold text-content-strong transition-colors cursor-pointer"
          @click="fetchClaims(currentPage)"
        >
          Actualizar
        </button>
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
        @click="fetchClaims(currentPage)"
      >
        Reintentar
      </button>
    </div>

    <!-- Skeletons -->
    <div v-if="loading" class="space-y-3.5">
      <div
        v-for="n in 4"
        :key="n"
        class="bg-surface border border-border-subtle rounded-card p-6 h-28 animate-pulse"
      />
    </div>

    <!-- Estado vacío -->
    <div
      v-else-if="claims.length === 0"
      class="bg-surface border border-border-subtle rounded-card p-12 text-center space-y-2 shadow-card"
    >
      <h3 class="text-base font-bold text-content-strong font-heading">
        No hay reclamos que coincidan con los filtros
      </h3>
      <p class="text-xs text-content-secondary">
        Probá seleccionando otro estado o motivo de reclamo.
      </p>
    </div>

    <!-- Listado de reclamos -->
    <div v-else class="space-y-3.5">
      <article
        v-for="claim in claims"
        :key="claim.id"
        class="bg-surface border border-border-subtle hover:border-brand/40 rounded-card px-6 py-5 shadow-2xs transition-colors flex flex-col lg:flex-row lg:items-center justify-between gap-4"
      >
        <div class="min-w-0 flex-1 space-y-1.5">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-content-muted font-heading tabular-nums">
              #REC-{{ claim.id }}
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-surface-sunken text-content-strong">
              Compra #{{ claim.sell_id }}
            </span>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
              :class="statusBadgeClass(claim.status)"
            >
              {{ claim.status_label }}
            </span>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
              :class="claim.was_picked_up_at_claim ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning'"
            >
              {{ claim.was_picked_up_at_claim ? 'Retirado' : 'No retirado' }}
            </span>
            <span
              v-if="claim.is_abusive"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-danger/12 text-danger"
            >
              Abusivo
            </span>
          </div>

          <h3 class="text-base font-extrabold text-content-strong font-heading truncate">
            {{ claim.reason_label }} — <span class="text-brand">{{ claim.establishment?.name || 'Comercio' }}</span>
          </h3>

          <p class="text-xs text-content-secondary line-clamp-2 leading-relaxed">
            {{ claim.description }}
          </p>

          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-content-secondary pt-0.5">
            <span>
              Cliente: <strong class="text-content-strong">{{ claim.customer?.name }} {{ claim.customer?.last_name }}</strong>
              <span v-if="claim.customer?.email"> ({{ claim.customer.email }})</span>
            </span>
            <span>
              Total compra: <strong class="text-content-strong tabular-nums">${{ formatCurrency(claim.sell?.gross_amount || 0) }}</strong>
            </span>
            <span v-if="claim.refund_amount !== null">
              Reembolsado: <strong class="text-success tabular-nums">${{ formatCurrency(claim.refund_amount) }}</strong>
            </span>
            <span>{{ formatDate(claim.created_at) }}</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 shrink-0">
          <button
            type="button"
            class="h-9 px-5 rounded-full text-xs font-bold font-heading transition-colors cursor-pointer"
            :class="
              ['pending', 'under_review'].includes(claim.status)
                ? 'bg-brand hover:bg-brand-hover text-surface shadow-xs'
                : 'bg-surface-sunken hover:bg-border-subtle/50 text-content-strong'
            "
            @click="openClaimDetail(claim.id)"
          >
            {{ ['pending', 'under_review'].includes(claim.status) ? 'Evaluar reclamo' : 'Ver resolución' }}
          </button>
        </div>
      </article>
    </div>

    <!-- Paginación -->
    <footer
      v-if="!loading && totalItems > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2"
    >
      <span class="text-xs text-content-secondary">
        Mostrando {{ fromItem }}–{{ toItem }} de {{ totalItems }} reclamos registrados
      </span>

      <div v-if="lastPage > 1" class="flex items-center gap-1.5">
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
          :disabled="currentPage >= lastPage"
          class="h-8 px-3 rounded-full bg-surface border border-border-subtle text-xs font-semibold text-content-strong disabled:opacity-40 hover:border-brand transition-colors cursor-pointer"
          @click="changePage(currentPage + 1)"
        >
          Siguiente →
        </button>
      </div>
    </footer>

    <!-- Modal / Panel Detalle del Reclamo -->
    <Teleport to="body">
      <div
        v-if="selectedClaim || loadingDetail || detailError"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closeDetail"
      >
        <div class="relative w-full max-w-4xl bg-surface border border-border-subtle rounded-modal p-6 shadow-card text-content max-h-[92vh] overflow-y-auto space-y-5">
          <div v-if="loadingDetail" class="py-14 text-center text-sm text-content-secondary">
            Cargando auditoría del reclamo...
          </div>

          <div v-else-if="detailError" class="space-y-4">
            <div class="flex items-center justify-between border-b border-border-subtle pb-3">
              <h2 class="text-base font-extrabold text-danger font-heading">Error al cargar reclamo</h2>
              <button
                type="button"
                class="text-content-secondary hover:text-content-strong p-1.5 rounded-lg hover:bg-surface-sunken cursor-pointer"
                @click="closeDetail"
              >
                ✕
              </button>
            </div>
            <div class="p-4 rounded-xl bg-danger/10 border border-danger/30 text-sm text-danger font-medium">
              {{ detailError }}
            </div>
          </div>

          <template v-else-if="selectedClaim">
            <!-- Header Modal -->
            <div class="flex items-start justify-between gap-4 border-b border-border-subtle pb-4">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="text-xl font-extrabold text-content-strong font-heading">
                    Reclamo #{{ selectedClaim.id }}
                  </h2>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                    :class="statusBadgeClass(selectedClaim.status)"
                  >
                    {{ selectedClaim.status_label }}
                  </span>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                    :class="selectedClaim.was_picked_up_at_claim ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning'"
                  >
                    {{ selectedClaim.was_picked_up_at_claim ? 'Compra ya retirada' : 'Compra NO retirada' }}
                  </span>
                </div>
                <p class="text-xs text-content-secondary mt-1">
                  {{ selectedClaim.reason_label }} • Comercio:
                  <strong class="text-content-strong">{{ selectedClaim.establishment?.name || 'Comercio' }}</strong>
                  • Compra #{{ selectedClaim.sell_id }}
                </p>
              </div>
              <button
                type="button"
                class="text-content-secondary hover:text-content-strong p-1.5 rounded-lg hover:bg-surface-sunken transition-colors cursor-pointer"
                aria-label="Cerrar detalle"
                @click="closeDetail"
              >
                ✕
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
              <!-- Columna izquierda: Descripción, Snapshot y Evidencias -->
              <div class="space-y-4">
                <section class="bg-canvas border border-border-subtle rounded-xl p-4 space-y-2">
                  <h3 class="text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                    Relato del comprador
                  </h3>
                  <p class="text-sm text-content-strong whitespace-pre-line leading-relaxed">
                    {{ selectedClaim.description }}
                  </p>
                  <div class="text-xs text-content-secondary pt-1">
                    Cliente:
                    <strong class="text-content-strong">
                      {{ selectedClaim.customer?.name }} {{ selectedClaim.customer?.last_name }}
                    </strong>
                    <span v-if="selectedClaim.customer?.email"> ({{ selectedClaim.customer.email }})</span>
                  </div>
                </section>

                <!-- Snapshot inmutable -->
                <section class="bg-canvas border border-border-subtle rounded-xl p-4 space-y-3">
                  <div class="flex items-center justify-between">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                      Snapshot inmutable al comprar
                    </h3>
                    <span class="text-xs font-extrabold text-content-strong font-heading tabular-nums">
                      Total: ${{ formatCurrency(selectedClaim.sell?.gross_amount || 0) }}
                    </span>
                  </div>

                  <div
                    v-for="det in selectedClaim.sell?.sell_details || []"
                    :key="det.id"
                    class="p-3 rounded-xl bg-surface border border-border-subtle space-y-1.5 text-xs"
                  >
                    <div class="flex justify-between font-bold text-content-strong">
                      <span>x{{ det.offer_quantity }} {{ det.pack_name }}</span>
                      <span class="tabular-nums">${{ formatCurrency(det.pack_price) }} c/u</span>
                    </div>
                    <p v-if="det.pack_description" class="text-content-secondary">{{ det.pack_description }}</p>

                    <div class="pt-1 flex flex-wrap items-center gap-1.5">
                      <span class="text-[11px] font-bold text-content-strong">Alérgenos declarados:</span>
                      <template v-if="Array.isArray(det.allergens) && det.allergens.length > 0">
                        <span
                          v-for="(al, idx) in det.allergens"
                          :key="idx"
                          class="px-2 py-0.5 rounded-full bg-warning/15 text-warning text-[10px] font-semibold"
                        >
                          {{ al }}
                        </span>
                      </template>
                      <span v-else class="text-[11px] text-content-muted">
                        Sin alérgenos declarados en snapshot
                      </span>
                    </div>

                    <div
                      v-if="det.estimated_weight_kg || det.minimum_value"
                      class="flex gap-3 text-[11px] text-content-secondary"
                    >
                      <span v-if="det.estimated_weight_kg">Peso est.: {{ det.estimated_weight_kg }} kg</span>
                      <span v-if="det.minimum_value">Valor mín.: ${{ formatCurrency(det.minimum_value) }}</span>
                    </div>
                  </div>
                </section>

                <!-- Evidencias fotográficas -->
                <section class="bg-canvas border border-border-subtle rounded-xl p-4 space-y-2.5">
                  <h3 class="text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                    Evidencias fotográficas
                  </h3>
                  <div
                    v-if="!selectedClaim.evidences || selectedClaim.evidences.length === 0"
                    class="text-xs text-content-muted"
                  >
                    El comprador no adjuntó fotografías (opcional según política).
                  </div>
                  <div v-else class="grid grid-cols-3 gap-2.5">
                    <button
                      v-for="ev in selectedClaim.evidences"
                      :key="ev.id"
                      type="button"
                      class="rounded-xl overflow-hidden border border-border-subtle bg-surface aspect-square flex items-center justify-center hover:border-brand transition-colors cursor-pointer disabled:cursor-default"
                      :disabled="!evidenceBlobUrls[ev.id]"
                      @click="evidenceBlobUrls[ev.id] && (enlargedImageUrl = evidenceBlobUrls[ev.id])"
                    >
                      <img
                        v-if="evidenceBlobUrls[ev.id]"
                        :src="evidenceBlobUrls[ev.id]"
                        :alt="ev.original_name"
                        class="w-full h-full object-cover"
                      />
                      <span v-else-if="evidenceErrors[ev.id]" class="text-[10px] text-danger px-2 text-center">
                        No disponible
                      </span>
                      <span v-else class="text-[10px] text-content-muted">Cargando...</span>
                    </button>
                  </div>
                </section>
              </div>

              <!-- Columna derecha: Señales anti-abuso y Resolución -->
              <div class="space-y-4">
                <!-- Señales anti-abuso -->
                <section
                  v-if="selectedClaim.customer_risk_summary"
                  class="bg-canvas border border-border-subtle rounded-xl p-4 space-y-3"
                >
                  <div class="flex items-center justify-between">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                      Señales Anti-Abuso
                    </h3>
                    <span
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                      :class="riskBadgeClass(selectedClaim.customer_risk_summary.risk_level)"
                    >
                      {{ riskLabel(selectedClaim.customer_risk_summary.risk_level) }}
                    </span>
                  </div>

                  <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">Compras pagadas</span>
                      <strong class="text-content-strong tabular-nums">
                        {{ selectedClaim.customer_risk_summary.total_purchases }}
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">Reclamos totales</span>
                      <strong class="text-content-strong tabular-nums">
                        {{ selectedClaim.customer_risk_summary.total_claims }}
                        ({{ selectedClaim.customer_risk_summary.claim_rate_percentage }}%)
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">Aprob. / Rech.</span>
                      <strong class="text-content-strong tabular-nums">
                        <span class="text-success">{{ selectedClaim.customer_risk_summary.approved_claims }}</span> /
                        <span class="text-danger">{{ selectedClaim.customer_risk_summary.rejected_claims }}</span>
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">No retirados (60d)</span>
                      <strong class="text-warning tabular-nums">
                        {{ selectedClaim.customer_risk_summary.unpicked_claims_last_60d }}
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">Últimos 30 días</span>
                      <strong class="text-content-strong tabular-nums">
                        {{ selectedClaim.customer_risk_summary.claims_last_30d }}
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface border border-border-subtle">
                      <span class="text-[10px] text-content-secondary block">Marcados abusivos</span>
                      <strong class="text-danger tabular-nums">
                        {{ selectedClaim.customer_risk_summary.abusive_claims }}
                      </strong>
                    </div>
                  </div>
                </section>

                <!-- Panel de resolución -->
                <section class="bg-canvas border border-border-subtle rounded-xl p-4 space-y-4">
                  <div class="flex items-center justify-between gap-2">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-content-secondary font-heading">
                      Resolución Administrativa
                    </h3>
                    <button
                      v-if="selectedClaim.status === 'pending'"
                      type="button"
                      :disabled="resolving"
                      class="h-7 px-3 rounded-full text-[11px] font-bold bg-brand/12 hover:bg-brand/20 text-brand transition-colors cursor-pointer disabled:opacity-50"
                      @click="markUnderReview"
                    >
                      Marcar "En revisión"
                    </button>
                  </div>

                  <div
                    v-if="actionSuccess"
                    class="p-3 rounded-xl bg-success/12 border border-success/30 text-xs text-success font-medium"
                  >
                    {{ actionSuccess }}
                  </div>
                  <div
                    v-if="actionError"
                    class="p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger font-medium"
                  >
                    {{ actionError }}
                  </div>

                  <template v-if="isResolvable">
                    <!-- Monto a reembolsar -->
                    <div class="space-y-1.5">
                      <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-content-strong">
                          Monto a reembolsar (máx. ${{ formatCurrency(grossAmountSelected) }})
                        </label>
                        <button
                          type="button"
                          class="text-[11px] font-bold text-brand hover:underline cursor-pointer"
                          @click="refundAmountInput = String(grossAmountSelected)"
                        >
                          Restaurar 100%
                        </button>
                      </div>
                      <input
                        v-model="refundAmountInput"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="grossAmountSelected"
                        class="w-full h-10 px-3 rounded-xl bg-surface border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-brand tabular-nums"
                      />
                      <p class="text-[11px] font-medium" :class="isPartialRefundPreview ? 'text-warning' : 'text-success'">
                        {{
                          isPartialRefundPreview
                            ? 'Se aplicará un REEMBOLSO PARCIAL (partially_refunded)'
                            : 'Se aplicará un REEMBOLSO TOTAL (refunded)'
                        }}
                      </p>
                    </div>

                    <!-- Notas de resolución -->
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-content-strong">
                        Notas de resolución (obligatoria al rechazar)
                      </label>
                      <textarea
                        v-model="resolutionNotes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Explicación para el comprador y auditoría interna..."
                        class="w-full p-3 rounded-xl bg-surface border border-border-subtle text-xs text-content-strong placeholder-content-muted focus:outline-none focus:border-brand resize-none"
                      />
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="space-y-2 text-xs text-content-strong">
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="deactivateOffer" type="checkbox" class="accent-brand" />
                        <span>Al aprobar: desactivar oferta vinculada</span>
                      </label>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="applyTrustPenalty" type="checkbox" class="accent-brand" />
                        <span>Al aprobar: registrar penalización de confianza al local (-15 pts)</span>
                      </label>
                      <label class="flex items-center gap-2 cursor-pointer text-danger font-medium">
                        <input v-model="isAbusive" type="checkbox" class="accent-danger" />
                        <span>Al rechazar: marcar como reclamo abusivo (bloqueo 30 días)</span>
                      </label>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-wrap gap-2.5 pt-1">
                      <button
                        type="button"
                        :disabled="resolving"
                        class="flex-1 h-10 px-4 rounded-full text-xs font-bold font-heading text-surface bg-success hover:opacity-90 disabled:opacity-50 cursor-pointer transition-opacity"
                        @click="resolveClaim('approve')"
                      >
                        Aprobar y Reembolsar
                      </button>
                      <button
                        type="button"
                        :disabled="resolving"
                        class="flex-1 h-10 px-4 rounded-full text-xs font-bold font-heading text-surface bg-danger hover:opacity-90 disabled:opacity-50 cursor-pointer transition-opacity"
                        @click="resolveClaim('reject')"
                      >
                        Rechazar Reclamo
                      </button>
                    </div>
                  </template>

                  <div v-else class="space-y-2 text-xs">
                    <div>
                      <span class="text-content-secondary">Resuelto por:</span>
                      <strong class="text-content-strong ml-1">
                        {{ selectedClaim.reviewer?.name || 'Administrador' }} {{ selectedClaim.reviewer?.last_name || '' }}
                      </strong>
                      <span class="text-content-muted ml-1">({{ formatDate(selectedClaim.reviewed_at) }})</span>
                    </div>
                    <div v-if="selectedClaim.refund_amount !== null">
                      <span class="text-content-secondary">Reembolso emitido:</span>
                      <strong class="text-success ml-1 tabular-nums">${{ formatCurrency(selectedClaim.refund_amount) }}</strong>
                      <span v-if="selectedClaim.gateway_refund_id" class="text-content-muted ml-1">
                        • Ref: {{ selectedClaim.gateway_refund_id }}
                      </span>
                    </div>
                    <div
                      v-if="selectedClaim.resolution_notes"
                      class="p-3 rounded-xl bg-surface border border-border-subtle text-content-strong leading-relaxed"
                    >
                      {{ selectedClaim.resolution_notes }}
                    </div>
                  </div>
                </section>
              </div>
            </div>
          </template>
        </div>
      </div>
    </Teleport>

    <!-- Lightbox de imagen ampliada -->
    <Teleport to="body">
      <div
        v-if="enlargedImageUrl"
        class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-surface-darkest/90"
        @click.self="enlargedImageUrl = null"
      >
        <div class="relative max-w-full max-h-[90vh]">
          <button
            type="button"
            class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-surface text-content-strong font-bold text-xs shadow-card flex items-center justify-center cursor-pointer"
            aria-label="Cerrar imagen"
            @click="enlargedImageUrl = null"
          >
            ✕
          </button>
          <img
            :src="enlargedImageUrl"
            alt="Evidencia ampliada"
            class="max-w-full max-h-[88vh] rounded-card object-contain"
          />
        </div>
      </div>
    </Teleport>
  </div>
</template>
