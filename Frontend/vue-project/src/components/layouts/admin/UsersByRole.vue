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

    <div v-else class="users-grid">
      <router-link
        v-for="user in users"
        :key="user.id"
        class="user-card card-link"
        :to="{ name: 'admin-user-detail', params: { id: user.id } }"
      >
        <div class="user-main">
          <h3 class="user-name">{{ user.name }} {{ user.last_name }}</h3>
          <p class="user-email">{{ user.email }}</p>
        </div>
        <div class="user-roles" v-if="Array.isArray(user.roles) && user.roles.length">
          <span
            v-for="r in user.roles"
            :key="typeof r === 'string' ? r : r.name"
            class="role-chip"
          >
            {{ typeof r === 'string' ? r : r.name }}
          </span>
        </div>
      </router-link>
    </div>
  </section>
</template>

<style scoped>
.role-section {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.role-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.role-title {
  margin: 0;
  font-size: 1.2rem;
  color: var(--color-text);
}

.role-count {
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 700;
  color: var(--color-text);
}

.empty-role {
  background: var(--color-darkest);
  border: 1px dashed var(--color-focus);
  border-radius: 10px;
  padding: 16px;
  text-align: center;
  color: var(--color-text);
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.user-card {
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  border-radius: 10px;
  padding: 12px;
  color: var(--color-text);
}

.card-link {
  text-decoration: none;
  display: block;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.card-link:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.25); }

.user-name {
  margin: 0 0 6px 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.user-email {
  margin: 0;
  opacity: 0.85;
  font-size: 0.95rem;
}

.user-roles {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.role-chip {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 0.8rem;
}

@media (max-width: 1024px) {
  .users-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .users-grid { grid-template-columns: 1fr; }
}
</style>
