<script setup>
import axiosInstance from "@/lib/axios";

const user = async () => {
  try {
    const response = await axiosInstance.get("/user");
    console.log(response.data);
  } catch (error) {
    console.log(error);
  }
};

const logout = async () => {
  try {
    const response = async () => await axiosInstance.post("/logout");
    console.log(response.data);
  } catch (error) {
    console.log(error);
  }
};

user();
</script>

<template>
  <p>Dashboard</p>
</template>

<style scoped></style>
