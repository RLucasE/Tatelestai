<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Encabezado de la Página -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/[0.08]">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-bold uppercase tracking-wider text-[#A78BFA]">Packs en Curso</span>
          <span
            v-if="!loading && activePurchasesCount > 0"
            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#7C3AED]/20 border border-[#7C3AED]/35 text-[#C4B5FD]"
          >
            {{ activePurchasesCount }} {{ activePurchasesCount === 1 ? 'activa' : 'activas' }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
          Mis Reservas Activas
        </h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2] mt-1">
          Monitorea tu horario de retiro, consulta tu código de ticket y gestiona tus pedidos.
        </p>
      </div>

      <!-- Acciones de Cabecera -->
      <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-[#2D2438] hover:bg-[#3D3450] text-[#E8EAF6] border border-white/[0.08] transition-all hover:scale-[1.02] active:scale-95 shadow-sm"
          :disabled="loading"
          @click="getPurchases"
        >
          <svg
            class="w-3.5 h-3.5 text-[#A78BFA]"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>{{ loading ? 'Actualizando...' : 'Actualizar' }}</span>
        </button>

        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-md transition-all hover:scale-[1.02] active:scale-95"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Explorar Packs</span>
        </RouterLink>
      </div>
    </header>

    <!-- Tip / Recordatorio de Retiro -->
    <div
      v-if="!loading && purchases.length > 0"
      class="flex items-center gap-3 p-3.5 rounded-xl bg-[#7C3AED]/10 border border-[#7C3AED]/20 text-xs text-[#C4B5FD]"
    >
      <svg class="w-5 h-5 text-[#A78BFA] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <span>
        <strong>Recordatorio:</strong> Al llegar al comercio, muestra tu código de retiro al personal para recibir tu pack sorpresa.
      </span>
    </div>

    <!-- Skeletons durante carga -->
    <div v-if="loading" class="space-y-4">
      <div
        v-for="n in 2"
        :key="n"
        class="bg-[#2D2438] border border-white/[0.06] rounded-2xl p-6 space-y-4 animate-pulse"
      >
        <div class="flex items-center justify-between pb-3 border-b border-[#3D3450]/40">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/[0.05]"></div>
            <div class="space-y-1.5">
              <div class="w-32 h-4 rounded bg-white/[0.08]"></div>
              <div class="w-48 h-3 rounded bg-white/[0.04]"></div>
            </div>
          </div>
          <div class="w-24 h-6 rounded-full bg-white/[0.06]"></div>
        </div>
        <div class="w-full h-8 rounded-xl bg-white/[0.03]"></div>
        <div class="space-y-2">
          <div class="w-full h-12 rounded-xl bg-white/[0.04]"></div>
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-[#3D3450]/40">
          <div class="w-24 h-6 rounded bg-white/[0.06]"></div>
          <div class="w-36 h-8 rounded-xl bg-white/[0.06]"></div>
        </div>
      </div>
    </div>

    <!-- Estado de Error -->
    <div
      v-else-if="error"
      class="bg-[#2D2438] border border-[#EF4444]/30 rounded-2xl p-8 text-center space-y-4"
    >
      <div class="w-12 h-12 rounded-2xl bg-[#EF4444]/15 border border-[#EF4444]/30 flex items-center justify-center mx-auto text-[#EF4444]">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <div>
        <h3 class="text-base font-bold text-white">No se pudieron cargar tus reservas</h3>
        <p class="text-xs text-[#A5A8C2] mt-1">{{ error }}</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] transition-all"
        @click="getPurchases"
      >
        Reintentar
      </button>
    </div>

    <!-- Estado Vacío -->
    <div
      v-else-if="purchases.length === 0"
      class="bg-[#2D2438]/40 border-2 border-dashed border-[#3D3450] rounded-2xl p-10 sm:p-14 text-center space-y-4"
    >
      <div class="w-16 h-16 rounded-2xl bg-[#7C3AED]/15 border border-[#7C3AED]/30 flex items-center justify-center mx-auto text-[#A78BFA]">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.8"
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          />
        </svg>
      </div>
      <div class="max-w-sm mx-auto space-y-1">
        <h3 class="text-lg font-bold text-white">No tienes reservas activas</h3>
        <p class="text-xs sm:text-sm text-[#A5A8C2] leading-relaxed">
          Cuando rescates un pack de excedentes gastronómicos, tu código de retiro y horario límite aparecerán aquí.
        </p>
      </div>
      <div class="pt-2">
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] shadow-lg shadow-[#7C3AED]/25 transition-all hover:scale-[1.02] active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span>Explorar packs cercanos</span>
        </RouterLink>
      </div>
    </div>

    <!-- Lista de Reservas Activas -->
    <div v-else class="space-y-4">
      <PurchaseCard
        v-for="purchase in purchases"
        :key="purchase.id"
        :purchase="purchase"
        @cancelled="handlePurchaseCancelled"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import axiosInstance from '@/lib/axios';
import PurchaseCard from './PurchaseCard.vue';

const purchases = ref([]);
const loading = ref(true);
const error = ref(null);

const activePurchasesCount = computed(() => {
  return purchases.value.filter(
    (p) => p.state !== 'cancelled' && p.state !== 'picked_up' && !p.is_picked_up
  ).length;
});

const getPurchases = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axiosInstance.get('/customer/pack-reservations');
    purchases.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Error fetching purchases:', err);
    error.value = 'No pudimos conectar con el servidor para actualizar tus reservas.';
  } finally {
    loading.value = false;
  }
};

const handlePurchaseCancelled = (purchaseId) => {
  // Actualizar el estado local para reflejar la cancelación sin recargar toda la lista
  const item = purchases.value.find((p) => p.id === purchaseId);
  if (item) {
    item.state = 'cancelled';
  }
};

onMounted(() => {
  getPurchases();
});
</script>
