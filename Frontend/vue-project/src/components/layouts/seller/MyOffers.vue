<script setup>
import { ref, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";
import OfferSellerCard from "./OfferSellerCard.vue";
import { useNotificationStore } from "@/stores/notifications";

const router = useRouter();
const notificationStore = useNotificationStore();

const offers = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchOffers = async (silent = false) => {
  const isSilent = silent === true;
  try {
    if (!isSilent) loading.value = true;
    const response = await axiosInstance.get("/my-packs");
    offers.value = response.data?.data || response.data || [];
    error.value = null;
  } catch (err) {
    console.error("Error fetching offers:", err);
    error.value = "Error al cargar tus ofertas";
    offers.value = [];
  } finally {
    if (!isSilent) loading.value = false;
  }
};

const handleOfferClick = (offer) => {
  router.push({ name: "edit-offer", params: { id: offer.id } });
};

const goToCreateOffer = () => {
  router.push({ name: "pack-templates" });
};

watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (event) {
      fetchOffers(true);
    }
  }
);

onMounted(() => {
  fetchOffers();
});
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 space-y-6 text-content">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-[22px] font-extrabold text-content tracking-tight">
          Mis Ofertas de Packs
        </h1>
        <p class="text-[13.5px] text-content-secondary">
          Gestioná y supervisá tus bolsas sorpresa activas y pasadas.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13px] font-bold transition cursor-pointer self-start sm:self-auto"
        @click="goToCreateOffer"
      >
        <span>+ Gestionar Packs y Plantillas</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-16 text-center space-y-3">
      <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-content-secondary">Cargando tus ofertas...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="p-6 rounded-[18px] bg-surface border border-danger text-center space-y-3"
    >
      <p class="text-sm font-semibold text-danger">{{ error }}</p>
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="fetchOffers"
      >
        Reintentar
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="offers.length === 0"
      class="p-12 rounded-[22px] bg-surface border border-dashed border-border-subtle text-center space-y-3"
    >
      <div class="text-3xl">🛍️</div>
      <h3 class="text-base font-bold text-content">No tenés ofertas creadas</h3>
      <p class="text-xs text-content-secondary max-w-md mx-auto">
        Comenzá publicando tu primera bolsa sorpresa desde tus Plantillas Maestras para rescatar excedentes.
      </p>
      <button
        type="button"
        class="px-5 py-2.5 rounded-[10px] bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="goToCreateOffer"
      >
        + Ir a Packs y Plantillas
      </button>
    </div>

    <!-- Offers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <OfferSellerCard
        v-for="offer in offers"
        :key="offer.id"
        :offer="offer"
        class="cursor-pointer"
        @click="handleOfferClick(offer)"
      />
    </div>
  </div>
</template>
