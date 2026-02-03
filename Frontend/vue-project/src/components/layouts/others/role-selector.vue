<script setup>
import axiosInstance from "@/lib/axios";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const role = ref("unknown_role");
const authStore = useAuthStore();
const loading = ref(false);
const errorMessage = ref("");

const selectRole = (role_selected) => {
  role.value = role_selected;
  console.log("Selected role:", role.value);
  errorMessage.value = "";
};

const hadleElection = async () => {
  if (role.value === "unknown_role") {
    errorMessage.value = "Por favor, selecciona un rol.";
    return;
  }

  loading.value = true;
  errorMessage.value = "";
  try {
    const response = await axiosInstance.post("/select-role", {
      role: role.value,
    });
    console.log("Role selection response:", response.data);
    await authStore.refreshUser();
    router.push({ name: role.value === "customer" ? "customer" : "seller" });
  } catch (error) {
    console.error("Error selecting role:", error);
    errorMessage.value = "Hubo un error al seleccionar el rol. Inténtalo de nuevo.";
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  try {
    await authStore.logout();
    router.push('/login');
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
  }
};
</script>

<template>
  <main class="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4">
    <section
      class="w-full max-w-3xl bg-[var(--color-background)] text-[var(--color-heading)] rounded-xl shadow-lg border border-[var(--color-border)] p-8"
    >
      <header class="mb-6">
        <h1 class="text-2xl font-semibold">Selecciona tu rol</h1>
        <p class="text-[var(--color-heading)]/70 text-sm mt-1">
          Elige cómo deseas usar la plataforma. Puedes cambiarlo más adelante si es necesario.
        </p>
      </header>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Customer Card -->
        <button
          type="button"
          @click="selectRole('customer')"
          :class="[
            'group relative w-full text-left rounded-xl border p-5 transition focus:outline-none',
            role === 'customer'
              ? 'border-[var(--color-secondary)] ring-2 ring-[var(--color-secondary)]'
              : 'border-[var(--color-border)] hover:border-[var(--color-border-hover)]'
          ]"
        >
          <div class="flex items-start gap-4">
            <span
              class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--color-primary)]/20 text-[var(--color-secondary)]"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                <path d="M12 12c2.485 0 4.5-2.015 4.5-4.5S14.485 3 12 3 7.5 5.015 7.5 7.5 9.515 12 12 12Z" />
                <path fill-rule="evenodd" d="M3.75 20.1a8.25 8.25 0 0116.5 0 .9.9 0 01-.9.9H4.65a.9.9 0 01-.9-.9Z" clip-rule="evenodd" />
              </svg>
            </span>
            <div>
              <h2 class="text-lg font-medium">Comprador</h2>
              <p class="text-sm text-[var(--color-heading)]/70 mt-1">
                Compra productos y aprovecha ofertas de los establecimientos.
              </p>
            </div>
          </div>
          <span
            v-if="role === 'customer'"
            class="absolute right-4 top-4 text-[var(--color-secondary)]"
            aria-hidden="true"
          >
            ✓
          </span>
        </button>

        <!-- Seller Card -->
        <button
          type="button"
          @click="selectRole('seller')"
          :class="[
            'group relative w-full text-left rounded-xl border p-5 transition focus:outline-none',
            role === 'seller'
              ? 'border-[var(--color-secondary)] ring-2 ring-[var(--color-secondary)]'
              : 'border-[var(--color-border)] hover:border-[var(--color-border-hover)]'
          ]"
        >
          <div class="flex items-start gap-4">
            <span
              class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--color-primary)]/20 text-[var(--color-secondary)]"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                <path d="M3 4.5h18V9a3 3 0 01-3 3H6a3 3 0 01-3-3V4.5Z" />
                <path d="M4.5 14.25h15V19.5a.75.75 0 01-.75.75H5.25a.75.75 0 01-.75-.75v-5.25Z" />
              </svg>
            </span>
            <div>
              <h2 class="text-lg font-medium">Vendedor</h2>
              <p class="text-sm text-[var(--color-heading)]/70 mt-1">
                Publica productos y gestiona tus ofertas y ventas.
              </p>
            </div>
          </div>
          <span
            v-if="role === 'seller'"
            class="absolute right-4 top-4 text-[var(--color-secondary)]"
            aria-hidden="true"
          >
            ✓
          </span>
        </button>
      </div>

      <div style="background: var(--color-primary); color: var(--color-text); border-left: 4px solid var(--color-primary); padding: 0.5rem; margin: 1rem 0; border-radius: var(--border-radius); font-size: 0.875rem;" role="alert">
        <p style="font-weight: bold; margin-bottom: 0.25rem;">Nota importante</p>
        <p>Debes tener el negocio registrado en google maps para poder ser un vendedor</p>
      </div>

       <div class="mt-6 flex items-center gap-4">
         <p v-if="errorMessage" class="text-red-400 text-sm">{{ errorMessage }}</p>
         <div class="ml-auto flex gap-3">
           <button
             @click="handleLogout"
             class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] bg-transparent text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-[var(--color-focus)]"
           >
             Cancelar
           </button>
           <button
             @click="hadleElection"
             :disabled="loading"
             class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] text-[var(--color-text)] px-4 py-2 font-medium hover:bg-[var(--color-secondary)] focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-[var(--color-focus)] disabled:opacity-60 disabled:cursor-not-allowed"
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
             {{ loading ? "Confirmando..." : "Confirmar" }}
           </button>
         </div>
       </div>
    </section>
  </main>
</template>

<style scoped></style>
