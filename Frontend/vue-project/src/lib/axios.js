import axios from "axios";
import router from "@/router";
import { useAuthStore } from "@/stores/auth";
import {
  getApiErrorCode,
  getApiErrorMessage,
  getApiRequestId,
} from "@/utils/apiError";

const axiosInstance = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true,
  withXSRFToken: true,
});

axiosInstance.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    // Enriquecer el error con propiedades normalizadas para consumo directo en vistas y stores
    error.userMessage = getApiErrorMessage(error);
    error.errorCode = getApiErrorCode(error);
    error.requestId = getApiRequestId(error);

    if (error.response) {
      const status = error.response.status;
      const method = error.config?.method?.toLowerCase();
      const isMutation = ["post", "put", "patch", "delete"].includes(method);

      if (router) {
        if (status === 401) {
          const authStore = useAuthStore();
          authStore.clearLocalSession();

          const skipAuthRedirect = Boolean(error.config?.skipAuthRedirect);
          const currentRouteName = router.currentRoute.value?.name;
          const isAuthRoute = currentRouteName === "login" || currentRouteName === "register";

          if (!skipAuthRedirect && !isAuthRoute) {
            try {
              await router.replace({ name: "login" });
            } catch (err) {
              console.error("Error al redirigir al login tras 401:", err);
            }
          }
        } else if (status === 403) {
          const authStore = useAuthStore();
          const currentState = error.response.data?.current_state;

          // Sincronización en caliente ante rechazo por estado de usuario (CheckUserState)
          if (currentState) {
            if (currentState === "guest") {
              authStore.clearLocalSession();
              router.replace({ name: "login" });
            } else {
              authStore.setState(currentState);
              router.replace(authStore.getDefaultRoute());
            }
          } else {
            // No redirigir a login en mutaciones o errores 403 de regla de negocio
            const skipForbiddenRedirect = Boolean(error.config?.skipForbiddenRedirect);
            const isBusinessForbidden = Boolean(error.errorCode && error.errorCode !== "FORBIDDEN");

            if (!isMutation && !skipForbiddenRedirect && !isBusinessForbidden) {
              router.replace({ name: "login" });
            }
          }
        } else if (status === 404) {
          const skipRedirect = Boolean(error.config?.skipNotFoundRedirect);

          if (!isMutation && !skipRedirect) {
            router.push({ name: "not-found" });
          }
        } else if (status === 500) {
          const skipServerErrorRedirect = Boolean(error.config?.skipServerErrorRedirect);

          if (!isMutation && !skipServerErrorRedirect) {
            router.push({ name: "server-error" });
          }
        }
      }
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
