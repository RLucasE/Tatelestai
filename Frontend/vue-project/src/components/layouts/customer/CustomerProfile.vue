<script setup>
import { computed } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);

const initials = computed(() => {
  const base = user.value?.name || user.value?.email || "";
  if (!base) return "";
  const parts = base.split(/\s|@/).filter(Boolean).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase?.() || "").join("");
});

const stateLabel = computed(() => {
  const s = user.value?.state;
  const map = {
    selecting: "Seleccionando rol",
    registering: "Registrando establecimiento",
    waiting_for_confirmation: "Esperando confirmación",
    active: "Activo",
  };
  return map[s] || s || "Desconocido";
});

async function refresh() {
  try {
    await authStore.refreshUser();
  } catch (e) {
    // Silencioso, la UI ya refleja ausencia de sesión
  }
}

async function handleLogout() {
  await authStore.logout();
  await router.replace({ name: "login" });
}
</script>

<template>
  <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-6 text-content">
    <div v-if="!user || !user.id" class="bg-surface border border-border-subtle rounded-[22px] p-8 text-center space-y-4 shadow-card">
      <p class="text-sm text-content-secondary">No hay sesión activa.</p>
      <RouterLink to="/login" class="inline-flex items-center justify-center h-10 px-6 rounded-[10px] bg-brand hover:bg-brand-hover text-surface font-bold text-xs shadow-sm transition-colors">
        Iniciar sesión
      </RouterLink>
    </div>

    <section v-else class="bg-surface border border-border-subtle rounded-[22px] overflow-hidden shadow-card">
      <header class="p-6 sm:p-7 border-b border-border-subtle flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-surface-sunken border border-border-subtle flex items-center justify-center font-extrabold text-brand text-lg tracking-wider" aria-hidden="true">
            {{ initials }}
          </div>
          <div>
            <h2 class="text-xl font-extrabold text-content-strong tracking-tight">
              {{ (user.name ? user.name + (user.last_name ? ' ' + user.last_name : '') : '') || "Usuario" }}
            </h2>
            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">{{ user.email }}</p>
          </div>
        </div>
        <button
          type="button"
          class="h-9 px-4 rounded-[10px] border border-border-subtle bg-surface hover:bg-surface-sunken text-content font-bold text-xs transition-colors cursor-pointer"
          @click="refresh"
        >
          Actualizar
        </button>
      </header>

      <div class="p-6 sm:p-7 space-y-4">
        <div class="p-4 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-between gap-4">
          <span class="text-xs font-bold text-content-secondary uppercase tracking-wider">ID de Usuario</span>
          <span class="font-mono text-sm font-bold text-content-strong">#{{ user.id }}</span>
        </div>

        <div class="p-4 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-between gap-4">
          <span class="text-xs font-bold text-content-secondary uppercase tracking-wider">Estado de Cuenta</span>
          <span
            class="px-3 py-1 rounded-full text-xs font-bold"
            :class="{
              'bg-success/15 text-success border border-success/30': user.state === 'active',
              'bg-warning/15 text-warning border border-warning/30': user.state === 'waiting_for_confirmation' || user.state === 'selecting',
              'bg-brand/10 text-brand border border-brand/20': user.state === 'registering',
            }"
          >
            {{ stateLabel }}
          </span>
        </div>

        <div class="p-4 rounded-xl bg-surface-sunken border border-border-subtle flex items-center justify-between gap-4">
          <span class="text-xs font-bold text-content-secondary uppercase tracking-wider">Roles</span>
          <div class="flex flex-wrap items-center gap-1.5">
            <span
              v-for="r in user.roles || []"
              :key="r"
              class="px-2.5 py-0.5 rounded-full bg-surface border border-border-subtle text-content text-xs font-semibold"
            >
              {{ r }}
            </span>
          </div>
        </div>
      </div>

      <footer class="p-6 sm:p-7 bg-surface-sunken/50 border-t border-border-subtle flex items-center justify-end">
        <button
          type="button"
          class="h-10 px-5 rounded-[10px] border border-danger/30 text-danger hover:bg-danger/10 font-bold text-xs sm:text-sm transition-colors cursor-pointer"
          @click="handleLogout"
        >
          Cerrar sesión
        </button>
      </footer>
    </section>
  </div>
</template>

<style scoped>
</style>
