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
      const data = error.response.data;

      console.log("Unauthorized access", status);
      console.log("Router:", router);
      console.log("Rutas disponibles:", router.getRoutes());
      localStorage.removeItem("authToken");
      sessionStorage.removeItem("userData");

      if (router) {
        if (status === 401) {
          // Handle unauthorized access
          console.log("a");
          try {
            await router.push({ name: "login" });
            console.log("b");
          } catch (err) {
            console.error("Error al redirigir:", err);
            window.location.href = "/login"; // ← Fallback con recarga de página
          }
        } else if (status === 403) {
          // Handle forbidden access
          router.push({ name: "login" });
        } else if (status === 404) {
          // Handle not found
          router.push({ name: "not-found" });
        } else if (status === 500) {
          // Handle server error
          router.push({ name: "server-error" });
        }
      } else {
        console.log("Router is not set");
      }
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
