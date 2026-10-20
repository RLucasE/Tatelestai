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
    if (!authStore.inactive()) {
      await router.replace(authStore.getDefaultRoute());
    }
  } catch (error) {
    console.error("Error al comprobar estado:", error);
  } finally {
    checking.value = false;
  }
};

onMounted(async () => {
  await authStore.refreshUser();
  if (!authStore.inactive()) {
    await router.replace(authStore.getDefaultRoute());
  }
});

const handleLogout = async () => {
  try {
    await authStore.logout();
    await router.replace({ name: "login" });
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  }
};
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
          class="w-10 h-10 text-red-500 shrink-0"
        >
          <path
            fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
            clip-rule="evenodd"
          />
        </svg>
        <div>
          <h1 class="text-2xl font-semibold mb-1 text-red-500">Cuenta Desactivada</h1>
          <p class="text-[var(--color-heading)]/80 mb-4">
            Tu cuenta ha sido desactivada temporalmente por un administrador. Si consideras que se trata
            de un error, por favor ponte en contacto con nuestro equipo de soporte.
          </p>
          <div class="bg-red-950/40 border-l-4 border-red-500 p-4 rounded text-sm text-red-200">
            <strong>Estado:</strong> Suspensión temporal de cuenta. Comunícate con soporte para mayor detalle sobre la reactivación.
          </div>
        </div>
      </div>

      <div class="mt-8 flex items-center justify-between flex-wrap gap-4 pt-4 border-t border-[var(--color-border)]">
        <button
          type="button"
          :disabled="checking"
          @click="handleCheckStatus"
          class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] disabled:opacity-60 cursor-pointer"
        >
          {{ checking ? "Comprobando..." : "Comprobar estado" }}
        </button>

        <div class="flex items-center gap-4">
          <a
            href="mailto:soporte@tatelestai.com"
            class="text-sm text-[var(--color-heading)]/70 hover:text-[var(--color-heading)] underline"
          >
            Contactar soporte
          </a>
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
