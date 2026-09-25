<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
  distance: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['click', 'quick-add']);

// Estado de microinteracción para botón de rescate rápido
const isAdding = ref(false);

const handleQuickAdd = (event) => {
  event.stopPropagation();
  if (isAdding.value) return;
  isAdding.value = true;
  emit('quick-add', props.offer);
  setTimeout(() => {
    isAdding.value = false;
  }, 1200);
};

// Nombre e iniciales del local
const establishmentName = computed(() => {
  return props.offer.establishment?.name || props.offer.establishment_name || 'Comercio adherido';
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'TA';
});

// Reloj dinámico y temporizador en tiempo real
const now = ref(Date.now());
let timerInterval = null;

onMounted(() => {
  timerInterval = setInterval(() => {
    now.value = Date.now();
  }, 30000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});

const timeCountdown = computed(() => {
  if (!props.offer.expiration_datetime) return null;
  const end = new Date(props.offer.expiration_datetime).getTime();
  const diff = end - now.value;

  if (diff <= 0) {
    return { label: 'Ventana cerrada', isExpired: true, isCritical: false };
  }

  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (hours === 0) {
    return {
      label: `Cierra en ${remainingMinutes}m`,
      isExpired: false,
      isCritical: remainingMinutes <= 30,
      isUrgent: true,
    };
  }

  if (hours < 2) {
    return {
      label: `Cierra en ${hours}h ${remainingMinutes}m`,
      isExpired: false,
      isCritical: false,
      isUrgent: true,
    };
  }

  return {
    label: `Cierra en ${hours}h`,
    isExpired: false,
    isCritical: false,
    isUrgent: false,
  };
});

// Ventana horaria de retiro formateada
const formattedPickupWindow = computed(() => {
  if (!props.offer.pickup_start_datetime && !props.offer.expiration_datetime) return null;
  try {
    const start = props.offer.pickup_start_datetime ? new Date(props.offer.pickup_start_datetime) : null;
    const end = props.offer.expiration_datetime ? new Date(props.offer.expiration_datetime) : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
      const startTime = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
      const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
      return `${dateLabel}, ${startTime} - ${endTime} hs`;
    }

    if (end) {
      return `Hasta ${end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })} hs`;
    }
  } catch (e) {
    return null;
  }
  return null;
});

</script>

<template>
  <article
    class="customer-card group"
    @click="emit('click', offer)"
    role="button"
    tabindex="0"
    :aria-label="`Bolsa sorpresa ${offer.title} de ${establishmentName}`"
    @keydown.enter="emit('click', offer)"
    @keydown.space.prevent="emit('click', offer)"
  >
    <!-- Cabecera Superior: Establecimiento + Distancia + Disponibles -->
    <header class="flex items-center justify-between gap-2">
      <!-- Establecimiento y distancia -->
      <div class="flex items-center gap-2 min-w-0">
        <span
          class="w-6 h-6 rounded-full bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-[#A78BFA] text-[10px] font-bold flex items-center justify-center shrink-0"
        >
          {{ establishmentInitials }}
        </span>
        <span class="text-xs font-semibold text-[#CBD5E1] truncate">
          {{ establishmentName }}
        </span>
        <span
          v-if="distance"
          class="shrink-0 text-[11px] text-[#A5A8C2]/60"
        >
          • {{ distance }}
        </span>
      </div>

      <!-- Disponibilidad / Cupos -->
      <div v-if="offer.quantity" class="shrink-0 flex items-center gap-1.5 text-[11px] font-medium text-[#A5A8C2]">
        <span
          class="w-1.5 h-1.5 rounded-full"
          :class="offer.quantity === 1 ? 'bg-[#EF4444]' : offer.quantity <= 3 ? 'bg-[#F59E0B]' : 'bg-[#10B981]'"
        ></span>
        <span>{{ offer.quantity }} {{ offer.quantity === 1 ? 'pack' : 'packs' }}</span>
      </div>
    </header>

    <!-- Título y Temporizador de Cierre -->
    <div class="space-y-1">
      <div class="flex items-start justify-between gap-2">
        <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-[#A78BFA] transition-colors line-clamp-1 leading-snug">
          {{ offer.title }}
        </h3>
        <!-- Temporizador de Cierre sutil -->
        <span
          v-if="timeCountdown"
          class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-md border"
          :class="[
            timeCountdown.isCritical
              ? 'bg-[#EF4444]/15 text-[#FCA5A5] border-[#EF4444]/30 animate-pulse'
              : timeCountdown.isUrgent
              ? 'bg-[#F59E0B]/15 text-[#FDE68A] border-[#F59E0B]/30'
              : 'bg-white/[0.04] text-[#A5A8C2] border-white/[0.08]'
          ]"
        >
          {{ timeCountdown.label }}
        </span>
      </div>

      <!-- Descripción Breve -->
      <p v-if="offer.description" class="text-xs text-[#94A3B8] line-clamp-2 leading-relaxed">
        {{ offer.description }}
      </p>
    </div>

    <!-- Etiquetas Minimalistas (Categoría / Alérgenos) -->
    <div v-if="offer.allergens?.length || offer.category" class="flex flex-wrap items-center gap-1">
      <span
        v-if="offer.category"
        class="bg-white/[0.04] border border-white/[0.08] text-[#CBD5E1] text-[10px] font-medium px-1.5 py-0.5 rounded"
      >
        {{ offer.category }}
      </span>
      <span
        v-for="(alg, idx) in (offer.allergens || []).slice(0, 3)"
        :key="idx"
        class="bg-white/[0.03] text-[#A5A8C2] text-[10px] font-medium px-1.5 py-0.5 rounded"
      >
        {{ alg }}
      </span>
      <span
        v-if="(offer.allergens || []).length > 3"
        class="text-[10px] text-[#787596] font-medium self-center"
      >
        +{{ offer.allergens.length - 3 }}
      </span>
    </div>

    <!-- Franja de Retiro y Precios / CTA -->
    <div class="space-y-2 mt-auto pt-2 border-t border-[#3D3450]/50">
      <!-- Horario de Retiro Minimalista -->
      <div
        v-if="formattedPickupWindow"
        class="flex items-center gap-1.5 text-xs text-[#A5A8C2]"
      >
        <svg class="w-3.5 h-3.5 text-[#A5A8C2]/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke-width="2"/>
          <polyline points="12 6 12 12 16 14" stroke-width="2"/>
        </svg>
        <span class="truncate">
          Retiro: <strong class="text-white font-medium">{{ formattedPickupWindow }}</strong>
        </span>
      </div>

      <!-- Fila de Precios y Botón Comprar -->
      <div class="flex items-center justify-between pt-0.5">
        <div class="flex flex-col">
          <span
            v-if="offer.minimum_value && Number(offer.minimum_value) > Number(offer.price)"
            class="text-[10px] text-[#787596] line-through font-medium leading-none"
          >
            ${{ Number(offer.minimum_value).toLocaleString('es-AR') }}
          </span>
          <div class="flex items-baseline">
            <span class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight">
              ${{ Number(offer.price || 0).toLocaleString('es-AR') }}
            </span>
          </div>
        </div>

        <!-- Botón Rápido de Rescate -->
        <button
          type="button"
          class="relative overflow-hidden text-xs font-bold px-3.5 py-1.5 rounded-xl transition-all duration-200 flex items-center gap-1.5 shadow-sm active:scale-95 select-none"
          :class="[
            isAdding
              ? 'bg-[#10B981] text-white'
              : 'bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-[#7C3AED]/20 hover:shadow-[#7C3AED]/35'
          ]"
          @click="handleQuickAdd"
          :aria-label="`Añadir ${offer.title} al carrito`"
        >
          <template v-if="isAdding">
            <svg class="w-3.5 h-3.5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <polyline points="20 6 9 17 4 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>¡Agregado!</span>
          </template>
          <template v-else>
            <span>Comprar</span>
          </template>
        </button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.customer-card {
  background-color: #2D2438;
  border: 1px solid #3D3450;
  border-radius: 14px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1),
              border-color 0.2s ease,
              box-shadow 0.2s ease;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  height: 100%;
  cursor: pointer;
}

.customer-card:hover {
  transform: translateY(-3px);
  border-color: #7C3AED;
  box-shadow: 0 8px 24px -4px rgba(124, 58, 237, 0.15),
              0 4px 12px -2px rgba(0, 0, 0, 0.3);
}

.customer-card:focus-visible {
  outline: 2px solid #7C3AED;
  outline-offset: 2px;
}
</style>
