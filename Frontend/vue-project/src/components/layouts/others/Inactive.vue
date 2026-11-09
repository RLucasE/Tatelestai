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
  <main class="min-h-screen flex items-center justify-center bg-canvas px-4 py-10 font-sans">
    <section
      class="w-full max-w-2xl bg-surface text-content rounded-[22px] shadow-card border border-border-subtle p-8"
    >
      <div class="flex items-start gap-4">
        <div class="p-3 bg-danger/10 text-danger rounded-2xl shrink-0">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            class="w-8 h-8"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-black mb-1.5 text-content-strong">Cuenta Desactivada</h1>
          <p class="text-sm text-content-secondary leading-relaxed mb-5">
            Tu cuenta ha sido desactivada temporalmente por un administrador. Si consideras que se trata
            de un error, por favor ponte en contacto con nuestro equipo de soporte.
          </p>
          <div class="bg-danger/10 border-l-4 border-danger p-4 rounded-xl text-xs text-content-strong font-medium">
            <strong class="text-danger">Estado:</strong> Suspensión temporal de cuenta. Comunícate con soporte para mayor detalle sobre la reactivación.
          </div>
        </div>
      </div>

      <div class="mt-8 flex items-center justify-between flex-wrap gap-4 pt-5 border-t border-border-subtle">
        <button
          type="button"
          :disabled="checking"
          @click="handleCheckStatus"
          class="inline-flex items-center justify-center rounded-[10px] bg-brand text-surface px-5 py-2.5 font-bold text-sm hover:bg-brand-hover shadow-btn transition cursor-pointer disabled:opacity-50"
        >
          {{ checking ? "Comprobando..." : "Comprobar estado" }}
        </button>

        <div class="flex items-center gap-4">
          <a
            href="mailto:soporte@tatelestai.com"
            class="text-sm text-content-secondary hover:text-content-strong underline transition"
          >
            Contactar soporte
          </a>
          <button
            type="button"
            @click="handleLogout"
            class="text-sm text-danger hover:text-danger/80 font-semibold underline cursor-pointer transition"
          >
            Cerrar sesión
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
