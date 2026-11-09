<script setup>
import { RouterLink, RouterView } from "vue-router";
import Logo from "@/components/common/Logo.vue";
</script>

<template>
  <div class="min-h-screen flex flex-col bg-canvas">
    <!-- PublicAuthNavbar (Figma exact: h=70px, bg=surface, border=border-subtle) -->
    <header class="bg-surface border-b border-border-subtle sticky top-0 z-40">
      <nav class="max-w-7xl mx-auto px-6 sm:px-10 lg:px-12">
        <div class="flex items-center justify-between h-[70px]">
          <!-- BrandLogo -->
          <RouterLink to="/" class="flex items-center shrink-0 hover:opacity-90 transition-opacity">
            <Logo variant="imagotipo" :height="28" />
          </RouterLink>

          <!-- AuthNavActions (Standard rounded-[10px] buttons: Registrarse = Principal, Iniciar sesión = Secundario) -->
          <div class="flex items-center gap-3">
            <!-- Botón secundario para Iniciar sesión -->
            <RouterLink
              to="/login"
              class="h-[40px] px-5 rounded-[10px] bg-surface hover:bg-canvas border border-border-subtle text-sm font-semibold text-content flex items-center justify-center transition-colors select-none"
            >
              Iniciar sesión
            </RouterLink>

            <!-- Botón principal para Registrarse -->
            <RouterLink
              to="/register"
              class="h-[40px] px-5 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-sm font-bold shadow-xs flex items-center justify-center transition-colors select-none"
            >
              Registrarse
            </RouterLink>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main canvas with warm cream background -->
    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
      <div class="w-full flex items-center justify-center">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style scoped>
</style>
