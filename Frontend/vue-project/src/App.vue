<script setup>
import { RouterLink, RouterView } from "vue-router";
</script>

<template class="p-0">
  <header>
    <div class="bg-state-100 dark:bg-slate-900">
      <nav class="container mx-auto flex items-center justify-between p-4">
        <RouterLink to="/">Home</RouterLink>
        <div class="flex space-x-2">
          <RouterLink to="/register">Register</RouterLink>
          <RouterLink to="/login">Login</RouterLink>
        </div>
      </nav>
    </div>
  </header>
  <main
    class="max-w-screen mx-auto bg-state-100 dark:bg-slate-900 min-h-screen"
  >
    <div class="mb-6 justify-center flex">
      <RouterView></RouterView>
    </div>
  </main>
</template>
