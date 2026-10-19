import { defineStore } from 'pinia';
import { ref } from 'vue';
import axiosInstance from '@/lib/axios';
import { getEchoInstance, disconnectEcho } from '@/lib/echo';

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([]);
  const unreadCount = ref(0);
  const loading = ref(false);
  const error = ref(null);

  // Sistema de Toasts flotantes
  const toasts = ref([]);

  // Banderas / Eventos reactivos para sincronizar vistas activas sin recargar página
  const lastOrderEvent = ref(null);

  let currentUserId = null;

  function pushToast({ title, message, type = 'info', actionUrl = null, timeout = 6000 }) {
    const id = Date.now() + Math.random().toString(36).substring(2, 7);
    const toast = { id, title, message, type, actionUrl };
    toasts.value.push(toast);

    if (timeout > 0) {
      setTimeout(() => {
        removeToast(id);
      }, timeout);
    }
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }

  async function fetchNotifications() {
    try {
      loading.value = true;
      error.value = null;
      const res = await axiosInstance.get('/notifications');
      notifications.value = res.data.data || [];
      unreadCount.value = res.data.unread_count || 0;
    } catch (err) {
      console.error('[NotificationStore] Error cargando notificaciones:', err);
      error.value = 'No se pudieron cargar las notificaciones.';
    } finally {
      loading.value = false;
    }
  }

  async function markAsRead(id) {
    try {
      const res = await axiosInstance.patch(`/notifications/${id}/read`);
      unreadCount.value = res.data.unread_count ?? Math.max(0, unreadCount.value - 1);
      const target = notifications.value.find((n) => n.id === id);
      if (target) {
        target.is_read = true;
        target.read_at = new Date().toISOString();
      }
    } catch (err) {
      console.error('[NotificationStore] Error marcando como leída:', err);
    }
  }

  async function markAllAsRead() {
    try {
      await axiosInstance.post('/notifications/read-all');
      unreadCount.value = 0;
      notifications.value.forEach((n) => {
        n.is_read = true;
        n.read_at = new Date().toISOString();
      });
    } catch (err) {
      console.error('[NotificationStore] Error marcando todas como leídas:', err);
    }
  }

  function connectRealtime(userId) {
    if (!userId) return;
    if (currentUserId === userId) return; // Ya conectado a este usuario

    currentUserId = userId;
    const echo = getEchoInstance();

    // 1. Canal privado del usuario autenticado (notificaciones de órdenes, cancelaciones, retiros)
    echo
      .private(`App.Models.User.${userId}`)
      .notification((notification) => {
        // Formatear al shape de NotificationResource
        const formatted = {
          id: notification.id,
          event_type: notification.event_type || 'info',
          title: notification.title || 'Nueva notificación',
          message: notification.message || '',
          sell_id: notification.sell_id,
          offer_id: notification.offer_id,
          establishment_id: notification.establishment_id,
          action_url: notification.action_url,
          meta: notification.meta || {},
          is_read: false,
          created_at: notification.created_at || new Date().toISOString(),
        };

        // Agregar al inicio del feed
        notifications.value.unshift(formatted);
        unreadCount.value += 1;

        // Disparar toast en pantalla
        const toastTypeMap = {
          new_sale: 'success',
          order_picked_up: 'success',
          cancelled_by_seller: 'error',
          cancelled_by_customer: 'warning',
          offer_sold_out: 'warning',
          cart_item_sold_out: 'error',
          cart_item_stock_reduced: 'warning',
        };

        pushToast({
          title: formatted.title,
          message: formatted.message,
          type: toastTypeMap[formatted.event_type] || 'info',
          actionUrl: formatted.action_url,
        });

        // Emitir reactividad para vistas que observan eventos de orden o carrito
        lastOrderEvent.value = {
          event_type: formatted.event_type,
          sell_id: formatted.sell_id,
          offer_id: formatted.offer_id,
          timestamp: Date.now(),
        };
      });
  }

  function disconnect() {
    if (currentUserId) {
      const echo = getEchoInstance();
      echo.leave(`App.Models.User.${currentUserId}`);
      disconnectEcho();
      currentUserId = null;
    }
  }

  return {
    notifications,
    unreadCount,
    loading,
    error,
    toasts,
    lastOrderEvent,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    pushToast,
    removeToast,
    connectRealtime,
    disconnect,
  };
});
