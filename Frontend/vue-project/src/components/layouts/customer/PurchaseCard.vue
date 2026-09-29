<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';

const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['cancelled']);

// Estado reactivo local para reflejar cambios (ej. cancelación inmediata)
const currentPurchase = ref({ ...props.purchase });
const showCode = ref(false);
const isCopied = ref(false);
const showCancelModal = ref(false);
const cancelling = ref(false);
const cancelError = ref(null);
const cancelSuccess = ref(false);

// Local gastronómico
const establishment = computed(() => {
  return currentPurchase.value.establishment || currentPurchase.value.food_establishment || {};
});

const establishmentName = computed(() => {
  return establishment.value.name || `Comercio #${currentPurchase.value.sold_by}`;
});

const establishmentInitials = computed(() => {
  const name = establishmentName.value.trim();
  const words = name.split(/\s+/);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase() || 'TA';
});

// Detalles de la reserva
const sellDetails = computed(() => {
  return currentPurchase.value.sell_details || [];
});

// Total calculado
const totalAmount = computed(() => {
  return sellDetails.value.reduce((total, detail) => {
    return total + (Number(detail.offer_quantity || 1) * Number(detail.pack_price || 0));
  }, 0);
});

// Formato de fecha de reserva
const formattedCreatedAt = computed(() => {
  if (!currentPurchase.value.created_at) return '';
  const date = new Date(currentPurchase.value.created_at);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
});

// Reloj en tiempo real para el temporizador de retiro
const now = ref(Date.now());
let timer = null;

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now();
  }, 30000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

// Temporizador y ventana límite de retiro
const pickupDeadlineInfo = computed(() => {
  if (!currentPurchase.value.max_pickup_datetime) {
    return { text: 'Horario según comercio', isUrgent: false, isCritical: false, isExpired: false };
  }

  const deadline = new Date(currentPurchase.value.max_pickup_datetime).getTime();
  const diffMs = deadline - now.value;

  const deadlineFormatted = new Date(currentPurchase.value.max_pickup_datetime).toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });

  if (diffMs <= 0) {
    return {
      text: `Plazo de retiro vencido (${deadlineFormatted} hs)`,
      isExpired: true,
      isUrgent: false,
      isCritical: false,
    };
  }

  const diffMinutes = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMinutes / 60);
  const remainingMinutes = diffMinutes % 60;

  if (diffHours === 0) {
    return {
      text: `Retirar antes de las ${deadlineFormatted} hs — ¡Quedan ${remainingMinutes}m!`,
      isUrgent: true,
      isCritical: remainingMinutes <= 20,
      isExpired: false,
    };
  }

  if (diffHours < 2) {
    return {
      text: `Retirar antes de las ${deadlineFormatted} hs — Quedan ${diffHours}h ${remainingMinutes}m`,
      isUrgent: true,
      isCritical: false,
      isExpired: false,
    };
  }

  return {
    text: `Retirar antes de las ${deadlineFormatted} hs`,
    isUrgent: false,
    isCritical: false,
    isExpired: false,
  };
});

// Etiquetas y clases según estado de la venta
const statusConfig = computed(() => {
  const state = currentPurchase.value.state || 'confirmed';
  switch (state) {
    case 'ready':
      return {
        label: 'Listo para retirar',
        badgeClass: 'bg-[#10B981]/15 text-[#34D399] border-[#10B981]/30',
        dotClass: 'bg-[#10B981]',
      };
    case 'confirmed':
      return {
        label: 'Confirmado',
        badgeClass: 'bg-[#7C3AED]/15 text-[#C4B5FD] border-[#7C3AED]/30',
        dotClass: 'bg-[#7C3AED]',
      };
    case 'pending':
      return {
        label: 'Pendiente',
        badgeClass: 'bg-[#F59E0B]/15 text-[#FDE68A] border-[#F59E0B]/30',
        dotClass: 'bg-[#F59E0B]',
      };
    case 'picked_up':
      return {
        label: 'Retirado',
        badgeClass: 'bg-[#10B981]/15 text-[#34D399] border-[#10B981]/30',
        dotClass: 'bg-[#10B981]',
      };
    case 'cancelled':
      return {
        label: 'Cancelado',
        badgeClass: 'bg-[#EF4444]/15 text-[#FCA5A5] border-[#EF4444]/30',
        dotClass: 'bg-[#EF4444]',
      };
    case 'expired':
      return {
        label: 'Expirado',
        badgeClass: 'bg-white/[0.06] text-[#A5A8C2] border-white/[0.1]',
        dotClass: 'bg-[#787596]',
      };
    default:
      return {
        label: state,
        badgeClass: 'bg-white/[0.06] text-[#A5A8C2] border-white/[0.1]',
        dotClass: 'bg-[#A5A8C2]',
      };
  }
});

// ¿Puede solicitar cancelación?
const canCancel = computed(() => {
  const state = currentPurchase.value.state;
  return state !== 'cancelled' && !currentPurchase.value.is_picked_up && state !== 'picked_up';
});

// Acción de copiar código
const copyPickupCode = async () => {
  if (!currentPurchase.value.pickup_code) return;
  try {
    await navigator.clipboard.writeText(currentPurchase.value.pickup_code);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  } catch (err) {
    console.error('Error al copiar al portapapeles:', err);
  }
};

// Confirmar cancelación en API
const executeCancellation = async () => {
  cancelling.value = true;
  cancelError.value = null;

  try {
    const response = await axiosInstance.post(`/customer/pack-reservations/${currentPurchase.value.id}/cancel`);
    cancelSuccess.value = true;
    currentPurchase.value.state = 'cancelled';

    setTimeout(() => {
      showCancelModal.value = false;
      emit('cancelled', currentPurchase.value.id);
    }, 1500);
  } catch (err) {
    console.error('Error al cancelar reserva:', err);
    cancelError.value = err.response?.data?.error || 'No se pudo cancelar la reserva en este momento.';
  } finally {
    cancelling.value = false;
  }
};
</script>

<template>
  <article
    class="relative bg-[#2D2438] border border-white/[0.08] hover:border-[#7C3AED]/40 rounded-2xl p-5 sm:p-6 shadow-md transition-all duration-200 flex flex-col gap-4 text-[#E8EAF6]"
  >
    <!-- Cabecera de la Reserva -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#3D3450]/60">
      <!-- Datos del Local Gastronómico -->
      <div class="flex items-center gap-3">
        <!-- Avatar Iniciales -->
        <div class="w-10 h-10 rounded-xl bg-[#7C3AED]/20 border border-[#7C3AED]/35 flex items-center justify-center text-xs font-extrabold text-[#C4B5FD] shrink-0">
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
            <svg class="w-3.5 h-3.5 text-[#7C3AED] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="truncate">{{ establishment.address }}</span>
          </RouterLink>
        </div>
      </div>

      <!-- Badge de Estado -->
      <div class="flex items-center self-start sm:self-center gap-2">
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border transition-all"
          :class="statusConfig.badgeClass"
        >
          <span class="w-2 h-2 rounded-full animate-pulse" :class="statusConfig.dotClass"></span>
          {{ statusConfig.label }}
        </span>
      </div>
    </header>

    <!-- Franja de Retiro y Temporizador de Tiempo Restante -->
    <div
      class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border text-xs"
      :class="[
        pickupDeadlineInfo.isCritical
          ? 'bg-[#EF4444]/10 border-[#EF4444]/30 text-[#FCA5A5]'
          : pickupDeadlineInfo.isUrgent
          ? 'bg-[#F59E0B]/10 border-[#F59E0B]/30 text-[#FDE68A]'
          : 'bg-white/[0.03] border-white/[0.06] text-[#A5A8C2]'
      ]"
    >
      <div class="flex items-center gap-2 truncate">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke-width="2" />
          <polyline points="12 6 12 12 16 14" stroke-width="2" />
        </svg>
        <span class="truncate font-medium">{{ pickupDeadlineInfo.text }}</span>
      </div>
      <span class="text-[11px] font-bold text-white shrink-0">Reserva #{{ currentPurchase.id }}</span>
    </div>

    <!-- Lista Minimalista de Packs Incluidos -->
    <div class="space-y-2 py-1">
      <div
        v-for="detail in sellDetails"
        :key="detail.id"
        class="flex items-start justify-between gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/[0.04]"
      >
        <div class="flex items-start gap-2.5 min-w-0">
          <span class="px-2 py-0.5 rounded-md bg-[#7C3AED]/20 border border-[#7C3AED]/30 text-xs font-bold text-[#A78BFA] shrink-0">
            x{{ detail.offer_quantity }}
          </span>
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-white truncate">{{ detail.pack_name }}</h4>
            <p v-if="detail.pack_description" class="text-xs text-[#94A3B8] line-clamp-1 mt-0.5">
              {{ detail.pack_description }}
            </p>
          </div>
        </div>

        <div class="text-right shrink-0">
          <div class="text-sm font-bold text-white">
            ${{ (Number(detail.offer_quantity) * Number(detail.pack_price)).toLocaleString('es-AR') }}
          </div>
          <span class="text-[10px] text-[#787596]">
            ${{ Number(detail.pack_price).toLocaleString('es-AR') }} c/u
          </span>
        </div>
      </div>
    </div>

    <!-- Módulo de Código de Retiro & Total -->
    <div class="mt-auto pt-3 border-t border-[#3D3450]/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <!-- Total -->
      <div class="flex items-baseline gap-2">
        <span class="text-xs text-[#A5A8C2] uppercase font-semibold">Total:</span>
        <span class="text-xl font-extrabold text-white tracking-tight">
          ${{ Number(totalAmount).toLocaleString('es-AR') }}
        </span>
      </div>

      <!-- Zona del Código de Retiro / Ticket Digital -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Componente Ticket de Código -->
        <div class="flex items-center gap-1.5 bg-[#0F0D15] border border-[#3D3450] rounded-xl px-3 py-1.5">
          <div class="flex items-center gap-2">
            <span class="text-[11px] uppercase tracking-wider font-semibold text-[#A5A8C2]">Código:</span>
            <span
              class="font-mono font-bold tracking-widest text-sm select-all"
              :class="showCode ? 'text-[#10B981]' : 'text-[#787596]'"
            >
              {{ showCode ? (currentPurchase.pickup_code || '---') : '••••••••' }}
            </span>
          </div>

          <!-- Botón de Alternar Ojo -->
          <button
            type="button"
            class="p-1 rounded-lg hover:bg-white/[0.08] text-[#A5A8C2] hover:text-white transition-colors"
            :title="showCode ? 'Ocultar código' : 'Mostrar código'"
            @click="showCode = !showCode"
          >
            <!-- Icono Ojo -->
            <svg v-if="!showCode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <!-- Icono Ojo Cerrado -->
            <svg v-else class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
          </button>

          <!-- Botón de Copiar -->
          <button
            type="button"
            class="p-1 rounded-lg hover:bg-white/[0.08] transition-colors"
            :class="isCopied ? 'text-[#10B981]' : 'text-[#A5A8C2] hover:text-white'"
            title="Copiar código al portapapeles"
            @click="copyPickupCode"
          >
            <svg v-if="!isCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <svg v-else class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </button>
        </div>

        <!-- Botón Cancelar Reserva (si está habilitado) -->
        <button
          v-if="canCancel"
          type="button"
          class="px-3 py-1.5 text-xs font-medium text-[#FCA5A5] hover:text-white bg-[#EF4444]/10 hover:bg-[#EF4444]/25 border border-[#EF4444]/30 rounded-xl transition-all active:scale-95"
          @click="showCancelModal = true"
        >
          Cancelar Reserva
        </button>
      </div>
    </div>

    <!-- Modal de Cancelación de Reserva -->
    <Teleport to="body">
      <div
        v-if="showCancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm transition-opacity"
        @click.self="!cancelling && (showCancelModal = false)"
      >
        <div class="relative w-full max-w-md bg-[#2D2438] border border-[#3D3450] rounded-2xl p-6 shadow-2xl space-y-4 text-[#E8EAF6]">
          <!-- Encabezado Modal -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-[#EF4444]/20 border border-[#EF4444]/30 flex items-center justify-center text-[#EF4444] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-white">¿Cancelar esta reserva?</h3>
                <p class="text-xs text-[#A5A8C2]">Reserva #{{ currentPurchase.id }} • {{ establishmentName }}</p>
              </div>
            </div>

            <button
              v-if="!cancelling"
              type="button"
              class="text-[#A5A8C2] hover:text-white p-1 rounded-lg hover:bg-white/[0.06]"
              @click="showCancelModal = false"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Mensaje y Condiciones -->
          <div class="space-y-2 text-xs text-[#94A3B8] leading-relaxed bg-[#1A1625] p-3.5 rounded-xl border border-white/[0.05]">
            <p class="text-[#E8EAF6] font-medium">Información sobre tu reembolso:</p>
            <ul class="list-disc pl-4 space-y-1">
              <li>El importe abonado (${{ Number(totalAmount).toLocaleString('es-AR') }}) se reembolsará al medio de pago original.</li>
              <li>El stock del pack volverá a estar disponible para que otro usuario pueda rescatarlo.</li>
              <li>Sujeto a la política de cancelación (más de 2h de anticipación o período de gracia de 15 minutos).</li>
            </ul>
          </div>

          <!-- Alerta de Error -->
          <div
            v-if="cancelError"
            class="p-3 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-xs text-[#FCA5A5] flex items-start gap-2"
          >
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke-width="2" />
              <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" />
              <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" />
            </svg>
            <span>{{ cancelError }}</span>
          </div>

          <!-- Mensaje de Éxito -->
          <div
            v-if="cancelSuccess"
            class="p-3 rounded-xl bg-[#10B981]/15 border border-[#10B981]/30 text-xs text-[#34D399] flex items-center gap-2"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>¡Reserva cancelada y reembolso emitido correctamente!</span>
          </div>

          <!-- Acciones -->
          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              class="px-4 py-2 text-xs font-semibold text-[#A5A8C2] hover:text-white bg-white/[0.05] hover:bg-white/[0.1] rounded-xl transition-all"
              :disabled="cancelling"
              @click="showCancelModal = false"
            >
              Cerrar
            </button>
            <button
              v-if="!cancelSuccess"
              type="button"
              class="px-4 py-2 text-xs font-bold text-white bg-[#EF4444] hover:bg-[#DC2626] rounded-xl transition-all shadow-md active:scale-95 disabled:opacity-50 flex items-center gap-1.5"
              :disabled="cancelling"
              @click="executeCancellation"
            >
              <svg v-if="cancelling" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ cancelling ? 'Cancelando...' : 'Confirmar Cancelación' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </article>
</template>
