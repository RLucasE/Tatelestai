import {defineStore} from "pinia";
import {ref} from "vue";

export const useStorageStore = defineStore(
    "storage",
    () => {
        let user = ref(null);

        function getUser() {
            return user;
        }

        function clearUser() {
            user.value = null;
        }

        return {getUser, clearUser};
    },
    {
        ppersist: {
            key: "storage", // Clave para guardar en localStorage o sessionStorage
            storage: localStorage, // Cambia a sessionStorage si prefieres que no sea persistente entre sesiones
        },
    }
);
