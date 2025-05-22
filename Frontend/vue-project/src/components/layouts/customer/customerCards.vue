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
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
  <div class="customer-card-box" v-for="offer in offers">
    <CustomerCard :offer="offer" />
  </div>
</template>

<style scoped>
.customer-card-box {
  background-color: var(--color-focus);
  display: flex;
  justify-content: center;
  align-items: center;
  width: 20rem;

  border-radius: var(--border-radius);
}

@media (min-width: 768px) {
  .customer-card-box {
    height: 5rem;
    margin: 2rem;
  }
}
</style>
