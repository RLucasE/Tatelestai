<script setup>
import axiosInstance from "@/lib/axios";

async function getRoleSelectorData() {
  try {
    const response = await axiosInstance.get("/test");
    console.log("Role selector data:", response.data);
  } catch (error) {
    console.error("Error fetching role selector data:", error);
  }
}

getRoleSelectorData();
</script>

<template>
  <main class="customer-cards-box">
    <div class="choice-container"></div>
  </main>
</template>

<style scoped>
.customer-cards-box {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: var(--color-bg);
  height: 100vh;
}

.choice-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 50px;
  width: 300px;
  background-color: var(--color-darkest);
}
</style>
