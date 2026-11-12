<script setup>
import { shallowRef, ref, computed, onMounted } from 'vue';
import axiosInstance from '@/lib/axios';
import AdminActivityBarChart from '@/components/layouts/admin/dashboard/AdminActivityBarChart.vue';
import AdminUsersByStateCard from '@/components/layouts/admin/dashboard/AdminUsersByStateCard.vue';
import AdminOffersByCategoryCard from '@/components/layouts/admin/dashboard/AdminOffersByCategoryCard.vue';
import AdminActionQueues from '@/components/layouts/admin/dashboard/AdminActionQueues.vue';

const loading = shallowRef(true);
const isDownloading = shallowRef(false);
const selectedPeriod = shallowRef('7d'); // '7d' | '30d' | 'year'

const pendingEstablishments = ref([]);
const pendingEstablishmentsCount = shallowRef(0);
const pendingLast24hCount = shallowRef(0);

const openReports = ref([]);
const openReportsCount = shallowRef(0);

const offerCategories = ref([]);
const userStatsTotal = shallowRef(0);
const userStatsList = ref([]);
const allSells = ref([]);

const currentYear = new Date().getFullYear();

const periodOptions = [
  { value: '7d', label: 'Últimos 7 días' },
  { value: '30d', label: 'Últimos 30 días' },
  { value: 'year', label: `Este año (${currentYear})` },
];

const totalActiveOffers = computed(() => {
  return offerCategories.value.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
});

const validSells = computed(() => {
  return allSells.value.filter(
    (s) => s.state !== 'cancelled' && s.payment_status !== 'refunded'
  );
});

const salesLast24h = computed(() => {
  const now = Date.now();
  const h24 = 24 * 60 * 60 * 1000;
  const current = validSells.value.filter((s) => now - new Date(s.created_at).getTime() <= h24).length;
  const previous = validSells.value.filter((s) => {
    const diff = now - new Date(s.created_at).getTime();
    return diff > h24 && diff <= h24 * 2;
  }).length;

  let deltaText = 'Sin cambios respecto a ayer';
  if (previous === 0 && current > 0) {
    deltaText = `+${current} respecto a ayer`;
  } else if (previous > 0) {
    const pct = Math.round(((current - previous) / previous) * 100);
    deltaText = `${pct >= 0 ? '+' : ''}${pct}% respecto a ayer`;
  }

  return { count: current, deltaText };
});

const periodStats = computed(() => {
  const now = new Date();
  let startDate;
  if (selectedPeriod.value === '7d') {
    startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
  } else if (selectedPeriod.value === '30d') {
    startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
  } else {
    startDate = new Date(now.getFullYear(), 0, 1);
  }

  const sellsInPeriod = validSells.value.filter((s) => new Date(s.created_at) >= startDate);
  const pickedUpSells = sellsInPeriod.filter((s) => s.state === 'picked_up');
  const sourceForRescue = pickedUpSells.length > 0 ? pickedUpSells : sellsInPeriod;

  const packsCount = sourceForRescue.reduce((acc, sell) => {
    const details = sell.sell_details || [];
    const qty = details.reduce((sum, d) => sum + (Number(d.offer_quantity) || 1), 0);
    return acc + (qty || 1);
  }, 0);

  const estimatedKg = Number((packsCount * 1.1).toFixed(1));
  const co2SavedKg = Math.round(estimatedKg * 2.5);

  return {
    packsCount,
    estimatedKg,
    co2SavedKg,
  };
});

const chartSubtitle = computed(() => {
  if (selectedPeriod.value === '7d') {
    return 'Actividad transaccional diaria de la plataforma (última semana)';
  }
  if (selectedPeriod.value === '30d') {
    return 'Actividad transaccional agrupada de los últimos 30 días';
  }
  return `Evolución mensual de pedidos en ${currentYear}`;
});

const chartBars = computed(() => {
  const now = new Date();

  if (selectedPeriod.value === '7d') {
    const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const bars = [];

    for (let i = 6; i >= 0; i--) {
      const d = new Date(now);
      d.setHours(0, 0, 0, 0);
      d.setDate(d.getDate() - i);
      const next = new Date(d);
      next.setDate(next.getDate() + 1);

      const daySells = validSells.value.filter((s) => {
        const dt = new Date(s.created_at);
        return dt >= d && dt < next;
      });

      const label = i === 0
        ? 'Hoy'
        : `${dayNames[d.getDay()]} ${String(d.getDate()).padStart(2, '0')}`;

      bars.push({
        key: d.toISOString().slice(0, 10),
        label,
        count: daySells.length,
      });
    }
    return bars;
  }

  if (selectedPeriod.value === '30d') {
    const bars = [];
    for (let block = 5; block >= 0; block--) {
      const end = new Date(now);
      end.setHours(23, 59, 59, 999);
      end.setDate(end.getDate() - block * 5);

      const start = new Date(end);
      start.setHours(0, 0, 0, 0);
      start.setDate(start.getDate() - 4);

      const blockSells = validSells.value.filter((s) => {
        const dt = new Date(s.created_at);
        return dt >= start && dt <= end;
      });

      const label = block === 0
        ? 'Últ. 5d'
        : `${String(start.getDate()).padStart(2, '0')}/${String(start.getMonth() + 1).padStart(2, '0')}`;

      bars.push({
        key: `block-${block}`,
        label,
        count: blockSells.length,
      });
    }
    return bars;
  }

  const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  const currentMonth = now.getMonth();
  const startMonth = Math.max(0, currentMonth - 6);
  const bars = [];

  for (let m = startMonth; m <= currentMonth; m++) {
    const monthSells = validSells.value.filter((s) => {
      const dt = new Date(s.created_at);
      return dt.getFullYear() === now.getFullYear() && dt.getMonth() === m;
    });
    bars.push({
      key: `month-${m}`,
      label: monthNames[m],
      count: monthSells.length,
    });
  }
  return bars;
});

const fetchDashboardData = async () => {
  try {
    loading.value = true;
    const [estRes, repRes, offersRes, usersRes, sellsRes] = await Promise.allSettled([
      axiosInstance.get('/adm/pending-establishments', { params: { status: 'pending', per_page: 5 } }),
      axiosInstance.get('/adm/reports', { params: { per_page: 20 } }),
      axiosInstance.get('/adm/active-offers-count'),
      axiosInstance.get('/adm/user-stats'),
      axiosInstance.get('/adm-sells'),
    ]);

    if (estRes.status === 'fulfilled') {
      const data = estRes.value.data;
      const list = Array.isArray(data?.data) ? data.data : [];
      pendingEstablishments.value = list.slice(0, 3);
      pendingEstablishmentsCount.value = Number(data?.stats?.pending ?? data?.total ?? list.length);

      const dayAgo = Date.now() - 24 * 60 * 60 * 1000;
      pendingLast24hCount.value = list.filter((e) => new Date(e.created_at).getTime() >= dayAgo).length;
    }

    if (repRes.status === 'fulfilled') {
      const list = Array.isArray(repRes.value.data?.data) ? repRes.value.data.data : [];
      const activeReports = list.filter((r) => r.status === 'pending' || r.status === 'reviewing');
      openReports.value = activeReports.slice(0, 3);
      openReportsCount.value = activeReports.length;
    }

    if (offersRes.status === 'fulfilled') {
      offerCategories.value = Array.isArray(offersRes.value.data?.data) ? offersRes.value.data.data : [];
    }

    if (usersRes.status === 'fulfilled') {
      userStatsTotal.value = Number(usersRes.value.data?.total ?? 0);
      userStatsList.value = Array.isArray(usersRes.value.data?.data) ? usersRes.value.data.data : [];
    }

    if (sellsRes.status === 'fulfilled') {
      const sellsData = sellsRes.value.data?.data ?? sellsRes.value.data ?? [];
      allSells.value = Array.isArray(sellsData) ? sellsData : [];
    }
  } catch (err) {
    console.error('Error cargando resumen administrativo:', err);
  } finally {
    loading.value = false;
  }
};

const downloadExcel = async () => {
  try {
    isDownloading.value = true;
    const response = await axiosInstance.get('/adm/export-dashboard', {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'dashboard-stats.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error al descargar el Excel:', error);
    alert('Error al descargar el archivo Excel');
  } finally {
    isDownloading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-9">
    <!-- Header y Selector de Período -->
    <section class="space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-content-strong tracking-tight font-heading">
            Panel de Administración
          </h1>
          <p class="text-xs sm:text-sm text-content-secondary mt-1">
            Supervisión operativa de comercios, moderación y actividad de la plataforma
          </p>
        </div>

        <!-- Filtros temporales + Exportar Excel -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="opt in periodOptions"
            :key="opt.value"
            type="button"
            class="h-9 px-4 rounded-full text-xs font-semibold font-heading transition-all cursor-pointer"
            :class="
              selectedPeriod === opt.value
                ? 'bg-brand text-surface shadow-xs'
                : 'bg-surface border border-border-subtle text-content-secondary hover:text-content-strong hover:border-content-muted'
            "
            @click="selectedPeriod = opt.value"
          >
            {{ opt.label }}
          </button>

          <button
            type="button"
            :disabled="isDownloading"
            class="h-9 px-4 rounded-full bg-surface border border-border-subtle hover:border-brand text-xs font-semibold text-content-strong inline-flex items-center gap-2 transition-colors disabled:opacity-60 cursor-pointer"
            @click="downloadExcel"
          >
            <svg class="w-4 h-4 text-content-strong" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>{{ isDownloading ? 'Exportando...' : 'Exportar Excel' }}</span>
          </button>
        </div>
      </div>

      <!-- Fila de 5 KPIs -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5 sm:gap-6 pt-1">
        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            SOLICITUDES PENDIENTES
          </span>
          <span class="text-2xl sm:text-[26px] font-extrabold text-brand font-heading tabular-nums block leading-tight">
            {{ pendingEstablishmentsCount }} {{ pendingEstablishmentsCount === 1 ? 'local' : 'locales' }}
          </span>
          <span class="text-xs text-content-secondary block">
            {{ pendingLast24hCount }} ingresaron en las últimas 24h
          </span>
        </div>

        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            REPORTES ABIERTOS
          </span>
          <span class="text-2xl sm:text-[26px] font-extrabold text-warning font-heading tabular-nums block leading-tight">
            {{ openReportsCount }} {{ openReportsCount === 1 ? 'caso' : 'casos' }}
          </span>
          <span class="text-xs text-content-secondary block">
            Pendientes o en revisión
          </span>
        </div>

        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            OFERTAS ACTIVAS
          </span>
          <span class="text-2xl sm:text-[26px] font-extrabold text-content-strong font-heading tabular-nums block leading-tight">
            {{ totalActiveOffers }} {{ totalActiveOffers === 1 ? 'pack' : 'packs' }}
          </span>
          <span class="text-xs text-content-secondary block">
            En {{ offerCategories.length }} {{ offerCategories.length === 1 ? 'rubro activo' : 'rubros activos' }}
          </span>
        </div>

        <div class="space-y-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            VENTAS ÚLTIMAS 24H
          </span>
          <span class="text-2xl sm:text-[26px] font-extrabold text-content-strong font-heading tabular-nums block leading-tight">
            {{ salesLast24h.count }} {{ salesLast24h.count === 1 ? 'pedido' : 'pedidos' }}
          </span>
          <span class="text-xs text-content-secondary block">
            {{ salesLast24h.deltaText }}
          </span>
        </div>

        <div class="space-y-1 col-span-2 sm:col-span-1">
          <span class="text-[11px] font-bold uppercase tracking-wider text-content-secondary block">
            COMIDA RESCATADA ({{ selectedPeriod === 'year' ? currentYear : selectedPeriod.toUpperCase() }})
          </span>
          <span class="text-2xl sm:text-[26px] font-extrabold text-success font-heading tabular-nums block leading-tight">
            {{ periodStats.estimatedKg }} kg
          </span>
          <span class="text-xs text-content-secondary block">
            {{ periodStats.co2SavedKg }} kg de CO₂ evitado ({{ periodStats.packsCount }} packs)
          </span>
        </div>
      </div>
    </section>

    <!-- Grid Principal 2 Columnas -->
    <div class="grid grid-cols-1 xl:grid-cols-[1.44fr_1fr] gap-8 lg:gap-10 items-start">
      <!-- Columna Izquierda: Evolución + Desglose Usuarios y Rubros -->
      <div class="space-y-8">
        <AdminActivityBarChart
          :bars="chartBars"
          :subtitle="chartSubtitle"
          :loading="loading"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
          <AdminUsersByStateCard
            :total="userStatsTotal"
            :states="userStatsList"
            :loading="loading"
          />

          <AdminOffersByCategoryCard
            :categories="offerCategories"
            :loading="loading"
          />
        </div>
      </div>

      <!-- Columna Derecha: Colas de Acción Inmediata -->
      <AdminActionQueues
        :pending-establishments="pendingEstablishments"
        :pending-establishments-count="pendingEstablishmentsCount"
        :open-reports="openReports"
        :open-reports-count="openReportsCount"
        :loading="loading"
      />
    </div>
  </div>
</template>
