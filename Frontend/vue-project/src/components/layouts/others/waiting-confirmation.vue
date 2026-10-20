<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();
const checking = ref(false);

const handleCheckStatus = async () => {
  checking.value = true;
  try {
    await authStore.refreshUser();
    if (authStore.active() || !authStore.waitingConfirmation()) {
      await router.replace(authStore.getDefaultRoute());
    }
  } catch (error) {
    console.error("Error al verificar estado:", error);
  } finally {
    checking.value = false;
  }
};

const handleLogout = async () => {
  try {
    await authStore.logout();
    await router.replace({ name: "login" });
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  }
};

onMounted(async () => {
  await authStore.refreshUser();
  if (authStore.active() || !authStore.waitingConfirmation()) {
    await router.replace(authStore.getDefaultRoute());
  }
});
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4 py-10">
    <section
      class="w-full max-w-2xl bg-[var(--color-background)] text-[var(--color-heading)] rounded-xl shadow-lg border border-[var(--color-border)] p-8"
    >
      <div class="flex items-start gap-4">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="w-10 h-10 text-[var(--color-secondary)] shrink-0"
        >
          <path
            fill-rule="evenodd"
            d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm10.5-6a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V6Zm-.75 10.5a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25Z"
            clip-rule="evenodd"
          />
        </svg>
        <div>
          <h1 class="text-2xl font-semibold mb-1">Estamos revisando tu solicitud</h1>
          <p class="text-[var(--color-heading)]/80">
            Estamos verificando los datos y documentación de tu establecimiento. Te notificaremos
            cuando sea aprobado para que puedas comenzar a publicar ofertas.
          </p>
        </div>
      </div>

      <div class="mt-8 flex items-center justify-between flex-wrap gap-4 pt-4 border-t border-[var(--color-border)]">
        <button
          type="button"
          :disabled="checking"
          @click="handleCheckStatus"
          class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] disabled:opacity-60"
        >
          {{ checking ? "Verificando..." : "Comprobar estado" }}
        </button>

        <button
          type="button"
          @click="handleLogout"
          class="text-sm text-[var(--color-heading)]/70 hover:text-[var(--color-heading)] underline cursor-pointer"
        >
          Cerrar sesión
        </button>
      </div>
    </section>
  </main>
</template>
