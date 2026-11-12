<script setup>
import { ref } from 'vue';
import Logo from '@/components/common/Logo.vue';
import BaseIcon from '@/components/ui/atoms/BaseIcon.vue';
import BaseButton from '@/components/ui/atoms/BaseButton.vue';
import BaseBadge from '@/components/ui/atoms/BaseBadge.vue';
import BaseChip from '@/components/ui/atoms/BaseChip.vue';
import BaseAvatar from '@/components/ui/atoms/BaseAvatar.vue';
import BaseSelect from '@/components/ui/atoms/BaseSelect.vue';
import BaseSkeleton from '@/components/ui/atoms/BaseSkeleton.vue';
import PriceDisplay from '@/components/ui/molecules/PriceDisplay.vue';
import RatingBadge from '@/components/ui/molecules/RatingBadge.vue';
import EstablishmentPill from '@/components/ui/molecules/EstablishmentPill.vue';
import PickupTimeBadge from '@/components/ui/molecules/PickupTimeBadge.vue';
import QuantityStepper from '@/components/ui/molecules/QuantityStepper.vue';
import SegmentedToggle from '@/components/ui/molecules/SegmentedToggle.vue';
import OfferCardSkeleton from '@/components/ui/molecules/OfferCardSkeleton.vue';
import ToastAlert from '@/components/ui/molecules/ToastAlert.vue';
import OfferCard from '@/components/ui/organisms/OfferCard.vue';
import OfferModal from '@/components/common/OfferModal.vue';

const activeCategory = ref('surprise');
const viewMode = ref('list');
const selectedSort = ref('discount');
const stepperValue = ref(2);
const showTestModal = ref(false);
const testToast = ref({ show: false, message: '', type: 'success' });

const triggerToast = (msg, type = 'success') => {
  testToast.value = { show: true, message: msg, type };
};

const sampleOffer = {
  id: 999,
  title: 'Pack Sorpresa Panadería & Facturas Artesanales',
  description: 'Contiene una selección de medialunas de manteca recién horneadas, panes de masa madre y mini delicias dulces.',
  price: 3500,
  minimum_value: 7000,
  quantity: 2,
  category: 'Panaderías',
  allergens: ['Gluten', 'Lácteos', 'Huevos', 'Frutos secos'],
  pickup_start_datetime: new Date(Date.now() + 3600000).toISOString(),
  expiration_datetime: new Date(Date.now() + 7200000).toISOString(),
  establishment: {
    id: 10,
    name: 'Panadería San José',
    address: 'Av. Belgrano 520, Salta',
    recent_rating_average: '4.9',
    recent_reviews_count: 32,
    rating_average: '4.8',
    reviews_count: 140,
  },
};

const colorTokens = [
  { name: 'Canvas (Warm Ivory)', hex: '#FAF7F2', varName: '--color-canvas', desc: 'Fondo principal de la aplicación' },
  { name: 'Surface (Pure White)', hex: '#FFFFFF', varName: '--color-surface', desc: 'Tarjetas primarias y modales' },
  { name: 'Surface Sunken (Soft Sand)', hex: '#F5EFEA', varName: '--color-surface-sunken', desc: 'Inputs y contenedores interiores' },
  { name: 'Surface Elevated (Warm Tint)', hex: '#FFF8F3', varName: '--color-surface-elevated', desc: 'Tarjetas activas y destaques cálidos' },
  { name: 'Midnight Plum Surface', hex: '#46334F', varName: '--color-content', desc: 'Contraste profundo y texto primario' },
  { name: 'Brand (Vermilion Flame)', hex: '#F24F13', varName: '--color-brand', desc: 'Acción principal, CTAs y acento solar' },
  { name: 'Brand Hover', hex: '#D9420B', varName: '--color-brand-hover', desc: 'Hover de botón primario' },
  { name: 'Sunset Amber', hex: '#F2921D', varName: '--color-brand-light', desc: 'Acento secundario, alertas y barras' },
  { name: 'Rescue Emerald', hex: '#10B981', varName: '--color-success', desc: 'Hitos completados y confirmación' },
  { name: 'Border Subtle', hex: '#DDD8E5', varName: '--color-border-subtle', desc: 'Bordes sutiles y líneas divisoras' },
  { name: 'Content Secondary (Twilight)', hex: '#8082A6', varName: '--color-content-secondary', desc: 'Subtítulos, metadatos y direcciones' },
  { name: 'Content Muted', hex: '#A39BB5', varName: '--color-content-muted', desc: 'Precios tachados y placeholders' },
  { name: 'Semantic Danger', hex: '#EF4444', varName: '--color-danger', desc: 'Errores, expirados y rechazos' },
];

const spacingTokens = [
  { name: '--space-inline-xs', px: '4px' },
  { name: '--space-inline-sm', px: '6px' },
  { name: '--space-cluster', px: '8px' },
  { name: '--space-stack-sm', px: '10px' },
  { name: '--space-stack-card', px: '12px' },
  { name: '--space-card-pad', px: '16px' },
  { name: '--space-toolbar-row', px: '18px' },
  { name: '--space-grid-gap', px: '24px' },
  { name: '--space-page-top', px: '24px' },
  { name: '--space-section', px: '32px' },
  { name: '--space-section-lg', px: '40px' },
];

const iconGroups = [
  {
    category: 'Navegación Principal',
    icons: [
      { name: 'compass', label: 'Explorar' },
      { name: 'shopping-bag', label: 'Mis Compras' },
      { name: 'ticket', label: 'Mis Reservas' },
      { name: 'history', label: 'Historial' },
      { name: 'shopping-cart', label: 'Mi Carrito' },
      { name: 'user', label: 'Mi Perfil' },
      { name: 'bell', label: 'Campanita' },
    ],
  },
  {
    category: 'Comercio, Rescate & Packs',
    icons: [
      { name: 'bag', label: 'Bolsa / Pack' },
      { name: 'pin', label: 'Ubicación' },
      { name: 'clock', label: 'Horario' },
      { name: 'map', label: 'Mapa' },
      { name: 'list', label: 'Lista' },
      { name: 'star-fill', label: 'Estrella Llena' },
      { name: 'star', label: 'Estrella Contorno' },
      { name: 'qr', label: 'Código QR / Cámara' },
      { name: 'filter', label: 'Filtro' },
    ],
  },
];
</script>

<template>
  <div class="min-h-screen bg-canvas text-content p-4 sm:p-8 max-w-7xl mx-auto space-y-12">
    <!-- Cabecera del Design System -->
    <header class="border-b border-border-subtle pb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <Logo variant="imagotipo" size="md" />
          <span class="px-3 py-1 rounded-full bg-warning/15 text-brand text-xs font-bold">
            Sunset Anime Light v2.0
          </span>
        </div>
        <p class="type-body text-content-secondary">
          Sistema de Diseño Oficial de Tatelestai — Paleta Sunset Anime Light, La Medialuna Minimalista, Tipografía y Componentes.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <RouterLink to="/seller/dashboard">
          <BaseButton variant="primary" size="sm" pill>
            Ir al Panel Vendedor →
          </BaseButton>
        </RouterLink>
      </div>
    </header>

    <!-- SECCIÓN 0: Identidad de Marca ("La Medialuna Minimalista") -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">0. Sistema de Marca — "La Medialuna Minimalista"</h2>
        <p class="type-body-sm text-content-secondary mt-0.5">
          Isotipo de 3 gajos geométricos (#F2921D + #F24F13) y Logotipo con punto solar en la "i".
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-6 rounded-[18px] bg-surface border border-border-subtle flex flex-col items-center justify-center gap-3">
          <Logo variant="isotipo" size="xl" />
          <span class="type-overline text-content-secondary">1. Isotipo (Símbolo)</span>
        </div>
        <div class="p-6 rounded-[18px] bg-surface border border-border-subtle flex flex-col items-center justify-center gap-3">
          <Logo variant="logotipo" size="lg" />
          <span class="type-overline text-content-secondary">2. Logotipo (Wordmark)</span>
        </div>
        <div class="p-6 rounded-[18px] bg-content text-surface flex flex-col items-center justify-center gap-3">
          <Logo variant="imagotipo" theme="dark" size="lg" />
          <span class="type-overline text-surface/70">3. Imagotipo sobre Midnight Plum</span>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 1: Tokens de Color -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">1. Paleta Cromática Sunset Anime Light</h2>
        <p class="type-body-sm text-content-secondary mt-0.5">
          13 tokens semánticos sincronizados con Figma (`BXOYJONPYo4OUI2bIT87PY`).
        </p>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <div
          v-for="tok in colorTokens"
          :key="tok.varName"
          class="bg-surface border border-border-subtle rounded-[16px] p-3.5 flex flex-col justify-between h-28 shadow-xs"
        >
          <div class="flex items-center justify-between">
            <span class="w-7 h-7 rounded-lg border border-border-subtle" :style="{ backgroundColor: tok.hex }" />
            <span class="text-[11px] font-mono text-content-secondary">{{ tok.hex }}</span>
          </div>
          <div>
            <div class="text-xs font-bold text-content truncate">{{ tok.name }}</div>
            <div class="text-[10px] text-content-secondary font-mono truncate">{{ tok.varName }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 2: Escala de Espaciado (11 Tokens) -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">2. Escala de Espaciado (11 Tokens Base 4px)</h2>
      </div>
      <div class="bg-surface border border-border-subtle rounded-[18px] p-6 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
        <div v-for="sp in spacingTokens" :key="sp.name" class="space-y-1.5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-mono font-bold text-content">{{ sp.name }}</span>
            <span class="font-mono text-content-secondary">{{ sp.px }}</span>
          </div>
          <div class="h-2.5 rounded-full bg-brand" :style="{ width: sp.px }" />
        </div>
      </div>
    </section>

    <!-- SECCIÓN 3: Escala Tipográfica (11 Estilos) -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">3. Escala Tipográfica (11 Estilos)</h2>
        <p class="type-body-sm text-content-secondary mt-0.5">
          Plus Jakarta Sans, Inter y JetBrains Mono con soporte tabular.
        </p>
      </div>
      <div class="bg-surface border border-border-subtle rounded-[18px] p-6 space-y-4">
        <div>
          <span class="type-overline block mb-1">.type-display</span>
          <div class="type-display">Rescatá comida deliciosa al final del día</div>
        </div>
        <div>
          <span class="type-overline block mb-1">.type-h1</span>
          <div class="type-h1">Registrar establecimiento gastronómico</div>
        </div>
        <div>
          <span class="type-overline block mb-1">.type-h2</span>
          <div class="type-h2">Rendimiento, Impacto y Ventas</div>
        </div>
        <div>
          <span class="type-overline block mb-1">.type-h3</span>
          <div class="type-h3">Pack Sorpresa Panadería & Facturas</div>
        </div>
        <div>
          <span class="type-overline block mb-1">.type-price</span>
          <div class="type-price text-brand">$3.500 <span class="text-sm font-normal text-content-secondary line-through ml-2">$7.000</span></div>
        </div>
        <div>
          <span class="type-overline block mb-1">.type-body & .type-body-sm</span>
          <p class="type-body max-w-2xl">
            Tatelestai conecta panaderías, restaurantes y cafeterías con consumidores para rescatar excedentes de comida en perfecto estado.
          </p>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
          <div>
            <span class="type-overline block mb-1">.type-mono</span>
            <span class="type-mono bg-surface-sunken border border-border-subtle px-3 py-1 rounded-lg text-content">
              PIN: 4829-X9
            </span>
          </div>
          <div>
            <span class="type-overline block mb-1">.type-mono-lg</span>
            <span class="type-mono-lg bg-surface-elevated border border-warning px-3.5 py-1 rounded-xl text-brand">
              7391-K2
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 4: Átomos & Moléculas -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">4. Átomos y Moléculas</h2>
      </div>

      <div class="bg-surface border border-border-subtle rounded-[18px] p-6 space-y-4">
        <h3 class="type-h3">BaseButton & BaseBadge</h3>
        <div class="flex flex-wrap items-center gap-3">
          <BaseButton variant="primary" size="md" pill>Primary Vermilion</BaseButton>
          <BaseButton variant="secondary" size="md" pill>Secondary Sand</BaseButton>
          <BaseButton variant="emerald" size="md" pill>Emerald</BaseButton>
          <BaseButton variant="outline" size="md" pill>Outline</BaseButton>
          <BaseButton variant="ghost" size="md" pill>Ghost</BaseButton>
          <BaseButton variant="danger" size="md" pill>Danger</BaseButton>
        </div>
        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-border-subtle">
          <BaseBadge variant="brand">Vermilion Badge</BaseBadge>
          <BaseBadge variant="warning" dot>Pendiente de retiro</BaseBadge>
          <BaseBadge variant="emerald" dot>Comercio Verificado</BaseBadge>
          <BaseBadge variant="danger" dot>Expirado</BaseBadge>
          <BaseBadge variant="neutral">Harinas</BaseBadge>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-surface border border-border-subtle rounded-[18px] p-6 space-y-4">
          <h3 class="type-h3">PriceDisplay, RatingBadge & BaseAvatar</h3>
          <PriceDisplay :price="3500" :original-price="7000" size="lg" :show-savings-badge="true" />
          <div class="flex items-center gap-3 pt-2 border-t border-border-subtle">
            <BaseAvatar name="San José" size="md" />
            <RatingBadge :establishment="sampleOffer.establishment" />
          </div>
        </div>

        <div class="bg-surface border border-border-subtle rounded-[18px] p-6 space-y-4">
          <h3 class="type-h3">PickupTimeBadge & QuantityStepper</h3>
          <PickupTimeBadge
            :pickup-start="sampleOffer.pickup_start_datetime"
            :expiration="sampleOffer.expiration_datetime"
            :show-window="true"
            :show-countdown="true"
          />
          <div class="pt-2 border-t border-border-subtle flex items-center justify-between">
            <span class="text-xs text-content-secondary">Selector de cantidad:</span>
            <QuantityStepper v-model="stepperValue" :min="1" :max="5" :available="5" />
          </div>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 5: Catálogo de Íconos -->
    <section class="space-y-4">
      <div class="border-b border-border-subtle pb-2">
        <h2 class="type-h2 text-content">5. Catálogo de Íconos SVG (BaseIcon)</h2>
      </div>
      <div v-for="group in iconGroups" :key="group.category" class="space-y-3">
        <h3 class="type-overline text-brand">{{ group.category }}</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
          <div
            v-for="icon in group.icons"
            :key="icon.name"
            class="bg-surface border border-border-subtle rounded-[14px] p-3 flex flex-col items-center justify-center gap-2 text-center hover:border-brand transition-colors"
          >
            <div class="w-9 h-9 rounded-lg bg-surface-sunken flex items-center justify-center text-content">
              <BaseIcon :name="icon.name" size="md" />
            </div>
            <div class="w-full truncate">
              <div class="text-xs font-semibold text-content truncate">{{ icon.label }}</div>
              <div class="text-[10px] text-content-secondary font-mono truncate">{{ icon.name }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal y Toast de prueba -->
    <OfferModal
      :is-visible="showTestModal"
      :offer="sampleOffer"
      @close="showTestModal = false"
      @offerAction="triggerToast('Agregado al carrito: ' + $event.quantity + ' unidad(es)', 'success')"
      @buyOffer="triggerToast('Reserva directa iniciada', 'info')"
    />

    <ToastAlert
      :show="testToast.show"
      :message="testToast.message"
      :type="testToast.type"
      @close="testToast.show = false"
    />
  </div>
</template>
