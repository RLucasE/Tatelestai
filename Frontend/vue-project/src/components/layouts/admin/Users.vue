<script setup>
import axiosInstance from "@/lib/axios.js";
import {onMounted, ref} from "vue";

const users = ref([]);
const getUsers = async () => {
  try {
    const response = await axiosInstance('/users');
    users.value = response.data;
  } catch (error) {
    console.error('Error fetching users:', error);
    return [];
  }
};

onMounted(getUsers);
</script>

<template>
hola
</template>

<style scoped>

</style>