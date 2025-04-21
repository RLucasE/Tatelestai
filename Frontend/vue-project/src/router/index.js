import { createRouter, createWebHistory } from "vue-router";
import Register from "@/views/auth/register.vue";
import HomeLayout from "@/components/layouts/HomeLayout.vue";
import Login from "@/views/auth/login.vue";
import DashboardLayout from "@/components/layouts/DashboardLayout.vue";
import CustomerLayout from "@/components/layouts/customer/CustomerLayout.vue";
import CustomerCard from "@/components/layouts/customer/CustomerCard.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      component: HomeLayout,
      children: [
        { path: "register", component: Register },
        { path: "login", component: Login },
      ],
    },

    {
      path: "/customer",
      name: "customer",
      component: CustomerLayout,
      children: [{ path: "", name: "main", component: CustomerCard }],
    },
  ],
});

export default router;
