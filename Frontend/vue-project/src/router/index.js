import { createRouter, createWebHistory } from "vue-router";
import Register from "@/views/auth/register.vue";
import HomeLayout from "@/components/layouts/HomeLayout.vue";
import Login from "@/views/auth/login.vue";
import DashboardLayout from "@/components/layouts/DashboardLayout.vue";
import CustomerLayout from "@/components/layouts/customer/CustomerLayout.vue";
import Card from "@/components/card.vue";

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
      path: "/dashboard",
      name: "dashboard",
      component: CustomerLayout,
      children: [{ path: "", name: "main", component: Card }],
    },
  ],
});

export default router;
