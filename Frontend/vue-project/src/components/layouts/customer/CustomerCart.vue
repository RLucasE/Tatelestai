<script setup>
import SellerSection from "./SellerSection.vue";
import { ref, computed, onMounted } from "vue";
import { RouterLink } from "vue-router";
import axiosInstance from "@/lib/axios";

const establishments = ref([]);
const loading = ref(true);
const error = ref(null);

const totalItemsCount = computed(() => {
  return establishments.value.reduce((total, group) => {
    return total + (Array.isArray(group) ? group.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0) : 0);
  }, 0);
});

const getCart = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axiosInstance.get("/customer-cart");
    if (Array.isArray(response.data)) {
      establishments.value = response.data.filter((group) => Array.isArray(group) && group.length > 0);
    } else {
      establishments.value = [];
    }
  } catch (err) {
    if (err.response?.status === 404) {
      establishments.value = [];
    } else {
      console.error("Error al cargar carrito:", err);
      error.value = "No se pudieron cargar las bolsas de tu carrito. Intenta nuevamente.";
    }
  } finally {
    loading.value = false;
  }
};

const handleRemoveOffer = async (offerId) => {
  try {
    await axiosInstance.delete(`/customer-cart/${offerId}`);
    await getCart();
  } catch (err) {
    console.error("Error removing offer:", err);
  }
};

function debounce(func, wait) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      func.apply(context, args);
    }, wait);
  };
}

const updateOfferQuantity = (offer, quantity) => {
  offer.quantity = quantity;
};

const updateQuantity = async (offer, quantity) => {
  try {
    await axiosInstance.put(`/customer-cart/${offer.offer_id}`, { quantity });
    await getCart();
  } catch (err) {
    console.error("Error updating quantity:", err);
  }
};

const debounceUpdateQuantity = debounce(updateQuantity, 400);

const handleQuantityChange = async (offer, quantity) => {
  const oldQuantity = offer.quantity;
  try {
    updateOfferQuantity(offer, quantity);
    debounceUpdateQuantity(offer, quantity);
  } catch (err) {
    console.error("Error updating quantity:", err);
    updateOfferQuantity(offer, oldQuantity);
  }
};

const removeEstablishmentOffers = async (establishmentId) => {
  try {
    await axiosInstance.delete(`/customer-cart/establishment/${establishmentId}`);
    await getCart();
  } catch (err) {
    console.error("Error removing establishment offers:", err);
  }
};

onMounted(() => {
  getCart();
});
</script>

<template>
  <div class="cart-page-wrapper">
    <div class="cart-container max-w-4xl mx-auto">
      <!-- Navegación y Cabecera -->
      <header class="cart-header-section mb-6">
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-[#A5A8C2] hover:text-white transition-colors mb-3 group"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Volver al Catálogo</span>
        </RouterLink>

        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#3D3450]/60 pb-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
              <span>Mi Carrito</span>
              <span
                v-if="totalItemsCount > 0"
                class="bg-[#7C3AED] text-white text-xs font-bold px-2.5 py-0.5 rounded-full"
              >
                {{ totalItemsCount }} {{ totalItemsCount === 1 ? 'pack' : 'packs' }}
              </span>
            </h1>
            <p class="text-xs sm:text-sm text-[#A5A8C2] mt-1">
              Revisa tus reservas y coordina el retiro de tus bolsas sorpresa
            </p>
          </div>

          <RouterLink
            v-if="establishments.length > 0"
            to="/customer/offers"
            class="text-xs font-bold text-[#A78BFA] hover:text-[#C4B5FD] transition-colors flex items-center gap-1.5 bg-[#7C3AED]/15 border border-[#7C3AED]/30 px-3 py-1.5 rounded-xl hover:bg-[#7C3AED]/25"
          >
            <span>+ Agregar más bolsas</span>
          </RouterLink>
        </div>
      </header>

      <!-- Estado de Carga (Skeletons armónicos) -->
      <div v-if="loading" class="space-y-6 py-4">
        <div
          v-for="i in 2"
          :key="i"
          class="bg-[#221C33] border border-[#3D3450] rounded-2xl p-5 animate-pulse space-y-4"
        >
          <!-- Cabecera skeleton -->
          <div class="flex items-center justify-between pb-3 border-b border-[#3D3450]/60">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-[#3D3450]"></div>
              <div class="space-y-1.5">
                <div class="h-4 bg-[#3D3450] rounded w-36"></div>
                <div class="h-3 bg-[#3D3450]/60 rounded w-24"></div>
              </div>
            </div>
            <div class="h-4 bg-[#3D3450]/40 rounded w-20"></div>
          </div>

          <!-- Item skeleton -->
          <div class="bg-[#2D2438] border border-[#3D3450]/60 rounded-xl p-4 space-y-3">
            <div class="h-4 bg-[#3D3450] rounded w-1/2"></div>
            <div class="h-3 bg-[#3D3450]/60 rounded w-3/4"></div>
            <div class="flex items-center justify-between pt-2">
              <div class="h-8 bg-[#3D3450]/80 rounded-xl w-24"></div>
              <div class="h-6 bg-[#3D3450] rounded w-20"></div>
            </div>
          </div>

          <!-- Footer skeleton -->
          <div class="flex items-center justify-between pt-3 border-t border-[#3D3450]/60">
            <div class="h-6 bg-[#3D3450] rounded w-28"></div>
            <div class="h-10 bg-[#7C3AED]/40 rounded-xl w-44"></div>
          </div>
        </div>
      </div>

      <!-- Estado de Error -->
      <div
        v-else-if="error"
        class="bg-[#2D2438] border border-[#EF4444]/40 rounded-2xl p-6 text-center text-white my-6 max-w-lg mx-auto space-y-3"
      >
        <div class="w-10 h-10 mx-auto text-[#EF4444] flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <p class="text-sm font-semibold">{{ error }}</p>
        <button
          type="button"
          class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-5 py-2.5 rounded-xl transition cursor-pointer"
          @click="getCart"
        >
          Reintentar carga
        </button>
      </div>

      <!-- Estado Vacío -->
      <div
        v-else-if="establishments.length === 0"
        class="bg-[#2D2438] border border-[#3D3450] rounded-2xl p-8 sm:p-12 text-center my-6 max-w-lg mx-auto shadow-xl space-y-4"
      >
        <div class="w-16 h-16 mx-auto rounded-2xl bg-[#7C3AED]/15 border border-[#7C3AED]/30 text-[#A78BFA] flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <div class="space-y-1">
          <h2 class="text-lg sm:text-xl font-bold text-white">Tu carrito está vacío</h2>
          <p class="text-xs sm:text-sm text-[#A5A8C2] leading-relaxed max-w-sm mx-auto">
            Todavía no agregaste ninguna bolsa sorpresa. ¡Explora los comercios de tu zona y rescata comida exquisita a precio reducido!
          </p>
        </div>
        <div class="pt-2">
          <RouterLink
            to="/customer/offers"
            class="inline-flex items-center justify-center gap-2 bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs sm:text-sm font-bold px-6 py-3 rounded-xl shadow-lg shadow-[#7C3AED]/25 transition-all duration-200 active:scale-95 cursor-pointer"
          >
            <span>Explorar Ofertas</span>
            <span class="text-base leading-none">→</span>
          </RouterLink>
        </div>
      </div>

      <!-- Grupos por Establecimiento -->
      <div v-else class="space-y-6">
        <SellerSection
          v-for="(offers, idx) in establishments"
          :key="offers[0]?.establishment_id ?? idx"
          :offers="offers"
          @offerRemoved="handleRemoveOffer"
          @quantityUpdated="handleQuantityChange"
          @removeAllOffers="removeEstablishmentOffers"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.cart-page-wrapper {
  min-height: calc(100vh - 4rem);
  background-color: #1A1625;
  color: #E8EAF6;
  padding: 1.5rem 1rem 3rem 1rem;
  width: 100%;
}
</style>
