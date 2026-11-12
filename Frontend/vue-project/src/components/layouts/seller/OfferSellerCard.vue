<script setup>
import { computed } from "vue";

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

const isExpired = computed(() => {
  if (!props.offer.expiration_datetime) return false;
  return new Date(props.offer.expiration_datetime) < new Date();
});

const isInactive = computed(() => {
  return props.offer.state === "inactive";
});

const discountPercentage = computed(() => {
  const price = Number(props.offer.price || 0);
  const minVal = Number(props.offer.minimum_value || 0);
  if (!price || !minVal || minVal <= price) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const formattedPickupWindow = computed(() => {
  if (!props.offer.pickup_start_datetime && !props.offer.expiration_datetime) return "-";
  try {
    const start = props.offer.pickup_start_datetime
      ? new Date(props.offer.pickup_start_datetime)
      : null;
    const end = props.offer.expiration_datetime
      ? new Date(props.offer.expiration_datetime)
      : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday
        ? "Hoy"
        : start.toLocaleDateString("es-AR", { day: "numeric", month: "short" });
      const startTime = start.toLocaleTimeString("es-AR", {
        hour: "2-digit",
        minute: "2-digit",
      });
      const endTime = end.toLocaleTimeString("es-AR", {
        hour: "2-digit",
        minute: "2-digit",
      });
      return `${dateLabel}, ${startTime} - ${endTime} hs`;
    }

    if (end) {
      return `Hasta ${end.toLocaleDateString("es-AR")} ${end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })}`;
    }
    return "-";
  } catch {
    return "-";
  }
});
</script>

<template>
  <div
    class="rounded-[18px] bg-surface border border-border-subtle p-6 flex flex-col justify-between gap-4 transition hover:border-warning text-content"
    :class="{ 'opacity-65': isExpired || isInactive }"
  >
    <div class="space-y-3">
      <div class="flex items-center justify-between gap-2">
        <span class="px-2.5 py-0.5 rounded-full bg-surface-sunken text-[11px] font-bold text-content">
          🛍️ Pack Sorpresa
        </span>
        <span
          v-if="isInactive"
          class="px-2.5 py-0.5 rounded-full bg-content-secondary/15 text-content-secondary text-[11px] font-bold uppercase"
        >
          Inactiva
        </span>
        <span
          v-else-if="isExpired"
          class="px-2.5 py-0.5 rounded-full bg-danger/15 text-danger text-[11px] font-bold uppercase"
        >
          Expirada
        </span>
        <span
          v-else
          class="px-2.5 py-0.5 rounded-full bg-brand/15 text-brand text-[11px] font-bold uppercase"
        >
          Activa
        </span>
      </div>

      <h3 class="text-[16px] font-bold text-content leading-snug">
        {{ offer.title }}
      </h3>

      <p class="text-[12.5px] text-content-secondary line-clamp-2">
        {{ offer.description }}
      </p>

      <div v-if="offer.allergens?.length" class="flex flex-wrap gap-1.5">
        <span
          v-for="(alg, idx) in offer.allergens"
          :key="idx"
          class="px-2 py-0.5 rounded-full bg-surface-sunken border border-border-subtle text-[11px] font-semibold text-content"
        >
          {{ alg }}
        </span>
      </div>

      <div v-if="offer.estimated_weight_kg" class="text-[12px] font-semibold text-content-secondary">
        ⚖️ Aprox. {{ offer.estimated_weight_kg }} kg
      </div>
    </div>

    <div class="pt-3 border-t border-border-subtle space-y-3">
      <div class="flex items-center justify-between text-[12px]">
        <span class="text-content-secondary">🕒 {{ formattedPickupWindow }}</span>
        <span class="font-bold text-brand">
          {{ offer.quantity }} cupo{{ offer.quantity === 1 ? "" : "s" }}
        </span>
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-baseline gap-2">
          <span class="text-[18px] font-extrabold text-content">
            ${{ Number(offer.price || 0).toLocaleString("es-AR") }}
          </span>
          <span
            v-if="offer.minimum_value"
            class="text-[12px] text-content-secondary line-through"
          >
            ${{ Number(offer.minimum_value).toLocaleString("es-AR") }}
          </span>
        </div>

        <span
          v-if="discountPercentage > 0"
          class="px-2.5 py-0.5 rounded-full bg-brand text-surface text-[11px] font-bold"
        >
          -{{ discountPercentage }}%
        </span>
      </div>
    </div>
  </div>
</template>
