<script setup>
import { computed, ref, watch } from "vue";
import { useRouter } from "vue-router";
import ReportModal from "./ReportModal.vue";
import EstablishmentPill from "@/components/ui/molecules/EstablishmentPill.vue";
import PriceDisplay from "@/components/ui/molecules/PriceDisplay.vue";
import PickupTimeBadge from "@/components/ui/molecules/PickupTimeBadge.vue";
import QuantityStepper from "@/components/ui/molecules/QuantityStepper.vue";
import BaseBadge from "@/components/ui/atoms/BaseBadge.vue";
import BaseButton from "@/components/ui/atoms/BaseButton.vue";
import BaseIcon from "@/components/ui/atoms/BaseIcon.vue";

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
  isVisible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "offerAction", "buyOffer"]);

const quantity = ref(1);
const showReportModal = ref(false);
const router = useRouter();

watch(
  () => props.offer,
  () => {
    quantity.value = 1;
  }
);

const availableQuantity = computed(() => {
  return props.offer?.quantity ?? props.offer?.offer_quantity ?? props.offer?.offer_max_quantity;
});

const establishment = computed(() => {
  return props.offer?.establishment || {
    id: props.offer?.food_establishment_id || props.offer?.establishment_id,
    name: props.offer?.establishment_name,
    address: props.offer?.establishment_address,
  };
});

const unitPrice = computed(() => {
  if (props.offer?.price != null) return Number(props.offer.price);
  if (props.offer?.offer_price != null) return Number(props.offer.offer_price);
  return 0;
});

const totalPrice = computed(() => {
  return unitPrice.value * (quantity.value || 1);
});

const closeModal = () => {
  emit("close");
};

const handleOfferAction = () => {
  emit("offerAction", {
    id: props.offer?.id || props.offer?.offer_id,
    quantity: quantity.value,
  });
  closeModal();
};

const buyOffer = () => {
  const establishmentId = props.offer?.food_establishment_id
    || props.offer?.establishment?.id
    || props.offer?.establishment_id;

  emit("buyOffer", {
    id: props.offer?.id || props.offer?.offer_id,
    quantity: quantity.value,
    food_establishment_id: establishmentId,
  });
};

const handleEstablishmentClick = (est) => {
  const estId = est?.id || props.offer?.food_establishment_id;
  if (estId) {
    closeModal();
    router.push({
      name: 'establishment-view',
      params: { id: estId },
    });
  }
};
</script>

<template>
  <div
    v-if="isVisible && offer"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm transition-all duration-200"
    @click="closeModal"
  >
    <div
      class="bg-surface border border-border-subtle rounded-modal max-w-lg w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh] text-content"
      @click.stop
    >
      <!-- Cabecera del Modal -->
      <header class="p-5 sm:p-6 pb-4 border-b border-border-subtle/60 flex items-start justify-between gap-3 bg-surface">
        <h2 class="type-h1 text-content-strong flex-1">
          {{ offer.title }}
        </h2>

        <div class="flex items-center gap-1 shrink-0 -mt-1">
          <!-- Botón Reportar Oferta -->
          <button
            type="button"
            class="text-warning/70 hover:text-warning hover:bg-warning/10 p-2 rounded-full transition-colors cursor-pointer"
            title="Reportar oferta"
            aria-label="Reportar oferta"
            @click="showReportModal = true"
          >
            <BaseIcon name="alert" size="sm" />
          </button>

          <!-- Botón Cerrar -->
          <button
            type="button"
            class="text-content-secondary hover:text-content-strong hover:bg-surface-sunken p-2 rounded-full transition-colors cursor-pointer"
            aria-label="Cerrar modal"
            @click="closeModal"
          >
            <BaseIcon name="close" size="sm" />
          </button>
        </div>
      </header>

      <!-- Contenido del Modal -->
      <div class="p-5 sm:p-6 overflow-y-auto space-y-4 flex-1">
        <!-- Tarjeta del Establecimiento Intercativa -->
        <EstablishmentPill
          :establishment="establishment"
          :fallback-name="offer.establishment_name"
          :interactive="true"
          @click="handleEstablishmentClick"
        />

        <!-- Descripción de la Oferta -->
        <div v-if="offer.description" class="space-y-1.5">
          <span class="type-overline">Descripción</span>
          <p class="type-body-sm text-content leading-relaxed bg-surface-sunken/60 border border-border-subtle/50 p-3.5 rounded-card">
            {{ offer.description }}
          </p>
        </div>

        <!-- Detalle de la Bolsa Sorpresa -->
        <div class="bg-surface-sunken border border-border-subtle rounded-card p-4 space-y-3">
          <!-- Precios Unitarios con PriceDisplay -->
          <div class="flex items-baseline justify-between gap-3">
            <div>
              <span class="text-xs text-content-secondary block mb-1">Precio por pack:</span>
              <PriceDisplay
                :price="unitPrice"
                :original-price="offer.minimum_value"
                size="md"
                unit-label="c/u"
                :show-savings-badge="true"
              />
            </div>

            <!-- Peso Estimado -->
            <span
              v-if="offer.estimated_weight_kg"
              class="text-xs text-content-secondary bg-surface-sunken border border-border-subtle px-2.5 py-1 rounded-full tabular-nums"
            >
              ~{{ Number(offer.estimated_weight_kg).toFixed(2) }} kg aprox.
            </span>
          </div>

          <!-- Horario de Retiro -->
          <div class="pt-2 border-t border-border-subtle/50">
            <PickupTimeBadge
              :pickup-start="offer.pickup_start_datetime"
              :expiration="offer.expiration_datetime"
              :show-window="true"
              :show-countdown="true"
            />
          </div>

          <!-- Etiquetas (Categoría / Alérgenos) -->
          <div
            v-if="offer.category || (offer.allergens && offer.allergens.length)"
            class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border-subtle/50"
          >
            <BaseBadge v-if="offer.category" variant="brand" size="xs">
              {{ offer.category }}
            </BaseBadge>
            <BaseBadge
              v-for="(alg, idx) in offer.allergens"
              :key="idx"
              variant="outline"
              size="xs"
            >
              {{ alg }}
            </BaseBadge>
          </div>
        </div>

        <!-- Selector de Cantidad y Total Calculado con QuantityStepper -->
        <div class="bg-surface-sunken border border-border-subtle rounded-card p-4 flex items-center justify-between gap-3">
          <div class="space-y-1">
            <span class="text-xs text-content-secondary block">Cantidad a reservar:</span>
            <QuantityStepper
              v-model="quantity"
              :min="1"
              :max="availableQuantity ?? 99"
              :available="availableQuantity"
            />
          </div>

          <div class="text-right">
            <span class="text-xs text-content-secondary block mb-0.5">Precio total:</span>
            <span class="type-price-hero text-content-strong">
              ${{ totalPrice.toLocaleString('es-AR') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Pie del Modal con Acciones -->
      <footer class="p-4 sm:p-5 bg-surface-sunken border-t border-border-subtle flex flex-wrap sm:flex-nowrap items-center gap-3">
        <BaseButton
          variant="secondary"
          size="lg"
          block
          @click="handleOfferAction"
        >
          <template #icon-left>
            <BaseIcon name="shopping-cart" size="sm" class="text-brand-light" />
          </template>
          Agregar al Carrito
        </BaseButton>

        <BaseButton
          variant="primary"
          size="lg"
          block
          @click="buyOffer"
        >
          <template #icon-left>
            <BaseIcon name="zap" size="sm" />
          </template>
          Comprar Ahora
        </BaseButton>
      </footer>
    </div>

    <!-- Modal de Reporte -->
    <ReportModal
      :is-visible="showReportModal"
      reportable-type="offer"
      :reportable-id="offer.id"
      @close="showReportModal = false"
    />
  </div>
</template>
