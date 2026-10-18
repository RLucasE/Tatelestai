<script setup>
import { ref, computed, onMounted } from 'vue';
import axiosInstance from '@/lib/axios';
import { useFormatters } from '@/composables/useFormatters';

const ventas = ref([]);
const loading = ref(true);
const error = ref(null);

const { formatCurrency } = useFormatters();

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const financialSummary = computed(() => {
  // Solo contabilizar ventas no canceladas para el acumulado neto
  const validVentas = ventas.value.filter((v) => v.state !== 'cancelled');

  const gross = validVentas.reduce((sum, v) => sum + Number(v.gross_amount || 0), 0);
  const commission = validVentas.reduce((sum, v) => sum + Number(v.commission_amount || 0), 0);
  const net = validVentas.reduce((sum, v) => sum + Number(v.net_amount || 0), 0);

  return {
    gross,
    commission,
    net,
    count: validVentas.length,
  };
});

const getStatusBadge = (venta) => {
  if (venta.state === 'cancelled') {
    return { text: 'Cancelado / Reembolsado', class: 'bg-[#EF4444]/15 border-[#EF4444]/30 text-[#F87171]' };
  }
  if (venta.is_picked_up || venta.state === 'picked_up') {
    return { text: 'Retirado', class: 'bg-[#10B981]/15 border-[#10B981]/30 text-[#34D399]' };
  }
  if (venta.state === 'expired') {
    return { text: 'Expirado (No-Show)', class: 'bg-[#EF4444]/15 border-[#EF4444]/30 text-[#F87171]' };
  }
  return { text: 'Pendiente de entrega', class: 'bg-[#F59E0B]/15 border-[#F59E0B]/30 text-[#FBBF24]' };
};

const fetchVentas = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axiosInstance.get('/sells');
    ventas.value = response.data;
  } catch (err) {
    error.value = 'Error al cargar el historial de ventas.';
    console.error('Error:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchVentas);
</script>

<template>
  <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-white tracking-tight">Mis Ventas</h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2]">
          Historial detallado de pedidos recibidos, comisiones retenidas y ganancias netas.
        </p>
      </div>

      <button
        type="button"
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-xs font-bold text-white transition-colors cursor-pointer self-start sm:self-center"
        @click="fetchVentas"
      >
        <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Actualizar
      </button>
    </div>

    <!-- Resumen Financiero Claro -->
    <div v-if="!loading && ventas.length > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] space-y-1">
        <span class="text-[11px] font-bold text-[#A5A8C2] uppercase tracking-wider block">Total Vendido</span>
        <p class="text-2xl font-black text-white leading-tight">
          ${{ formatCurrency(financialSummary.gross) }}
        </p>
        <span class="text-[10px] text-[#A5A8C2]">{{ financialSummary.count }} pedidos liquidados</span>
      </div>

      <div class="p-4 rounded-2xl bg-[#2D2438] border border-white/[0.08] space-y-1">
        <span class="text-[11px] font-bold text-[#A5A8C2] uppercase tracking-wider block">Comisión Descontada (15%)</span>
        <p class="text-2xl font-black text-[#F87171] leading-tight">
          -${{ formatCurrency(financialSummary.commission) }}
        </p>
        <span class="text-[10px] text-[#A5A8C2]">Tasa del 15% por transacción</span>
      </div>

      <div class="p-4 rounded-2xl bg-[#2D2438] border border-[#10B981]/40 bg-[#10B981]/5 space-y-1">
        <span class="text-[11px] font-bold text-[#34D399] uppercase tracking-wider block">Ganancia Neta (85%)</span>
        <p class="text-2xl font-black text-[#10B981] leading-tight">
          ${{ formatCurrency(financialSummary.net) }}
        </p>
        <span class="text-[10px] text-[#34D399]/80 font-medium">Abonado a tu comercio</span>
      </div>
    </div>

    <!-- Estado de Carga -->
    <div v-if="loading" class="text-center py-16 space-y-3">
      <div class="w-10 h-10 border-3 border-[#7C3AED] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-[#A5A8C2]">Cargando ventas...</p>
    </div>

    <!-- Estado de Error -->
    <div v-else-if="error" class="p-6 rounded-2xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-center space-y-3">
      <p class="text-sm font-semibold text-[#F87171]">{{ error }}</p>
      <button
        type="button"
        class="px-4 py-2 rounded-xl bg-[#EF4444] text-white font-bold text-xs cursor-pointer"
        @click="fetchVentas"
      >
        Reintentar
      </button>
    </div>

    <!-- Sin Ventas -->
    <div
      v-else-if="!ventas.length"
      class="p-12 rounded-3xl bg-[#2D2438] border border-dashed border-white/[0.1] text-center space-y-2"
    >
      <div class="w-12 h-12 rounded-2xl bg-white/[0.05] text-[#A5A8C2] flex items-center justify-center mx-auto">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-white">No tienes ventas registradas aún</h3>
      <p class="text-xs text-[#A5A8C2]">Cuando los clientes compren tus bolsas sorpresa, aparecerán detalladas aquí.</p>
    </div>

    <!-- Lista de Ventas -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="venta in ventas"
        :key="venta.id"
        class="p-5 rounded-2xl bg-[#2D2438] border border-white/[0.08] space-y-3.5 transition-all hover:bg-[#3D3450]/60"
      >
        <!-- Cabecera de la Venta -->
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-bold text-white">Venta #{{ venta.id }}</h3>
            <span class="text-xs text-[#A5A8C2] block mt-0.5">{{ formatDate(venta.created_at) }}</span>
          </div>

          <span
            class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border shrink-0"
            :class="getStatusBadge(venta).class"
          >
            {{ getStatusBadge(venta).text }}
          </span>
        </div>

        <!-- Bolsas Vendidas -->
        <div class="space-y-1.5 pt-1">
          <span class="text-[10px] font-bold text-[#A5A8C2] uppercase tracking-wider block">Bolsas sorpresa</span>
          <div
            v-for="detalle in venta.sell_details"
            :key="detalle.id"
            class="flex items-center justify-between text-xs text-[#E8EAF6]"
          >
            <div class="flex items-center gap-1.5 truncate">
              <span class="font-bold text-[#A78BFA]">x{{ detalle.offer_quantity }}</span>
              <span class="truncate">{{ detalle.pack_name }}</span>
            </div>
            <span class="font-medium shrink-0 ml-2 text-white">
              ${{ formatCurrency(Number(detalle.offer_quantity) * Number(detalle.pack_price)) }}
            </span>
          </div>
        </div>

        <!-- Desglose Financiero -->
        <div class="p-3 rounded-xl bg-[#1A1625] border border-white/[0.04] grid grid-cols-3 gap-2 text-[11px] pt-2">
          <div>
            <span class="text-[10px] text-[#A5A8C2] block">Total Bruto</span>
            <strong class="text-white font-bold">${{ formatCurrency(venta.gross_amount) }}</strong>
          </div>
          <div>
            <span class="text-[10px] text-[#F87171]/80 block">Comisión 15%</span>
            <span class="text-[#F87171] font-semibold">-${{ formatCurrency(venta.commission_amount) }}</span>
          </div>
          <div>
            <span class="text-[10px] text-[#34D399] block">Tu Ganancia</span>
            <strong class="text-[#10B981] font-extrabold">${{ formatCurrency(venta.net_amount) }}</strong>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
