<script setup>
import OfferCard from '@/components/ui/organisms/OfferCard.vue';

defineProps({
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
</script>

<template>
  <OfferCard
    :offer="offer"
    :distance="distance"
    @click="emit('click', $event)"
  />
</template>
