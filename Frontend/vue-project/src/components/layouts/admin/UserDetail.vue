<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';
import SellerSection from './SellerSection.vue';
import CustomerSection from './CustomerSection.vue';

const route = useRoute();
const router = useRouter();
const userId = route.params.id;

const user = ref(null);
const loading = ref(true);
const error = ref(null);
const activeRole = ref('default');

const fetchUser = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get(`/users/${userId}`);
    user.value = data || null;
    error.value = null;

    const roles = Array.isArray(user.value?.roles) ? user.value.roles : [];
    activeRole.value = roles[0] || 'default';
  } catch (err) {
    console.error('Error fetching user detail:', err);
    error.value = 'No se pudo cargar el usuario';
    user.value = null;
  } finally {
    loading.value = false;
  }
};

const roles = computed(() => Array.isArray(user.value?.roles) ? user.value.roles : []);

// Computed properties para determinar qué secciones mostrar
const isSeller = computed(() => {
  return roles.value.some(role => role.name === 'seller');
});

const isCustomer = computed(() => {
  return roles.value.some(role => role.name === 'customer');
});

const roleLabels = {
  admin: 'Administrador',
  super_admin: 'Super Administrador',
  seller: 'Vendedor',
  customer: 'Cliente',
  default: 'Sin rol',
};

onMounted(fetchUser);
</script>

<template>
  <div class="user-detail-page">
    <div class="user-detail-container">
      <div class="header">
        <button class="back-btn" @click="() => router.push({ name: 'admin-users' })">← Volver</button>
        <h1 class="title">Detalle de usuario</h1>
        <p class="subtitle" v-if="user">ID: {{ user.id }}</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Cargando usuario...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button class="retry-btn" @click="fetchUser">Reintentar</button>
      </div>

      <div v-else-if="user" class="content">
        <section class="card">
          <h2 class="section-title">Datos básicos</h2>
          <div class="data-grid">
            <div class="data-item"><span class="label">Nombre</span><span class="value">{{ user.name }}</span></div>
            <div class="data-item"><span class="label">Apellido</span><span class="value">{{ user.last_name }}</span></div>
            <div class="data-item full"><span class="label">Email</span><span class="value">{{ user.email }}</span></div>
            <div class="data-item full">
              <span class="label">Roles</span>
              <div class="roles">
                <span v-for="role in roles" :key="role" class="role-chip" :class="{ active: role === activeRole }" @click="activeRole = role">
                  {{ roleLabels[role.name] || role }}
                </span>
                <span v-if="roles.length === 0" class="role-chip">{{ roleLabels.default }}</span>
              </div>
            </div>
          </div>
        </section>

        <SellerSection v-if="isSeller" :user="user" @updated="fetchUser" />
        <CustomerSection v-if="isCustomer" :user="user" />
      </div>

    </div>
  </div>
</template>

<style scoped>
.user-detail-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.user-detail-container {
  color: var(--color-text);
}

.header {
  margin-bottom: 2rem;
}

.back-btn {
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  padding: 0.5rem 1rem;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
  margin-bottom: 1rem;
  display: inline-block;
}

.back-btn:hover {
  background-color: var(--color-focus);
}

.title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 0.5rem 0;
}

.subtitle {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 1rem;
  margin: 0;
}

.loading,
.error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  background: var(--color-darkest);
  border-radius: var(--border-radius);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error {
  color: var(--color-text);
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.retry-btn:hover {
  background-color: var(--color-focus);
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card {
  background: var(--color-darkest);
  border-radius: var(--border-radius);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 1.25rem 0;
}

.data-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.data-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.data-item.full {
  grid-column: 1 / -1;
}

.label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
  opacity: 0.7;
}

.value {
  color: var(--color-text);
  font-weight: 500;
}

.roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.role-chip {
  background-color: color-mix(in oklab, var(--color-darkest), var(--color-bg) 10%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--color-text);
}

.role-chip:hover {
  background-color: color-mix(in oklab, var(--color-darkest), var(--color-bg) 15%);
  border-color: rgba(255, 255, 255, 0.2);
}

.role-chip.active {
  background-color: var(--color-primary);
  border-color: var(--color-accent);
  color: var(--color-text);
}

@media (max-width: 768px) {
  .user-detail-page {
    padding: 1rem;
  }

  .data-grid {
    grid-template-columns: 1fr;
  }

  .title {
    font-size: 1.75rem;
  }
}
</style>
