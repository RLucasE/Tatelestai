<script setup>
import { computed } from "vue";

const props = defineProps({
  role: { type: String, default: "default" },
  title: { type: String, default: "" },
  users: { type: Array, default: () => [] },
});

const roleLabels = {
  admin: "Administradores",
  super_admin: "Super Administradores",
  seller: "Vendedores",
  customer: "Clientes",
  default: "Usuarios sin rol",
};

const sectionTitle = computed(() => props.title || roleLabels[props.role] || props.role);
const usersCount = computed(() => (Array.isArray(props.users) ? props.users.length : 0));
</script>

<template>
  <section class="role-section">
    <div class="role-header">
      <h2 class="role-title">{{ sectionTitle }}</h2>
      <span class="role-count">{{ usersCount }}</span>
    </div>

    <div v-if="usersCount === 0" class="empty-role">
      <p>No hay usuarios con este rol</p>
    </div>

    <table v-else class="users-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Roles</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="user in users"
          :key="user.id"
          class="user-row"
          @click="$router.push({ name: 'admin-user-detail', params: { id: user.id } })"
        >
          <td class="user-name">{{ user.name }} {{ user.last_name }}</td>
          <td class="user-email">{{ user.email }}</td>
          <td class="user-roles">
            <span
              v-for="r in user.roles"
              :key="typeof r === 'string' ? r : r.name"
              class="role-chip"
            >
              {{ typeof r === 'string' ? r : r.name }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.role-section {
  background: var(--color-darkest);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.role-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border);
}

.role-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
}

.role-count {
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  padding: 0.25rem 0.75rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  color: var(--color-text);
}

.empty-role {
  padding: 2rem;
  text-align: center;
  color: var(--color-text);
  opacity: 0.5;
}

.empty-role p {
  margin: 0;
  font-size: 0.875rem;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table thead th {
  text-align: left;
  padding: 0.75rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
  opacity: 0.7;
}

.user-row {
  cursor: pointer;
  border-bottom: 1px solid var(--color-border);
}

.user-row:last-child {
  border-bottom: none;
}

.user-row:hover {
  background: var(--color-primary);
}

.user-row td {
  padding: 0.875rem 1.25rem;
  color: var(--color-text);
  font-size: 0.875rem;
}

.user-name {
  font-weight: 500;
}

.user-email {
  opacity: 0.7;
}

.user-roles {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  align-items: center;
}

.role-chip {
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
  color: var(--color-text);
  text-transform: capitalize;
  min-width: 80px;
  text-align: center;
  display: inline-block;
}

@media (max-width: 768px) {
  .users-table {
    display: block;
    overflow-x: auto;
  }

  .users-table thead {
    display: none;
  }

  .users-table tbody,
  .users-table tr,
  .users-table td {
    display: block;
  }

  .user-row {
    padding: 1rem 1.25rem;
  }

  .user-row td {
    padding: 0.25rem 0;
  }

  .user-name {
    font-size: 1rem;
    margin-bottom: 0.25rem;
  }

  .user-email {
    margin-bottom: 0.5rem;
  }
}
</style>
