import { createRouter, createWebHistory } from "vue-router";
import customerRoutes from "./modules/customer-routes";
import noAuthRoutes from "./modules/no-auth-routes";
import sellerRoutes from "./modules/seller-routes";
import otherRoutes from "./modules/other-routes";
import adminRoutes from "./modules/admin-route";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    ...noAuthRoutes,
    ...customerRoutes,
    ...sellerRoutes,
    ...adminRoutes,
    ...otherRoutes,
  ],
});

/**
 * Valida los permisos de rol y sub-estados requeridos por la ruta.
 * Retorna true para permitir la navegación o un descriptor de ruta para redirigir.
 */
function checkRouteAccess(to, authStore) {
  const hasMeta = (key) => to.matched.some((record) => Boolean(record.meta?.[key]));

  // 1. Si la cuenta está inactiva, solo puede acceder a /inactive
  if (authStore.inactive()) {
    return hasMeta("requiresInactive") ? true : { name: "inactive" };
  }

  // 2. Si no está inactivo pero navega a /inactive, enviarlo a su pantalla de trabajo
  if (hasMeta("requiresInactive")) {
    return authStore.getDefaultRoute();
  }

  // 3. Rutas de Administrador
  if (hasMeta("requiresAdmin")) {
    return authStore.isAdmin() ? true : authStore.getDefaultRoute();
  }

  // 4. Rutas de Comprador (Customer)
  if (hasMeta("requiresCustomer")) {
    return authStore.isCustomer() ? true : authStore.getDefaultRoute();
  }

  // 5. Rutas de Vendedor (Seller)
  if (hasMeta("requiresSeller")) {
    if (!authStore.isSeller()) {
      return authStore.getDefaultRoute();
    }

    if (hasMeta("requiresRegisteringEstablishment")) {
      return authStore.registeringEstablishment() ? true : authStore.getDefaultRoute();
    }
    if (hasMeta("requiresWaitingConfirmation")) {
      return authStore.waitingConfirmation() ? true : authStore.getDefaultRoute();
    }
    if (hasMeta("requiresDeniedConfirmation")) {
      return authStore.deniedConfirmation() ? true : authStore.getDefaultRoute();
    }
    if (hasMeta("requiresActive")) {
      return authStore.active() ? true : authStore.getDefaultRoute();
    }

    // Por defecto las rutas de vendedor requieren estado activo
    return authStore.active() ? true : authStore.getDefaultRoute();
  }

  // 6. Rutas de Rol por defecto (selección de rol)
  if (hasMeta("requiresDefault")) {
    if (authStore.isDefaultRole() && hasMeta("requiresSelectingRole") && authStore.selectingRole()) {
      return true;
    }
    return authStore.getDefaultRoute();
  }

  // Si la ruta protegida no tiene requerimientos específicos de rol
  return true;
}

router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  // Asegura la verificación de sesión (0ms si RAM está presente o si no hay pista de sesión en localStorage)
  await authStore.ensureSessionVerified();

  const isExplicitPublicRoute = to.matched.every((record) => record.meta?.requiresAuth === false);
  const isLoggedIn = authStore.isLoggedIn();

  // Caso A: Ruta pública (ej: /login, /register)
  if (isExplicitPublicRoute) {
    if (isLoggedIn) {
      const target = authStore.getDefaultRoute();
      if (target && target.name !== to.name) {
        return target;
      }
    }
    return true;
  }

  // Caso B: Ruta protegida
  if (!isLoggedIn) {
    return { name: "login" };
  }

  // Caso C: Usuario autenticado accediendo a ruta protegida
  const accessResult = checkRouteAccess(to, authStore);

  // Evitar auto-redirecciones cíclicas si el destino coincide con la ruta actual
  if (accessResult && typeof accessResult === "object" && accessResult.name === to.name) {
    return true;
  }

  return accessResult;
});

export default router;
