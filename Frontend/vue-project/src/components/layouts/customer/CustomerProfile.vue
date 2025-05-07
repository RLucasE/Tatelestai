<script setup>
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

const getUser = () => {
  return authStore.getUser();
};

const user = getUser();
</script>
<template>
  <div>{{ user }}</div>
</template>
