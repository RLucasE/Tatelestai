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
    <div class="users-container">
      <div class="header">
        <h1 class="title">Usuarios</h1>
        <p class="subtitle">Listado de usuarios agrupados por rol</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando usuarios...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="getUsers">Reintentar</button>
      </div>

      <div v-else class="content">
        <div class="role-sections">
          <UsersByRole
              v-for="role in rolesOrder"
              :key="role"
              :role="role"
              :title="roleLabels[role]"
              :users="usersByRole[role]"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.users-page {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.users-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  color: var(--color-text);
  width: 100%;
}

.header {
  margin-bottom: 16px;
}

.title {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  margin: 4px 0 0 0;
  opacity: 0.85;
}

.loading, .error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 14px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.retry-btn {
  margin-top: 12px;
  padding: 10px 16px;
  background: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}
</style>