<script setup>
import axiosInstance from "@/lib/axios";
import CustomerCard from "./CustomerCard.vue";
import { ref } from "vue";

const offers = ref(null);

const getOffers = async () => {
  try {
    const response = await axiosInstance.get("/offers");
    offers.value = response.data;
    return response.data;
  } catch (error) {
    console.error("Error fetching offers:", error);
    return null;
  }
};

getOffers();
</script>
<template>
  <div v-for="offer in offers" class="customer-card-box">
    <CustomerCard :offer="offer" />
  </div>
</template>
