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

  if (!to.meta.requiresAuth) {
    console.log("heavy ferifyi session...");
    try {
      await authStore.heavyVerifySession();
      next({ name: "customer" });
    } catch (error) {
      next();
      console.log(error);
    }
  }

  if (to.meta.requiresAuth) {
    try {
      await authStore.verifySession();
    } catch (error) {
      console.log(error);
    }

    if (authStore.isLoggedIn()) {
      if (!from.meta.requiresAuth) {
        if (authStore.isCustomer() && to.meta.requiresCustomer) {
          next();
        }
        if (authStore.isSeller() && to.meta.requiresSeller) {
          next();
        }
      } else {
        next();
      }
    }

    if (!authStore.isLoggedIn()) {
      next({ name: "login" });
    }
  }
});

export default router;
