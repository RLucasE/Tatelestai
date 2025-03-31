<script setup>
import { reactive } from "vue";
import axiosInstance from "@/lib/axios";
import Button from "@/components/Button.vue";

const form = reactive({
  email: "",
  password: "",
});

const login = async (data) => {
  await axiosInstance.get("/sanctum/csrf-cookie", {
    baseURL: "http://localhost:8000",
  });
  try {
    const response = await axiosInstance.post("/login", data);
    console.log(response.data);
  } catch (error) {
    console.log(error);
  }
};
</script>

<template>
  <form
    @submit.prevent="login(form)"
    class="max-w-sm mx-auto mx-w-auto min-w-2xs p-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 dark:shadow-none"
  >
    <div class="mb-5">
      <label
        for="email"
        class="block mb-5 text-sm font-medium text-gray-900 dark:text-white"
        >Your email</label
      >
      <input
        type="email"
        id="email"
        v-model="form.email"
        class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
        placeholder="example@gmail.com"
        required
      />
    </div>
    <div class="mb-5">
      <label
        for="password"
        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >Your password</label
      >
      <input
        type="password"
        id="password"
        v-model="form.password"
        class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
        required
      />
    </div>
    <Button button-type="submit" button-text="Login"></Button>
  </form>
</template>

<style scoped></style>
