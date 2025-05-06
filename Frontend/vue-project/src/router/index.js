import { createRouter, createWebHistory } from "vue-router";
import customerRoutes from "./modules/customer-routes";
import noAuthRoutes from "./modules/no-auth-routes";
import sellerRoutes from "./modules/seller-routes";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [...noAuthRoutes, ...customerRoutes, ...sellerRoutes],
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  try {
    await authStore.verifySession();
  } catch (error) {
    if (!to.meta.requiresAuth) {
      next();
    }
  }
  console.log(to.meta.requiresAuth, authStore.isLoggedIn());

  if (to.meta.requiresAuth && authStore.isLoggedIn()) {
    next();
  }

  if (!to.meta.requiresAuth && authStore.isLoggedIn()) {
    switch (true) {
      case authStore.isCustomer():
        next({ name: "customer" });
        break;
      case authStore.isSeller():
        next({ name: "seller" });
        break;
      default:
        next({ name: "login" });
    }
  }

  if (to.meta.requiresAuth && !authStore.isLoggedIn()) {
    next({ name: "login" });
  }
});

export default router;
