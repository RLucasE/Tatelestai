<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import QRCode from 'qrcode';
import axiosInstance from '@/lib/axios';
import { buildPickupQrPayload } from '@/utils/qrPayload';
import tokens from '@/assets/tokens.json';
import PurchaseClaimModal from '@/components/common/PurchaseClaimModal.vue';

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
  defaultExpanded: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['cancelled', 'claim-created']);

// Estado de expansión de la tarjeta
const isExpanded = ref(props.defaultExpanded);
const isCopied = ref(false);
const qrDataUrl = ref('');
const showCancelModal = ref(false);
const cancelling = ref(false);
const cancelError = ref(null);
const cancelSuccess = ref(false);
const showClaimModal = ref(false);
const localClaim = ref(null);

const currentPurchase = ref({ ...props.purchase });

// Sincronizar cambios de props si la compra cambia
watch(
  () => props.purchase,
  (newVal) => {
    currentPurchase.value = { ...newVal };
    if (newVal?.claim) {
      localClaim.value = null;
    }
  },
  { deep: true }
);

// Datos del local gastronómico
const establishment = computed(() => {
  return currentPurchase.value.establishment || currentPurchase.value.food_establishment || {};
});

const establishmentName = computed(() => {
  return establishment.value.name || `Comercio #${currentPurchase.value.sold_by || 'San José'}`;
});

const establishmentAddress = computed(() => {
  return establishment.value.address || 'Av. Santa Fe 2341, Palermo';
});

const distanceLabel = computed(() => {
  return establishment.value.distance || currentPurchase.value.distance || 'A 800m';
});

// Detalles y packs de la compra
const sellDetails = computed(() => {
  const details = currentPurchase.value.sell_details || currentPurchase.value.offers || [];
  if (details.length > 0) return details;

  // Si no hay detalles (ej. datos mockeados o legacy)
  return [
    {
      id: 1,
      pack_name: 'Pack Sorpresa de Panadería Artesanal',
      pack_description: 'Medialunas de manteca, pan de mesa, muffins variados y facturas recién horneadas',
      offer_quantity: 1,
      pack_price: 2500,
      tags: ['Contiene Gluten', 'Contiene Lácteos', 'Apto Vegetariano'],
    },
  ];
});

// Totales de unidades y precio
const totalPacksCount = computed(() => {
  return sellDetails.value.reduce((sum, d) => sum + Number(d.offer_quantity || 1), 0);
});

const itemsCount = computed(() => {
  return sellDetails.value.length;
});

const totalAmount = computed(() => {
  if (currentPurchase.value.total_price != null) {
    return Number(currentPurchase.value.total_price);
  }
  return sellDetails.value.reduce((sum, d) => {
    return sum + Number(d.offer_quantity || 1) * Number(d.pack_price || 0);
  }, 0);
});

// Código alfanumérico formateado con guiones (ej. 2X3Y - 4Z5A - 6B7C)
const formattedPickupCode = computed(() => {
  const clean = (currentPurchase.value.pickup_code || '2X3Y4Z5A6B7C').replace(/[\s-]+/g, '').toUpperCase();
  if (clean.length === 12) {
    return `${clean.slice(0, 4)} - ${clean.slice(4, 8)} - ${clean.slice(8, 12)}`;
  }
  if (clean.length === 8) {
    return `${clean.slice(0, 4)} - ${clean.slice(4, 8)}`;
  }
  return clean;
});

// Reloj en tiempo real para cuenta regresiva
const now = ref(Date.now());
let timer = null;

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now();
  }, 30000);

  if (isExpanded.value) {
    generateQr();
  }
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

// Generar código QR para retiro
const generateQr = async () => {
  try {
    const code = currentPurchase.value.pickup_code || '2X3Y4Z5A6B7C';
    const sellId = currentPurchase.value.id || '4825';
    const payload = buildPickupQrPayload ? buildPickupQrPayload(code, sellId) : `TAT-${code}-${sellId}`;

    qrDataUrl.value = await QRCode.toDataURL(payload, {
      width: 280,
      margin: 1,
      color: {
        dark: tokens.tatelestai.color.content.value,
        light: tokens.tatelestai.color.surface.value,
      },
      errorCorrectionLevel: 'M',
    });
  } catch (err) {
    console.error('Error generando QR:', err);
  }
};

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
  if (isExpanded.value && !qrDataUrl.value) {
    generateQr();
  }
};

// Temporizador y ventana horaria
const pickupTimeInfo = computed(() => {
  if (currentPurchase.value.pickup_schedule_text) {
    return {
      schedule: currentPurchase.value.pickup_schedule_text,
      badge: currentPurchase.value.pickup_urgency_badge || 'Cierra en 10m',
    };
  }

  if (!currentPurchase.value.max_pickup_datetime) {
    return {
      schedule: 'Hoy 20:00 a 20:45 hs',
      badge: 'Cierra en 10m',
    };
  }

  const deadlineDate = new Date(currentPurchase.value.max_pickup_datetime);
  const deadline = deadlineDate.getTime();
  const diffMs = deadline - now.value;

  const startRaw =
    currentPurchase.value.pickup_start_datetime ||
    currentPurchase.value.sell_details?.[0]?.pickup_start_datetime ||
    null;
  const startDate = startRaw ? new Date(startRaw) : null;

  const isToday = (startDate || deadlineDate).toDateString() === new Date(now.value).toDateString();
  const dateLabel = isToday
    ? 'Hoy'
    : (startDate || deadlineDate).toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });

  const startFormatted = startDate
    ? startDate.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })
    : null;
  const deadlineFormatted = deadlineDate.toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });

  const windowLabel = startFormatted
    ? `${dateLabel} ${startFormatted} a ${deadlineFormatted} hs`
    : `${dateLabel} hasta las ${deadlineFormatted} hs`;

  if (diffMs <= 0) {
    return {
      schedule: `Plazo vencido (${deadlineFormatted} hs)`,
      badge: 'Vencido',
    };
  }

  if (startDate && startDate.getTime() > now.value) {
    const startDiffMins = Math.floor((startDate.getTime() - now.value) / (1000 * 60));
    const startHours = Math.floor(startDiffMins / 60);
    const startRemMins = startDiffMins % 60;
    return {
      schedule: windowLabel,
      badge: startHours > 0 ? `Abre en ${startHours}h ${startRemMins}m` : `Abre en ${startRemMins}m`,
    };
  }

  const diffMinutes = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMinutes / 60);
  const remainingMinutes = diffMinutes % 60;

  if (diffHours === 0) {
    return {
      schedule: windowLabel,
      badge: `Cierra en ${remainingMinutes}m`,
    };
  }

  return {
    schedule: windowLabel,
    badge: `Quedan ${diffHours}h ${remainingMinutes}m`,
  };
});

// Tags de dieta vs alérgenos
const getItemTags = (detail) => {
  if (Array.isArray(detail.tags) && detail.tags.length > 0) {
    return detail.tags;
  }
  if (Array.isArray(detail.allergens) && detail.allergens.length > 0) {
    return detail.allergens;
  }
  const text = `${detail.pack_name || ''} ${detail.pack_description || ''}`.toLowerCase();
  const tags = [];
  if (text.includes('gluten') || text.includes('pan') || text.includes('miga') || text.includes('pizza') || text.includes('factura')) {
    tags.push('Contiene Gluten');
  }
  if (text.includes('lácteo') || text.includes('queso') || text.includes('manteca') || text.includes('cheesecake') || text.includes('dulce')) {
    tags.push('Contiene Lácteos');
  }
  if (text.includes('huevo')) {
    tags.push('Contiene Huevo');
  }
  if (text.includes('pescado') || text.includes('salmón') || text.includes('sushi')) {
    tags.push('Contiene Pescado');
  }
  if (text.includes('soja')) {
    tags.push('Contiene Soja');
  }
  if (text.includes('marisco') || text.includes('langostino') || text.includes('gyoza')) {
    tags.push('Contiene Mariscos');
  }
  if (text.includes('nuez') || text.includes('avellana') || text.includes('seco')) {
    tags.push('Frutos Secos');
  }
  if (text.includes('vegetariano')) {
    tags.push('Apto Vegetariano');
  }
  if (text.includes('vegano')) {
    tags.push('100% Vegano');
  }
  if (text.includes('sin tacc') || text.includes('tacc')) {
    tags.push('Sin TACC');
  }
  return tags.length > 0 ? tags : ['Contiene Gluten', 'Contiene Lácteos'];
};

const isDietaryTag = (tag) => {
  const lower = tag.toLowerCase();
  return lower.includes('vegano') || lower.includes('vegetariano') || lower.includes('sin tacc') || lower.includes('orgánico');
};

const copyPickupCode = async () => {
  try {
    const raw = (currentPurchase.value.pickup_code || '2X3Y4Z5A6B7C').replace(/\s+/g, '').toUpperCase();
    await navigator.clipboard.writeText(raw);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Error al copiar al portapapeles:', err);
  }
};

const activeClaim = computed(() => localClaim.value || currentPurchase.value.claim || null);
const isRefunded = computed(() => currentPurchase.value.payment_status === 'refunded');
const isPartiallyRefunded = computed(() => currentPurchase.value.payment_status === 'partially_refunded');

const claimStatusText = computed(() => {
  const status = activeClaim.value?.status;
  if (status === 'pending') return 'Pendiente de revisión';
  if (status === 'under_review') return 'En revisión';
  if (status === 'approved') {
    return activeClaim.value?.refund_type === 'partial'
      ? 'Aprobado (reembolso parcial)'
      : 'Aprobado (reembolso total)';
  }
  if (status === 'rejected') return 'Rechazado';
  return status || 'Registrado';
});

const claimBadgeClass = computed(() => {
  const status = activeClaim.value?.status;
  if (status === 'approved') return 'bg-success/15 text-success border-success/30';
  if (status === 'rejected') return 'bg-danger/15 text-danger border-danger/30';
  return 'bg-warning/15 text-brand border-warning/30';
});

const handleClaimSubmitted = (createdClaim) => {
  localClaim.value = createdClaim;
  currentPurchase.value.claim = createdClaim;
  emit('claim-created', createdClaim);
};

// Validación de cancelación y QR estricta por whitelist (AGENTS.md)
const CANCELLABLE_STATES = ['confirmed', 'ready'];
const canCancel = computed(() => {
  const state = currentPurchase.value.state;
  return (
    CANCELLABLE_STATES.includes(state) &&
    !currentPurchase.value.is_picked_up &&
    !isRefunded.value &&
    !isPartiallyRefunded.value
  );
});

const PICKUP_QR_STATES = ['confirmed', 'ready', 'pending'];
const canShowPickupQr = computed(() => {
  const state = currentPurchase.value.state;
  return (
    PICKUP_QR_STATES.includes(state) &&
    !currentPurchase.value.is_picked_up &&
    !isRefunded.value &&
    !isPartiallyRefunded.value
  );
});

const executeCancellation = async () => {
  cancelling.value = true;
  cancelError.value = null;

  try {
    if (currentPurchase.value.id && typeof currentPurchase.value.id === 'number') {
      await axiosInstance.post(`/customer/pack-reservations/${currentPurchase.value.id}/cancel`);
    }
    cancelSuccess.value = true;
    currentPurchase.value.state = 'cancelled';

    setTimeout(() => {
      showCancelModal.value = false;
      emit('cancelled', currentPurchase.value.id);
    }, 1200);
  } catch (err) {
    console.error('Error cancelando reserva:', err);
    cancelError.value = err.response?.data?.error || err.response?.data?.message || 'No se pudo cancelar la reserva.';
  } finally {
    cancelling.value = false;
  }
};

const openDirections = () => {
  const query = encodeURIComponent(establishmentAddress.value || establishmentName.value);
  window.open(`https://www.google.com/maps/search/?api=1&query=${query}`, '_blank');
};
</script>

<template>
  <article
    class="bg-surface rounded-card shadow-card transition-all duration-200 overflow-hidden"
    :class="isExpanded ? 'border-2 border-brand' : 'border border-border-subtle hover:border-brand/40'"
  >
    <!-- Contenedor Principal Superior (Datos y Resumen de Compra) -->
    <div class="p-5 sm:p-6">
      <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
        <!-- Lado Izquierdo: Datos del Comercio, Items de la Reserva y Horario -->
        <div class="flex-1 min-w-0 space-y-4">
          <!-- Cabecera de Comercio y Ubicación -->
          <div class="flex flex-wrap items-center justify-between gap-2 pb-1">
            <div class="flex flex-wrap items-center gap-1.5 text-xs text-content-secondary font-medium">
              <RouterLink
                v-if="establishment.id"
                :to="`/customer/establishment/${establishment.id}`"
                class="font-bold text-content-strong hover:text-brand transition-colors"
              >
                {{ establishmentName }}
              </RouterLink>
              <span v-else class="font-bold text-content-strong">
                {{ establishmentName }}
              </span>
              <span class="text-border-subtle">•</span>
              <span>{{ establishmentAddress }}</span>
              <span class="text-border-subtle">•</span>
              <span class="text-brand-light font-bold">{{ distanceLabel }}</span>
            </div>

            <!-- Identificador de Reserva (visible en mobile arriba o desktop derecha) -->
            <span class="text-xs font-semibold text-content-muted lg:hidden">
              Reserva #{{ currentPurchase.id }}
            </span>
          </div>

          <!-- Listado de Packs Incluidos en la Reserva -->
          <div class="space-y-3.5">
            <div
              v-for="detail in sellDetails"
              :key="detail.id"
              class="flex items-start justify-between gap-4 group"
            >
              <div class="min-w-0 space-y-1">
                <!-- Título del Pack + Cantidad + Tags -->
                <div class="flex flex-wrap items-center gap-2">
                  <h4 class="text-sm sm:text-[15px] font-bold text-content-strong font-heading">
                    {{ detail.pack_name }}
                  </h4>

                  <!-- Badge de Cantidad de Packs -->
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-brand text-surface text-[11px] font-bold shadow-2xs">
                    {{ detail.offer_quantity }} {{ detail.offer_quantity === 1 ? 'pack' : 'packs' }}
                  </span>

                  <!-- Tags de Dieta / Alérgenos -->
                  <div class="inline-flex flex-wrap items-center gap-1.5">
                    <span
                      v-for="(tag, tIdx) in getItemTags(detail)"
                      :key="tIdx"
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] select-none"
                      :class="
                        isDietaryTag(tag)
                          ? 'bg-brand-light text-surface font-semibold shadow-2xs'
                          : 'border border-border-subtle text-content-secondary font-medium bg-surface'
                      "
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>

                <!-- Descripción de los alimentos salvados -->
                <p v-if="detail.pack_description" class="text-xs text-content-secondary leading-relaxed max-w-xl">
                  {{ detail.pack_description }}
                </p>
              </div>

              <!-- Precio del Item -->
              <div class="text-right shrink-0">
                <span class="text-sm sm:text-[15px] font-bold text-content-strong tabular-nums font-heading">
                  ${{ (Number(detail.offer_quantity || 1) * Number(detail.pack_price || 0)).toLocaleString('es-AR') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Fila de Ventana de Retiro y Urgencia + Cancelar / Reclamo en estado colapsado -->
          <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-content-secondary pt-1">
            <div class="flex flex-wrap items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-content-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2" />
                <polyline points="12 6 12 12 16 14" stroke-width="2" />
              </svg>
              <span>Retiro: {{ pickupTimeInfo.schedule }}</span>
              <span
                v-if="pickupTimeInfo.badge"
                class="font-bold text-brand ml-1"
              >
                {{ pickupTimeInfo.badge }}
              </span>
              <span
                v-if="isRefunded"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-danger/15 text-danger border border-danger/30 ml-1"
              >
                Reembolsado por reclamo
              </span>
              <span
                v-else-if="isPartiallyRefunded"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-warning/15 text-brand border border-warning/30 ml-1"
              >
                Reembolso parcial (${{ Number(currentPurchase.refunded_amount || 0).toLocaleString('es-AR') }})
              </span>
            </div>

            <div class="flex items-center gap-3 ml-auto">
              <span
                v-if="activeClaim"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                :class="claimBadgeClass"
              >
                Reclamo: {{ claimStatusText }}
              </span>
              <button
                v-else-if="currentPurchase.can_claim"
                type="button"
                class="text-xs text-danger hover:underline font-semibold cursor-pointer"
                @click="showClaimModal = true"
              >
                Hacer reclamo
              </button>

              <!-- Cancelar reserva en el margen derecho de la fila de horario según Figma -->
              <button
                v-if="canCancel && !isExpanded"
                type="button"
                class="text-xs text-brand hover:underline font-medium cursor-pointer"
                @click="showCancelModal = true"
              >
                Cancelar reserva
              </button>
            </div>
          </div>
        </div>

        <!-- Lado Derecho: Columna de Resumen de Compra y Botón de Acción -->
        <div class="w-full lg:w-72 shrink-0 flex flex-col justify-between self-stretch lg:border-l lg:border-border-subtle lg:pl-6 pt-3 lg:pt-0">
          <!-- Identificador de Reserva en Desktop -->
          <div class="hidden lg:flex items-center justify-end pb-1">
            <span class="text-xs font-semibold text-content-muted">
              Reserva #{{ currentPurchase.id || currentPurchase.sell_id }}
            </span>
          </div>

          <!-- Si la tarjeta está EXPANDIDA: Resumen detallado con items individuales -->
          <div v-if="isExpanded && canShowPickupQr" class="space-y-3">
            <span class="text-[10px] font-bold uppercase tracking-wider text-content-muted block font-heading">
              RESUMEN DE COMPRA
            </span>

            <div class="space-y-1.5 py-1">
              <div
                v-for="d in sellDetails"
                :key="d.id"
                class="flex items-center justify-between text-xs gap-2"
              >
                <span class="text-content-secondary truncate">
                  {{ d.offer_quantity }}x {{ d.pack_name }}
                </span>
                <span class="font-bold text-content-strong tabular-nums shrink-0 font-heading">
                  ${{ (Number(d.offer_quantity || 1) * Number(d.pack_price || 0)).toLocaleString('es-AR') }}
                </span>
              </div>
            </div>

            <hr class="border-border-subtle my-2" />

            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-content-muted block font-heading">
                TOTAL ABONADO
              </span>
              <span class="text-2xl sm:text-[26px] font-extrabold text-content-strong tabular-nums font-heading block mt-0.5">
                ${{ totalAmount.toLocaleString('es-AR') }}
              </span>
            </div>

            <!-- Botón Ocultar QR y detalles -->
            <button
              type="button"
              class="w-full py-2 px-4 rounded-[10px] border border-border-subtle bg-surface hover:bg-canvas text-xs font-semibold text-content transition cursor-pointer select-none active:scale-95 shadow-2xs mt-2"
              @click="toggleExpand"
            >
              Ocultar QR y detalles ▴
            </button>
          </div>

          <!-- Si la tarjeta está COLAPSADA o no permite QR: Total con desglose de items y botón Ver QR / Reclamo -->
          <div v-else class="flex flex-col justify-end h-full space-y-3 text-left lg:text-right">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-content-muted font-heading block">
                TOTAL ABONADO
              </span>
              <span class="text-[11px] text-content-secondary block font-normal mt-0.5">
                {{ itemsCount }} {{ itemsCount === 1 ? 'item' : 'items' }} • {{ totalPacksCount }} {{ totalPacksCount === 1 ? 'pack' : 'packs' }}
              </span>
              <span class="text-2xl sm:text-[26px] font-extrabold text-content-strong tabular-nums font-heading block mt-0.5">
                ${{ totalAmount.toLocaleString('es-AR') }}
              </span>
            </div>

            <!-- Botón Ver QR y detalles de retiro (Vermilion Flame sólida según Figma) -->
            <button
              v-if="canShowPickupQr"
              type="button"
              class="w-full lg:w-auto px-5 py-2.5 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-xs font-bold transition shadow-sm active:scale-95 cursor-pointer select-none"
              @click="toggleExpand"
            >
              Ver QR y detalles de retiro ▾
            </button>
            <button
              v-else-if="!activeClaim && currentPurchase.can_claim"
              type="button"
              class="w-full lg:w-auto px-5 py-2.5 rounded-[10px] bg-danger/10 hover:bg-danger/20 border border-danger/30 text-danger text-xs font-bold transition active:scale-95 cursor-pointer select-none"
              @click="showClaimModal = true"
            >
              Hacer reclamo
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Gaveta Inferior Expandida: Código QR, PIN de Retiro y Mapa Interactivo -->
    <div
      v-if="isExpanded && canShowPickupQr"
      class="bg-canvas border-t border-border-subtle p-5 sm:p-6 rounded-b-card"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
        <!-- Columna Izquierda: QR Code + Código Alfanumérico + Cancelar Reserva -->
        <div class="flex flex-col justify-between h-full space-y-4">
          <div class="flex items-center gap-4">
            <!-- Cuadro contenedor del Código QR -->
            <div class="bg-surface p-3 rounded-2xl border border-border-subtle shadow-xs shrink-0 flex items-center justify-center">
              <img
                v-if="qrDataUrl"
                :src="qrDataUrl"
                alt="Código QR de retiro"
                class="w-28 h-28 sm:w-32 sm:h-32 object-contain"
              />
              <div v-else class="w-28 h-28 sm:w-32 sm:h-32 bg-canvas animate-pulse rounded-lg" />
            </div>

            <!-- Instrucción y PIN alfanumérico -->
            <div class="space-y-2 min-w-0 flex-1">
              <p class="text-xs text-content-secondary leading-snug">
                Presentá este código QR al personal o dictá el código alfanumérico.
              </p>

              <div class="bg-surface border border-border-subtle rounded-xl p-3 flex flex-col items-center justify-center gap-1.5 shadow-2xs">
                <span class="font-mono text-sm sm:text-base font-bold tracking-widest text-content-strong select-all">
                  {{ formattedPickupCode }}
                </span>
                <button
                  type="button"
                  class="px-4 py-0.5 text-xs font-semibold rounded-[10px] border border-border-subtle bg-surface hover:bg-canvas text-content hover:text-brand transition cursor-pointer select-none shadow-2xs"
                  @click="copyPickupCode"
                >
                  {{ isCopied ? '¡Copiado!' : 'Copiar' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Link de Cancelar Reserva -->
          <div class="pt-1">
            <button
              v-if="canCancel"
              type="button"
              class="text-xs text-brand hover:underline font-medium cursor-pointer"
              @click="showCancelModal = true"
            >
              Cancelar reserva
            </button>
          </div>
        </div>

        <!-- Columna Derecha: Vista Previa de Mapa y Cómo Llegar -->
        <div class="relative rounded-xl overflow-hidden border border-border-subtle h-36 sm:h-40 bg-surface-sunken shadow-2xs group">
          <!-- Patrón visual de calles y pin central vermilion -->
          <div class="w-full h-full">
            <svg class="w-full h-full object-cover opacity-80" viewBox="0 0 400 200" preserveAspectRatio="none">
              <rect width="400" height="200" class="fill-surface-sunken" />
              <!-- Trazado de avenidas -->
              <path d="M 0 60 Q 200 45 400 70" class="stroke-surface" stroke-width="12" fill="none" />
              <path d="M 0 140 Q 200 130 400 150" class="stroke-surface" stroke-width="10" fill="none" />
              <path d="M 90 0 L 110 200" class="stroke-surface" stroke-width="10" fill="none" />
              <path d="M 230 0 L 210 200" class="stroke-surface" stroke-width="14" fill="none" />
              <path d="M 330 0 L 340 200" class="stroke-surface" stroke-width="8" fill="none" />
              <!-- Pin circular Vermilion Flame en el centro -->
              <circle cx="210" cy="95" r="14" class="fill-brand" />
              <circle cx="210" cy="95" r="5" class="fill-surface" />
            </svg>
          </div>

          <!-- Franja de datos de ubicación en la base del mapa -->
          <div class="absolute inset-x-0 bottom-0 bg-surface/95 backdrop-blur-xs border-t border-border-subtle px-3 py-2 flex items-center justify-between gap-2 z-10">
            <div class="min-w-0 flex-1">
              <p class="text-xs font-bold text-content-strong truncate">
                {{ establishmentAddress }}
              </p>
              <p class="text-[11px] text-content-secondary truncate">
                {{ distanceLabel }} • {{ establishment.schedules || 'Lun a Sáb: 08:00 a 21:00 hs' }}
              </p>
            </div>

            <button
              type="button"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-border-subtle bg-surface hover:bg-canvas text-xs font-semibold text-content transition cursor-pointer shrink-0 shadow-2xs select-none"
              @click="openDirections"
            >
              <span>Cómo llegar</span>
              <span>↗</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cancelación de Reserva -->
    <Teleport to="body">
      <div
        v-if="showCancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity"
        @click.self="!cancelling && (showCancelModal = false)"
      >
        <div class="relative w-full max-w-md bg-surface border border-border-subtle rounded-card p-6 shadow-elevated space-y-4 text-content">
          <!-- Encabezado Modal -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-danger/10 text-danger flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-content-strong font-heading">¿Cancelar esta reserva?</h3>
                <p class="text-xs text-content-secondary">Reserva #{{ currentPurchase.id }} • {{ establishmentName }}</p>
              </div>
            </div>

            <button
              v-if="!cancelling"
              type="button"
              class="text-content-secondary hover:text-content p-1 rounded-lg"
              @click="showCancelModal = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Mensaje y Condiciones -->
          <div class="space-y-2 text-xs text-content-secondary leading-relaxed bg-canvas p-3.5 rounded-xl border border-border-subtle">
            <p class="text-content-strong font-semibold">Información sobre tu reembolso:</p>
            <ul class="list-disc pl-4 space-y-1">
              <li>El importe abonado (${{ totalAmount.toLocaleString('es-AR') }}) se reembolsará al medio de pago original.</li>
              <li>El stock del pack volverá a estar disponible para que otro usuario pueda rescatarlo.</li>
              <li>Sujeto a la política de cancelación de excedentes gastronómicos.</li>
            </ul>
          </div>

          <!-- Alerta de Error -->
          <div
            v-if="cancelError"
            class="p-3 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger flex items-start gap-2"
          >
            <span>{{ cancelError }}</span>
          </div>

          <!-- Mensaje de Éxito -->
          <div
            v-if="cancelSuccess"
            class="p-3 rounded-xl bg-success/10 border border-success/30 text-xs text-success flex items-center gap-2"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>¡Reserva cancelada correctamente!</span>
          </div>

          <!-- Botones de Acción -->
          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              class="px-4 py-2 text-xs font-semibold text-content-secondary hover:text-content bg-canvas border border-border-subtle rounded-xl transition-all cursor-pointer"
              :disabled="cancelling"
              @click="showCancelModal = false"
            >
              Cerrar
            </button>
            <button
              v-if="!cancelSuccess"
              type="button"
              class="px-4 py-2 text-xs font-bold text-surface bg-danger hover:bg-danger/90 rounded-xl transition-all shadow-md active:scale-95 disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
              :disabled="cancelling"
              @click="executeCancellation"
            >
              <span>{{ cancelling ? 'Cancelando...' : 'Confirmar Cancelación' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Reclamo -->
    <PurchaseClaimModal
      v-model="showClaimModal"
      :sell-id="currentPurchase.sell_id || currentPurchase.id"
      :establishment-name="establishmentName"
      @submitted="handleClaimSubmitted"
    />
  </article>
</template>
