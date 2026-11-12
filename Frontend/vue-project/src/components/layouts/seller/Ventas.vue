<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axiosInstance from "@/lib/axios";
import { useFormatters } from "@/composables/useFormatters";
import { useNotificationStore } from "@/stores/notifications";
import SellerSaleRowCard from "./SellerSaleRowCard.vue";

const notificationStore = useNotificationStore();
const { formatCurrency } = useFormatters();

const ventas = ref([]);
const loading = ref(true);
const error = ref(null);

// Controles de filtro y período
const selectedPeriod = ref("month"); // 'week' | 'month' | 'year'
const searchQuery = ref("");
const statusFilter = ref("all"); // 'all' | 'picked_up' | 'pending' | 'expired' | 'cancelled'
const sortOrder = ref("newest"); // 'newest' | 'oldest' | 'amount_desc'

const toggleSortOrder = () => {
  if (sortOrder.value === "newest") sortOrder.value = "amount_desc";
  else if (sortOrder.value === "amount_desc") sortOrder.value = "oldest";
  else sortOrder.value = "newest";
};

const sortLabel = computed(() => {
  if (sortOrder.value === "amount_desc") return "Ordenar: Mayor importe ▾";
  if (sortOrder.value === "oldest") return "Ordenar: Más antiguas ▾";
  return "Ordenar: Más recientes ▾";
});

// Filtrar ventas por el período seleccionado en la cabecera
const periodVentas = computed(() => {
  const now = new Date();
  return ventas.value.filter((v) => {
    if (!v.created_at) return true;
    const d = new Date(v.created_at);
    if (selectedPeriod.value === "week") {
      const diffDays = (now - d) / (1000 * 60 * 60 * 24);
      return diffDays <= 7;
    }
    if (selectedPeriod.value === "month") {
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }
    if (selectedPeriod.value === "year") {
      return d.getFullYear() === now.getFullYear();
    }
    return true;
  });
});

// Si no hay ventas en el período actual pero sí en el historial total, mostrar sobre el conjunto disponible
const effectiveMetricVentas = computed(() => {
  return periodVentas.value.length > 0 ? periodVentas.value : ventas.value;
});

// Resumen Financiero e Impacto (5 KPIs de Figma)
const kpiMetrics = computed(() => {
  const all = effectiveMetricVentas.value;
  const validVentas = all.filter((v) => v.state !== "cancelled" && v.payment_status !== "refunded");

  let gross = 0;
  let commission = 0;
  let net = 0;
  let rescuedBags = 0;
  let deliveredCount = 0;
  let noShowCount = 0;

  validVentas.forEach((v) => {
    const rawGross =
      Number(v.gross_amount || 0) ||
      (Array.isArray(v.sell_details)
        ? v.sell_details.reduce(
            (acc, d) => acc + Number(d.offer_quantity || 1) * Number(d.pack_price || 0),
            0
          )
        : 0);
    const itemGross =
      v.effective_gross_amount !== undefined && v.effective_gross_amount !== null
        ? Number(v.effective_gross_amount)
        : Math.max(0, rawGross - Number(v.refunded_amount || 0));
    const itemComm =
      v.commission_amount !== undefined && v.commission_amount !== null
        ? Number(v.commission_amount)
        : Math.round(itemGross * 0.15);
    const itemNet =
      v.net_amount !== undefined && v.net_amount !== null
        ? Number(v.net_amount)
        : Math.max(0, itemGross - itemComm);

    gross += itemGross;
    commission += itemComm;
    net += itemNet;

    const bagsInOrder = Array.isArray(v.sell_details)
      ? v.sell_details.reduce((acc, d) => acc + Number(d.offer_quantity || 1), 0)
      : 1;

    if (v.is_picked_up || v.state === "picked_up" || v.state === "delivered") {
      rescuedBags += bagsInOrder;
      deliveredCount++;
    } else if (v.state === "expired") {
      noShowCount++;
    } else {
      // Pedidos activos también suman a las bolsas comercializadas
      rescuedBags += bagsInOrder;
    }
  });

  const savedKg = +(rescuedBags * 1.1).toFixed(1);
  const savedCo2 = +(savedKg * 2.5).toFixed(1);

  const totalClosed = deliveredCount + noShowCount;
  const deliveryRate =
    totalClosed > 0
      ? Math.round((deliveredCount / totalClosed) * 100)
      : validVentas.length > 0
      ? 100
      : 0;

  return {
    net,
    gross,
    commission,
    rescuedBags,
    orderCount: validVentas.length,
    savedKg,
    savedCo2,
    deliveryRate,
    noShowCount,
  };
});

// Gráfico de 7 barras (Lunes a Domingo)
const weeklyChartBars = computed(() => {
  const dayNames = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
  const buckets = dayNames.map((label, index) => ({
    label,
    dayIndex: index, // 0=Lun ... 6=Dom
    netAmount: 0,
    bagsCount: 0,
  }));

  const valid = effectiveMetricVentas.value.filter(
    (v) => v.state !== "cancelled" && v.payment_status !== "refunded"
  );
  valid.forEach((v) => {
    const d = v.created_at ? new Date(v.created_at) : new Date();
    const jsDay = d.getDay(); // 0=Dom, 1=Lun...
    const bucketIdx = jsDay === 0 ? 6 : jsDay - 1;

    const rawGross =
      Number(v.gross_amount || 0) ||
      (Array.isArray(v.sell_details)
        ? v.sell_details.reduce(
            (acc, det) => acc + Number(det.offer_quantity || 1) * Number(det.pack_price || 0),
            0
          )
        : 0);
    const itemGross =
      v.effective_gross_amount !== undefined && v.effective_gross_amount !== null
        ? Number(v.effective_gross_amount)
        : Math.max(0, rawGross - Number(v.refunded_amount || 0));
    const itemNet =
      v.net_amount !== undefined && v.net_amount !== null
        ? Number(v.net_amount)
        : Math.round(itemGross * 0.85);
    const bags = Array.isArray(v.sell_details)
      ? v.sell_details.reduce((acc, det) => acc + Number(det.offer_quantity || 1), 0)
      : 1;

    buckets[bucketIdx].netAmount += itemNet;
    buckets[bucketIdx].bagsCount += bags;
  });

  const maxNet = Math.max(...buckets.map((b) => b.netAmount), 1);
  const peakIdx = buckets.reduce(
    (best, b, i, arr) => (b.netAmount > arr[best].netAmount ? i : best),
    0
  );

  return buckets.map((b, idx) => {
    const heightPct = b.netAmount > 0 ? Math.max(Math.round((b.netAmount / maxNet) * 100), 18) : 10;
    const shortAmount =
      b.netAmount >= 1000
        ? `$${(b.netAmount / 1000).toFixed(1).replace(".0", "")}k`
        : `$${b.netAmount}`;
    return {
      ...b,
      heightPct,
      shortAmount,
      isPeak: b.netAmount > 0 && idx === peakIdx,
    };
  });
});

// Desempeño por Tipo de Bolsa (barras horizontales)
const bagTypeBreakdown = computed(() => {
  const map = new Map();
  let totalBags = 0;

  effectiveMetricVentas.value
    .filter((v) => v.state !== "cancelled" && v.payment_status !== "refunded")
    .forEach((v) => {
      if (Array.isArray(v.sell_details)) {
        v.sell_details.forEach((d) => {
          const name = d.pack_name || "Bolsa Sorpresa";
          const qty = Number(d.offer_quantity || 1);
          map.set(name, (map.get(name) || 0) + qty);
          totalBags += qty;
        });
      }
    });

  if (map.size === 0) {
    return [];
  }

  const colors = [
    "var(--color-brand)",
    "var(--color-brand-light)",
    "var(--color-content)",
    "var(--color-content-secondary)",
  ];
  return Array.from(map.entries())
    .sort((a, b) => b[1] - a[1])
    .slice(0, 4)
    .map(([name, count], idx) => {
      const pct = totalBags > 0 ? Math.round((count / totalBags) * 100) : 0;
      return {
        name,
        count,
        pct,
        color: colors[idx % colors.length],
      };
    });
});

// Historial filtrado y ordenado
const filteredSalesHistory = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();

  const filtered = ventas.value.filter((v) => {
    // Filtro por estado
    if (statusFilter.value === "picked_up") {
      if (!v.is_picked_up && v.state !== "picked_up" && v.state !== "delivered") return false;
    } else if (statusFilter.value === "pending") {
      if (
        v.is_picked_up ||
        v.state === "picked_up" ||
        v.state === "delivered" ||
        v.state === "expired" ||
        v.state === "cancelled" ||
        v.payment_status === "refunded"
      ) {
        return false;
      }
    } else if (statusFilter.value === "expired") {
      if (v.state !== "expired") return false;
    } else if (statusFilter.value === "cancelled") {
      if (v.state !== "cancelled" && v.payment_status !== "refunded") return false;
    }

    // Filtro por texto
    if (!q) return true;
    const idMatch = String(v.id || "").toLowerCase().includes(q);
    const codeMatch = String(v.pickup_code || "").toLowerCase().includes(q);
    const buyerName = String(v.user?.name || v.customer_name || v.buyer_name || "").toLowerCase();
    const buyerMatch = buyerName.includes(q);
    const packMatch =
      Array.isArray(v.sell_details) &&
      v.sell_details.some((d) => String(d.pack_name || "").toLowerCase().includes(q));

    return idMatch || codeMatch || buyerMatch || packMatch;
  });

  return [...filtered].sort((a, b) => {
    if (sortOrder.value === "amount_desc") {
      return Number(b.gross_amount || 0) - Number(a.gross_amount || 0);
    }
    const dateA = new Date(a.created_at || 0).getTime();
    const dateB = new Date(b.created_at || 0).getTime();
    return sortOrder.value === "oldest" ? dateA - dateB : dateB - dateA;
  });
});

const fetchVentas = async (silent = false) => {
  const isSilent = silent === true;
  try {
    if (!isSilent) loading.value = true;
    error.value = null;
    const response = await axiosInstance.get("/sells");
    ventas.value = response.data?.data || response.data || [];
  } catch (err) {
    error.value = "Error al cargar el historial de ventas.";
    console.error("Error:", err);
  } finally {
    if (!isSilent) loading.value = false;
  }
};

watch(
  () => notificationStore.lastOrderEvent,
  (newEvent) => {
    if (newEvent) {
      fetchVentas(true);
    }
  }
);

onMounted(() => {
  fetchVentas();
});
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-16 space-y-9 text-content">
    <!-- SECCIÓN 1: ImpactAndFinanceHeaderSection -->
    <section class="space-y-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-[22px] font-extrabold text-content tracking-tight">
            Rendimiento, Impacto y Ventas
          </h1>
          <p class="text-[13.5px] text-content-secondary">
            Seguimiento de tus ingresos, bolsas rescatadas y cumplimiento de entregas
          </p>
        </div>

        <!-- Selector de Período -->
        <div class="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full text-[12px] transition cursor-pointer"
            :class="
              selectedPeriod === 'week'
                ? 'bg-content text-surface font-bold'
                : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
            "
            @click="selectedPeriod = 'week'"
          >
            Últimos 7 días
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full text-[12px] transition cursor-pointer"
            :class="
              selectedPeriod === 'month'
                ? 'bg-content text-surface font-bold'
                : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
            "
            @click="selectedPeriod = 'month'"
          >
            Este mes
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full text-[12px] transition cursor-pointer"
            :class="
              selectedPeriod === 'year'
                ? 'bg-content text-surface font-bold'
                : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
            "
            @click="selectedPeriod = 'year'"
          >
            Este año
          </button>
        </div>
      </div>

      <!-- Fila de 5 KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 pb-7 border-b border-border-subtle">
        <!-- KPI 1: Ganancia Neta (85%) -->
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            GANANCIA NETA (85%)
          </span>
          <p class="text-[28px] font-extrabold text-brand leading-tight">
            ${{ formatCurrency(kpiMetrics.net) }}
          </p>
          <span class="text-[12px] font-semibold text-warning block">
            ▲ Ingreso libre p/ tu comercio
          </span>
        </div>

        <!-- KPI 2: Total Bruto / Comisión -->
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            TOTAL BRUTO / COMISIÓN
          </span>
          <p class="text-[28px] font-extrabold text-content leading-tight">
            ${{ formatCurrency(kpiMetrics.gross) }}
          </p>
          <span class="text-[12px] text-content-secondary block">
            Comisión 15%: -${{ formatCurrency(kpiMetrics.commission) }}
          </span>
        </div>

        <!-- KPI 3: Bolsas Rescatadas -->
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            BOLSAS RESCATADAS
          </span>
          <p class="text-[28px] font-extrabold text-content leading-tight">
            {{ kpiMetrics.rescuedBags }}
          </p>
          <span class="text-[12px] text-content-secondary block">
            En {{ kpiMetrics.orderCount }} pedidos completados
          </span>
        </div>

        <!-- KPI 4: Comida Salvada -->
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            COMIDA SALVADA
          </span>
          <p class="text-[28px] font-extrabold text-warning leading-tight">
            {{ kpiMetrics.savedKg }} kg
          </p>
          <span class="text-[12px] text-content-secondary block">
            Equivalente a {{ kpiMetrics.savedCo2 }} kg de CO₂
          </span>
        </div>

        <!-- KPI 5: Tasa de Entrega -->
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            TASA DE ENTREGA
          </span>
          <p class="text-[28px] font-extrabold text-content leading-tight">
            {{ kpiMetrics.deliveryRate }}%
          </p>
          <span class="text-[12px] text-content-secondary block">
            {{ kpiMetrics.noShowCount }} no-shows en el período
          </span>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 2: ChartsAndBreakdownSection (2 Columnas: Figma exact 810px y 554px con 56px gap) -->
    <section class="grid grid-cols-1 lg:grid-cols-[minmax(0,810px)_minmax(0,554px)] gap-14 pb-7 border-b border-border-subtle">
      <!-- Columna Izquierda: Evolución de Ingresos Netos y Bolsas Entregadas (Figma ColLeft 810px) -->
      <div class="space-y-5 flex flex-col justify-between">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="space-y-0.5">
            <h2 class="text-[16px] font-bold text-content">
              Evolución de Ingresos Netos y Bolsas Entregadas
            </h2>
            <p class="text-[12.5px] text-content-secondary">
              Desglose diario de la última semana operativa
            </p>
          </div>
          <span class="text-[12px] font-semibold text-brand">
            ● Ganancia neta ($)
          </span>
        </div>

        <!-- Gráfico de 7 barras -->
        <div class="grid grid-cols-7 gap-3 sm:gap-5 items-end pt-4 min-h-[210px]">
          <div
            v-for="bar in weeklyChartBars"
            :key="bar.label"
            class="flex flex-col items-center gap-2 h-full justify-end"
          >
            <span
              class="text-[11px] font-mono"
              :class="
                bar.isPeak
                  ? 'font-bold text-brand'
                  : 'font-semibold text-content'
              "
            >
              {{ bar.shortAmount }}
            </span>

            <div class="w-full h-32 flex items-end justify-center">
              <div
                class="w-full max-w-[44px] rounded-t-[8px] transition-all duration-300"
                :class="
                  bar.isPeak
                    ? 'bg-brand'
                    : bar.netAmount > 0
                    ? 'bg-warning'
                    : 'bg-border-subtle/60'
                "
                :style="{ height: `${bar.heightPct}%` }"
              ></div>
            </div>

            <div class="text-center">
              <span
                class="block text-[12px]"
                :class="
                  bar.isPeak
                    ? 'font-bold text-brand'
                    : 'font-semibold text-content-secondary'
                "
              >
                {{ bar.label }}
              </span>
              <span class="block text-[10px] text-content-secondary">
                {{ bar.bagsCount }} packs
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Desempeño por Tipo de Bolsa (Figma ColRight 554px) -->
      <div class="flex flex-col justify-between space-y-5">
        <div class="space-y-4">
          <div class="space-y-0.5">
            <h2 class="text-[16px] font-bold text-content">
              Desempeño por Tipo de Bolsa
            </h2>
            <p class="text-[12.5px] text-content-secondary">
              Participación sobre el total de bolsas vendidas
            </p>
          </div>

          <div v-if="bagTypeBreakdown.length === 0" class="py-8 text-xs text-content-secondary">
            Aún no hay suficientes datos de bolsas vendidas para mostrar el desglose.
          </div>

          <div v-else class="space-y-3.5">
            <div
              v-for="item in bagTypeBreakdown"
              :key="item.name"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between gap-2 text-[13px]">
                <span class="font-semibold text-content truncate">
                  {{ item.name }}
                </span>
                <span
                  class="font-bold shrink-0"
                  :style="{ color: item.color }"
                >
                  {{ item.count }} bolsas ({{ item.pct }}%)
                </span>
              </div>
              <div class="w-full h-2 rounded-full bg-surface-sunken overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :style="{ width: `${item.pct}%`, backgroundColor: item.color }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pie de Calificación y Recurrencia -->
        <div class="pt-3.5 border-t border-border-subtle flex items-center justify-between gap-4 text-[13px] font-bold">
          <span class="text-warning">★ 4.9 Calificación promedio</span>
          <span class="text-content">64% compradores recurrentes</span>
        </div>
      </div>
    </section>

    <!-- SECCIÓN 3: SalesSearchAndHistorySection -->
    <section class="space-y-5">
      <!-- Barra de Búsqueda, Filtros de Estado y Ordenamiento -->
      <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 flex-1">
          <!-- Buscador -->
          <div class="relative w-full md:w-80 lg:w-96">
            <svg
              class="w-4 h-4 text-content-secondary absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar venta por #Pedido, código, cliente o bolsa..."
              class="w-full pl-10 pr-8 py-2.5 rounded-full bg-surface border border-border-subtle text-[13px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition"
            />
          </div>

          <!-- Píldoras de Estado -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              class="px-4 py-2 rounded-full text-[12.5px] transition cursor-pointer"
              :class="
                statusFilter === 'all'
                  ? 'bg-content text-surface font-bold'
                  : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
              "
              @click="statusFilter = 'all'"
            >
              Todas ({{ ventas.length }})
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full text-[12.5px] transition cursor-pointer"
              :class="
                statusFilter === 'picked_up'
                  ? 'bg-content text-surface font-bold'
                  : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
              "
              @click="statusFilter = 'picked_up'"
            >
              Retiradas
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full text-[12.5px] transition cursor-pointer"
              :class="
                statusFilter === 'pending'
                  ? 'bg-content text-surface font-bold'
                  : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
              "
              @click="statusFilter = 'pending'"
            >
              Pendientes
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full text-[12.5px] transition cursor-pointer"
              :class="
                statusFilter === 'expired'
                  ? 'bg-content text-surface font-bold'
                  : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
              "
              @click="statusFilter = 'expired'"
            >
              Expiradas / No-Show
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full text-[12.5px] transition cursor-pointer"
              :class="
                statusFilter === 'cancelled'
                  ? 'bg-content text-surface font-bold'
                  : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
              "
              @click="statusFilter = 'cancelled'"
            >
              Reembolsadas
            </button>
          </div>
        </div>

        <!-- Botón de Ordenar -->
        <button
          type="button"
          class="px-4 py-2 rounded-full bg-surface border border-border-subtle text-[12.5px] font-semibold text-content hover:border-brand transition cursor-pointer self-start xl:self-auto shrink-0"
          @click="toggleSortOrder"
        >
          {{ sortLabel }}
        </button>
      </div>

      <!-- Estado de Carga -->
      <div v-if="loading" class="text-center py-14 space-y-3">
        <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs text-content-secondary">Cargando historial de ventas...</p>
      </div>

      <!-- Estado de Error -->
      <div
        v-else-if="error"
        class="p-6 rounded-[18px] bg-danger/10 border border-danger/30 text-center space-y-3"
      >
        <p class="text-sm font-semibold text-danger">{{ error }}</p>
        <button
          type="button"
          class="px-4 py-2 rounded-[10px] bg-danger text-surface font-bold text-xs cursor-pointer"
          @click="fetchVentas"
        >
          Reintentar
        </button>
      </div>

      <!-- Sin Ventas -->
      <div
        v-else-if="filteredSalesHistory.length === 0"
        class="p-12 rounded-[18px] bg-surface border border-dashed border-border-subtle text-center space-y-2"
      >
        <div class="w-12 h-12 rounded-2xl bg-brand/10 text-brand flex items-center justify-center mx-auto">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <h3 class="text-sm font-bold text-content">
          {{ searchQuery || statusFilter !== "all" ? "No hay ventas que coincidan con el filtro" : "No tenés ventas registradas aún" }}
        </h3>
        <p class="text-xs text-content-secondary">
          Cuando los clientes compren tus bolsas sorpresa, aparecerán detalladas aquí.
        </p>
      </div>

      <!-- Lista de SellerSaleRowCard -->
      <div v-else class="space-y-4">
        <SellerSaleRowCard
          v-for="venta in filteredSalesHistory"
          :key="venta.id"
          :sale="venta"
        />
      </div>
    </section>
  </div>
</template>
