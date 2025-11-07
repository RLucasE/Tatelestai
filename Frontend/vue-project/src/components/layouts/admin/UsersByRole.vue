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
  background: var(--color-darkest);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  transition: box-shadow 0.3s ease;
}

.role-section:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.role-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.75rem 2rem;
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.6), rgba(37, 43, 58, 0.4));
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.role-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text);
  letter-spacing: -0.2px;
}

.role-count {
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--color-accent-light);
  min-width: 2.5rem;
  text-align: center;
}

.empty-role {
  padding: 4rem 2rem;
  text-align: center;
  color: var(--color-text);
  opacity: 0.5;
}

.empty-role p {
  margin: 0;
  font-size: 0.9375rem;
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
  padding: 2rem;
}

.user-card {
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.4), rgba(37, 43, 58, 0.3));
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1.25rem;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.user-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--color-accent), var(--color-info));
  opacity: 0;
  transition: opacity 0.25s ease;
}

.card-link {
  text-decoration: none;
  display: block;
}

.card-link:hover .user-card {
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.6), rgba(37, 43, 58, 0.5));
  border-color: rgba(99, 102, 241, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.card-link:hover .user-card::before {
  opacity: 1;
}

.user-main {
  margin-bottom: 1rem;
}

.user-name {
  margin: 0 0 0.375rem 0;
  font-size: 1.0625rem;
  font-weight: 600;
  color: var(--color-text);
  letter-spacing: -0.2px;
}

.user-email {
  margin: 0;
  opacity: 0.6;
  font-size: 0.875rem;
  color: var(--color-text);
  font-weight: 400;
}

.user-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.role-chip {
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.25);
  border-radius: 16px;
  padding: 0.3125rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-accent-light);
  text-transform: capitalize;
}

@media (max-width: 1024px) {
  .users-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .users-grid {
    grid-template-columns: 1fr;
    padding: 1.25rem;
  }

  .role-header {
    padding: 1.25rem 1.5rem;
  }
}
</style>
