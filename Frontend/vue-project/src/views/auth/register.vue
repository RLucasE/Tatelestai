<script setup>
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
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

const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const loading = ref(false);
const errorMessage = ref("");

const handleRegister = async () => {
  Object.keys(errors).forEach((key) => (errors[key] = []));
  errorMessage.value = "";
  loading.value = true;
  try {
    await authStore.register(form);
    await router.replace(authStore.getDefaultRoute());
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
  <section
    class="w-full max-w-[560px] bg-surface rounded-[22px] border border-border-subtle shadow-sm p-8 sm:p-11 space-y-6"
  >
    <!-- RegisterHeader (Figma exact) -->
    <header class="space-y-1">
      <span class="block text-[11px] font-bold text-brand tracking-widest uppercase">
        ALTA DE USUARIO • COMUNIDAD TATELESTAI
      </span>
      <h1 class="text-2xl sm:text-[28px] font-extrabold text-content tracking-tight leading-tight">
        Crear cuenta
      </h1>
      <p class="text-sm text-content-secondary pt-1">
        Registrate para comenzar a rescatar alimentos excedentes o sumar tu local gastronómico.
      </p>
    </header>

    <!-- Error banner -->
    <div
      v-if="errorMessage"
      class="p-3.5 rounded-xl bg-danger/10 border border-danger/30 text-danger text-xs font-semibold flex items-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ errorMessage }}</span>
    </div>

    <!-- RegisterFormFields -->
    <form @submit.prevent="handleRegister" class="space-y-4 sm:space-y-5">
      <!-- NameAndLastNameRow (2 cols on sm) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[13px] font-bold text-content mb-1.5" for="name">
            Nombre
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Lucas"
            required
            class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle px-4 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
          />
          <template v-if="Array.isArray(errors.name) && errors.name.length">
            <p v-for="err in errors.name" :key="err" class="text-danger text-xs font-medium mt-1">
              {{ err }}
            </p>
          </template>
        </div>

        <div>
          <label class="block text-[13px] font-bold text-content mb-1.5" for="last_name">
            Apellido
          </label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            placeholder="Gómez"
            required
            class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle px-4 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
          />
          <template v-if="Array.isArray(errors.last_name) && errors.last_name.length">
            <p v-for="err in errors.last_name" :key="err" class="text-danger text-xs font-medium mt-1">
              {{ err }}
            </p>
          </template>
        </div>
      </div>

      <!-- Field — Correo electrónico -->
      <div>
        <label class="block text-[13px] font-bold text-content mb-1.5" for="email">
          Correo electrónico
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          placeholder="lucas.gomez@gmail.com"
          required
          class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle px-4 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
        />
        <template v-if="Array.isArray(errors.email) && errors.email.length">
          <p v-for="err in errors.email" :key="err" class="text-danger text-xs font-medium mt-1">
            {{ err }}
          </p>
        </template>
      </div>

      <!-- PasswordAndConfirmRow (2 cols on sm) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Contraseña -->
        <div>
          <label class="block text-[13px] font-bold text-content mb-1.5" for="password">
            Contraseña
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="••••••••••••"
              required
              class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle pl-4 pr-11 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-content-secondary hover:text-content p-1 focus:outline-none cursor-pointer"
              :title="showPassword ? 'Ocultar' : 'Mostrar'"
            >
              <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
          <template v-if="Array.isArray(errors.password) && errors.password.length">
            <p v-for="err in errors.password" :key="err" class="text-danger text-xs font-medium mt-1">
              {{ err }}
            </p>
          </template>
        </div>

        <!-- Confirmar contraseña -->
        <div>
          <label class="block text-[13px] font-bold text-content mb-1.5" for="password_confirmation">
            Confirmar contraseña
          </label>
          <div class="relative">
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="showPasswordConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="••••••••••••"
              required
              class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle pl-4 pr-11 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
            />
            <button
              type="button"
              @click="showPasswordConfirm = !showPasswordConfirm"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-content-secondary hover:text-content p-1 focus:outline-none cursor-pointer"
              :title="showPasswordConfirm ? 'Ocultar' : 'Mostrar'"
            >
              <svg v-if="!showPasswordConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Btn_Primary_CrearCuenta (Figma: h=50px, rounded=[10px], brand) -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full h-[50px] rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-[15px] flex items-center justify-center transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed select-none cursor-pointer"
      >
        <svg
          v-if="loading"
          class="animate-spin -ml-1 mr-2 h-4 w-4 text-surface"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span>{{ loading ? "Creando cuenta..." : "Crear cuenta" }}</span>
      </button>

      <!-- RegisterFooterSwitch (Figma exact) -->
      <div class="pt-2 text-center text-[13px] text-content-secondary">
        ¿Ya tenés una cuenta registrada?
        <RouterLink to="/login" class="font-bold text-brand hover:underline ml-1">
          Iniciar sesión →
        </RouterLink>
      </div>
    </form>
  </section>
</template>

<style scoped>
</style>
