<script setup>
import SellerSection from "./SellerSection.vue";
import { ref, onMounted } from "vue";
import axiosInstance from "@/lib/axios";

const establishments = ref([]);

const getCart = async () => {
  try {
    const response = await axiosInstance.get("/customer-cart");
    establishments.value = response.data;
    console.log(establishments.value);
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  getCart();
});
</script>

<template>
  <div class="customer-cart-container">
    <SellerSection v-for="offers in establishments" :offers="offers" />
  </div>
</template>
