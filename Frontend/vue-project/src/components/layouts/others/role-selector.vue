<script setup>
import axiosInstance from "@/lib/axios";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const role = ref("unknown_role");
const authStore = useAuthStore();

const selectRole = (role_selected) => {
  role.value = role_selected;
  console.log("Selected role:", role.value);
};

const hadleElection = async () => {
  if (role.value === "unknown_role") {
    alert("Por favor, seleccione un rol.");
    return;
  }

  try {
    const response = await axiosInstance.post("/select-role", {
      role: role.value,
    });
    console.log("Role selection response:", response.data);
    await authStore.refreshUser();
    router.push({
      name: role.value === "customer" ? "customer" : "seller",
    });
  } catch (error) {
    console.error("Error selecting role:", error);
    alert("Hubo un error al seleccionar el rol. Inténtalo de nuevo.");
  }
};
</script>

<template>
  <main class="customer-cards-box">
    <div class="choice-container">
      <div
        class="card"
        @click="selectRole('customer')"
        :class="{ selected: role === 'customer' }"
      >
        Comprador
      </div>

      <div
        class="card"
        @click="selectRole('seller')"
        :class="{ selected: role === 'seller' }"
      >
        Vendedor
      </div>
    </div>

    <button @click="hadleElection">confirmar</button>
  </main>
</template>

<style scoped>
.customer-cards-box {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: var(--color-bg);
  height: 100vh;
}

.customer-cards-box button {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 10px 20px;
  background-color: var(--color-primary);
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.choice-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4rem;
  height: 700px;
  width: 70%;
}

.card {
  background-color: var(--color-primary);
  display: inline;
  width: 25%;
  height: 90%;
  cursor: pointer;
  transition: background 0.2s;
}

.card.selected {
  background-color: var(
    --color-darkest
  ); /* Cambia este color para el seleccionado */
  color: #fff;
}
</style>
