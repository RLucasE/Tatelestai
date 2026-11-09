<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        @click.self="close"
      >
        <div
          class="bg-surface border border-border-subtle rounded-3xl p-6 sm:p-8 max-w-sm w-full text-center shadow-elevated relative text-content animate-in fade-in zoom-in-95 duration-200"
        >
          <!-- Botón de Cerrar -->
          <button
            type="button"
            @click="close"
            class="absolute top-4 right-4 text-content-secondary hover:text-content p-1.5 rounded-full hover:bg-canvas transition-colors cursor-pointer"
            aria-label="Cerrar modal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Ícono de Estrellas Superior -->
          <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-warning/10 border border-warning/25 flex items-center justify-center text-warning">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
            </svg>
          </div>

          <!-- Título y Subtítulo -->
          <h3 class="text-lg font-bold text-content-strong tracking-tight font-heading">
            ¿Qué te pareció tu rescate?
          </h3>
          <p v-if="establishmentName" class="text-xs text-content-secondary mt-1 truncate">
            {{ establishmentName }}
          </p>

          <!-- Selector de 5 Estrellas -->
          <div class="flex items-center justify-center gap-2 my-6">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              class="p-1 focus:outline-none transition-transform hover:scale-125 duration-150 cursor-pointer"
              @mouseenter="hoverRating = star"
              @mouseleave="hoverRating = 0"
              @click="selectedRating = star"
            >
              <svg
                class="w-8 h-8 sm:w-9 sm:h-9 transition-colors duration-150"
                :class="isStarActive(star) ? 'text-warning fill-current drop-shadow-sm' : 'text-border-subtle fill-none stroke-current stroke-2'"
                viewBox="0 0 24 24"
              >
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
              </svg>
            </button>
          </div>

          <!-- Feedback de Texto sutil según estrella -->
          <p class="text-xs font-semibold h-4 text-brand-light transition-opacity">
            {{ ratingLabel }}
          </p>

          <!-- Mensaje de Error si ocurre -->
          <div v-if="errorMessage" class="mt-3 p-2.5 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger">
            {{ errorMessage }}
          </div>

          <!-- Botón de Envío -->
          <button
            type="button"
            :disabled="selectedRating === 0 || isSubmitting"
            @click="submitRating"
            class="w-full mt-5 py-3 px-4 rounded-[10px] font-bold text-sm tracking-wide text-surface transition-all duration-200 flex items-center justify-center gap-2 shadow-md cursor-pointer select-none"
            :class="selectedRating === 0 ? 'bg-canvas text-content-muted cursor-not-allowed border border-border-subtle opacity-70' : 'bg-brand hover:bg-brand-hover shadow-brand/25 active:scale-[0.98]'"
          >
            <svg
              v-if="isSubmitting"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-surface"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ isSubmitting ? 'Enviando...' : 'Confirmar calificación' }}</span>
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  sellId: {
    type: [Number, String],
    default: null,
  },
  establishmentName: {
    type: String,
    default: '',
  },
  initialRating: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['update:modelValue', 'reviewed']);

const selectedRating = ref(props.initialRating || 0);
const hoverRating = ref(0);
const isSubmitting = ref(false);
const errorMessage = ref('');

watch(
  () => props.initialRating,
  (val) => {
    if (val) selectedRating.value = val;
  }
);

watch(
  () => props.modelValue,
  (val) => {
    if (val && props.initialRating) {
      selectedRating.value = props.initialRating;
    }
  }
);

const isStarActive = (star) => {
  if (hoverRating.value > 0) {
    return star <= hoverRating.value;
  }
  return star <= selectedRating.value;
};

const ratingLabel = computed(() => {
  const current = hoverRating.value || selectedRating.value;
  switch (current) {
    case 1:
      return 'Muy insatisfecho';
    case 2:
      return 'Regular';
    case 3:
      return 'Aceptable';
    case 4:
      return 'Muy bueno';
    case 5:
      return '¡Excelente experiencia!';
    default:
      return '';
  }
});

const close = () => {
  if (isSubmitting.value) return;
  selectedRating.value = 0;
  hoverRating.value = 0;
  errorMessage.value = '';
  emit('update:modelValue', false);
};

const submitRating = async () => {
  if (!props.sellId || selectedRating.value === 0 || isSubmitting.value) return;

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const response = await axiosInstance.post(`/customer/pack-reservations/${props.sellId}/review`, {
      rating: selectedRating.value,
    });

    emit('reviewed', {
      sellId: props.sellId,
      rating: selectedRating.value,
      data: response.data,
    });

    close();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Ocurrió un error al enviar tu calificación. Intenta nuevamente.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>
