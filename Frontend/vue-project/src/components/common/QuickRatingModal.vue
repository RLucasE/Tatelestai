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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        @click.self="close"
      >
        <div
          class="bg-[#2D2438] border border-white/[0.08] rounded-3xl p-6 sm:p-8 max-w-sm w-full text-center shadow-2xl relative text-[#E8EAF6] animate-in fade-in zoom-in-95 duration-200"
        >
          <!-- Botón de Cerrar -->
          <button
            @click="close"
            class="absolute top-4 right-4 text-[#A5A8C2] hover:text-white p-1.5 rounded-full hover:bg-white/10 transition-colors"
            aria-label="Cerrar modal"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Ícono de Estrellas Superior -->
          <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-[#F59E0B]/15 border border-[#F59E0B]/30 flex items-center justify-center text-[#F59E0B]">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
              <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
            </svg>
          </div>

          <!-- Título y Subtítulo -->
          <h3 class="text-lg font-bold text-white tracking-tight">
            ¿Qué te pareció tu rescate?
          </h3>
          <p v-if="establishmentName" class="text-xs text-[#A5A8C2] mt-1 truncate">
            {{ establishmentName }}
          </p>

          <!-- Selector de 5 Estrellas -->
          <div class="flex items-center justify-center gap-2 my-6">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              class="p-1 focus:outline-none transition-transform hover:scale-125 duration-150"
              @mouseenter="hoverRating = star"
              @mouseleave="hoverRating = 0"
              @click="selectedRating = star"
            >
              <svg
                class="w-8 h-8 sm:w-9 sm:h-9 transition-colors duration-150"
                :class="isStarActive(star) ? 'text-[#F59E0B] fill-current drop-shadow-[0_0_8px_rgba(245,158,11,0.5)]' : 'text-[#787596]/40 fill-none stroke-current stroke-2'"
                viewBox="0 0 24 24"
              >
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
              </svg>
            </button>
          </div>

          <!-- Feedback de Texto sutil según estrella -->
          <p class="text-xs font-semibold h-4 text-[#A78BFA] transition-opacity">
            {{ ratingLabel }}
          </p>

          <!-- Mensaje de Error si ocurre -->
          <div v-if="errorMessage" class="mt-3 p-2.5 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#F87171]">
            {{ errorMessage }}
          </div>

          <!-- Botón de Envío -->
          <button
            type="button"
            :disabled="selectedRating === 0 || isSubmitting"
            @click="submitRating"
            class="w-full mt-5 py-3 px-4 rounded-xl font-bold text-sm tracking-wide text-white transition-all duration-200 flex items-center justify-center gap-2 shadow-lg"
            :class="selectedRating === 0 ? 'bg-[#3D3450] text-[#787596] cursor-not-allowed opacity-60' : 'bg-[#7C3AED] hover:bg-[#6D28D9] shadow-[#7C3AED]/30 active:scale-[0.98]'"
          >
            <svg
              v-if="isSubmitting"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
import { ref, computed } from 'vue';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  sellId: {
    type: [Number, String],
    required: true,
  },
  establishmentName: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'reviewed']);

const selectedRating = ref(0);
const hoverRating = ref(0);
const isSubmitting = ref(false);
const errorMessage = ref('');

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
  if (selectedRating.value === 0 || isSubmitting.value) return;

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
