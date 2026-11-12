<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useNotificationStore } from '@/stores/notifications';

const notificationStore = useNotificationStore();
const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value && notificationStore.notifications.length === 0) {
    notificationStore.fetchNotifications();
  }
};

const closeDropdown = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});

const formatTime = (isoString) => {
  if (!isoString) return '';
  const date = new Date(isoString);
  const now = new Date();
  const diffMinutes = Math.floor((now - date) / (1000 * 60));

  if (diffMinutes < 1) return 'Recién';
  if (diffMinutes < 60) return `Hace ${diffMinutes}m`;
  const diffHours = Math.floor(diffMinutes / 60);
  if (diffHours < 24) return `Hace ${diffHours}h`;
  return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' });
};

const getEventIconClass = (eventType) => {
  switch (eventType) {
    case 'new_sale':
    case 'order_picked_up':
    case 'claim_approved':
      return 'bg-success/15 text-success border-success/30';
    case 'cancelled_by_seller':
    case 'claim_rejected':
      return 'bg-danger/15 text-danger border-danger/30';
    case 'cancelled_by_customer':
    case 'offer_sold_out':
    case 'claim_created':
    case 'claim_refunded_seller':
      return 'bg-warning/15 text-brand border-warning/35';
    default:
      return 'bg-brand/15 text-brand border-brand/30';
  }
};
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <!-- Botón de Campanita -->
    <button
      type="button"
      class="relative flex items-center justify-center w-9 h-9 rounded-full transition-all select-none border border-transparent text-content hover:bg-canvas hover:border-border-subtle active:scale-95 cursor-pointer"
      :class="{ 'bg-canvas border-border-subtle': isOpen }"
      aria-label="Notificaciones"
      @click="toggleDropdown"
    >
      <svg class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.8"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>

      <!-- Badge de no leídas -->
      <span
        v-if="notificationStore.unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-surface bg-brand rounded-full border-2 border-surface"
      >
        {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
      </span>
    </button>

    <!-- Dropdown Flotante -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform scale-95 opacity-0 translate-y-1"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 sm:w-96 rounded-[20px] bg-surface border border-border-subtle shadow-card py-3 z-50 overflow-hidden text-content"
      >
        <!-- Encabezado del Dropdown -->
        <div class="flex items-center justify-between px-4 pb-2.5 border-b border-border-subtle">
          <div class="flex items-center gap-2">
            <span class="type-overline text-content">Notificaciones</span>
            <span
              v-if="notificationStore.unreadCount > 0"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand/14 text-brand border border-brand/30 tabular-nums"
            >
              {{ notificationStore.unreadCount }} nuevas
            </span>
          </div>

          <button
            v-if="notificationStore.unreadCount > 0"
            type="button"
            class="text-xs font-bold text-brand hover:text-brand-hover transition-colors cursor-pointer"
            @click="notificationStore.markAllAsRead"
          >
            Marcar todas leídas
          </button>
        </div>

        <!-- Lista de Notificaciones -->
        <div class="max-h-80 overflow-y-auto divide-y divide-border-subtle/60 custom-scrollbar">
          <div
            v-if="notificationStore.notifications.length === 0"
            class="p-6 text-center text-xs text-content-secondary"
          >
            <p>No tienes notificaciones por el momento.</p>
          </div>

          <div
            v-for="item in notificationStore.notifications"
            :key="item.id"
            class="p-3.5 hover:bg-canvas transition-colors flex items-start gap-3 cursor-pointer"
            :class="{ 'bg-surface-elevated': !item.is_read }"
            @click="notificationStore.markAsRead(item.id)"
          >
            <!-- Icono semántico según event_type -->
            <div
              class="w-8 h-8 rounded-xl border flex items-center justify-center shrink-0 mt-0.5"
              :class="getEventIconClass(item.event_type)"
            >
              <svg
                v-if="item.event_type === 'new_sale' || item.event_type === 'order_picked_up'"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg
                v-else-if="item.event_type === 'cancelled_by_seller'"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg
                v-else-if="item.event_type === 'cancelled_by_customer' || item.event_type === 'offer_sold_out'"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <svg
                v-else
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <!-- Contenido -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-1">
                <h4 class="text-xs font-bold text-content truncate">{{ item.title }}</h4>
                <span class="text-[10px] text-content-secondary shrink-0">{{ formatTime(item.created_at) }}</span>
              </div>
              <p class="text-xs text-content-secondary mt-0.5 line-clamp-2 leading-relaxed">
                {{ item.message }}
              </p>

              <RouterLink
                v-if="item.action_url"
                :to="item.action_url"
                class="inline-block mt-1 text-[11px] font-bold text-brand hover:text-brand-hover"
                @click="isOpen = false"
              >
                Ver pedido →
              </RouterLink>
            </div>

            <!-- Punto de no leída -->
            <div v-if="!item.is_read" class="w-2 h-2 rounded-full bg-brand shrink-0 mt-2"></div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
