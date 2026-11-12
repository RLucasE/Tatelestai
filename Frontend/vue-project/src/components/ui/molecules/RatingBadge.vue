<script setup>
import { computed } from 'vue';
import BaseIcon from '../atoms/BaseIcon.vue';

const props = defineProps({
  establishment: {
    type: Object,
    default: () => ({}),
  },
});

const ratingInfo = computed(() => {
  const est = props.establishment || {};
  const recentAvg = est.recent_rating_average != null ? Number(est.recent_rating_average) : null;
  const histAvg = est.rating_average != null ? Number(est.rating_average) : null;
  const histCount = Number(est.reviews_count || 0);

  if (recentAvg !== null && recentAvg > 0) {
    return {
      type: 'recent',
      score: recentAvg.toFixed(1),
      count: Number(est.recent_reviews_count || histCount),
      globalScore: histAvg !== null ? histAvg.toFixed(1) : null,
      globalCount: histCount,
    };
  }

  if (histAvg !== null && histAvg > 0) {
    return {
      type: 'historical',
      score: histAvg.toFixed(1),
      count: histCount,
    };
  }

  return {
    type: 'new',
    score: null,
    count: 0,
  };
});
</script>

<template>
  <!-- Calificación Reciente -->
  <span
    v-if="ratingInfo.type === 'recent'"
    class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-warning/15 border border-warning/30 text-warning text-xs font-bold tabular-nums"
    :title="`Reciente: ${ratingInfo.score} ★ ${ratingInfo.globalScore ? '· Global: ' + ratingInfo.globalScore + ' ★' : ''}`"
  >
    <BaseIcon name="star-fill" size="xs" />
    <span>{{ ratingInfo.score }}</span>
  </span>

  <!-- Calificación Histórica -->
  <span
    v-else-if="ratingInfo.type === 'historical'"
    class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-warning/10 border border-warning/20 text-warning text-xs font-bold tabular-nums"
    :title="`Global: ${ratingInfo.score} ★`"
  >
    <BaseIcon name="star-fill" size="xs" />
    <span>{{ ratingInfo.score }}</span>
  </span>
</template>
