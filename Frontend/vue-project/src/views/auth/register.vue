<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  name: "",
  last_name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const errors = reactive({
  name: [],
  last_name: [],
  email: [],
  password: [],
});

const loading = ref(false);
const errorMessage = ref("");

const handleRegister = async () => {
  Object.keys(errors).forEach((key) => (errors[key] = []));
  errorMessage.value = "";
  loading.value = true;
  try {
    await authStore.register(form);
    router.push("/customer");
  } catch (error) {
    console.error("Register failed:", error);
    errorMessage.value = "No pudimos crear tu cuenta. Revisa los campos e inténtalo de nuevo.";
    if (error?.response?.data?.errors) {
      errors.name = error.response.data.errors.name || [];
      errors.last_name = error.response.data.errors.last_name || [];
      errors.email = error.response.data.errors.email || [];
      errors.password = error.response.data.errors.password || [];
    }
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
        <h1 class="text-2xl font-semibold">Crear cuenta</h1>
        <p class="text-[var(--color-heading)]/70 text-sm mt-1">
          Regístrate para comenzar a utilizar la plataforma.
        </p>
      </header>

      <form @submit.prevent="handleRegister" class="space-y-5">
        <div>
          <label class="block text-sm font-medium mb-1" for="name">Nombre</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Tu nombre"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] placeholder-[var(--color-text)]/60 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
          <template v-if="Array.isArray(errors.name) && errors.name.length">
            <p v-for="error in errors.name" :key="error" class="text-red-400 text-sm mt-1">{{ error }}</p>
          </template>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="last_name">Apellido</label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            placeholder="Tu apellido"
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] placeholder-[var(--color-text)]/60 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
          <template v-if="Array.isArray(errors.last_name) && errors.last_name.length">
            <p v-for="error in errors.last_name" :key="error" class="text-red-400 text-sm mt-1">{{ error }}</p>
          </template>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="email">Correo</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="example@gmail.com"
            required
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] placeholder-[var(--color-text)]/60 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
          <template v-if="Array.isArray(errors.email) && errors.email.length">
            <p v-for="error in errors.email" :key="error" class="text-red-400 text-sm mt-1">{{ error }}</p>
          </template>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="password">Contraseña</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-primary)]/30 text-[var(--color-text)] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-secondary)]"
          />
          <template v-if="Array.isArray(errors.password) && errors.password.length">
            <p v-for="error in errors.password" :key="error" class="text-red-400 text-sm mt-1">{{ error }}</p>
          </template>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1" for="password_confirmation">Confirmar contraseña</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
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
            {{ loading ? "Creando cuenta..." : "Crear cuenta" }}
          </button>
        </div>
      </form>
    </section>
  </main>
</template>

<style scoped></style>
