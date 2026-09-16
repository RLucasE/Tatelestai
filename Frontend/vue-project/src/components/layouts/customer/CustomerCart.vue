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
      error.value = "No se pudieron cargar los productos de tu carrito. Intenta nuevamente.";
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
    alert("Hubo un error al actualizar la cantidad");
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
    <div class="cart-container">
      <!-- Navegación y Cabecera -->
      <header class="cart-header-section mb-6">
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center gap-2 text-sm text-[#A5A8C2] hover:text-white transition-colors mb-3 group"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Volver a Ofertas</span>
        </RouterLink>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#3D3450]/60 pb-4">
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
            class="text-xs font-semibold text-[#A78BFA] hover:text-[#C4B5FD] transition-colors"
          >
            + Agregar más bolsas
          </RouterLink>
        </div>
      </header>

      <!-- Estado de Carga -->
      <div v-if="loading" class="space-y-4 py-8">
        <div
          v-for="i in 2"
          :key="i"
          class="bg-[#2D2438] border border-[#4A4058]/50 rounded-2xl p-6 animate-pulse space-y-4"
        >
          <div class="h-6 bg-[#3D3450] rounded-md w-1/3"></div>
          <div class="h-20 bg-[#1F1A2C] rounded-xl"></div>
          <div class="h-10 bg-[#3D3450] rounded-xl w-1/4 ml-auto"></div>
        </div>
      </div>

      <!-- Estado de Error -->
      <div
        v-else-if="error"
        class="bg-[#EF4444]/10 border border-[#EF4444]/30 rounded-2xl p-6 text-center text-white my-6"
      >
        <p class="text-sm font-medium mb-3">{{ error }}</p>
        <button
          type="button"
          class="bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors"
          @click="getCart"
        >
          Reintentar
        </button>
      </div>

      <!-- Estado Vacío -->
      <div
        v-else-if="establishments.length === 0"
        class="bg-[#2D2438] border border-[#4A4058] rounded-2xl p-10 text-center my-6 max-w-lg mx-auto shadow-lg"
      >
        <div class="w-16 h-16 mx-auto mb-4 text-[#7C3AED]/70 flex items-center justify-center">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 6h18" stroke-width="1.8"/>
            <path d="M16 10a4 4 0 0 1-8 0" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-white mb-2">Tu carrito está vacío</h2>
        <p class="text-sm text-[#A5A8C2] mb-6 leading-relaxed">
          Todavía no agregaste ninguna bolsa sorpresa gastronómica. ¡Explora los comercios de tu zona y rescata comida deliciosa!
        </p>
        <RouterLink
          to="/customer/offers"
          class="inline-flex items-center justify-center gap-2 bg-[#7C3AED] hover:bg-[#6D28D9] text-white text-sm font-bold px-6 py-3 rounded-xl shadow-lg shadow-[#7C3AED]/25 transition-all active:scale-95"
        >
          <span>Explorar Ofertas</span>
          <span class="text-base leading-none">→</span>
        </RouterLink>
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

.cart-container {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
