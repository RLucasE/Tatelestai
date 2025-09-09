import {createRouter, createWebHistory} from "vue-router";
import customerRoutes from "./modules/customer-routes";
import noAuthRoutes from "./modules/no-auth-routes";
import sellerRoutes from "./modules/seller-routes";
import otherRoutes from "./modules/other-routes";
import adminRoutes from "./modules/admin-route";
import {useAuthStore} from "@/stores/auth";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [...noAuthRoutes, ...customerRoutes, ...sellerRoutes, ...adminRoutes, ...otherRoutes],
});

function redirectByRole(authStore) {
    // Si no está logueado, no redirige.
    if (!authStore.isLoggedIn()) return null;

    // Cliente
    if (authStore.isCustomer()) return {name: "customer-offers"};

    // Vendedor
    if (authStore.isSeller()) {
        if (authStore.registeringEstablishment())
            return {name: "register-establishment"};
        if (authStore.waitingConfirmation())
            return {name: "waiting-confirmation"};
        if (authStore.deniedConfirmation())
            return {name: "denied-confirmation"};
        if (authStore.inactive()) return {name: "inactive"};
        return {name: "seller"};
    }

    if (authStore.isAdmin()) {
        return {name: "admin"};
    }

    // Rol por defecto
    if (authStore.isDefaultRole()) {
        if (authStore.selectingRole()) return {name: "select-role"};
        return {path: "/"};
    }

    // Fallback
    return {path: "/"};
}

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (!to.meta.requiresAuth) {
        try {
            await authStore.heavyVerifySession();
            const dest = redirectByRole(authStore);
            if (dest) return next(dest);
            return next();
        } catch (error) {
            return next();
        }
    }

    if (to.meta.requiresAuth) {
        try {
            await authStore.verifySession();
        } catch (error) {
            console.log(error);
        }
        if (authStore.isLoggedIn()) {
            switch (true) {
                case to.meta.requiresCustomer && authStore.isCustomer():
                    next();
                    break;
                case to.meta.requiresSeller && authStore.isSeller():
                    // Manage seller-specific states
                    switch (true) {
                        case to.meta.requiresRegisteringEstablishment &&
                        authStore.registeringEstablishment():
                            next();
                            break;
                        case to.meta.requiresWaitingConfirmation &&
                        authStore.waitingConfirmation():
                            next();
                            break;
                        case to.meta.requiresDeniedConfirmation &&
                        authStore.deniedConfirmation():
                            next();
                            break;
                        case to.meta.requiresActive && authStore.active():
                            next();
                            break;
                        default: {
                            const dest = redirectByRole(authStore);
                            if (dest) return next(dest);
                            return next();
                        }
                    }
                    break;
                case to.meta.requiresDefault && authStore.isDefaultRole():
                    if (to.meta.requiresSelectingRole && authStore.selectingRole()) {
                        next();
                    }
                    break;
                case to.meta.requiresAdmin && authStore.isAdmin():
                    next();
                    break;
                case to.meta.requiresInactive && authStore.inactive():
                    next();
                    break;
                default: {
                    const dest = redirectByRole(authStore);
                    if (dest) return next(dest);
                    return next();
                }
            }
        }

        if (!authStore.isLoggedIn()) {
            return next({name: "login"});
        }
    }
});

export default router;
