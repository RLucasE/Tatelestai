<script setup>
import { shallowRef, computed } from 'vue';

const props = defineProps({
  report: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['status-updated', 'action-taken']);

const showDetails = shallowRef(false);
const isUpdating = shallowRef(false);
const isTakingAction = shallowRef(false);
const showUpdateModal = shallowRef(false);
const showActionModal = shallowRef(false);
const selectedStatus = shallowRef('');
const adminNotes = shallowRef('');
const updateError = shallowRef(null);
const actionError = shallowRef(null);
const actionConfirmText = shallowRef('');

const reportableTypeName = computed(() => {
  const type = props.report.reportable_type || '';
  if (type.includes('Offer')) return 'Oferta';
  if (type.includes('FoodEstablishment')) return 'Establecimiento';
  if (type.includes('User')) return 'Usuario';
  return 'Otro';
});

const statusLabel = computed(() => {
  const labels = {
    pending: 'Pendiente',
    reviewing: 'En revisión',
    resolved: 'Resuelto',
    dismissed: 'Descartado',
  };
  return labels[props.report.status] || props.report.status;
});

const statusBadgeClass = computed(() => {
  const classes = {
    pending: 'bg-warning/15 text-warning',
    reviewing: 'bg-surface-sunken text-content-strong',
    resolved: 'bg-success/15 text-success',
    dismissed: 'bg-surface-sunken text-content-secondary',
  };
  return classes[props.report.status] || 'bg-surface-sunken text-content-secondary';
});

const reasonLabel = computed(() => {
  const labels = {
    inappropriate: 'Contenido inapropiado',
    fraud: 'Fraude',
    false_information: 'Información falsa',
    spam: 'Spam',
    offensive: 'Ofensivo',
    other: 'Otro',
  };
  return labels[props.report.reason] || props.report.reason;
});

const reasonBadgeClass = computed(() => {
  if (props.report.reason === 'fraud' || props.report.reason === 'false_information') {
    return 'bg-brand/12 text-brand';
  }
  if (props.report.reason === 'inappropriate' || props.report.reason === 'offensive') {
    return 'bg-danger/12 text-danger';
  }
  return 'bg-warning/15 text-warning';
});

const targetTitle = computed(() => {
  const r = props.report.reportable;
  if (!r) return `Elemento #${props.report.reportable_id}`;
  if (props.report.reportable_type?.includes('User')) {
    const fullName = `${r.name || ''} ${r.last_name || ''}`.trim() || `Usuario #${props.report.reportable_id}`;
    return r.email ? `${fullName} (${r.email})` : fullName;
  }
  if (r.title) {
    const estName = r.food_establishment?.name;
    return estName ? `${r.title} — ${estName}` : r.title;
  }
  if (r.name) {
    return r.address ? `${r.name} (${r.address})` : r.name;
  }
  return `Elemento #${props.report.reportable_id}`;
});

const isResolvedOrDismissed = computed(() => {
  return props.report.status === 'resolved' || props.report.status === 'dismissed';
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const openUpdateModal = () => {
  selectedStatus.value = props.report.status;
  adminNotes.value = props.report.admin_notes || '';
  updateError.value = null;
  showUpdateModal.value = true;
};

const closeUpdateModal = () => {
  showUpdateModal.value = false;
  selectedStatus.value = '';
  adminNotes.value = '';
  updateError.value = null;
};

const updateStatus = async () => {
  if (!selectedStatus.value) {
    updateError.value = 'Debes seleccionar un estado.';
    return;
  }

  try {
    isUpdating.value = true;
    updateError.value = null;
    await emit('status-updated', props.report.id, selectedStatus.value, adminNotes.value);
    closeUpdateModal();
  } catch (err) {
    console.error('Error updating status:', err);
    updateError.value = err.response?.data?.message || 'Error al actualizar el estado.';
  } finally {
    isUpdating.value = false;
  }
};

const actionMessage = computed(() => {
  const type = props.report.reportable_type || '';
  if (type.includes('Offer')) {
    return '¿Estás seguro de que deseas desactivar esta oferta?';
  }
  if (type.includes('FoodEstablishment')) {
    return '¿Estás seguro de que deseas desactivar este establecimiento y a su vendedor? Todas sus ofertas serán desactivadas.';
  }
  if (type.includes('User')) {
    return '¿Estás seguro de que deseas desactivar este usuario? Si tiene un establecimiento, todas sus ofertas serán desactivadas.';
  }
  return '¿Estás seguro de que deseas tomar medidas sobre este reporte?';
});

const canTakeAction = computed(() => {
  const type = props.report.reportable_type || '';
  return type.includes('Offer') || type.includes('FoodEstablishment') || type.includes('User');
});

const openActionModal = () => {
  actionError.value = null;
  actionConfirmText.value = '';
  showActionModal.value = true;
};

const closeActionModal = () => {
  showActionModal.value = false;
  actionError.value = null;
  actionConfirmText.value = '';
};

const takeAction = async () => {
  if (actionConfirmText.value.toLowerCase().trim() !== 'confirmar') {
    actionError.value = 'Debés escribir "CONFIRMAR" para continuar.';
    return;
  }

  try {
    isTakingAction.value = true;
    actionError.value = null;
    await emit('action-taken', props.report.id);
    closeActionModal();
  } catch (err) {
    console.error('Error taking action:', err);
    actionError.value = err.response?.data?.message || 'Error al tomar medidas sobre el reporte.';
  } finally {
    isTakingAction.value = false;
  }
};
</script>

<template>
  <article class="bg-surface border border-border-subtle hover:border-brand/40 rounded-card px-6 py-5 shadow-2xs transition-colors space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <!-- Contenido Principal -->
      <div class="min-w-0 space-y-1.5">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-bold text-content-muted font-heading tabular-nums">
            #REP-{{ report.id }}
          </span>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-surface-sunken text-content-strong">
            {{ reportableTypeName }}
          </span>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
            :class="reasonBadgeClass"
          >
            {{ reasonLabel }}
          </span>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
            :class="statusBadgeClass"
          >
            {{ statusLabel }}
          </span>
        </div>

        <h3 class="text-base font-extrabold text-content-strong font-heading truncate">
          {{ targetTitle }}
        </h3>

        <p class="text-xs text-content-secondary leading-relaxed">
          <span v-if="report.description" class="italic text-content-strong/90">
            "{{ report.description }}"
          </span>
          <span v-if="report.admin_notes" class="text-content-strong">
            {{ report.description ? ' — ' : '' }}Nota interna: {{ report.admin_notes }}
          </span>
          <span class="text-content-secondary">
            {{ (report.description || report.admin_notes) ? ' • ' : '' }}Reportado por {{ report.reporter?.name || 'Usuario' }} • {{ formatDate(report.created_at) }}
          </span>
        </p>
      </div>

      <!-- Acciones Derecha -->
      <div class="flex flex-wrap items-center justify-end gap-2.5 shrink-0">
        <button
          type="button"
          class="h-9 px-4 rounded-full bg-surface border border-border-subtle hover:border-brand text-xs font-semibold text-content-strong inline-flex items-center gap-1.5 transition-colors cursor-pointer"
          @click="openUpdateModal"
        >
          <span>Cambiar estado</span>
          <svg class="w-3.5 h-3.5 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <button
          v-if="!isResolvedOrDismissed && canTakeAction"
          type="button"
          class="h-9 px-5 rounded-full bg-brand hover:bg-brand-hover text-surface text-xs font-bold font-heading shadow-xs transition-colors cursor-pointer"
          @click="openActionModal"
        >
          Tomar medida
        </button>

        <button
          v-else
          type="button"
          class="h-9 px-4 rounded-full bg-surface-sunken hover:bg-border-subtle/50 text-content-strong text-xs font-semibold transition-colors cursor-pointer"
          @click="showDetails = !showDetails"
        >
          {{ showDetails ? 'Ocultar detalle' : 'Ver historial' }}
        </button>
      </div>
    </div>

    <!-- Detalle Expandido -->
    <div
      v-if="showDetails"
      class="pt-3 border-t border-border-subtle grid grid-cols-1 md:grid-cols-2 gap-4 text-xs"
    >
      <div class="bg-canvas rounded-xl p-3.5 space-y-1.5">
        <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
          Elemento Reportado
        </span>
        <p class="text-content-strong font-medium">Tipo: {{ reportableTypeName }} (ID #{{ report.reportable_id }})</p>
        <p v-if="report.reporter?.email" class="text-content-secondary">
          Reportado por: {{ report.reporter.name }} ({{ report.reporter.email }})
        </p>
      </div>

      <div class="bg-canvas rounded-xl p-3.5 space-y-1.5">
        <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
          Auditoría Administrativa
        </span>
        <p class="text-content-strong font-medium">
          Revisado por: {{ report.reviewer?.name || 'Pendiente de revisión' }}
        </p>
        <p v-if="report.reviewed_at" class="text-content-secondary">
          Fecha revisión: {{ formatDate(report.reviewed_at) }}
        </p>
        <p v-if="report.admin_notes" class="text-content-strong">
          Notas: {{ report.admin_notes }}
        </p>
      </div>
    </div>

    <!-- Modal Cambiar Estado -->
    <Teleport to="body">
      <div
        v-if="showUpdateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closeUpdateModal"
      >
        <div class="w-full max-w-md bg-surface border border-border-subtle rounded-modal p-6 shadow-card space-y-4">
          <div class="flex items-center justify-between border-b border-border-subtle pb-3">
            <h4 class="text-base font-extrabold text-content-strong font-heading">
              Actualizar estado • #REP-{{ report.id }}
            </h4>
            <button type="button" class="text-content-secondary hover:text-content-strong cursor-pointer" @click="closeUpdateModal">
              ✕
            </button>
          </div>

          <div class="space-y-3">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-content-secondary uppercase">Estado</label>
              <select
                v-model="selectedStatus"
                class="w-full h-10 px-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-brand"
              >
                <option value="pending">Pendiente</option>
                <option value="reviewing">En revisión</option>
                <option value="resolved">Resuelto</option>
                <option value="dismissed">Descartado</option>
              </select>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-content-secondary uppercase">Notas internas</label>
              <textarea
                v-model="adminNotes"
                rows="3"
                placeholder="Detalle o resolución administrativa..."
                class="w-full p-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-brand resize-none"
              />
            </div>

            <div v-if="updateError" class="p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger font-medium">
              {{ updateError }}
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              :disabled="isUpdating"
              class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold text-content-strong cursor-pointer"
              @click="closeUpdateModal"
            >
              Cancelar
            </button>
            <button
              type="button"
              :disabled="isUpdating"
              class="h-9 px-5 rounded-full bg-brand hover:bg-brand-hover text-surface text-xs font-bold cursor-pointer disabled:opacity-50"
              @click="updateStatus"
            >
              {{ isUpdating ? 'Guardando...' : 'Guardar estado' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Tomar Medida -->
    <Teleport to="body">
      <div
        v-if="showActionModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-darkest/60 backdrop-blur-xs"
        @click.self="closeActionModal"
      >
        <div class="w-full max-w-md bg-surface border border-border-subtle rounded-modal p-6 shadow-card space-y-4">
          <div class="flex items-center justify-between border-b border-border-subtle pb-3">
            <h4 class="text-base font-extrabold text-danger font-heading">
              Confirmar medida • #REP-{{ report.id }}
            </h4>
            <button type="button" class="text-content-secondary hover:text-content-strong cursor-pointer" @click="closeActionModal">
              ✕
            </button>
          </div>

          <p class="text-xs text-content-strong leading-relaxed bg-danger/10 border border-danger/20 rounded-xl p-3">
            {{ actionMessage }}
          </p>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content-secondary">
              Escribí <strong class="text-content-strong">CONFIRMAR</strong> para ejecutar la medida
            </label>
            <input
              v-model="actionConfirmText"
              type="text"
              placeholder="CONFIRMAR"
              class="w-full h-10 px-3 rounded-xl bg-canvas border border-border-subtle text-sm text-content-strong focus:outline-none focus:border-danger"
            />
          </div>

          <div v-if="actionError" class="p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger font-medium">
            {{ actionError }}
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              :disabled="isTakingAction"
              class="h-9 px-4 rounded-full bg-surface-sunken text-xs font-semibold text-content-strong cursor-pointer"
              @click="closeActionModal"
            >
              Cancelar
            </button>
            <button
              type="button"
              :disabled="isTakingAction"
              class="h-9 px-5 rounded-full bg-danger hover:opacity-90 text-surface text-xs font-bold cursor-pointer disabled:opacity-50"
              @click="takeAction"
            >
              {{ isTakingAction ? 'Ejecutando...' : 'Ejecutar medida' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </article>
</template>
