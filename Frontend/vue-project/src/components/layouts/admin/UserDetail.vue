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
  padding: 24px;
}
.user-detail-container {
  max-width: 960px;
  margin: 0 auto;
  color: var(--color-text);
}
.header { margin-bottom: 16px; display: flex; align-items: center; gap: 12px; }
.title {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.subtitle { margin: 0; opacity: 0.8; }

.back-btn {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 8px;
  color: var(--color-text);
  padding: 6px 10px;
  cursor: pointer;
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
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.card {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}
.section-title { margin: 0 0 10px 0; font-size: 1.2rem; }

.data-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.data-item { display: flex; flex-direction: column; }
.data-item.full { grid-column: 1/-1; }
.label { opacity: 0.7; font-size: 0.85rem; }
.value { font-weight: 700; }

.roles { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.role-chip {
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 0.8rem;
  cursor: pointer;
}
.role-chip.active { outline: 2px solid var(--color-focus); }
</style>
