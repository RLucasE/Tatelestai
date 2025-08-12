<script setup>
import { computed } from "vue";
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useStorageStore } from "@/stores/storage";

const authStore = useAuthStore();
const storage = useStorageStore();

// user es un ref reactivo proveniente del storage
const user = storage.getUser();

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
</script>

<template>
  <div class="profile-container">
    <div v-if="!user || !user.id" class="empty">
      <p>No hay sesión activa.</p>
      <RouterLink to="/login" class="btn">Iniciar sesión</RouterLink>
    </div>

    <section v-else class="profile-card">
      <header class="profile-header">
        <div class="avatar" aria-hidden="true">{{ initials }}</div>
        <div class="identity">
          <h2 class="name">{{ user.name + " " +user.last_name || "Usuario" }}</h2>

          <p class="email">{{ user.email }}</p>
        </div>
        <button class="btn refresh" @click="refresh">Actualizar</button>
      </header>

      <div class="profile-body">
        <div class="row">
          <span class="label">ID</span>
          <span class="value">#{{ user.id }}</span>
        </div>
        <div class="row">
          <span class="label">Estado</span>
          <span class="badge" :class="user.state">{{ stateLabel }}</span>
        </div>
        <div class="row roles">
          <span class="label">Roles</span>
          <div class="chips">
            <span v-for="r in user.roles || []" :key="r" class="chip">{{ r }}</span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.profile-container {
  width: 100%;
  max-width: 860px;
  margin: 24px auto;
  padding: 0 16px;
  color: var(--color-text);
}
.empty {
  background: color-mix(in oklab, var(--color-primary), white 6%);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 24px;
  display: grid;
  gap: 12px;
  text-align: center;
}
.profile-card {
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(34,32,31,0.25);
}
.profile-header {
  display: grid;
  grid-template-columns: 56px 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 16px;
  background: var(--color-secondary);
  border-bottom: 1px solid var(--color-focus);
}
.avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-darkest);
  display: grid;
  place-items: center;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.identity .name { margin: 0; font-size: 1.25rem; }
.identity .email { margin: 2px 0 0; opacity: 0.85; font-size: 0.95rem; }

.btn {
  appearance: none;
  border: 1px solid var(--color-border);
  background: var(--color-focus);
  color: var(--color-text);
  border-radius: 10px;
  padding: 8px 12px;
  cursor: pointer;
  text-decoration: none;
}
.btn:hover { background: var(--color-darkest); }
.refresh { white-space: nowrap; }

.profile-body { padding: 16px; display: grid; gap: 12px; }
.row { display: grid; grid-template-columns: 140px 1fr; align-items: center; gap: 8px; }
.label { opacity: 0.85; }
.value { font-weight: 600; }

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 999px;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
}

.roles .chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chip {
  padding: 4px 8px;
  border-radius: 999px;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
}
</style>
