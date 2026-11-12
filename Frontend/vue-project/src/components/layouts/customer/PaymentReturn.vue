<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios';
import QRCode from 'qrcode';
import { buildPickupQrPayload, formatPickupCodeInput } from '@/utils/qrPayload';
import tokens from '@/assets/tokens.json';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const verificationSuccess = ref(false);
const orderData = ref(null);
const errorMessage = ref('');
const qrDataUrl = ref('');
const qrDataUrls = ref({});
const isCopied = ref(false);
const copiedSellId = ref(null);

const sellsList = computed(() => {
  if (Array.isArray(orderData.value?.sells) && orderData.value.sells.length > 0) {
    return orderData.value.sells;
  }
  if (orderData.value?.pickup_code) {
    return [
      {
        sell_id: orderData.value.sell_id,
        pickup_code: orderData.value.pickup_code,
        pickup_start_datetime: orderData.value.pickup_start_datetime || null,
        max_pickup_datetime: orderData.value.max_pickup_datetime || null,
        offers: orderData.value.offers || [],
      },
    ];
  }
  return [];
});

const formatSellPickupWindow = (sell) => {
  const start = sell?.pickup_start_datetime ? new Date(sell.pickup_start_datetime) : null;
  const end = sell?.max_pickup_datetime ? new Date(sell.max_pickup_datetime) : null;
  if (start && end) {
    const isToday = start.toDateString() === new Date().toDateString();
    const dateLabel = isToday ? 'Hoy' : start.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
    const startStr = start.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    const endStr = end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    return `${dateLabel} de ${startStr} a ${endStr} hs`;
  }
  if (end) {
    return `Hoy hasta las ${end.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })} hs`;
  }
  return null;
};

const formattedPickupCode = computed(() => {
  const code = orderData.value?.pickup_code;
  return code ? formatPickupCodeInput(code) : '';
});

const maxPickupTimeFormatted = computed(() => {
  const dt = orderData.value?.max_pickup_datetime;
  if (!dt) return null;
  return new Date(dt).toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
  });
});

const verifyPaymentReturn = async () => {
  loading.value = true;
  errorMessage.value = '';

  const sellId = route.query.external_reference || route.query.sell_id;
  const paymentId = route.query.payment_id || route.query.collection_id;
  const status = route.query.status || route.query.collection_status || 'approved';
  const preferenceId = route.query.preference_id;

  if (!sellId) {
    errorMessage.value = 'No se encontró la referencia del pedido en el retorno de Mercado Pago.';
    loading.value = false;
    return;
  }

  try {
    const response = await axiosInstance.post('/customer/payment/verify', {
      sell_id: Number(sellId),
      payment_id: paymentId ? String(paymentId) : null,
      status: status,
      preference_id: preferenceId ? String(preferenceId) : null,
    });

    orderData.value = response.data?.data || null;
    verificationSuccess.value = true;
    sessionStorage.removeItem('purchaseConfirmation');

    for (const sell of sellsList.value) {
      if (sell?.pickup_code) {
        try {
          const payload = buildPickupQrPayload(sell.pickup_code, sell.sell_id);
          const url = await QRCode.toDataURL(payload, {
            width: 260,
            margin: 2,
            color: {
              dark: tokens.tatelestai.color.content.value,
              light: tokens.tatelestai.color.surface.value,
            },
          });
          qrDataUrls.value[sell.sell_id] = url;
          if (!qrDataUrl.value) {
            qrDataUrl.value = url;
          }
        } catch (qrErr) {
          console.error('Error generando QR:', qrErr);
        }
      }
    }
  } catch (error) {
    console.error('Error al verificar pago:', error);
    errorMessage.value =
      error.response?.data?.error ||
      error.response?.data?.message ||
      'No se pudo verificar el estado del pago con Mercado Pago.';
    verificationSuccess.value = false;
  } finally {
    loading.value = false;
  }
};

const copyPickupCode = async (codeToCopy = null, sellId = null) => {
  const code = codeToCopy || orderData.value?.pickup_code;
  if (!code) return;
  try {
    await navigator.clipboard.writeText(code);
    isCopied.value = true;
    copiedSellId.value = sellId;
    setTimeout(() => {
      isCopied.value = false;
      copiedSellId.value = null;
    }, 2000);
  } catch (err) {
    console.error('Error al copiar código:', err);
  }
};

const goToPurchases = () => {
  router.push({ name: 'customer-purchases' });
};

const goToCart = () => {
  router.push({ name: 'cart' });
};

const goToOffers = () => {
  router.push({ name: 'customer-offers' });
};

onMounted(() => {
  verifyPaymentReturn();
});
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-canvas text-content flex items-center justify-center p-4 sm:p-8">
    <!-- ESTADO 1: VERIFICANDO -->
    <div
      v-if="loading"
      class="w-full max-w-xl bg-surface border border-border-subtle rounded-[22px] p-8 sm:p-10 shadow-card text-center space-y-6"
    >
      <div class="w-20 h-20 rounded-full bg-surface-sunken border border-border-subtle flex items-center justify-center mx-auto text-brand">
        <div class="w-11 h-11 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div class="space-y-2">
        <span class="text-[11px] font-bold uppercase tracking-wider text-brand block">
          Conciliación en Tiempo Real • Mercado Pago
        </span>
        <h2 class="text-2xl font-black text-content-strong tracking-tight">
          Verificando el estado de tu pago...
        </h2>
        <p class="text-sm text-content-secondary max-w-md mx-auto">
          Estamos confirmando la acreditación con Mercado Pago y generando tu código único de retiro. Por favor, no cierres ni recargues esta ventana.
        </p>
      </div>

      <!-- Checklist de progreso -->
      <div class="p-4 sm:p-5 rounded-2xl bg-surface-sunken border border-border-subtle text-left space-y-3">
        <div class="flex items-center gap-3 text-xs sm:text-sm font-semibold text-content-strong">
          <span class="w-5 h-5 rounded-full bg-success/20 text-success flex items-center justify-center shrink-0 font-bold">
            ✓
          </span>
          <span>Cupo de pack sorpresa bloqueado temporalmente</span>
        </div>
        <div class="flex items-center gap-3 text-xs sm:text-sm font-bold text-brand">
          <span class="w-5 h-5 rounded-full border-2 border-brand border-t-transparent animate-spin shrink-0"></span>
          <span>Validando comprobante de pago con Mercado Pago...</span>
        </div>
        <div class="flex items-center gap-3 text-xs sm:text-sm text-content-muted">
          <span class="w-5 h-5 rounded-full border border-border-subtle shrink-0"></span>
          <span>Emisión de código alfanumérico y QR de mostrador</span>
        </div>
      </div>

      <p class="text-xs text-content-muted font-medium">
        🔒 Conexión cifrada de extremo a extremo con Mercado Pago Checkout Pro
      </p>
    </div>

    <!-- ESTADO 2: PAGO APROBADO (TICKET DE RETIRO INMEDIATO) -->
    <div
      v-else-if="verificationSuccess"
      class="w-full max-w-2xl bg-surface border border-success/40 rounded-[22px] overflow-hidden shadow-card"
    >
      <!-- Cabecera de Éxito -->
      <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-success/15 text-success border border-success/30 rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-success block">
              {{
                sellsList.length > 1
                  ? `Pago Aprobado • ${sellsList.length} Turnos de Retiro`
                  : `Pago Aprobado • Reserva #${orderData?.sell_id || 'Confirmada'}`
              }}
            </span>
            <h2 class="text-xl sm:text-2xl font-black text-content-strong tracking-tight">
              {{
                sellsList.length > 1
                  ? '¡Tus packs sorpresa ya están reservados!'
                  : '¡Tu pack sorpresa ya está reservado!'
              }}
            </h2>
            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
              {{
                sellsList.length > 1
                  ? `Acreditamos tu pago con éxito. Generamos ${sellsList.length} pases QR independientes según el horario de retiro de cada bolsa.`
                  : 'Acreditamos tu pago con éxito. Tu ticket digital de retiro inmediato ya está activo.'
              }}
            </p>
          </div>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-surface-sunken border border-success/30 self-start sm:self-center shrink-0">
          <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
          <span class="text-xs font-bold text-success">Listo para retirar</span>
        </div>
      </div>

      <!-- Línea Perforada de Ticket -->
      <div class="border-t-2 border-dashed border-border-subtle"></div>

      <!-- Cuerpo del Ticket (QR + Código Alfanumérico por cada turno) -->
      <div class="p-6 sm:p-8 bg-surface-sunken space-y-6">
        <div
          v-for="(sell, sIdx) in sellsList"
          :key="sell.sell_id || sIdx"
          class="p-5 sm:p-6 rounded-2xl bg-surface border border-border-subtle flex flex-col sm:flex-row items-center gap-6 shadow-2xs"
        >
          <!-- QR en Tarjeta Blanca de Alto Contraste -->
          <div v-if="qrDataUrls[sell.sell_id] || qrDataUrl" class="shrink-0">
            <div class="p-3 bg-surface rounded-2xl shadow-sm border border-border-subtle">
              <img
                :src="qrDataUrls[sell.sell_id] || qrDataUrl"
                alt="Código QR de retiro"
                class="w-44 h-44 sm:w-48 sm:h-48 object-contain rounded-lg"
              />
            </div>
          </div>

          <!-- Consola de Código Alfanumérico -->
          <div class="flex-1 text-center sm:text-left space-y-3 w-full min-w-0">
            <div>
              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
                  {{
                    sellsList.length > 1
                      ? `Ticket Turno ${sIdx + 1} • Reserva #${sell.sell_id}`
                      : 'Ticket de Retiro Inmediato'
                  }}
                </span>
              </div>
              <p class="text-xs text-content-secondary mt-0.5">
                Mostrá este QR en el mostrador o dictá el código alfanumérico al personal del local:
              </p>
              <div v-if="Array.isArray(sell.offers) && sell.offers.length" class="flex flex-wrap gap-1.5 mt-2 justify-center sm:justify-start">
                <span
                  v-for="(item, iIdx) in sell.offers"
                  :key="iIdx"
                  class="px-2.5 py-0.5 rounded-full bg-surface-sunken border border-border-subtle text-[11px] font-semibold text-content"
                >
                  {{ item.quantity }}x {{ item.title }}
                </span>
              </div>
            </div>

            <div class="p-3.5 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-between gap-3">
              <span class="font-mono text-xl sm:text-2xl font-black tracking-widest text-brand select-all truncate">
                {{ formatPickupCodeInput(sell.pickup_code) }}
              </span>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[10px] text-xs font-bold transition-all cursor-pointer shrink-0"
                :class="
                  isCopied && copiedSellId === sell.sell_id
                    ? 'bg-success/20 text-success border border-success/30'
                    : 'bg-surface hover:bg-surface-sunken border border-border-subtle text-content'
                "
                @click="copyPickupCode(sell.pickup_code, sell.sell_id)"
              >
                <svg v-if="!(isCopied && copiedSellId === sell.sell_id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
                <svg v-else class="w-3.5 h-3.5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isCopied && copiedSellId === sell.sell_id ? '¡Copiado!' : 'Copiar' }}</span>
              </button>
            </div>

            <p v-if="formatSellPickupWindow(sell)" class="text-xs text-warning font-semibold">
              🕒 Ventana de retiro: {{ formatSellPickupWindow(sell) }}
            </p>
            <p v-else-if="maxPickupTimeFormatted" class="text-xs text-warning font-semibold">
              🕒 Horario límite de retiro: hoy hasta las {{ maxPickupTimeFormatted }} hs
            </p>
          </div>
        </div>

        <!-- Acciones Post-Pago -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-1">
          <p class="text-xs text-content-secondary text-center sm:text-left">
            También podés consultar {{ sellsList.length > 1 ? 'estos códigos' : 'este código' }} en cualquier momento desde <strong class="text-content-strong">Mis Reservas</strong>.
          </p>
          <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto shrink-0">
            <button
              type="button"
              class="h-11 px-5 rounded-[10px] bg-surface hover:bg-surface-sunken border border-border-subtle text-content font-bold text-sm transition-colors cursor-pointer"
              @click="goToOffers"
            >
              Seguir explorando
            </button>
            <button
              type="button"
              class="h-11 px-6 rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-sm transition-colors cursor-pointer shadow-sm"
              @click="goToPurchases"
            >
              Ver en Mis Reservas →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ESTADO 3: PAGO RECHAZADO / ERROR -->
    <div
      v-else
      class="w-full max-w-xl bg-surface border border-danger/40 rounded-[22px] p-6 sm:p-8 shadow-card text-center space-y-6"
    >
      <div class="w-16 h-16 bg-danger/10 text-danger border border-danger/30 rounded-2xl flex items-center justify-center mx-auto">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>

      <div class="space-y-1.5">
        <span class="text-[11px] font-bold uppercase tracking-wider text-danger block">
          Operación No Completada
        </span>
        <h2 class="text-2xl font-black text-content-strong tracking-tight">
          No pudimos procesar el pago
        </h2>
        <p class="text-sm text-danger font-medium">
          {{ errorMessage || 'El pago fue rechazado o cancelado en Mercado Pago.' }}
        </p>
      </div>

      <!-- Notificación de Fondos No Debitados -->
      <div class="p-4 rounded-2xl bg-surface-sunken border border-border-subtle flex items-start gap-3.5 text-left">
        <div class="w-9 h-9 rounded-xl bg-success/15 text-success flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
          </svg>
        </div>
        <div class="space-y-1">
          <h4 class="text-xs sm:text-sm font-bold text-content-strong">
            Tu dinero está a salvo: no se debitaron fondos de tu cuenta
          </h4>
          <p class="text-xs text-content-secondary leading-relaxed">
            Ningún cargo fue aplicado a tu tarjeta ni saldo de Mercado Pago. Los cupos reservados fueron liberados automáticamente para que puedas volver a intentarlo.
          </p>
        </div>
      </div>

      <!-- Botones de Acción (Reintentar + Explorar) -->
      <div class="pt-2 flex flex-col sm:flex-row gap-3">
        <button
          type="button"
          class="h-11 px-5 rounded-[10px] bg-surface hover:bg-surface-sunken border border-border-subtle text-content font-bold text-sm transition-colors cursor-pointer"
          @click="goToOffers"
        >
          Explorar otros packs
        </button>
        <button
          type="button"
          class="flex-1 h-11 px-6 rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-sm transition-colors cursor-pointer shadow-sm inline-flex items-center justify-center gap-2"
          @click="goToCart"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>Reintentar pago desde Mi Carrito</span>
        </button>
      </div>
    </div>
  </div>
</template>
