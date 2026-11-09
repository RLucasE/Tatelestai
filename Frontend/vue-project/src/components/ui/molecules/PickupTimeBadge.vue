<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import BaseIcon from '../atoms/BaseIcon.vue';
import BaseBadge from '../atoms/BaseBadge.vue';

const props = defineProps({
  pickupStart: {
    type: String,
    default: null,
  },
  expiration: {
    type: String,
    default: null,
  },
  showCountdown: {
    type: Boolean,
    default: true,
  },
  showWindow: {
    type: Boolean,
    default: true,
  },
  asBadge: {
    type: Boolean,
    default: false,
  },
});

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

// Ventana horaria formateada
const formattedPickupWindow = computed(() => {
  if (!props.pickupStart && !props.expiration) return null;
  try {
    const start = props.pickupStart ? new Date(props.pickupStart) : null;
    const end = props.expiration ? new Date(props.expiration) : null;

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

// Temporizador de cierre
const countdown = computed(() => {
  if (!props.expiration) return null;
  const end = new Date(props.expiration).getTime();
  const diff = end - now.value;

  if (diff <= 0) {
    return { label: 'Ventana cerrada', variant: 'danger', isCritical: false };
  }

  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (hours === 0) {
    return {
      label: `Cierra en ${remainingMinutes}m`,
      variant: remainingMinutes <= 30 ? 'danger' : 'warning',
      isCritical: remainingMinutes <= 30,
    };
  }

  if (hours < 2) {
    return {
      label: `Cierra en ${hours}h ${remainingMinutes}m`,
      variant: 'warning',
      isCritical: false,
    };
  }

  return {
    label: `Cierra en ${hours}h`,
    variant: 'neutral',
    isCritical: false,
  };
});
</script>

<template>
  <div class="flex items-center gap-x-2 gap-y-1 flex-wrap">
    <!-- Horario de Retiro -->
    <div
      v-if="showWindow && formattedPickupWindow"
      class="flex items-center gap-1.5 text-xs text-content-secondary tabular-nums"
    >
      <BaseIcon name="clock" size="xs" class="text-content-secondary/70 shrink-0" />
      <span>
        Retiro: <strong class="text-content-strong font-medium">{{ formattedPickupWindow }}</strong>
      </span>
    </div>

    <!-- Countdown: Texto directo limpio (Estilo Figma) o Badge Pill si asBadge es true -->
    <span
      v-if="showCountdown && countdown && !asBadge"
      class="text-xs font-semibold tabular-nums shrink-0"
      :class="[
        countdown.variant === 'danger'
          ? 'text-danger'
          : countdown.variant === 'warning'
            ? 'text-amber-400'
            : 'text-content-secondary'
      ]"
    >
      {{ countdown.label }}
    </span>

    <BaseBadge
      v-else-if="showCountdown && countdown && asBadge"
      :variant="countdown.variant"
      :pulse="countdown.isCritical"
      size="xs"
      class="shrink-0"
    >
      {{ countdown.label }}
    </BaseBadge>
  </div>
</template>
