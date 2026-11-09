<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import BaseIcon from '../atoms/BaseIcon.vue';

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

const emit = defineEmits(['click']);

// Datos del comercio
const establishment = computed(() => {
  return props.offer.establishment || {
    name: props.offer.establishment_name || 'Comercio adherido',
    address: props.offer.establishment_address,
  };
});

// Iniciales para el avatar de 24x24 (Figma: LP en brand-light sobre brand)
const storeInitials = computed(() => {
  const name = establishment.value.name || 'Comercio';
  const words = name.trim().split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
});

const effectiveDistance = computed(() => {
  return props.distance || props.offer?._distanceFormatted || null;
});

// Tags unificados (categoría y alérgenos con estilo Figma: outline sutil de 22px de altura)
const allTags = computed(() => {
  const list = [];
  if (props.offer.category) {
    list.push(props.offer.category);
  }
  if (Array.isArray(props.offer.allergens)) {
    props.offer.allergens.forEach((alg) => {
      if (!list.includes(alg)) list.push(alg);
    });
  }
  return list;
});

// Formato de precios exacto Figma (Tachado 13px arriba, Rescate 24px ExtraBold abajo)
const formattedPrice = computed(() => {
  const p = Number(props.offer.price || 0);
  return p.toLocaleString('es-AR');
});

const formattedOriginalPrice = computed(() => {
  const orig = Number(props.offer.minimum_value || 0);
  const current = Number(props.offer.price || 0);
  return orig > current ? orig.toLocaleString('es-AR') : null;
});

// Temporizador y ventana horaria
const now = ref(Date.now());
let timer = null;

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now();
  }, 30000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

const formattedPickupWindow = computed(() => {
  if (!props.offer.pickup_start_datetime && !props.offer.expiration_datetime) return null;
  try {
    const start = props.offer.pickup_start_datetime ? new Date(props.offer.pickup_start_datetime) : null;
    const end = props.offer.expiration_datetime ? new Date(props.offer.expiration_datetime) : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
      const startTime = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: true });
      const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: true });
      return `Retiro: ${dateLabel}, ${startTime} - ${endTime} hs`;
    }

    if (end) {
      const isToday = end.toDateString() === new Date().toDateString();
      const dateLabel = isToday ? 'Hoy' : end.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
      const endTime = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: true });
      return `Retiro: ${dateLabel} hasta ${endTime} hs`;
    }
  } catch (e) {
    return null;
  }
  return null;
});

const countdown = computed(() => {
  if (!props.offer.expiration_datetime) return null;
  const end = new Date(props.offer.expiration_datetime).getTime();
  const diff = end - now.value;

  if (diff <= 0) {
    return { label: 'Ventana cerrada', isDanger: true };
  }

  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (hours === 0) {
    return {
      label: `Cierra en ${remainingMinutes}m`,
      isDanger: remainingMinutes <= 30,
      isWarning: remainingMinutes > 30,
    };
  }

  if (hours < 2) {
    return {
      label: `Cierra en ${hours}h ${remainingMinutes}m`,
      isDanger: false,
      isWarning: true,
    };
  }

  return {
    label: `Cierra en ${hours}h`,
    isDanger: false,
    isWarning: false,
  };
});
</script>

<template>
  <article
    class="bg-surface border border-border-subtle rounded-card p-5 shadow-card hover:shadow-card-hover hover:border-brand transition-all duration-200 flex flex-col justify-between h-full min-w-0 max-w-full sm:max-w-[520px] w-full cursor-pointer group select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand overflow-hidden"
    role="button"
    tabindex="0"
    :aria-label="`Bolsa sorpresa ${offer.title} de ${establishment.name}`"
    @click="emit('click', offer)"
    @keydown.enter="emit('click', offer)"
    @keydown.space.prevent="emit('click', offer)"
  >
    <!-- 1. Header (Figma 14:255): EstablishmentPill con StoreName -->
    <header class="h-4 flex items-center justify-between gap-2">
      <div class="flex items-center gap-1.5 min-w-0">
        <!-- Store Name (Figma 14:259: 12px Regular text-content-secondary) -->
        <span class="text-xs text-content-secondary font-normal truncate">
          {{ establishment.name }}
        </span>
        <span v-if="effectiveDistance" class="text-xs text-content-secondary font-normal shrink-0">
          • {{ effectiveDistance }}
        </span>
      </div>
    </header>

    <!-- 2. Body (Figma 14:260: Gap 6px): Título 18px Bold text-content-strong + Descripción 14px Regular text-content-secondary -->
    <div class="flex flex-col gap-1.5 flex-1 min-w-0 mt-2">
      <h3 class="text-[18px] font-bold text-content-strong leading-6 font-heading line-clamp-1 group-hover:text-brand transition-colors">
        {{ offer.title }}
      </h3>

      <p v-if="offer.description" class="text-[14px] text-content-secondary font-normal line-clamp-2 leading-5">
        {{ offer.description }}
      </p>
    </div>

    <!-- 3. Tags (Figma 14:263): Pastillas sutiles con borde de 22px de alto, texto 11px text-content-secondary -->
    <div v-if="allTags.length" class="h-[22px] flex items-center gap-1.5 overflow-hidden mt-2">
      <span
        v-for="(tag, idx) in allTags.slice(0, 3)"
        :key="idx"
        class="h-[22px] px-2 py-0.5 rounded-full border border-border-subtle bg-transparent text-[11px] text-content-secondary font-medium leading-none flex items-center justify-center shrink-0 select-none"
      >
        {{ tag }}
      </span>
      <span
        v-if="allTags.length > 3"
        class="text-[11px] text-content-muted font-medium self-center tabular-nums pl-0.5"
      >
        +{{ allTags.length - 3 }}
      </span>
    </div>

    <!-- 4. Footer (Figma 14:268): Borde superior 1px border-border-subtle, gap 10px -->
    <div class="pt-2.5 border-t border-border-subtle flex flex-col gap-2 mt-auto">
      <!-- PickupRow (Figma 14:269): Distribución con horario e indicador de cierre -->
      <div class="flex flex-col gap-1">
        <!-- Fila 1: Horario de Retiro con Icono Reloj 14x14 (Figma 14:270: 12px Regular text-content) -->
        <div
          v-if="formattedPickupWindow"
          class="flex items-center gap-1.5 text-xs text-content tabular-nums font-normal"
        >
          <BaseIcon name="clock" size="xs" class="text-content-secondary shrink-0 w-3.5 h-3.5" />
          <span>{{ formattedPickupWindow }}</span>
        </div>

        <!-- Fila 2: CountdownBadge (Figma 2011:4152: 12px SemiBold text-brand) -->
        <div v-if="countdown" class="h-4 flex items-center">
          <span
            class="text-xs font-semibold tabular-nums leading-none text-brand"
          >
            {{ countdown.label }}
          </span>
        </div>
      </div>

      <!-- PriceActionRow (Figma 14:277: Altura 44px, HUG PriceBlock sin botón comprar) -->
      <div class="h-11 flex items-center justify-between pt-0.5">
        <!-- PriceBlock (Figma 14:278): Tachado 13px text-content-muted arriba, Rescate 24px ExtraBold text-content-strong abajo -->
        <div class="flex flex-col justify-center leading-none">
          <span
            v-if="formattedOriginalPrice"
            class="text-[13px] font-medium text-content-muted line-through tabular-nums leading-none"
          >
            ${{ formattedOriginalPrice }}
          </span>

          <span class="text-2xl font-extrabold text-content-strong tracking-tight tabular-nums leading-tight pt-0.5 font-heading">
            ${{ formattedPrice }}
          </span>
        </div>
      </div>
    </div>
  </article>
</template>
