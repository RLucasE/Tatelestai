<template>
  <article
    class="bg-surface border border-border-subtle hover:border-brand/40 rounded-[22px] p-5 sm:p-6 shadow-card transition-all duration-200 flex flex-col gap-4 text-content"
  >
    <!-- Cabecera de la Tarjeta -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border-subtle">
      <!-- Datos del Comercio -->
      <div class="flex items-center gap-3">
        <!-- Avatar Iniciales -->
        <div class="w-10 h-10 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-center text-xs font-extrabold text-brand shrink-0">
          {{ establishmentInitials }}
        </div>

        <div class="flex flex-col min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="text-base font-extrabold text-content-strong truncate hover:text-brand transition-colors">
              {{ establishmentName }}
            </h3>
            <span class="text-[11px] text-content-muted shrink-0">• {{ formattedCreatedAt }}</span>
          </div>

          <RouterLink
            v-if="establishment.id && establishment.address"
            :to="`/customer/establishment/${establishment.id}`"
            class="text-xs text-content-secondary hover:text-brand flex items-center gap-1 transition-colors truncate mt-0.5"
          >
            <svg class="w-3.5 h-3.5 text-brand shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="truncate">{{ establishment.address }}</span>
          </RouterLink>
        </div>
      </div>

      <!-- Badge de Estado: Rescatado / Entregado -->
      <div class="flex items-center self-start sm:self-center gap-2">
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-success/15 text-success border border-success/30"
        >
          <svg class="w-3.5 h-3.5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          <span>Rescatado con éxito</span>
        </span>
      </div>
    </header>

    <!-- Franja de Impacto Ecológico -->
    <div class="flex items-center justify-between gap-3 px-3.5 py-2 rounded-xl bg-surface-sunken border border-border-subtle text-xs text-brand font-semibold">
      <div class="flex items-center gap-2 truncate">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span class="truncate font-bold">Alimento salvado del desperdicio</span>
      </div>
      <span class="text-[11px] font-bold text-content-muted shrink-0">
        Reserva #{{ purchase.sell_id || purchase.id }}
      </span>
    </div>

    <!-- Lista de Packs Rescatados -->
    <div class="space-y-2 py-1">
      <div
        v-for="(offer, index) in offers"
        :key="index"
        class="flex items-start justify-between gap-3 p-3 rounded-xl bg-surface-sunken border border-border-subtle/60"
      >
        <div class="flex items-start gap-2.5 min-w-0">
          <span class="px-2 py-0.5 rounded-md bg-brand text-surface text-xs font-bold shrink-0 shadow-2xs">
            x{{ offer.offer_quantity }}
          </span>
          <div class="min-w-0">
            <h4 class="text-sm font-bold text-content-strong truncate">{{ offer.pack_name }}</h4>
            <p v-if="offer.pack_description" class="text-xs text-content-secondary line-clamp-1 mt-0.5">
              {{ offer.pack_description }}
            </p>
          </div>
        </div>

        <div class="text-right shrink-0">
          <div class="text-sm font-black text-content-strong">
            ${{ (Number(offer.offer_quantity) * Number(offer.pack_price)).toLocaleString('es-AR') }}
          </div>
          <span class="text-[10px] text-content-muted">
            ${{ Number(offer.pack_price).toLocaleString('es-AR') }} c/u
          </span>
        </div>
      </div>
    </div>

    <!-- Footer con Total Abonado -->
    <footer class="mt-auto pt-3 border-t border-border-subtle flex items-center justify-between gap-4">
      <div class="flex items-center gap-1.5 text-xs text-success font-semibold">
        <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Retiro confirmado</span>
      </div>

      <div class="flex items-baseline gap-2">
        <span class="text-xs text-content-secondary uppercase font-semibold">Total abonado:</span>
        <span class="text-xl font-black text-content-strong tracking-tight">
          ${{ Number(totalPrice).toLocaleString('es-AR') }}
        </span>
      </div>
    </footer>

    <!-- Franja de Calificación -->
    <div class="pt-2.5 border-t border-border-subtle/60 flex items-center justify-between gap-3 text-xs">
      <div v-if="localRating || purchase.user_rating" class="flex items-center gap-1.5 text-brand-light font-bold">
        <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
          <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
        </svg>
        <span>Tu calificación: {{ localRating || purchase.user_rating }}/5</span>
      </div>

      <div v-else-if="purchase.can_review && !isReviewedLocal" class="w-full flex justify-end">
        <button
          type="button"
          @click="showRatingModal = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-[10px] text-xs font-bold text-brand-light bg-brand-light/10 hover:bg-brand-light/20 border border-brand-light/30 transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
        >
          <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
          </svg>
          <span>Calificar compra</span>
        </button>
      </div>

      <div v-else-if="purchase.is_reviewed || isReviewedLocal" class="flex items-center gap-1 text-success font-semibold">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Calificado</span>
      </div>
    </div>

    <!-- Modal de Calificación -->
    <QuickRatingModal
      v-model="showRatingModal"
      :sell-id="purchase.sell_id || purchase.id"
      :establishment-name="establishmentName"
      @reviewed="handleReviewed"
    />
  </article>
</template>

<script setup>
import { ref, computed } from 'vue';
import { RouterLink } from 'vue-router';
import QuickRatingModal from '@/components/common/QuickRatingModal.vue';

const showRatingModal = ref(false);
const localRating = ref(null);
const isReviewedLocal = ref(false);

const handleReviewed = ({ rating }) => {
  localRating.value = rating;
  isReviewedLocal.value = true;
};

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
});

// Información del local
const establishment = computed(() => {
  return props.purchase.establishment || props.purchase.food_establishment || {};
});

const establishmentName = computed(() => {
  return establishment.value.name || `Comercio #${props.purchase.sold_by || ''}`;
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'TA';
});

// Lista de ofertas / packs
const offers = computed(() => {
  return props.purchase.offers || props.purchase.sell_details || [];
});

// Total calculado o recibido
const totalPrice = computed(() => {
  if (props.purchase.total_price !== undefined && props.purchase.total_price !== null) {
    return props.purchase.total_price;
  }
  return offers.value.reduce((total, offer) => {
    return total + (Number(offer.offer_quantity || 1) * Number(offer.pack_price || 0));
  }, 0);
});

// Fecha formateada
const formattedCreatedAt = computed(() => {
  if (!props.purchase.created_at) return '';
  const date = new Date(props.purchase.created_at);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
});
</script>
