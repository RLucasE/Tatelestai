<script setup>
import { reactive, ref } from "vue";
import axiosInstance from "@/lib/axios";
import Button from "@/components/Button.vue";
import { useRouter } from "vue-router";

const router = useRouter();

const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const errors = reactive({
  name: [],
  email: [],
  password: [],
});

const register = async (data) => {
  await axiosInstance.get("/sanctum/csrf-cookie", {
    baseURL: "http://localhost:8000",
  });
  try {
    await axiosInstance.post("/register", data);
    errors.name = [];
    errors.email = [];
    errors.password = [];
    router.push("/dashboard");
  } catch (e) {
    errors.name = e.response.data.errors.name;
    errors.email = e.response.data.errors.email;
    errors.password = e.response.data.errors.password;
  }
};
</script>

<template>
  <form
    @submit.prevent="register(form)"
    class="max-w-sm mx-auto mx-w-auto min-w-2xs p-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 dark:shadow-none"
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
      />

      <template v-if="Array.isArray(errors.name) && errors.name.length">
        <span v-for="error in errors.name" :key="error" class="text-red-500">{{
          error
        }}</span>
      </template>
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
      <template v-if="Array.isArray(errors.email) && errors.email.length">
        <span v-for="error in errors.email" :key="error" class="text-red-500">{{
          error
        }}</span>
      </template>
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
      <template v-if="Array.isArray(errors.password) && errors.password.length">
        <span
          v-for="error in errors.password"
          :key="error"
          class="text-red-500"
        >
          {{ error }}
        </span>
      </template>
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
    <Button buttonText="Registrarse" buttonType="submit"></Button>
  </form>
</template>

<style scoped></style>
