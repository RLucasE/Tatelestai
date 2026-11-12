<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import OnboardingTopNavbar from "./OnboardingTopNavbar.vue";

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
  await authStore.refreshUser();
  if (authStore.active() || !authStore.waitingConfirmation()) {
    await router.replace(authStore.getDefaultRoute());
  }
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-canvas text-content">
    <!-- Navbar with single Cerrar sesión button at the top -->
    <OnboardingTopNavbar badge-text="VERIFICACIÓN EN CURSO" />

    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
      <!-- WaitingConfirmationCard (Figma exact: max-w-[640px], rounded-[22px], border-brand-light, bg-surface) -->
      <section
        class="w-full max-w-[640px] bg-surface rounded-[22px] border border-brand-light shadow-sm p-6 sm:p-11 space-y-6"
      >
        <!-- ReviewClockBadge -->
        <div class="w-14 h-14 rounded-[18px] bg-brand-light/15 border border-brand-light/40 flex items-center justify-center">
          <svg class="w-7 h-7 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <!-- WaitingTextGroup -->
        <header class="space-y-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-brand">
            SOLICITUD EN REVISIÓN • VERIFICACIÓN DE COMERCIO
          </span>
          <h1 class="text-2xl sm:text-[28px] font-extrabold text-content tracking-tight leading-tight">
            Estamos revisando tu solicitud
          </h1>
          <p class="text-sm text-content-secondary pt-0.5 leading-relaxed">
            Estamos verificando los datos y la documentación de tu establecimiento para habilitar tu cuenta en Tatelestai.
          </p>
        </header>

        <!-- ReviewTimelineBox -->
        <div class="p-5 rounded-[14px] bg-canvas border border-border-subtle space-y-3">
          <!-- Stage 1 -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs sm:text-[13px] font-semibold text-content">
              ✓ 1. Datos y ubicación del comercio enviados
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-success/15 text-success text-[11px] font-bold tracking-wide shrink-0">
              RECIBIDO
            </span>
          </div>

          <!-- Stage 2 -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs sm:text-[13px] font-bold text-content">
              ● 2. Verificación documental por administración
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-brand/15 text-brand text-[11px] font-bold tracking-wide shrink-0">
              EN REVISIÓN
            </span>
          </div>

          <!-- Stage 3 -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs sm:text-[13px] font-normal text-content-muted">
              ○ 3. Habilitación para publicar bolsas sorpresa
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-content-muted/15 text-content-muted text-[11px] font-semibold tracking-wide shrink-0">
              PENDIENTE
            </span>
          </div>
        </div>

        <!-- EmailNotificationBox (Aviso informativo de notificación por email) -->
        <div class="p-4 sm:p-5 rounded-[14px] bg-surface-elevated border border-brand-light/35 flex items-start gap-3.5">
          <div class="w-9 h-9 rounded-[10px] bg-brand/12 text-brand flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div class="space-y-1">
            <h2 class="text-[13.5px] font-bold text-content">
              Te notificaremos por correo electrónico
            </h2>
            <p class="text-[12.5px] text-content-secondary leading-relaxed">
              Te enviaremos un email a tu casilla cuando el comercio sea verificado y habilitado para publicar ofertas, o si la administración requiere corregir algún documento. Podés cerrar sesión con tranquilidad; no hace falta que permanezcas en esta pantalla.
            </p>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
</style>
