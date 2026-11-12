<script setup>
import { onMounted, onUnmounted } from 'vue';
import CustomerNavbar from '@/components/ui/organisms/CustomerNavbar.vue';
import RealtimeToastContainer from '@/components/common/RealtimeToastContainer.vue';
import { useNotificationStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const notificationStore = useNotificationStore();

onMounted(() => {
  const user = authStore.getUser();
  if (user?.id) {
    notificationStore.fetchNotifications();
    notificationStore.connectRealtime(user.id);
  }
});

onUnmounted(() => {
  notificationStore.disconnect();
});
</script>

<template>
  <CustomerNavbar />

  <!-- Contenedor Principal del Marketplace -->
  <main class="min-h-[calc(100vh-4rem)] w-full bg-canvas text-content">
    <RouterView />
    <RealtimeToastContainer />
  </main>
</template>
