<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
  await authStore.refreshUser();
  // Si el usuario vuelve a estar activo, redirigir según su rol
  if (authStore.active()) {
    if (authStore.user.roles.includes('seller')) {
      router.push({ name: "seller" });
    } else if (authStore.user.roles.includes('customer')) {
      router.push({ name: "customer" });
    } else if (authStore.user.roles.includes('admin')) {
      router.push({ name: "admin" });
    }
  }
});

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: "login" });
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
          class="w-10 h-10 text-red-500"
        >
          <path
            fill-rule="evenodd"
            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
            clip-rule="evenodd"
          />
        </svg>
        <div>
          <h1 class="text-2xl font-semibold mb-1 text-red-600">Cuenta Desactivada</h1>
          <p class="text-[var(--color-heading)]/80 mb-4">
            Tu cuenta ha sido desactivada por un administrador. Si crees que esto es un error
            o necesitas más información, por favor contacta con el soporte técnico.
          </p>
          <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-700">
                  <strong>Motivo:</strong> Tu cuenta ha sido suspendida temporalmente.
                  Contacta con soporte para obtener más detalles sobre la reactivación.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-between flex-wrap gap-4">
        <button
          @click="authStore.refreshUser()"
          class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-[var(--color-focus)]"
        >
          Comprobar estado
        </button>

        <div class="flex items-center gap-4">
          <a
            href="mailto:soporte@tuapp.com"
            class="text-sm text-[var(--color-heading)]/70 hover:text-[var(--color-heading)] underline"
          >
            Contactar soporte
          </a>
          <button
            @click="handleLogout"
            class="text-sm text-red-600 hover:text-red-800 underline"
          >
            Cerrar sesión
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
