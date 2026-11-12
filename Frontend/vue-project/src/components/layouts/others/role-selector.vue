<script setup>
import axiosInstance from "@/lib/axios";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import OnboardingTopNavbar from "./OnboardingTopNavbar.vue";
import RoleOptionCard from "./RoleOptionCard.vue";

const router = useRouter();
const role = ref("seller");
const authStore = useAuthStore();
const loading = ref(false);
const errorMessage = ref("");

const selectRole = (role_selected) => {
  role.value = role_selected;
  errorMessage.value = "";
};

const hadleElection = async () => {
  if (!role.value || role.value === "unknown_role") {
    errorMessage.value = "Por favor, seleccioná un rol.";
    return;
  }

  loading.value = true;
  errorMessage.value = "";
  try {
    await axiosInstance.post("/select-role", {
      role: role.value,
    });
    await authStore.refreshUser();
    await router.replace(authStore.getDefaultRoute());
  } catch (error) {
    console.error("Error selecting role:", error);
    errorMessage.value =
      "Hubo un error al seleccionar el rol. Intentalo de nuevo.";
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  try {
    await authStore.logout();
    router.push("/login");
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  }
};
</script>

<template>
  <div class="min-h-screen flex flex-col bg-canvas text-content">
    <!-- Navbar with badge and rounded-full logout -->
    <OnboardingTopNavbar badge-text="PASO 1 DE 2 • CONFIGURACIÓN" />

    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
      <!-- RoleSelectorCard (Figma exact: width 896px, height ~425px, rounded-[22px], border-border-subtle, bg-surface) -->
      <section
        class="w-full max-w-[896px] bg-surface rounded-[22px] border border-border-subtle shadow-sm p-6 sm:p-10 lg:py-10 lg:px-12 space-y-6"
      >
        <!-- RoleHeader -->
        <header class="space-y-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-brand">
            PASO 1 DE 2 • PERFIL DE USUARIO
          </span>
          <h1 class="text-2xl sm:text-[26px] font-extrabold text-content tracking-tight leading-tight">
            ¿Qué quieres hacer en la aplicación?
          </h1>
          <p class="text-sm text-content-secondary pt-0.5">
            Elegí cómo deseás usar la plataforma. Podrás cambiarlo más adelante si es necesario.
          </p>
        </header>

        <!-- RoleCardsGrid (Figma: Comprar vs Vender en 2 columnas) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <RoleOptionCard
            role="customer"
            title="Comprar"
            description="Comprá bolsas sorpresa y rescatá alimentos de comercios cercanos con grandes descuentos."
            :selected="role === 'customer'"
            @select="selectRole('customer')"
          />

          <RoleOptionCard
            role="seller"
            title="Vender"
            description="Publicá tus excedentes gastronómicos, evitá el desperdicio y gestioná tus ventas y retiros."
            :selected="role === 'seller'"
            @select="selectRole('seller')"
          />
        </div>

        <!-- Error message if any -->
        <div
          v-if="errorMessage"
          class="p-3.5 rounded-xl bg-danger/10 border border-danger/30 text-danger text-xs font-semibold flex items-center gap-2"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- RoleFooterActions (Standard project buttons: corner radius 10px, NOT rounded-full) -->
        <div class="pt-4 border-t border-border-subtle flex items-center justify-between gap-4">
          <button
            type="button"
            class="h-[46px] px-6 rounded-[10px] bg-canvas hover:bg-surface-sunken border border-border-subtle text-sm font-semibold text-content transition-colors cursor-pointer select-none"
            @click="handleLogout"
          >
            Cancelar
          </button>

          <button
            type="button"
            :disabled="loading"
            class="h-[46px] inline-flex items-center justify-center gap-2 px-7 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-sm font-bold transition-colors shadow-xs disabled:opacity-60 cursor-pointer select-none"
            @click="hadleElection"
          >
            <svg
              v-if="loading"
              class="animate-spin h-4 w-4 text-surface"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span>{{ loading ? "Confirmando..." : "Confirmar y continuar →" }}</span>
          </button>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
</style>
