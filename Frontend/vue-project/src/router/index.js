import { createRouter, createWebHistory } from "vue-router";
import customerRoutes from "./modules/customer-routes";
import noAuthRoutes from "./modules/no-auth-routes";
import sellerRoutes from "./modules/seller-routes";
import otherRoutes from "./modules/other-routes";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [...noAuthRoutes, ...customerRoutes, ...sellerRoutes, ...otherRoutes],
});

function redirectByRole(authStore, next) {
  if (authStore.isCustomer()) {
    next({ name: "customer" });
  } else if (authStore.isSeller()) {
    if (authStore.registeringEstablishment())
      next({ name: "register-establishment" });
    else next({ name: "seller" });
  } else if (authStore.isDefaultRole()) {
    if (authStore.selectingRole()) {
      console.log("2");
      next({ name: "select-role" });
    }
  } else {
    next({ path: "/" });
  }
}

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (!to.meta.requiresAuth) {
    console.log("1");
    try {
      await authStore.heavyVerifySession();
      redirectByRole(authStore, next);
    } catch (error) {
      next();
      console.log(error);
    }
  }

  if (to.meta.requiresAuth) {
    console.log("3");
    try {
      await authStore.verifySession();
    } catch (error) {
      console.log(error);
    }
    console.log("4");

    if (authStore.isLoggedIn()) {
      switch (true) {
        case to.meta.requiresCustomer && authStore.isCustomer():
          next();
          break;
        case to.meta.requiresSeller && authStore.isSeller():
          if (
            to.meta.requiresRegisteringEstablishment &&
            authStore.registeringEstablishment()
          ) {
            next();
          } else next();

          break;
        case to.meta.requiresDefault && authStore.isDefaultRole():
          if (to.meta.requiresSelectingRole && authStore.selectingRole()) {
            next();
          }

          break;
        default:
          redirectByRole(authStore, next);
      }
    }

    if (!authStore.isLoggedIn()) {
      next({ name: "login" });
    }
  }
});

export default router;
