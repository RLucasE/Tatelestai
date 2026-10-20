<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();
const canceling = ref(false);
const checking = ref(false);

const handleCheckStatus = async () => {
  checking.value = true;
  try {
    await authStore.refreshUser();
    if (!authStore.deniedConfirmation()) {
      await router.replace(authStore.getDefaultRoute());
    }
  } catch (error) {
    console.error("Error checking status:", error);
  } finally {
    checking.value = false;
  }
};

const handleCancelRegistration = async () => {
  if (!confirm("¿Deseas reiniciar tu solicitud y volver a la selección de rol?")) {
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
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4 py-10">
    <section class="w-full max-w-xl bg-[var(--color-background)] text-[var(--color-heading)] rounded-xl shadow-lg border border-[var(--color-border)] p-8 text-center space-y-6">
      <div class="flex flex-col items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-14 h-14 text-red-500">
          <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm2.47 6.53a.75.75 0 0 0-1.06-1.06L12 9.94 10.59 8.53a.75.75 0 1 0-1.06 1.06L10.94 11l-1.41 1.41a.75.75 0 1 0 1.06 1.06L12 12.06l1.41 1.41a.75.75 0 0 0 1.06-1.06L13.06 11l1.41-1.41Z" clip-rule="evenodd" />
        </svg>
        <h1 class="text-2xl font-semibold text-red-500">Establecimiento Rechazado</h1>
        <p class="text-[var(--color-heading)]/80 leading-relaxed">
          Tu solicitud de registro para el establecimiento no ha sido aprobada. Para más información
          o consultas, comunícate con soporte en <a href="mailto:soporte@tatelestai.com" class="font-medium underline text-[var(--color-primary)] hover:text-[var(--color-secondary)]">soporte@tatelestai.com</a>.
        </p>
      </div>

      <div class="pt-4 border-t border-[var(--color-border)] flex items-center justify-between flex-wrap gap-4">
        <button
          type="button"
          :disabled="canceling"
          @click="handleCancelRegistration"
          class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] disabled:opacity-60"
        >
          {{ canceling ? "Cancelando..." : "Reiniciar registro" }}
        </button>

        <div class="flex items-center gap-4">
          <button
            type="button"
            :disabled="checking"
            @click="handleCheckStatus"
            class="text-sm text-[var(--color-heading)]/80 hover:text-[var(--color-heading)] underline cursor-pointer"
          >
            {{ checking ? "Comprobando..." : "Comprobar estado" }}
          </button>
          <button
            type="button"
            @click="handleLogout"
            class="text-sm text-red-400 hover:text-red-300 underline cursor-pointer"
          >
            Cerrar sesión
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
