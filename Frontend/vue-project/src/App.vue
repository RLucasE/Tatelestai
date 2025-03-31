<script setup>
import { RouterLink, RouterView } from "vue-router";
</script>

<template class="p-0">
  <header class="">
    <div
      class="bg-state-100 bg-white shadow-lg dark:bg-gray-800 dark:shadow-none flex justify-center items-center"
    >
      <nav class="container">
        <div class="flex items-center justify-between">
          <RouterLink to="/" class="text-zinc-200 m-4">Home</RouterLink>
          <div class="flex space-x-2">
            <RouterLink to="/register" class="text-zinc-200 m-4"
              >Register</RouterLink
            >
            <RouterLink to="/login" class="text-zinc-200 m-4">Login</RouterLink>
          </div>
        </div>
      </nav>
    </div>
  </header>
  <main
    class="max-w-screen flex items-center justify-center bg-state-100 h-screen dark:bg-slate-900"
  >
    <div class="max-w-full">
      <RouterView></RouterView>
    </div>
  </main>
</template>
