<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  sellId: {
    type: [Number, String],
    required: true,
  },
  establishmentName: {
    type: String,
    default: 'el comercio',
  },
});

const emit = defineEmits(['update:modelValue', 'submitted']);

const reasonOptions = [
  { value: 'spoiled_food', label: 'Comida en mal estado o vencida' },
  { value: 'not_as_described', label: 'Contenido muy diferente a lo publicado' },
  { value: 'undeclared_allergen', label: 'Contiene un alérgeno declarado como ausente' },
  { value: 'missing_items', label: 'Faltan productos o porciones del pack' },
  { value: 'pickup_issue', label: 'Problema en el retiro o incumplimiento del local' },
  { value: 'other', label: 'Otro motivo justificado' },
];

const reason = ref('spoiled_food');
const description = ref('');
const evidenceFiles = ref([]);
const submitting = ref(false);
const errorMessage = ref(null);
const fileInputRef = ref(null);

const descriptionLength = computed(() => description.value.trim().length);
const isValidDescription = computed(() => descriptionLength.value >= 10 && descriptionLength.value <= 1000);

const clearPreviews = () => {
  evidenceFiles.value.forEach((item) => {
    if (item.previewUrl) {
      URL.revokeObjectURL(item.previewUrl);
    }
  });
  evidenceFiles.value = [];
};

const resetForm = () => {
  reason.value = 'spoiled_food';
  description.value = '';
  errorMessage.value = null;
  clearPreviews();
};

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      errorMessage.value = null;
    } else {
      clearPreviews();
    }
  }
);

onBeforeUnmount(() => {
  clearPreviews();
});

const closeModal = () => {
  if (submitting.value) return;
  emit('update:modelValue', false);
};

const handleFileSelection = (event) => {
  errorMessage.value = null;
  const selected = Array.from(event.target.files || []);
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
  const maxBytes = 5 * 1024 * 1024;

  for (const file of selected) {
    if (evidenceFiles.value.length >= 5) {
      errorMessage.value = 'Podés adjuntar como máximo 5 fotos de evidencia.';
      break;
    }
    if (!allowedTypes.includes(file.type)) {
      errorMessage.value = `El archivo "${file.name}" no es una imagen válida (JPG, PNG o WEBP).`;
      continue;
    }
    if (file.size > maxBytes) {
      errorMessage.value = `La imagen "${file.name}" supera el límite de 5 MB.`;
      continue;
    }
    evidenceFiles.value.push({
      file,
      name: file.name,
      previewUrl: URL.createObjectURL(file),
    });
  }

  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const removeEvidence = (index) => {
  const item = evidenceFiles.value[index];
  if (item?.previewUrl) {
    URL.revokeObjectURL(item.previewUrl);
  }
  evidenceFiles.value.splice(index, 1);
};

const submitClaim = async () => {
  if (!isValidDescription.value || submitting.value) return;

  submitting.value = true;
  errorMessage.value = null;

  try {
    const formData = new FormData();
    formData.append('reason', reason.value);
    formData.append('description', description.value.trim());

    evidenceFiles.value.forEach((item, idx) => {
      formData.append(`evidences[${idx}]`, item.file);
    });

    const response = await axiosInstance.post(
      `/customer/pack-reservations/${props.sellId}/claims`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      }
    );

    const createdClaim = response.data?.data || response.data;
    emit('submitted', createdClaim);
    resetForm();
    emit('update:modelValue', false);
  } catch (err) {
    const backendMsg = err.response?.data?.message;
    const errors = err.response?.data?.errors;
    if (errors) {
      const firstKey = Object.keys(errors)[0];
      errorMessage.value = errors[firstKey]?.[0] || backendMsg || 'No se pudo enviar el reclamo.';
    } else {
      errorMessage.value = backendMsg || 'No se pudo registrar tu reclamo. Verificá los datos e intentá nuevamente.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="relative w-full max-w-lg bg-surface border border-border-subtle rounded-card p-6 shadow-elevated space-y-5 text-content max-h-[90vh] overflow-y-auto">
        <!-- Encabezado -->
        <div class="flex items-start justify-between gap-3 border-b border-border-subtle pb-3">
          <div>
            <h3 class="text-lg font-extrabold text-content-strong font-heading">Hacer reclamo de compra</h3>
            <p class="text-xs text-content-secondary mt-0.5">
              Compra #{{ sellId }} en <strong class="text-content-strong">{{ establishmentName }}</strong> • Revisión por nuestro equipo
            </p>
          </div>
          <button
            type="button"
            class="text-content-secondary hover:text-content p-1 rounded-lg hover:bg-canvas transition-colors cursor-pointer"
            @click="closeModal"
            aria-label="Cerrar"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Aviso informativo -->
        <div class="p-3 rounded-xl bg-brand/10 border border-brand/30 text-xs text-content leading-relaxed">
          Podés reclamar dentro de los <strong class="text-content-strong">14 días</strong> aunque ya haya pasado la ventana de cancelación inmediata. Un administrador evaluará tu caso y podrá emitir un reembolso total o parcial.
        </div>

        <!-- Motivo -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-content-secondary">
            Motivo del reclamo *
          </label>
          <select
            v-model="reason"
            class="w-full px-3.5 py-2.5 rounded-xl bg-canvas border border-border-subtle text-sm text-content focus:outline-none focus:border-brand"
          >
            <option v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Descripción obligatoria -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-content-secondary">
              Descripción del problema *
            </label>
            <span
              class="text-[11px] font-medium"
              :class="isValidDescription ? 'text-success' : 'text-content-secondary'"
            >
              {{ descriptionLength }}/1000 (mín. 10)
            </span>
          </div>
          <textarea
            v-model="description"
            rows="4"
            maxlength="1000"
            placeholder="Contanos con detalle qué ocurrió con tu pedido (estado de la comida, diferencia con lo publicado, alérgeno encontrado o inconveniente en el local)..."
            class="w-full px-3.5 py-2.5 rounded-xl bg-canvas border border-border-subtle text-sm text-content placeholder-content-muted focus:outline-none focus:border-brand resize-none"
          />
        </div>

        <!-- Evidencias fotográficas opcionales -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-content-secondary">
              Fotos de evidencia (opcional, máx. 5)
            </label>
            <span class="text-[11px] text-content-muted">JPG, PNG o WEBP • máx. 5 MB c/u</span>
          </div>

          <input
            ref="fileInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="hidden"
            @change="handleFileSelection"
          />

          <button
            v-if="evidenceFiles.length < 5"
            type="button"
            class="w-full py-2.5 px-4 rounded-xl border border-dashed border-border-subtle hover:border-brand bg-canvas text-xs font-semibold text-brand hover:text-brand-hover transition-colors flex items-center justify-center gap-2 cursor-pointer"
            @click="fileInputRef?.click()"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Adjuntar fotografías ({{ evidenceFiles.length }}/5)</span>
          </button>

          <div v-if="evidenceFiles.length > 0" class="grid grid-cols-3 sm:grid-cols-5 gap-2 pt-1">
            <div
              v-for="(item, idx) in evidenceFiles"
              :key="idx"
              class="relative group rounded-xl overflow-hidden border border-border-subtle bg-canvas aspect-square"
            >
              <img :src="item.previewUrl" :alt="item.name" class="w-full h-full object-cover" />
              <button
                type="button"
                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/80 text-white text-xs flex items-center justify-center hover:bg-danger transition-colors cursor-pointer"
                @click="removeEvidence(idx)"
                aria-label="Quitar foto"
              >
                ×
              </button>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div
          v-if="errorMessage"
          class="p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger font-medium"
        >
          {{ errorMessage }}
        </div>

        <!-- Botones -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-border-subtle">
          <button
            type="button"
            :disabled="submitting"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-content-secondary hover:text-content bg-canvas border border-border-subtle transition-colors cursor-pointer"
            @click="closeModal"
          >
            Cancelar
          </button>
          <button
            type="button"
            :disabled="!isValidDescription || submitting"
            class="px-5 py-2 rounded-xl text-xs font-bold text-surface bg-brand hover:bg-brand-hover disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm flex items-center gap-2 cursor-pointer"
            @click="submitClaim"
          >
            <svg v-if="submitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <span>{{ submitting ? 'Enviando reclamo...' : 'Enviar reclamo' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
