<script setup>
import { reactive, ref } from "vue";
import axiosInstance from "@/lib/axios";

const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const register = async (data) => {
  await axiosInstance.get("/sanctum/csrf-cookie", {
    baseURL: "http://localhost:8000",
  });
  try {
    const response = await axiosInstance.post("/register", data);
    console.log(response.data);
  } catch (error) {
    console.log(error);
  }
};
</script>

<template>
  <form
    @submit.prevent="register(form)"
    class="max-w-sm mx-auto mx-w-auto p-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 dark:shadow-none"
  >
    <div class="mb-5">
      <label
        for="name"
        class="block mb-5 text-sm font-medium text-gray-900 dark:text-white"
        >Name</label
      >
      <input
        type="name"
        id="name"
        v-model="form.name"
        class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
        placeholder="Name"
        required
      />
    </div>
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
    <div class="mb-5">
      <label
        for="password_confirmation"
        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >Password Confirmation</label
      >
      <input
        type="password"
        id="password_confirmation"
        v-model="form.password_confirmation"
        class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
        required
      />
    </div>
    <button
      type="submit"
      class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
    >
      Register new account
    </button>
  </form>
</template>

<style scoped></style>
