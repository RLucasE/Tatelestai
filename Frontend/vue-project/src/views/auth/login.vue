<script setup>
import { reactive } from "vue";
import Button from "@/components/Button.vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  email: "",
  password: "",
});

const login = async (data) => {
  try {
    await authStore.login(data);
    switch (true) {
      case authStore.isCustomer():
        try {
          router.push({ name: "customer" });
        } catch (error) {
          console.log(error);
        }

        break;
      case authStore.isAdmin():
        router.push("/admin");
        break;
      case authStore.isSeller():
        router.push("/seller");
        break;
      default:
        console.error("Unknown user role");
    }
  } catch (error) {
    console.error("Login failed:", error);
    throw error;
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
        autocomplete="email"
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
        autocomplete="current-password"
        class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
        required
      />
    </div>
    <Button button-type="submit" button-text="Login"></Button>
  </form>
</template>

<style scoped></style>
