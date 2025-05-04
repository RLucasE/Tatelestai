import { createRouter, createWebHistory } from "vue-router";
import Register from "@/views/auth/register.vue";
import HomeLayout from "@/components/layouts/HomeLayout.vue";
import Login from "@/views/auth/login.vue";
import CustomerLayout from "@/components/layouts/customer/CustomerLayout.vue";
import CustomerCard from "@/components/layouts/customer/CustomerCard.vue";
import { useAuthStore } from "@/stores/auth";
import DashboardLayout from "@/components/layouts/DashboardLayout.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      component: HomeLayout,
      children: [
        {
          name: "register",
          path: "register",
          component: Register,
          meta: { requiresAuth: false },
        },
        {
          name: "login",
          path: "login",
          component: Login,
          meta: { requiresAuth: false },
        },
      ],
      meta: {
        requiresAuth: false,
      },
    },
    {
      path: "/customer",
      name: "customer",
      component: CustomerLayout,
      children: [{ path: "", name: "main", component: CustomerCard }],
      meta: {
        requiresAuth: true,
        requiresCustomer: true,
      },
    },
    {
      path: "/seller",
      name: "seller",
      component: DashboardLayout,
      meta: {
        requiresAuth: true,
        requiresCustomer: true,
      },
    },
  ],
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
