<script setup>
import { RouterLink, RouterView } from "vue-router";
import Logo from "@/components/common/Logo.vue";
</script>

<template class="p-0">
  <header>
    <div class="bg-[var(--color-background)]/80 backdrop-blur border-b border-[var(--color-border)]">
      <nav class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between h-14">
          <RouterLink to="/" class="logo-link">
            <Logo />
          </RouterLink>
          <div class="flex items-center gap-2">
            <RouterLink
              to="/register"
              class="px-3 py-1.5 rounded-lg text-sm text-[var(--color-heading)] hover:bg-[var(--color-background-soft)]"
              >Registrarse</RouterLink
            >
            <RouterLink
              to="/login"
              class="px-3 py-1.5 rounded-lg text-sm text-[var(--color-text)] bg-[var(--color-primary)] hover:bg-[var(--color-secondary)]"
              >Iniciar sesión</RouterLink
            >
          </div>
        </div>
      </nav>
    </div>
  </header>

  <main class="min-h-[calc(100vh-3.5rem)] flex items-center justify-center bg-[var(--color-bg)]">
    <div class="w-full">
      <RouterView />
    </div>
  </main>
</template>

<style scoped>
.logo-link {
  text-decoration: none;
}
</style>
