// stores/users.js
import { defineStore } from "pinia";
import axiosInstance from "@/lib/axios";
import { loginLogic, registerLogic } from "@/lib/authentication";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
  }),
  getters: {
    isLoggedIn: (state) => !!state.user,
  },
  actions: {
    async fetchUser() {
      try {
        const data = await axiosInstance.get("/user");
        console.log("Fetched user", data.data);
        this.user = data.data;
      } catch (error) {
        console.log(error);
      }
    },
    async login(data) {
      try {
        await loginLogic(data);
        await this.fetchUser();
      } catch (error) {
        console.error("login error in auth store", error);
        throw error;
      }
    },
    async register(data) {
      try {
        await registerLogic(data);
        await this.fetchUser();
      } catch (error) {
        console.error("register error in auth store", error);
        throw error;
      }
    },
  },
});
