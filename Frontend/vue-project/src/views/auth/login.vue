<script setup>
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  email: "",
  password: "",
});

const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref("");

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    await authStore.login(form);
    await router.replace(authStore.getDefaultRoute());
  } catch (error) {
    console.error("Login failed:", error);
    errorMessage.value = "No pudimos iniciar sesión. Verifica tus credenciales e inténtalo nuevamente.";
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <section
    class="w-full max-w-[500px] bg-surface rounded-[22px] border border-border-subtle shadow-sm p-8 sm:p-11 space-y-6"
  >
    <!-- LoginHeader (Figma exact) -->
    <header class="space-y-1">
      <span class="block text-[11px] font-bold text-brand tracking-widest uppercase">
        BIENVENIDO DE NUEVO • TATELESTAI
      </span>
      <h1 class="text-2xl sm:text-[28px] font-extrabold text-content tracking-tight leading-tight">
        Iniciar sesión
      </h1>
      <p class="text-sm text-content-secondary pt-1">
        Accedé a tu cuenta para rescatar bolsas sorpresa o gestionar tu comercio.
      </p>
    </header>

    <!-- Error message banner -->
    <div
      v-if="errorMessage"
      class="p-3.5 rounded-xl bg-danger/10 border border-danger/30 text-danger text-xs font-semibold flex items-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ errorMessage }}</span>
    </div>

    <!-- LoginFormFields -->
    <form @submit.prevent="handleLogin" class="space-y-4 sm:space-y-5">
      <!-- Correo electrónico -->
      <div>
        <label class="block text-[13px] font-bold text-content mb-1.5" for="email">
          Correo electrónico
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          placeholder="example@gmail.com"
          required
          class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle px-4 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
        />
      </div>

      <!-- Contraseña con toggle mostrar/ocultar -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-[13px] font-bold text-content" for="password">
            Contraseña
          </label>
        </div>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="••••••••••••"
            required
            class="w-full h-[46px] rounded-xl bg-canvas border border-border-subtle pl-4 pr-11 text-sm text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-colors"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-content-secondary hover:text-content p-1 focus:outline-none cursor-pointer"
            :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
          >
            <!-- Eye icon / Eye off icon -->
            <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Btn_Primary_Ingresar (Figma: h=50px, rounded=[10px], brand) -->
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
        <span>{{ loading ? "Ingresando a mi cuenta..." : "Ingresar a mi cuenta" }}</span>
      </button>

      <!-- LoginFooterSwitch (Figma exact) -->
      <div class="pt-2 text-center text-[13px] text-content-secondary">
        ¿Todavía no tenés una cuenta?
        <RouterLink to="/register" class="font-bold text-brand hover:underline ml-1">
          Crear cuenta gratis →
        </RouterLink>
      </div>
    </form>
  </section>
</template>

<style scoped>
</style>
