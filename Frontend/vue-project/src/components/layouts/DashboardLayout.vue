<script setup>
import { onMounted, onUnmounted } from "vue";
import SellerTopNavbar from "./seller/SellerTopNavbar.vue";
import RealtimeToastContainer from "@/components/common/RealtimeToastContainer.vue";
import { useNotificationStore } from "@/stores/notifications";
import { useAuthStore } from "@/stores/auth";

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
  <div class="min-h-screen flex flex-col bg-canvas text-content">
    <SellerTopNavbar />
    <main class="flex-1 w-full">
      <RouterView />
      <RealtimeToastContainer />
    </main>
  </div>
</template>
