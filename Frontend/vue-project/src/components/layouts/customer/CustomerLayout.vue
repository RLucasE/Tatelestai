<script setup>
import axiosInstance from "@/lib/axios";
import { ref } from "vue";
import CustomerCard from "./CustomerCard.vue";
import { useAuthStore } from "@/stores/auth";
</script>

<template>
  <nav class="flex justify-between items-center bg-gray-500 pl-6 pr-6 h-16">
    <div>
      <RouterLink
        class="py-2 px-4 leading-normal text-white no-underline hover:bg-gray-700"
        to="/customer"
        >Logotipo</RouterLink
      >
    </div>
    <div>
      <RouterLink
        class="py-2 px-4 leading-normal text-white no-underline hover:bg-gray-700"
        to="/customer/offers"
        >Ofertas</RouterLink
      >

      <RouterLink
        class="py-2 px-4 leading-normal text-white no-underline hover:bg-gray-700"
        to="/customer/profile"
        >Perfil</RouterLink
      >
    </div>
  </nav>
  <main class="customer-cards-box bg-white">
    <RouterView></RouterView>
  </main>
</template>

<style scoped>
.customer-cards-box {
  display: flex;
  flex-wrap: wrap;
  background-color: slategrey;
  justify-content: center;
  gap: 16px;
  padding: 16px;
  height: 100vh;
}
</style>
