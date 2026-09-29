<template>
  <article
    class="bg-[#2D2438] border border-white/[0.08] hover:border-[#10B981]/40 rounded-2xl p-5 sm:p-6 shadow-md transition-all duration-200 flex flex-col gap-4 text-[#E8EAF6]"
  >
    <!-- Cabecera de la Tarjeta -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#3D3450]/60">
      <!-- Datos del Comercio -->
      <div class="flex items-center gap-3">
        <!-- Avatar Iniciales -->
        <div class="w-10 h-10 rounded-xl bg-[#10B981]/15 border border-[#10B981]/30 flex items-center justify-center text-xs font-extrabold text-[#34D399] shrink-0">
          {{ establishmentInitials }}
        </div>

        <div class="flex flex-col min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="text-base font-bold text-white truncate hover:text-[#A78BFA] transition-colors">
              {{ establishmentName }}
            </h3>
            <span class="text-[11px] text-[#787596] shrink-0">• {{ formattedCreatedAt }}</span>
          </div>

          <RouterLink
            v-if="establishment.id && establishment.address"
            :to="`/customer/establishment/${establishment.id}`"
            class="text-xs text-[#A5A8C2] hover:text-white flex items-center gap-1 transition-colors truncate mt-0.5"
          >
            <svg class="w-3.5 h-3.5 text-[#10B981] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#10B981]/15 text-[#34D399] border border-[#10B981]/30"
        >
          <svg class="w-3.5 h-3.5 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          <span>Rescatado con éxito</span>
        </span>
      </div>
    </header>

    <!-- Franja de Impacto Ecológico -->
    <div class="flex items-center justify-between gap-3 px-3.5 py-2 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 text-xs text-[#34D399]">
      <div class="flex items-center gap-2 truncate">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span class="truncate font-medium">Alimento salvado del desperdicio</span>
      </div>
      <span class="text-[11px] font-bold text-white/80 shrink-0">
        Reserva #{{ purchase.sell_id || purchase.id }}
      </span>
    </div>

    <!-- Lista de Packs Rescatados -->
    <div class="space-y-2 py-1">
      <div
        v-for="(offer, index) in offers"
        :key="index"
        class="flex items-start justify-between gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/[0.04]"
      >
        <div class="flex items-start gap-2.5 min-w-0">
          <span class="px-2 py-0.5 rounded-md bg-[#10B981]/20 border border-[#10B981]/30 text-xs font-bold text-[#34D399] shrink-0">
            x{{ offer.offer_quantity }}
          </span>
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-white truncate">{{ offer.pack_name }}</h4>
            <p v-if="offer.pack_description" class="text-xs text-[#94A3B8] line-clamp-1 mt-0.5">
              {{ offer.pack_description }}
            </p>
          </div>
        </div>

        <div class="text-right shrink-0">
          <div class="text-sm font-bold text-white">
            ${{ (Number(offer.offer_quantity) * Number(offer.pack_price)).toLocaleString('es-AR') }}
          </div>
          <span class="text-[10px] text-[#787596]">
            ${{ Number(offer.pack_price).toLocaleString('es-AR') }} c/u
          </span>
        </div>
      </div>
    </div>

    <!-- Footer con Total Abonado -->
    <footer class="mt-auto pt-3 border-t border-[#3D3450]/60 flex items-center justify-between gap-4">
      <div class="flex items-center gap-1.5 text-xs text-[#A5A8C2]">
        <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Retiro confirmado</span>
      </div>

      <div class="flex items-baseline gap-2">
        <span class="text-xs text-[#A5A8C2] uppercase font-semibold">Total abonado:</span>
        <span class="text-xl font-extrabold text-white tracking-tight">
          ${{ Number(totalPrice).toLocaleString('es-AR') }}
        </span>
      </div>
    </footer>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

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
