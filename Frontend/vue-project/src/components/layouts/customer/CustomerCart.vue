<template>
  <div class="customer-cart-container">
    <!-- Sección de Vendedor 1 -->
    <SellerSection
      sellerName="Vendedor 1"
      :products="[
        'Producto 1 aef aef jiaoeifjoiaj efioa iojefoiajeoifjaoiejf',
        'Producto 2',
        'Producto 3',
      ]"
    />

    <!-- Sección de Vendedor 2 -->
    <SellerSection
      sellerName="Vendedor 2"
      :products="['Producto 1', 'Producto 2', 'Producto 3']"
    />
  </div>
</template>

<script setup>
import axios from "axios";
import axiosInstance from "@/lib/axios";
import { onMounted } from "vue";
import SellerSection from "./SellerSection.vue";

const getCart = async () => {
  const response = null;
  const loading = true;

  try {
    response = await axiosInstance.get("/customer-cart");
    console.log(response.data);
  } catch (error) {
    console.log(error);
  }
};

onMounted(() => {
  getCart();
});
</script>

<style scoped>
.customer-cart-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  padding: 1rem;
  max-width: 1200px;
  margin: 0 auto;
  background-color: var(--color-bg);
  color: var(--color-text);
}

/* Responsive design */
@media (max-width: 768px) {
  .customer-cart-container {
    padding: 0.5rem;
  }
}
</style>
