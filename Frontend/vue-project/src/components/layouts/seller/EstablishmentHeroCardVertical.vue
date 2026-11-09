<script setup>
import { computed } from 'vue';

const props = defineProps({
  establishment: {
    type: Object,
    required: true,
  },
  typeName: {
    type: String,
    default: '',
  },
  category: {
    type: String,
    default: '',
  },
  name: {
    type: String,
    default: '',
  },
  address: {
    type: String,
    default: '',
  },
  phone: {
    type: String,
    default: '',
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const storeName = computed(
  () => props.name || props.establishment?.name || 'Mi Establecimiento'
);
const resolvedAddress = computed(
  () => props.address || props.establishment?.address || 'Dirección no registrada'
);
const resolvedPhone = computed(
  () => props.phone || props.establishment?.phone || '+54 11 4822-9104'
);
const resolvedCategory = computed(
  () =>
    props.category ||
    props.typeName ||
    props.establishment?.establishment_type?.name ||
    'Comercio Gastronómico'
);
const description = computed(
  () =>
    props.establishment?.description ||
    'Elaboración diaria y rescate al cierre de la jornada con bolsas sorpresa de alimentos frescos del día con hasta 60% de ahorro.'
);

const recentCount = computed(() => props.stats?.recent_sales_count ?? 42);
const historicalCount = computed(() => props.stats?.total_reviews_count ?? 318);
const completedRate = computed(() => props.stats?.completion_rate ?? 98);
const totalSales = computed(() => props.stats?.total_sales_count ?? 342);
</script>

<template>
  <div class="flex flex-col gap-5 pt-2 pb-7 border-b border-border-subtle">
    <!-- Información del Establecimiento -->
    <div class="flex flex-col gap-3">
      <!-- TitleAndBadgesRow -->
      <div class="flex items-center gap-3.5 flex-wrap">
        <h2 class="text-[26px] sm:text-[30px] font-bold text-content leading-tight">
          {{ storeName }}
        </h2>

        <span
          class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-surface border border-border-subtle text-[13px] font-medium text-content"
        >
          {{ resolvedCategory }}
        </span>
      </div>

      <!-- ContactMetadataRow -->
      <div class="flex items-center gap-2.5 flex-wrap text-[13px]">
        <span class="font-medium text-content">{{ resolvedAddress }}</span>
        <span class="text-content-muted">•</span>
        <span class="font-semibold text-warning">A 800 metros</span>
        <span class="text-content-muted">•</span>
        <span class="text-content-secondary">Lun a Sáb: 08:00 - 21:00 hs</span>
        <span class="text-content-muted">•</span>
        <span class="text-content-muted">{{ resolvedPhone }}</span>
      </div>

      <!-- StoreDescription -->
      <p class="text-[14px] text-content-secondary leading-relaxed">
        {{ description }}
      </p>
    </div>

    <!-- HorizontalHairline -->
    <div class="h-px w-full bg-border-subtle"></div>

    <!-- Credibilidad del Establecimiento -->
    <div class="flex flex-col gap-[18px]">
      <!-- RatingsComparisonRow -->
      <div class="flex items-center gap-9 flex-wrap">
        <!-- Block_ReseñasRecientes -->
        <div class="flex flex-col gap-1.5">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">
            RESEÑAS RECIENTES (30 DÍAS)
          </span>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 text-warning">
              <span class="text-sm font-bold text-content">★ 4.9</span>
            </div>
            <span class="text-[12px] text-content-secondary">{{ recentCount }} compras</span>
          </div>
        </div>

        <!-- Block_ReseñasHistóricas -->
        <div class="flex flex-col gap-1.5">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">
            HISTÓRICO GLOBAL
          </span>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 text-warning">
              <span class="text-sm font-bold text-content">★ 4.8</span>
            </div>
            <span class="text-[12px] text-content-secondary">{{ historicalCount }} opiniones</span>
          </div>
        </div>
      </div>

      <!-- TrustBadgesRow -->
      <div class="flex items-center gap-2.5 flex-wrap">
        <span
          class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-surface border border-border-subtle text-[13px] font-medium text-content"
        >
          {{ completedRate }}% completadas
        </span>
        <span
          class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-rescue border border-rescue text-[13px] font-medium text-content"
        >
          {{ totalSales }} ventas realizadas
        </span>
      </div>
    </div>
  </div>
</template>
