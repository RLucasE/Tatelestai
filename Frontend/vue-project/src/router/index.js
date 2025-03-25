import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import Register from "@/views/auth/register.vue";
import Login from "@/views/auth/login.vue";
import Dashboard from "@/views/auth/dashboard.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: HomeView,
    },
    {
      path: "/register",
      name: "register",
      component: Register,
    },
    {
      path: "/login",
      name: "login",
      component: Login,
    },
    {
      path: "/dashboard",
      name: "dashboard",
      component: Dashboard,
    },
  ],
});

export default router;
