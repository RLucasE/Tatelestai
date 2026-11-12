<script setup>
import { computed } from 'vue';
import BaseAvatar from '../atoms/BaseAvatar.vue';
import BaseIcon from '../atoms/BaseIcon.vue';
import RatingBadge from './RatingBadge.vue';

const props = defineProps({
  establishment: {
    type: Object,
    default: () => ({}),
  },
  fallbackName: {
    type: String,
    default: 'Comercio adherido',
  },
  distance: {
    type: String,
    default: null,
  },
  address: {
    type: String,
    default: '',
  },
  interactive: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['click']);

const name = computed(() => {
  return props.establishment?.name || props.fallbackName;
});

const initials = computed(() => {
  const n = name.value.trim();
  const words = n.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return n.slice(0, 2).toUpperCase() || 'CA';
});
</script>

<template>
  <!-- Modo Tarjeta Interactivo (ej: en OfferModal) -->
  <div
    v-if="interactive"
    class="bg-surface-sunken border border-border-subtle hover:border-brand/60 rounded-card p-3 sm:p-3.5 flex items-center justify-between gap-3 transition-all cursor-pointer group select-none"
    @click="emit('click', establishment)"
    title="Ver comercio y catálogo de ofertas"
  >
    <div class="flex items-center gap-3 min-w-0">
      <BaseAvatar :name="name" :initials="initials" size="md" />
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-sm font-bold text-content-strong group-hover:text-brand-light transition-colors truncate">
          <span>{{ name }}</span>
          <BaseIcon name="arrow-right" size="xs" class="text-content-secondary transition-transform group-hover:translate-x-0.5" />
        </div>
        <p v-if="address || establishment.address" class="text-xs text-content-secondary truncate flex items-center gap-1 mt-0.5">
          <BaseIcon name="pin" size="xs" class="text-content-secondary/70 shrink-0" />
          <span class="truncate">{{ address || establishment.address }}</span>
        </p>
      </div>
    </div>
    <RatingBadge :establishment="establishment" />
  </div>

  <!-- Modo Inline Compacto (ej: en OfferCard Header) -->
  <div v-else class="flex items-center gap-2 min-w-0">
    <BaseAvatar :name="name" :initials="initials" size="xs" />
    <span class="text-xs font-semibold text-content-strong truncate">
      {{ name }}
    </span>
    <RatingBadge :establishment="establishment" />
    <span v-if="distance" class="shrink-0 text-xs text-content-secondary/70 tabular-nums">
      • {{ distance }}
    </span>
  </div>
</template>
