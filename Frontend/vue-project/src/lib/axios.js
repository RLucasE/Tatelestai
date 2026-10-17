import axios from "axios";
import router from "@/router";

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
    if (error.response) {
      const status = error.response.status;

      if (router) {
        if (status === 401) {
          localStorage.removeItem("authToken");
          sessionStorage.removeItem("userData");

          try {
            await router.push({ name: "login" });
          } catch (err) {
            console.error("Error al redirigir:", err);
            window.location.href = "/login"; // ← Fallback con recarga de página
          }
        } else if (status === 403) {
          router.push({ name: "login" });
        } else if (status === 404) {
          // No redirigir a página global 404 en operaciones de mutación o peticiones que indiquen omitirlo
          const method = error.config?.method?.toLowerCase();
          const isMutation = ["post", "put", "patch", "delete"].includes(method);
          const skipRedirect = Boolean(error.config?.skipNotFoundRedirect);

          if (!isMutation && !skipRedirect) {
            router.push({ name: "not-found" });
          }
        } else if (status === 500) {
          // Handle server error
          router.push({ name: "server-error" });
        }
      }
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
