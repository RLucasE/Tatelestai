<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import axiosInstance from "@/lib/axios";
import OnboardingTopNavbar from "./OnboardingTopNavbar.vue";

const router = useRouter();
const authStore = useAuthStore();
const canceling = ref(false);
const checking = ref(false);
const verificationSummary = ref(null);

const fetchVerificationSummary = async () => {
  try {
    const res = await axiosInstance.get("/seller/verification-summary");
    verificationSummary.value = res.data?.data ?? res.data ?? null;
  } catch {
    // Fallback a las observaciones estáticas si el endpoint no devuelve datos
  }
};

const observationItems = computed(() => {
  const items = [];
  if (verificationSummary.value?.verification_notes) {
    items.push(verificationSummary.value.verification_notes);
  }
  const rejectedFiles = (verificationSummary.value?.files || []).filter(
    (f) => f.status === "rejected" && f.rejection_reason
  );
  rejectedFiles.forEach((f) => {
    items.push(f.rejection_reason);
  });
  if (items.length === 0) {
    return [
      "El archivo de Habilitación Municipal adjunto (habilitacion_comercial_2026.pdf) se encuentra incompleto o ilegible.",
      "Podés reiniciar tu solicitud para volver a adjuntar la documentación corregida.",
    ];
  }
  return items;
});

const handleCheckStatus = async () => {
  checking.value = true;
  try {
    await authStore.refreshUser();
    if (!authStore.deniedConfirmation()) {
      await router.replace(authStore.getDefaultRoute());
    } else {
      await fetchVerificationSummary();
    }
  } catch (error) {
    console.error("Error checking status:", error);
  } finally {
    checking.value = false;
  }
};

const handleCancelRegistration = async () => {
  if (!confirm("¿Deseás reiniciar tu solicitud y volver a la selección de rol?")) {
    return;
  }

  canceling.value = true;
  try {
    await authStore.cancelSellerRegistration();
    await router.replace(authStore.getDefaultRoute());
  } catch (error) {
    console.error("Error canceling registration:", error);
  } finally {
    canceling.value = false;
  }
};

const handleLogout = async () => {
  try {
    await authStore.logout();
    await router.replace({ name: "login" });
  } catch (error) {
    console.error("Error logging out:", error);
  }
};

onMounted(() => {
  fetchVerificationSummary();
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-canvas text-content">
    <OnboardingTopNavbar badge-text="SOLICITUD OBSERVADA" />

    <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
      <!-- DeniedConfirmationCard (Figma exact: max-w-[640px], rounded-[22px], border-danger, bg-surface) -->
      <section
        class="w-full max-w-[640px] bg-surface rounded-[22px] border border-danger shadow-sm p-6 sm:p-11 space-y-6"
      >
        <!-- WarningBadge -->
        <div class="w-14 h-14 rounded-[18px] bg-danger/15 border border-danger/40 flex items-center justify-center text-danger">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>

        <!-- HeaderSection -->
        <header class="space-y-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-danger">
            SOLICITUD NO APROBADA • VERIFICACIÓN DE COMERCIO
          </span>
          <h1 class="text-2xl sm:text-[28px] font-extrabold text-content tracking-tight leading-tight">
            Establecimiento Rechazado
          </h1>
          <p class="text-sm text-content-secondary pt-0.5 leading-relaxed">
            Tu solicitud de registro para el establecimiento no ha sido aprobada. Revisá las observaciones detalladas a continuación para subsanar la documentación.
          </p>
        </header>

        <!-- ObservationDetailsSection -->
        <div class="p-5 rounded-[14px] bg-danger/5 border border-danger/25 space-y-2">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-danger">
            MOTIVO DE LA OBSERVACIÓN
          </span>
          <p
            v-for="(obs, idx) in observationItems"
            :key="idx"
            class="text-[13px] font-medium text-content"
          >
            • {{ obs }}
          </p>
        </div>

        <!-- SupportContactSection -->
        <div class="space-y-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-content-secondary">
            ¿NECESITÁS AYUDA O TENÉS CONSULTAS?
          </span>
          <p class="text-[13px] text-content">
            Para más información o asistencia con tu registro, comunicate con soporte en
            <a
              href="mailto:soporte@tatelestai.com"
              class="font-bold text-brand underline hover:text-brand-hover ml-1"
            >
              soporte@tatelestai.com
            </a>
          </p>
        </div>

        <!-- DeniedActionsRow (Figma standard buttons: corner radius 10px, non-pill) -->
        <div class="pt-2 border-t border-border-subtle flex items-center justify-between flex-wrap gap-3">
          <button
            type="button"
            :disabled="canceling"
            class="h-[46px] inline-flex items-center justify-center gap-2 px-6 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-sm font-bold transition-colors shadow-xs disabled:opacity-60 cursor-pointer select-none"
            @click="handleCancelRegistration"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ canceling ? "Cancelando..." : "Reiniciar registro" }}</span>
          </button>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              :disabled="checking"
              class="h-[46px] px-5 rounded-[10px] bg-canvas hover:bg-surface-sunken border border-border-subtle text-sm font-semibold text-content transition-colors cursor-pointer select-none"
              @click="handleCheckStatus"
            >
              {{ checking ? "Comprobando..." : "Comprobar estado" }}
            </button>
            <button
              type="button"
              class="h-[46px] px-5 rounded-[10px] bg-canvas hover:bg-surface-sunken border border-border-subtle text-sm font-semibold text-content transition-colors cursor-pointer select-none"
              @click="handleLogout"
            >
              Cerrar sesión
            </button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
</style>
