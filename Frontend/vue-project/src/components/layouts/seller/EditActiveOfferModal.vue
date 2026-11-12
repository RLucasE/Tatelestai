<script setup>
import { ref, reactive, watch, computed } from 'vue';
import axiosInstance from '@/lib/axios';
import { useFormatters } from '@/composables/useFormatters';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  offerId: {
    type: [Number, String],
    default: null,
  },
  offer: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'saved', 'deactivated']);

const { formatTime } = useFormatters();

const loading = ref(false);
const saving = ref(false);
const deactivating = ref(false);
const confirmDeactivate = ref(false);
const errorMessage = ref('');

const loadedOffer = ref(null);

const form = reactive({
  quantity: 1,
  price: 3500,
  minimum_value: 8000,
});

const targetId = computed(() => {
  return props.offerId ?? props.offer?.offer_id ?? props.offer?.id ?? loadedOffer.value?.id;
});

const displayOffer = computed(() => {
  return loadedOffer.value || props.offer || {};
});

const offerSubtitle = computed(() => {
  const title = displayOffer.value.title || displayOffer.value.name || 'Bolsa Sorpresa';
  const start = displayOffer.value.pickup_start_datetime || displayOffer.value.start_datetime;
  const end = displayOffer.value.expiration_datetime || displayOffer.value.end_datetime;
  if (start || end) {
    return `${title} • Retiro ${formatTime(start)} a ${formatTime(end)} hs`;
  }
  return title;
});

const syncFromOffer = (src) => {
  if (!src) return;
  form.quantity = Number(src.stock_remaining ?? src.quantity ?? 1);
  form.price = Number(src.price ?? (src.packs?.[0]?.price || 3500));
  form.minimum_value = Number(src.minimum_value ?? (src.packs?.[0]?.original_value || form.price * 2));
};

const fetchFullOffer = async () => {
  const id = targetId.value;
  if (!id) return;
  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await axiosInstance.get(`/my-packs/${id}`);
    loadedOffer.value = res.data?.data ?? res.data;
    syncFromOffer(loadedOffer.value);
  } catch (err) {
    console.error('Error cargando detalles de oferta:', err);
    if (props.offer) {
      syncFromOffer(props.offer);
    } else {
      errorMessage.value = 'No se pudo cargar la oferta.';
    }
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      confirmDeactivate.value = false;
      errorMessage.value = '';
      loadedOffer.value = null;
      if (props.offer) {
        syncFromOffer(props.offer);
      }
      fetchFullOffer();
    }
  }
);

const closeModal = () => {
  emit('update:modelValue', false);
};

const handleSave = async () => {
  const id = targetId.value;
  if (!id) return;
  saving.value = true;
  errorMessage.value = '';

  try {
    const res = await axiosInstance.patch(`/packs/${id}`, {
      quantity: Number(form.quantity),
      price: Number(form.price),
      minimum_value: Number(form.minimum_value),
    });
    emit('saved', res.data?.data ?? res.data);
    closeModal();
  } catch (err) {
    console.error('Error actualizando oferta:', err);
    errorMessage.value =
      err.response?.data?.message || 'No se pudo guardar el ajuste de la oferta.';
  } finally {
    saving.value = false;
  }
};

const handleDeactivate = async () => {
  const id = targetId.value;
  if (!id) return;
  deactivating.value = true;
  errorMessage.value = '';

  try {
    await axiosInstance.delete(`/packs/${id}`);
    emit('deactivated', id);
    closeModal();
  } catch (err) {
    console.error('Error desactivando oferta:', err);
    errorMessage.value =
      err.response?.data?.message || 'No se pudo desactivar la oferta.';
  } finally {
    deactivating.value = false;
  }
};
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-content/50 backdrop-blur-xs"
    @click.self="closeModal"
  >
    <div
      class="w-full max-w-[460px] rounded-[22px] bg-surface border border-border-subtle p-7 sm:p-8 shadow-card flex flex-col gap-[22px] text-content"
    >
      <!-- Header -->
      <div class="flex flex-col gap-1">
        <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
          AJUSTE RÁPIDO DE LOTE ACTIVO
        </span>
        <h2 class="text-[22px] font-extrabold text-content leading-tight">
          Ajustar Stock y Precio
        </h2>
        <p class="text-[12.5px] font-medium text-content-secondary">
          {{ offerSubtitle }}
        </p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="p-3 rounded-[12px] bg-danger/12 border border-danger/30 text-danger text-xs font-semibold"
      >
        {{ errorMessage }}
      </div>

      <!-- Form -->
      <form class="flex flex-col gap-5" @submit.prevent="handleSave">
        <!-- Cantidad / Stock disponible -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[12px] font-semibold text-content">
            Cantidad / Stock disponible *
          </label>
          <div class="relative flex items-center">
            <input
              v-model.number="form.quantity"
              type="number"
              min="0"
              max="99"
              class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border border-transparent text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
            />
            <span class="absolute right-3.5 text-xs font-semibold text-content-secondary">unidades</span>
          </div>
        </div>

        <!-- Precio de venta & Valor mínimo -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[12px] font-semibold text-content">
              Precio de venta ($)
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-[13.5px] font-semibold text-content-secondary">$</span>
              <input
                v-model.number="form.price"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3.5 py-[11px] rounded-[12px] bg-surface-sunken border border-transparent text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-[12px] font-semibold text-content">
              Valor mínimo ($)
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-[13.5px] font-semibold text-content-secondary">$</span>
              <input
                v-model.number="form.minimum_value"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3.5 py-[11px] rounded-[12px] bg-surface-sunken border border-transparent text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
              />
            </div>
          </div>
        </div>

        <!-- Actions Row -->
        <div class="flex items-center justify-between gap-3 pt-1">
          <div v-if="!confirmDeactivate">
            <button
              type="button"
              class="text-[13px] font-semibold text-danger hover:underline cursor-pointer"
              :disabled="deactivating || saving"
              @click="confirmDeactivate = true"
            >
              Desactivar oferta
            </button>
          </div>
          <div v-else class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-[10px] bg-danger text-surface text-xs font-bold cursor-pointer disabled:opacity-50"
              :disabled="deactivating"
              @click="handleDeactivate"
            >
              {{ deactivating ? 'Desactivando...' : 'Confirmar baja' }}
            </button>
            <button
              type="button"
              class="px-2.5 py-1.5 rounded-[10px] bg-surface-sunken text-content text-xs font-semibold cursor-pointer"
              @click="confirmDeactivate = false"
            >
              Cancelar
            </button>
          </div>

          <button
            type="submit"
            class="px-[22px] py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13.5px] font-bold transition-all active:scale-95 cursor-pointer disabled:opacity-50"
            :disabled="saving || deactivating"
          >
            {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
