import axios from "axios";

let router;

const axiosInstance = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true,
  withXSRFToken: true,
});

export function setAxiosRouter(r) {
  router = r;
}

axiosInstance.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response) {
      const status = error.response.status;
      const data = error.response.data;

      if (status === 401) {
        // Handle unauthorized access
        router.push({ name: "login" });
      } else if (status === 403) {
        // Handle forbidden access
        router.push({ name: "forbidden" });
      } else if (status === 404) {
        // Handle not found
        router.push({ name: "not-found" });
      } else if (status === 500) {
        // Handle server error
        router.push({ name: "server-error" });
      }
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
