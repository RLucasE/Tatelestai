import { createRouter, createWebHistory } from "vue-router";
import Register from "@/views/auth/register.vue";
import HomeLayout from "@/components/layouts/HomeLayout.vue";
import Login from "@/views/auth/login.vue";
import CustomerLayout from "@/components/layouts/customer/CustomerLayout.vue";
import CustomerCard from "@/components/layouts/customer/CustomerCard.vue";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      component: HomeLayout,
      children: [
        { path: "register", component: Register },
        { name: "login", path: "login", component: Login },
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

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  // Rutas públicas
  const publicRoutes = ["/", "/login", "/register"];

  // Verifica si la ruta es pública
  if (publicRoutes.includes(to.path)) {
    return next(); // Permite el acceso
  }

  // Si la ruta no es pública, verifica si el usuario está autenticado
  if (!authStore.isLoggedIn) {
    return next({ name: "login" }); // Redirige al login si no está autenticado
  }

  // Permite el acceso a rutas protegidas si el usuario está autenticado
  next();
});

export default router;
