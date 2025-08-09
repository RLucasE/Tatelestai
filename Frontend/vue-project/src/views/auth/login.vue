<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  email: "",
  password: "",
});

const loading = ref(false);
const errorMessage = ref("");

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    await authStore.login(form);
    window.location.reload();
  } catch (error) {
    console.error("Login failed:", error);
    errorMessage.value = "No pudimos iniciar sesión. Verifica tus credenciales e inténtalo nuevamente.";
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4">
    <section
      class="w-full max-w-md bg-[var(--color-background)] text-[var(--color-heading)] rounded-xl shadow-lg border border-[var(--color-border)] p-6"
    >
      <header class="mb-5">
        <h1 class="text-2xl font-semibold">Iniciar sesión</h1>
        <p class="text-[var(--color-heading)]/70 text-sm mt-1">
          Accede a tu cuenta para continuar.
        </p>
      </header>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-medium mb-1" for="email">Correo</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            placeholder="example@gmail.com"
            required
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] placeholder-[var(--color-text)]/60 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="password">Contraseña</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
        </div>

        <div class="pt-2 flex items-center gap-3">
          <p v-if="errorMessage" class="text-red-400 text-sm">{{ errorMessage }}</p>
          <button
            type="submit"
            :disabled="loading"
            class="ml-auto inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-[var(--color-focus)] disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <svg
              v-if="loading"
              class="animate-spin -ml-1 mr-2 h-5 w-5 text-[var(--color-text)]"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            {{ loading ? "Ingresando..." : "Ingresar" }}
          </button>
        </div>
      </form>
    </section>
  </main>
  
</template>

<style scoped></style>
