<script setup>
import axiosInstance from "@/lib/axios.js";
import {onMounted, ref, computed} from "vue";
import UsersByRole from "@/components/layouts/admin/UsersByRole.vue";

const users = ref([]);
const loading = ref(true);
const error = ref(null);

const rolesOrder = [
  "admin",
  "seller",
  "customer",
  "default",
];

const roleLabels = {
  admin: "Administradores",
  seller: "Vendedores",
  customer: "Clientes",
  default: "Usuarios sin rol",
};

const getUsers = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance("/users");
    users.value = Array.isArray(response.data) ? response.data : [];
    error.value = null;
  } catch (err) {
    console.error("Error fetching users:", err);
    error.value = "No se pudieron cargar los usuarios";
    users.value = [];
  } finally {
    loading.value = false;
  }
};

const usersByRole = computed(() => {
  const grouped = rolesOrder.reduce((acc, role) => {
    acc[role] = [];
    return acc;
  }, {});

  for (const user of users.value) {
    const userRoles = Array.isArray(user.roles) ? user.roles : [];
    let placed = false;
    for (const r of userRoles) {
      const roleName = typeof r === 'string' ? r : r?.name;
      if (roleName && grouped[roleName]) {
        grouped[roleName].push(user);
        placed = true;
      }
    }
    if (!placed) grouped.default.push(user);
  }
  return grouped;
});

onMounted(getUsers);
</script>

<template>
  <div class="users-page">
    <div class="page-header">
      <h1 class="page-title">Usuarios</h1>
    </div>

    <div v-if="loading" class="loading">
      <p>Cargando usuarios...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button class="retry-btn" @click="getUsers">Reintentar</button>
    </div>

    <div v-else class="users-list">
      <UsersByRole
          v-for="role in rolesOrder"
          :key="role"
          :role="role"
          :title="roleLabels[role]"
          :users="usersByRole[role]"
      />
    </div>
  </div>
</template>

<style scoped>
.users-page {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
}

.loading,
.error {
  padding: 2rem;
  text-align: center;
  color: var(--color-text);
}

.error p {
  margin: 0 0 1rem 0;
}

.retry-btn {
  padding: 0.5rem 1rem;
  background-color: var(--color-accent);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 0.875rem;
}

.retry-btn:hover {
  background-color: var(--color-accent-hover);
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
</style>