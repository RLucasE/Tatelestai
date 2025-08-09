<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
  await authStore.refreshUser();
  if (authStore.active()) {
    router.push({ name: "seller" });
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
          class="w-10 h-10 text-[var(--color-secondary)]"
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
            Estamos verificando tu establecimiento. Te notificaremos por correo
            electrónico cuando sea aprobado. Esto suele tardar poco tiempo.
          </p>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-between">
        <button
          @click="authStore.refreshUser()"
          class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-[var(--color-focus)]"
        >
          Comprobar estado
        </button>

        <router-link
          :to="{ name: 'seller' }"
          class="text-sm text-[var(--color-heading)]/70 hover:text-[var(--color-heading)] underline"
          >Volver al panel</router-link
        >
      </div>
    </section>
  </main>
</template>
