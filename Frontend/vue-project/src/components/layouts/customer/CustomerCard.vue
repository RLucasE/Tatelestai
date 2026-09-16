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

// Estilo visual del banner según categoría
const bannerTheme = computed(() => {
  const cat = (props.offer.category || props.offer.title || '').toLowerCase();
  if (cat.includes('panad') || cat.includes('pan')) {
    return {
      gradient: 'from-amber-950/70 via-[#2D2438] to-[#1A1625]',
      badgeBg: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    };
  }
  if (cat.includes('vegan') || cat.includes('saludable') || cat.includes('ensalada')) {
    return {
      gradient: 'from-emerald-950/70 via-[#2D2438] to-[#1A1625]',
      badgeBg: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    };
  }
  if (cat.includes('dulce') || cat.includes('pastel') || cat.includes('repost')) {
    return {
      gradient: 'from-pink-950/70 via-[#2D2438] to-[#1A1625]',
      badgeBg: 'bg-pink-500/20 text-pink-300 border-pink-500/30',
    };
  }
  if (cat.includes('pizza') || cat.includes('plato') || cat.includes('almuerzo') || cat.includes('comida')) {
    return {
      gradient: 'from-orange-950/70 via-[#2D2438] to-[#1A1625]',
      badgeBg: 'bg-orange-500/20 text-orange-300 border-orange-500/30',
    };
  }
  return {
    gradient: 'from-[#4C1D95]/60 via-[#2D2438] to-[#1A1625]',
    badgeBg: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
  };
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
    <!-- Visual Banner (16:9) -->
    <div class="card-hero-banner relative overflow-hidden bg-gradient-to-br" :class="bannerTheme.gradient">
      <!-- Imagen si existe -->
      <img
        v-if="offer.image_url || offer.cover_image || offer.image"
        :src="offer.image_url || offer.cover_image || offer.image"
        :alt="offer.title"
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
        loading="lazy"
      />

      <!-- Capa de sombra inferior suave para asegurar contraste -->
      <div class="absolute inset-0 bg-gradient-to-t from-[#2D2438] via-transparent to-black/30 pointer-events-none"></div>

      <!-- Icono temático de fondo sutil si no hay foto -->
      <div
        v-if="!offer.image_url && !offer.cover_image && !offer.image"
        class="absolute inset-0 flex items-center justify-center text-white/10 select-none group-hover:scale-110 transition-transform duration-300"
      >
        <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M3 6h18" stroke-width="1.5"/>
          <path d="M16 10a4 4 0 0 1-8 0" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>


      <!-- Indicador minimalista de cupos disponibles -->
      <div v-if="offer.quantity" class="absolute top-2.5 left-2.5 z-10">
        <span
          class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-[#151120]/80 backdrop-blur-md border border-[#3D3450] text-[#CBD5E1]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="offer.quantity === 1 ? 'bg-[#EF4444]' : offer.quantity <= 3 ? 'bg-[#F59E0B]' : 'bg-[#10B981]'"
          ></span>
          <span>{{ offer.quantity }} {{ offer.quantity === 1 ? 'disponible' : 'disponibles' }}</span>
        </span>
      </div>

      <!-- Temporizador Flotante Dinámico en la esquina inferior del Banner -->
      <div v-if="timeCountdown" class="absolute bottom-2.5 right-3 z-10">
        <span
          class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full backdrop-blur-md border transition-colors shadow-xs"
          :class="[
            timeCountdown.isCritical
              ? 'bg-[#EF4444]/25 text-[#FCA5A5] border-[#EF4444]/40 animate-pulse'
              : timeCountdown.isUrgent
              ? 'bg-[#F59E0B]/25 text-[#FDE68A] border-[#F59E0B]/40'
              : 'bg-[#151120]/80 text-[#CBD5E1] border-[#3D3450]'
          ]"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <polyline points="12 6 12 12 16 14" stroke-width="2"/>
          </svg>
          <span>{{ timeCountdown.label }}</span>
        </span>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3.5">
      <!-- Establecimiento y Distancia -->
      <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2 min-w-0">
            <span
              class="w-6 h-6 rounded-full bg-[#7C3AED]/20 border border-[#7C3AED]/40 text-[#A78BFA] text-[10px] font-extrabold flex items-center justify-center shrink-0"
            >
              {{ establishmentInitials }}
            </span>
            <span class="text-xs font-semibold text-[#CBD5E1] truncate">
              {{ establishmentName }}
            </span>
          </div>

          <!-- Píldora de distancia -->
          <span
            v-if="distance"
            class="shrink-0 inline-flex items-center gap-1 bg-[#1F1A2C] border border-[#3D3450] text-[#A78BFA] text-[11px] font-bold px-2 py-0.5 rounded-full"
          >
            <svg class="w-2.5 h-2.5 text-[#7C3AED]" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
            {{ distance }}
          </span>
        </div>

        <!-- Título de la Oferta -->
        <h3 class="text-base font-bold text-white group-hover:text-[#A78BFA] transition-colors line-clamp-1 leading-snug">
          {{ offer.title }}
        </h3>

        <!-- Descripción Breve -->
        <p v-if="offer.description" class="text-xs text-[#94A3B8] line-clamp-2 leading-relaxed">
          {{ offer.description }}
        </p>

        <!-- Etiquetas de Alérgenos / Tipo de Dieta -->
        <div v-if="offer.allergens?.length || offer.category" class="flex flex-wrap items-center gap-1.5 pt-1">
          <span
            v-if="offer.category"
            class="bg-[#221C33] border border-[#3D3450] text-[#CBD5E1] text-[10px] font-medium px-2 py-0.5 rounded-md"
          >
            {{ offer.category }}
          </span>
          <span
            v-for="(alg, idx) in (offer.allergens || []).slice(0, 3)"
            :key="idx"
            class="bg-[#1F1A2C] text-[#94A3B8] text-[10px] font-medium px-2 py-0.5 rounded-md"
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
      </div>

      <!-- Barra de Horario de Retiro y Precios -->
      <div class="space-y-3 pt-1">
        <!-- Horario de Retiro -->
        <div
          v-if="formattedPickupWindow"
          class="bg-[#221C33] border border-[#3D3450] rounded-xl px-3 py-1.5 flex items-center gap-2 text-xs"
        >
          <svg class="w-3.5 h-3.5 text-[#F59E0B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <polyline points="12 6 12 12 16 14" stroke-width="2"/>
          </svg>
          <span class="text-[#A5A8C2] truncate">
            Retiro: <strong class="text-white font-semibold">{{ formattedPickupWindow }}</strong>
          </span>
        </div>

        <!-- Fila de Precios y Acción Rápida -->
        <div class="flex items-center justify-between pt-2 border-t border-[#3D3450]/60">
          <div class="flex flex-col">
            <span
              v-if="offer.minimum_value && Number(offer.minimum_value) > Number(offer.price)"
              class="text-[11px] text-[#787596] line-through font-medium"
            >
              ${{ Number(offer.minimum_value).toLocaleString('es-AR') }}
            </span>
            <div class="flex items-baseline">
              <span class="text-xl font-extrabold text-white tracking-tight">
                ${{ Number(offer.price || 0).toLocaleString('es-AR') }}
              </span>
            </div>
          </div>

          <!-- Botón Rápido de Rescate con Microinteracción -->
          <button
            type="button"
            class="relative overflow-hidden text-xs font-bold px-3.5 py-2 rounded-xl transition-all duration-200 flex items-center gap-1.5 shadow-md active:scale-95 select-none"
            :class="[
              isAdding
                ? 'bg-[#10B981] text-white shadow-[#10B981]/30'
                : 'bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-[#7C3AED]/25 hover:shadow-[#7C3AED]/40'
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
    </div>
  </article>
</template>

<style scoped>
.customer-card {
  background-color: #2D2438;
  border: 1px solid #4A4058;
  border-radius: 16px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
              border-color 0.25s ease,
              box-shadow 0.25s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
  cursor: pointer;
}

.customer-card:hover {
  transform: translateY(-4px);
  border-color: #7C3AED;
  box-shadow: 0 12px 28px -4px rgba(124, 58, 237, 0.2),
              0 6px 14px -2px rgba(0, 0, 0, 0.4);
}

.customer-card:focus-visible {
  outline: 2px solid #7C3AED;
  outline-offset: 2px;
}

.card-hero-banner {
  aspect-ratio: 16 / 9;
  min-height: 140px;
}
</style>
