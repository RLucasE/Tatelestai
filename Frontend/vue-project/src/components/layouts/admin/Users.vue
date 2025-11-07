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
    <div class="admin-header">
      <h1 class="page-title">Usuarios</h1>
      <p class="page-subtitle">Gestión de usuarios del sistema</p>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando usuarios...</p>
    </div>

    <div v-else-if="error" class="error-container">
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
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: 100vh;
}

.admin-header {
  margin-bottom: 2.5rem;
  text-align: center;
}

.page-title {
  font-size: 2.25rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 0.75rem 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  color: var(--color-text);
  opacity: 0.6;
  font-size: 1rem;
  margin: 0;
  font-weight: 400;
}

.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.loading-spinner {
  width: 48px;
  height: 48px;
  border: 3px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-container {
  color: var(--color-text);
}

.error-container p {
  margin: 0 0 1.5rem 0;
  font-size: 1rem;
}

.retry-btn {
  padding: 0.75rem 1.5rem;
  background-color: var(--color-accent);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.retry-btn:hover {
  background-color: var(--color-accent-hover);
  transform: translateY(-1px);
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

@media (max-width: 768px) {
  .users-page {
    padding: 1.5rem;
  }

  .page-title {
    font-size: 1.875rem;
  }

  .admin-header {
    margin-bottom: 2rem;
  }

  .users-list {
    gap: 1.5rem;
  }
}
</style>