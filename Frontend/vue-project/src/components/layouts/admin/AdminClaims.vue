<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import axiosInstance from '@/lib/axios';
import { useFormatters } from '@/composables/useFormatters';
import { useNotificationStore } from '@/stores/notifications';

const { formatCurrency } = useFormatters();
const notificationStore = useNotificationStore();

const claims = ref([]);
const counts = ref({ pending: 0, under_review: 0, approved: 0, rejected: 0 });
const loading = ref(true);
const error = ref(null);
const filterStatus = ref('');
const filterReason = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

const selectedClaim = ref(null);
const loadingDetail = ref(false);
const detailError = ref(null);

// Evidencias privadas cargadas via blob URL
const evidenceBlobUrls = ref({});
const enlargedImageUrl = ref(null);
let evidenceRequestToken = 0;

// Formulario de resolución
const refundAmountInput = ref('');
const resolutionNotes = ref('');
const isAbusive = ref(false);
const deactivateOffer = ref(false);
const applyTrustPenalty = ref(false);
const resolving = ref(false);
const actionError = ref(null);
const actionSuccess = ref(null);

const reasonOptions = [
  { value: '', label: 'Todos los motivos' },
  { value: 'spoiled_food', label: 'Comida en mal estado' },
  { value: 'not_as_described', label: 'No coincide con lo publicado' },
  { value: 'undeclared_allergen', label: 'Alérgeno no declarado' },
  { value: 'missing_items', label: 'Faltantes en el pack' },
  { value: 'pickup_issue', label: 'Problema en el retiro' },
  { value: 'other', label: 'Otro motivo' },
];

const statusOptions = [
  { value: '', label: 'Todos' },
  { value: 'pending', label: 'Pendientes' },
  { value: 'under_review', label: 'En revisión' },
  { value: 'approved', label: 'Aprobados' },
  { value: 'rejected', label: 'Rechazados' },
];

const revokeEvidenceUrls = () => {
  evidenceRequestToken++;
  Object.values(evidenceBlobUrls.value).forEach((url) => {
    if (url) URL.revokeObjectURL(url);
  });
  evidenceBlobUrls.value = {};
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
    claims.value = response.data.data || [];
    if (response.data.counts) {
      counts.value = response.data.counts;
    }
    if (response.data.meta) {
      currentPage.value = response.data.meta.current_page || 1;
      lastPage.value = response.data.meta.last_page || 1;
    }
  } catch (err) {
    console.error('Error al cargar reclamos:', err);
    error.value = 'No se pudieron cargar los reclamos.';
  } finally {
    if (!silent) loading.value = false;
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
      evidenceBlobUrls.value[ev.id] = blobUrl;
    } catch (e) {
      console.error(`Error cargando evidencia ${ev.id}:`, e);
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
    const detail = response.data.data || response.data;
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
    const updated = response.data.data;
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
  return selectedClaim.value && ['pending', 'under_review'].includes(selectedClaim.value.status);
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

  let payload = {
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
    const resolved = response.data.data;
    selectedClaim.value = {
      ...selectedClaim.value,
      ...resolved,
      customer_risk_summary: selectedClaim.value.customer_risk_summary,
    };
    actionSuccess.value = response.data.message || 'Resolución aplicada correctamente.';
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
      return 'bg-[#F59E0B]/15 text-[#FBBF24] border-[#F59E0B]/30';
    case 'under_review':
      return 'bg-[#7C3AED]/20 text-[#C4B5FD] border-[#7C3AED]/40';
    case 'approved':
      return 'bg-[#10B981]/15 text-[#34D399] border-[#10B981]/30';
    case 'rejected':
      return 'bg-[#EF4444]/15 text-[#F87171] border-[#EF4444]/30';
    default:
      return 'bg-white/10 text-white border-white/20';
  }
};

const riskBadgeClass = (level) => {
  switch (level) {
    case 'high':
      return 'bg-[#EF4444]/20 text-[#FCA5A5] border-[#EF4444]/40';
    case 'medium':
      return 'bg-[#F59E0B]/20 text-[#FBBF24] border-[#F59E0B]/40';
    default:
      return 'bg-[#10B981]/15 text-[#34D399] border-[#10B981]/30';
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
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 text-[#E8EAF6] space-y-6">
    <!-- Cabecera -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#3D3450] pb-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Moderación de Reclamos y Reembolsos</h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2] mt-1">
          Evaluá reclamos dentro de la ventana de 14 días, revisá alérgenos congelados, evidencias y señales anti-abuso.
        </p>
      </div>
      <button
        type="button"
        class="self-start sm:self-auto px-4 py-2 rounded-xl text-xs font-bold bg-[#7C3AED]/20 hover:bg-[#7C3AED]/30 text-[#C4B5FD] border border-[#7C3AED]/40 transition-colors cursor-pointer"
        @click="fetchClaims(currentPage)"
      >
        Actualizar listado
      </button>
    </div>

    <!-- Contadores rápidos -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-3.5">
        <span class="text-[11px] font-bold uppercase text-[#FBBF24]">Pendientes</span>
        <div class="text-2xl font-extrabold text-white mt-1">{{ counts.pending }}</div>
      </div>
      <div class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-3.5">
        <span class="text-[11px] font-bold uppercase text-[#C4B5FD]">En revisión</span>
        <div class="text-2xl font-extrabold text-white mt-1">{{ counts.under_review }}</div>
      </div>
      <div class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-3.5">
        <span class="text-[11px] font-bold uppercase text-[#34D399]">Aprobados</span>
        <div class="text-2xl font-extrabold text-white mt-1">{{ counts.approved }}</div>
      </div>
      <div class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-3.5">
        <span class="text-[11px] font-bold uppercase text-[#F87171]">Rechazados</span>
        <div class="text-2xl font-extrabold text-white mt-1">{{ counts.rejected }}</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-[#2D2438] border border-white/[0.08] rounded-2xl p-4 flex flex-wrap items-center gap-4">
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="opt in statusOptions"
          :key="opt.value"
          type="button"
          class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-colors cursor-pointer"
          :class="filterStatus === opt.value
            ? 'bg-[#7C3AED] text-white border-[#7C3AED]'
            : 'bg-[#1A1625] text-[#A5A8C2] border-[#3D3450] hover:text-white'"
          @click="filterStatus = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>

      <div class="ml-auto flex items-center gap-2">
        <label class="text-xs text-[#A5A8C2] font-semibold">Motivo:</label>
        <select
          v-model="filterReason"
          class="px-3 py-1.5 rounded-xl bg-[#1A1625] border border-[#3D3450] text-xs text-white focus:outline-none focus:border-[#7C3AED]"
        >
          <option v-for="r in reasonOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
      </div>
    </div>

    <!-- Estado carga / error / vacío -->
    <div v-if="loading" class="text-center py-12 text-sm text-[#A5A8C2]">
      Cargando reclamos...
    </div>

    <div v-else-if="error" class="p-4 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-sm text-[#FCA5A5]">
      {{ error }}
    </div>

    <div v-else-if="claims.length === 0" class="bg-[#2D2438] border border-white/[0.08] rounded-2xl p-10 text-center text-sm text-[#A5A8C2]">
      No hay reclamos que coincidan con los filtros seleccionados.
    </div>

    <!-- Listado de reclamos -->
    <div v-else class="space-y-3">
      <div
        v-for="claim in claims"
        :key="claim.id"
        class="bg-[#2D2438] border border-white/[0.08] hover:border-[#7C3AED]/50 rounded-2xl p-4 sm:p-5 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4"
      >
        <div class="space-y-1.5 min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-extrabold text-white">Reclamo #{{ claim.id }}</span>
            <span class="text-xs text-[#A5A8C2]">• Compra #{{ claim.sell_id }}</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border" :class="statusBadgeClass(claim.status)">
              {{ claim.status_label }}
            </span>
            <span
              class="px-2 py-0.5 rounded-md text-[10px] font-semibold border"
              :class="claim.was_picked_up_at_claim
                ? 'bg-[#10B981]/10 text-[#34D399] border-[#10B981]/25'
                : 'bg-[#F59E0B]/10 text-[#FBBF24] border-[#F59E0B]/25'"
            >
              {{ claim.was_picked_up_at_claim ? 'Retirado' : 'No retirado' }}
            </span>
            <span
              v-if="claim.is_abusive"
              class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#EF4444]/20 text-[#FCA5A5] border border-[#EF4444]/30"
            >
              Abusivo
            </span>
          </div>

          <div class="text-sm font-bold text-white">
            {{ claim.reason_label }} — <span class="text-[#C4B5FD]">{{ claim.establishment?.name || 'Comercio' }}</span>
          </div>

          <p class="text-xs text-[#A5A8C2] line-clamp-2">{{ claim.description }}</p>

          <div class="flex flex-wrap items-center gap-4 text-[11px] text-[#787596] pt-1">
            <span>Cliente: <strong class="text-[#E8EAF6]">{{ claim.customer?.name }} {{ claim.customer?.last_name }}</strong> ({{ claim.customer?.email }})</span>
            <span>Total compra: <strong class="text-white">${{ formatCurrency(claim.sell?.gross_amount || 0) }}</strong></span>
            <span v-if="claim.refund_amount !== null">
              Reembolsado: <strong class="text-[#34D399]">${{ formatCurrency(claim.refund_amount) }}</strong>
            </span>
            <span>Fecha: {{ formatDate(claim.created_at) }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] transition-colors shadow-md cursor-pointer"
            @click="openClaimDetail(claim.id)"
          >
            Evaluar reclamo
          </button>
        </div>
      </div>

      <!-- Paginación simple -->
      <div v-if="lastPage > 1" class="flex items-center justify-center gap-3 pt-2">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D2438] border border-[#3D3450] disabled:opacity-40"
          @click="fetchClaims(currentPage - 1)"
        >
          Anterior
        </button>
        <span class="text-xs text-[#A5A8C2]">Página {{ currentPage }} de {{ lastPage }}</span>
        <button
          type="button"
          :disabled="currentPage >= lastPage"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D2438] border border-[#3D3450] disabled:opacity-40"
          @click="fetchClaims(currentPage + 1)"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Modal / Panel Detalle del Reclamo -->
    <Teleport to="body">
      <div
        v-if="selectedClaim || loadingDetail || detailError"
        class="fixed inset-0 z-[1300] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="closeDetail"
      >
        <div class="relative w-full max-w-4xl bg-[#1A1625] border border-[#3D3450] rounded-2xl p-6 shadow-2xl text-[#E8EAF6] max-h-[92vh] overflow-y-auto space-y-5">
          <div v-if="loadingDetail" class="py-12 text-center text-sm text-[#A5A8C2]">
            Cargando auditoría del reclamo...
          </div>

          <div v-else-if="detailError" class="p-4 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-sm text-[#FCA5A5]">
            {{ detailError }}
          </div>

          <template v-else-if="selectedClaim">
            <!-- Header Modal -->
            <div class="flex items-start justify-between gap-4 border-b border-[#3D3450] pb-4">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="text-xl font-extrabold text-white">Reclamo #{{ selectedClaim.id }}</h2>
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border" :class="statusBadgeClass(selectedClaim.status)">
                    {{ selectedClaim.status_label }}
                  </span>
                  <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#2D2438] border border-[#3D3450]">
                    {{ selectedClaim.was_picked_up_at_claim ? 'Compra ya retirada' : 'Compra NO retirada' }}
                  </span>
                </div>
                <p class="text-xs text-[#A5A8C2] mt-1">
                  {{ selectedClaim.reason_label }} • Comercio: <strong class="text-white">{{ selectedClaim.establishment?.name }}</strong> • Compra #{{ selectedClaim.sell_id }}
                </p>
              </div>
              <button
                type="button"
                class="text-[#A5A8C2] hover:text-white p-1.5 rounded-lg hover:bg-white/5 cursor-pointer"
                @click="closeDetail"
              >
                ✕
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
              <!-- Columna izquierda: Descripción, Evidencias y Snapshot -->
              <div class="space-y-4">
                <section class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-4 space-y-2">
                  <h3 class="text-xs font-bold uppercase tracking-wider text-[#A5A8C2]">Relato del comprador</h3>
                  <p class="text-sm text-white whitespace-pre-line leading-relaxed">{{ selectedClaim.description }}</p>
                  <div class="text-[11px] text-[#787596] pt-1">
                    Cliente: {{ selectedClaim.customer?.name }} {{ selectedClaim.customer?.last_name }} ({{ selectedClaim.customer?.email }})
                  </div>
                </section>

                <!-- Snapshot inmutable -->
                <section class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-4 space-y-3">
                  <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#A5A8C2]">Snapshot inmutable al comprar</h3>
                    <span class="text-xs font-bold text-white">Total: ${{ formatCurrency(selectedClaim.sell?.gross_amount || 0) }}</span>
                  </div>

                  <div
                    v-for="det in selectedClaim.sell?.sell_details || []"
                    :key="det.id"
                    class="p-3 rounded-xl bg-[#1A1625] border border-[#3D3450] space-y-1.5 text-xs"
                  >
                    <div class="flex justify-between font-bold text-white">
                      <span>x{{ det.offer_quantity }} {{ det.pack_name }}</span>
                      <span>${{ formatCurrency(det.pack_price) }} c/u</span>
                    </div>
                    <p v-if="det.pack_description" class="text-[#A5A8C2]">{{ det.pack_description }}</p>

                    <div class="pt-1 flex flex-wrap items-center gap-1.5">
                      <span class="text-[11px] font-bold text-[#C4B5FD]">Alérgenos declarados:</span>
                      <template v-if="Array.isArray(det.allergens) && det.allergens.length > 0">
                        <span
                          v-for="(al, idx) in det.allergens"
                          :key="idx"
                          class="px-2 py-0.5 rounded-md bg-[#F59E0B]/15 text-[#FBBF24] border border-[#F59E0B]/30 text-[10px] font-semibold"
                        >
                          {{ al }}
                        </span>
                      </template>
                      <span v-else class="text-[11px] text-[#787596]">Sin alérgenos declarados en snapshot</span>
                    </div>

                    <div v-if="det.estimated_weight_kg || det.minimum_value" class="flex gap-3 text-[11px] text-[#787596]">
                      <span v-if="det.estimated_weight_kg">Peso est.: {{ det.estimated_weight_kg }} kg</span>
                      <span v-if="det.minimum_value">Valor mín.: ${{ formatCurrency(det.minimum_value) }}</span>
                    </div>
                  </div>
                </section>

                <!-- Evidencias fotográficas -->
                <section class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-4 space-y-2">
                  <h3 class="text-xs font-bold uppercase tracking-wider text-[#A5A8C2]">Evidencias fotográficas</h3>
                  <div v-if="!selectedClaim.evidences || selectedClaim.evidences.length === 0" class="text-xs text-[#787596]">
                    El comprador no adjuntó fotografías (opcional según política).
                  </div>
                  <div v-else class="grid grid-cols-3 gap-2">
                    <button
                      v-for="ev in selectedClaim.evidences"
                      :key="ev.id"
                      type="button"
                      class="rounded-xl overflow-hidden border border-[#3D3450] bg-[#1A1625] aspect-square hover:border-[#7C3AED] transition-colors cursor-pointer"
                      @click="enlargedImageUrl = evidenceBlobUrls[ev.id]"
                    >
                      <img
                        v-if="evidenceBlobUrls[ev.id]"
                        :src="evidenceBlobUrls[ev.id]"
                        :alt="ev.original_name"
                        class="w-full h-full object-cover"
                      />
                      <span v-else class="text-[10px] text-[#787596]">Cargando...</span>
                    </button>
                  </div>
                </section>
              </div>

              <!-- Columna derecha: Señales anti-abuso y Resolución -->
              <div class="space-y-4">
                <!-- Señales anti-abuso -->
                <section v-if="selectedClaim.customer_risk_summary" class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-4 space-y-3">
                  <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#A5A8C2]">Señales Anti-Abuso</h3>
                    <span
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border uppercase"
                      :class="riskBadgeClass(selectedClaim.customer_risk_summary.risk_level)"
                    >
                      Riesgo {{ selectedClaim.customer_risk_summary.risk_level }}
                    </span>
                  </div>

                  <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">Compras pagadas</span>
                      <strong class="text-white">{{ selectedClaim.customer_risk_summary.total_purchases }}</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">Reclamos totales</span>
                      <strong class="text-white">{{ selectedClaim.customer_risk_summary.total_claims }} ({{ selectedClaim.customer_risk_summary.claim_rate_percentage }}%)</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">Aprob. / Rech.</span>
                      <strong class="text-white">
                        <span class="text-[#34D399]">{{ selectedClaim.customer_risk_summary.approved_claims }}</span> /
                        <span class="text-[#F87171]">{{ selectedClaim.customer_risk_summary.rejected_claims }}</span>
                      </strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">No retirados (60d)</span>
                      <strong class="text-[#FBBF24]">{{ selectedClaim.customer_risk_summary.unpicked_claims_last_60d }}</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">Últimos 30 días</span>
                      <strong class="text-white">{{ selectedClaim.customer_risk_summary.claims_last_30d }}</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-[#1A1625]">
                      <span class="text-[10px] text-[#787596] block">Marcados abusivos</span>
                      <strong class="text-[#F87171]">{{ selectedClaim.customer_risk_summary.abusive_claims }}</strong>
                    </div>
                  </div>
                </section>

                <!-- Panel de resolución -->
                <section class="bg-[#2D2438] border border-white/[0.08] rounded-xl p-4 space-y-4">
                  <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#A5A8C2]">Resolución Administrativa</h3>
                    <button
                      v-if="selectedClaim.status === 'pending'"
                      type="button"
                      :disabled="resolving"
                      class="px-3 py-1 rounded-lg text-[11px] font-bold bg-[#7C3AED]/20 hover:bg-[#7C3AED]/30 text-[#C4B5FD] border border-[#7C3AED]/40 cursor-pointer"
                      @click="markUnderReview"
                    >
                      Marcar "En revisión"
                    </button>
                  </div>

                  <div v-if="actionSuccess" class="p-3 rounded-xl bg-[#10B981]/15 border border-[#10B981]/30 text-xs text-[#34D399]">
                    {{ actionSuccess }}
                  </div>
                  <div v-if="actionError" class="p-3 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#FCA5A5]">
                    {{ actionError }}
                  </div>

                  <template v-if="isResolvable">
                    <!-- Monto a reembolsar -->
                    <div class="space-y-1.5">
                      <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-[#E8EAF6]">
                          Monto a reembolsar (máx. ${{ formatCurrency(grossAmountSelected) }})
                        </label>
                        <button
                          type="button"
                          class="text-[11px] text-[#C4B5FD] hover:underline cursor-pointer"
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
                        class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border border-[#3D3450] text-sm text-white focus:outline-none focus:border-[#7C3AED]"
                      />
                      <p class="text-[11px]" :class="isPartialRefundPreview ? 'text-[#FBBF24]' : 'text-[#34D399]'">
                        {{ isPartialRefundPreview ? 'Se aplicará un REEMBOLSO PARCIAL (partially_refunded)' : 'Se aplicará un REEMBOLSO TOTAL (refunded)' }}
                      </p>
                    </div>

                    <!-- Notas de resolución -->
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-[#E8EAF6]">
                        Notas de resolución (obligatoria al rechazar)
                      </label>
                      <textarea
                        v-model="resolutionNotes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Explicación para el comprador y auditoría interna..."
                        class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border border-[#3D3450] text-xs text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED] resize-none"
                      />
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="space-y-2 text-xs">
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="deactivateOffer" type="checkbox" class="accent-[#7C3AED]" />
                        <span>Al aprobar: desactivar oferta vinculada</span>
                      </label>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="applyTrustPenalty" type="checkbox" class="accent-[#7C3AED]" />
                        <span>Al aprobar: registrar penalización de confianza al local (-15 pts)</span>
                      </label>
                      <label class="flex items-center gap-2 cursor-pointer text-[#FCA5A5]">
                        <input v-model="isAbusive" type="checkbox" class="accent-[#EF4444]" />
                        <span>Al rechazar: marcar como reclamo abusivo (bloqueo 30 días)</span>
                      </label>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-wrap gap-3 pt-2">
                      <button
                        type="button"
                        :disabled="resolving"
                        class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-[#10B981] hover:bg-[#059669] disabled:opacity-50 cursor-pointer transition-colors"
                        @click="resolveClaim('approve')"
                      >
                        Aprobar y Reembolsar
                      </button>
                      <button
                        type="button"
                        :disabled="resolving"
                        class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-[#EF4444]/80 hover:bg-[#EF4444] disabled:opacity-50 cursor-pointer transition-colors"
                        @click="resolveClaim('reject')"
                      >
                        Rechazar Reclamo
                      </button>
                    </div>
                  </template>

                  <div v-else class="space-y-2 text-xs">
                    <div>
                      <span class="text-[#A5A8C2]">Resuelto por:</span>
                      <strong class="text-white ml-1">{{ selectedClaim.reviewer?.name }} {{ selectedClaim.reviewer?.last_name }}</strong>
                      <span class="text-[#787596] ml-1">({{ formatDate(selectedClaim.reviewed_at) }})</span>
                    </div>
                    <div v-if="selectedClaim.refund_amount !== null">
                      <span class="text-[#A5A8C2]">Reembolso emitido:</span>
                      <strong class="text-[#34D399] ml-1">${{ formatCurrency(selectedClaim.refund_amount) }}</strong>
                      <span v-if="selectedClaim.gateway_refund_id" class="text-[#787596] ml-1">• Ref: {{ selectedClaim.gateway_refund_id }}</span>
                    </div>
                    <div v-if="selectedClaim.resolution_notes" class="p-3 rounded-lg bg-[#1A1625] text-[#E8EAF6]">
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
        class="fixed inset-0 z-[1400] flex items-center justify-center p-4 bg-black/90"
        @click="enlargedImageUrl = null"
      >
        <img :src="enlargedImageUrl" alt="Evidencia ampliada" class="max-w-full max-h-[90vh] rounded-xl object-contain" />
      </div>
    </Teleport>
  </div>
</template>
