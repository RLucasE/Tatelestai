// stores/auth.js
import { defineStore } from "pinia";
import { ref } from "vue";
import axiosInstance from "@/lib/axios";
import { USER_STATES, isValidState } from "@/constants/userStates.js";

const SESSION_HINT_KEY = "tatelestai_has_session";
const SANCTUM_BASE_URL = "http://localhost:8000";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null); // Única fuente de verdad en memoria RAM
  const isAuthenticated = ref(false);
  const isSessionChecked = ref(false);

  // Promesa para deduplicación de vuelo único (single-flight)
  let fetchUserPromise = null;

  function hasSessionHint() {
    try {
      return Boolean(localStorage.getItem(SESSION_HINT_KEY));
    } catch {
      return false;
    }
  }

  function setSessionHint() {
    try {
      localStorage.setItem(SESSION_HINT_KEY, "1");
    } catch {}
  }

  function removeSessionHint() {
    try {
      localStorage.removeItem(SESSION_HINT_KEY);
      localStorage.removeItem("authToken");
      sessionStorage.removeItem("userData");
    } catch {}
  }

  function clearLocalSession() {
    user.value = null;
    isAuthenticated.value = false;
    isSessionChecked.value = true;
    removeSessionHint();
  }

  function getUser() {
    return user.value;
  }

  function isLoggedIn() {
    return user.value !== null;
  }

  // Helpers de verificación de roles seguros ante nulos
  function hasRole(roleName) {
    return Array.isArray(user.value?.roles) && user.value.roles.includes(roleName);
  }

  function isCustomer() {
    return hasRole("customer");
  }

  function isAdmin() {
    return hasRole("admin");
  }

  function isSeller() {
    return hasRole("seller");
  }

  function isDefaultRole() {
    return hasRole("default");
  }

  // Helpers de verificación de estados
  function selectingRole() {
    return user.value?.state === USER_STATES.SELECTING || user.value?.state === "selecting";
  }

  function registeringEstablishment() {
    return user.value?.state === USER_STATES.REGISTERING || user.value?.state === "registering";
  }

  function waitingConfirmation() {
    return user.value?.state === USER_STATES.WAITING_FOR_CONFIRMATION || user.value?.state === "waiting_for_confirmation";
  }

  function deniedConfirmation() {
    return user.value?.state === USER_STATES.DENIED_CONFIRMATION || user.value?.state === "denied_confirmation";
  }

  function active() {
    return user.value?.state === USER_STATES.ACTIVE || user.value?.state === "active";
  }

  function inactive() {
    return user.value?.state === USER_STATES.INACTIVE || user.value?.state === "inactive";
  }

  /**
   * Determina la ruta por defecto según rol y estado del usuario autenticado.
   * Centraliza la matriz de redirección para evitar discrepancias.
   */
  function getDefaultRoute() {
    if (!isLoggedIn()) {
      return { name: "login" };
    }

    // 1. Inactivo prevalece para cualquier usuario
    if (inactive()) {
      return { name: "inactive" };
    }

    // 2. Administrador
    if (isAdmin()) {
      return { name: "admin-dashboard" };
    }

    // 3. Cliente
    if (isCustomer()) {
      return { name: "customer-offers" };
    }

    // 4. Vendedor (5 sub-estados)
    if (isSeller()) {
      if (registeringEstablishment()) {
        return { name: "register-establishment" };
      }
      if (waitingConfirmation()) {
        return { name: "waiting-confirmation" };
      }
      if (deniedConfirmation()) {
        return { name: "denied-confirmation" };
      }
      return { name: "seller-dashboard" };
    }

    // 5. Rol por defecto (selección de rol)
    if (isDefaultRole()) {
      return { name: "select-role" };
    }

    // Fallback defensivo
    return { name: "login" };
  }

  /**
   * Petición de usuario al backend con deduplicación de vuelo único (single-flight).
   */
  async function fetchUser() {
    if (fetchUserPromise) {
      return fetchUserPromise;
    }

    fetchUserPromise = (async () => {
      try {
        const response = await axiosInstance.get("/user", { skipAuthRedirect: true });
        const userData = response.data;
        user.value = userData;
        isAuthenticated.value = true;
        setSessionHint();
        return userData;
      } catch (error) {
        clearLocalSession();
        throw error;
      } finally {
        isSessionChecked.value = true;
        fetchUserPromise = null;
      }
    })();

    return fetchUserPromise;
  }

  /**
   * Verificación rápida y confiable de sesión:
   * - Si ya tenemos los datos en memoria: retorna en 0ms.
   * - Si no hay señal de sesión (localStorage hint): retorna null en 0ms sin peticiones HTTP.
   * - Si hay señal pero la memoria está vacía (F5 / recarga): llama a fetchUser() una única vez.
   */
  async function ensureSessionVerified() {
    if (user.value) {
      return user.value;
    }

    if (!hasSessionHint()) {
      isSessionChecked.value = true;
      return null;
    }

    try {
      return await fetchUser();
    } catch (error) {
      return null;
    }
  }

  async function refreshUser() {
    try {
      return await fetchUser();
    } catch (error) {
      console.log("Error refreshing user in auth store:", error);
      return null;
    }
  }

  async function ensureCsrfCookie() {
    await axiosInstance.get("/sanctum/csrf-cookie", {
      baseURL: SANCTUM_BASE_URL,
      skipAuthRedirect: true,
    });
  }

  async function login(credentials) {
    try {
      await ensureCsrfCookie();
      await axiosInstance.post("/login", credentials);
      setSessionHint();
      return await fetchUser();
    } catch (error) {
      clearLocalSession();
      console.error("Login error in auth store:", error);
      throw error;
    }
  }

  async function register(data) {
    try {
      await ensureCsrfCookie();
      await axiosInstance.post("/register", data);
      setSessionHint();
      return await fetchUser();
    } catch (error) {
      clearLocalSession();
      console.error("Register error in auth store:", error);
      throw error;
    }
  }

  async function logout() {
    try {
      await axiosInstance.post("/logout", {}, { skipAuthRedirect: true });
    } catch (error) {
      console.error("Error al hacer logout en el servidor:", error);
    } finally {
      clearLocalSession();
    }
  }

  function setState(state) {
    if (user.value && isValidState(state)) {
      user.value.state = state;
    }
  }

  async function cancelSellerRegistration() {
    try {
      const response = await axiosInstance.post("/cancel-seller-registration");
      await refreshUser();
      return response.data;
    } catch (error) {
      console.error("Error canceling seller registration:", error);
      throw error;
    }
  }

  return {
    user,
    isAuthenticated,
    isSessionChecked,
    getUser,
    isLoggedIn,
    isCustomer,
    isAdmin,
    isSeller,
    isDefaultRole,
    selectingRole,
    registeringEstablishment,
    waitingConfirmation,
    deniedConfirmation,
    active,
    inactive,
    getDefaultRoute,
    ensureSessionVerified,
    fetchUser,
    refreshUser,
    login,
    register,
    logout,
    clearLocalSession,
    setState,
    cancelSellerRegistration,
  };
});
