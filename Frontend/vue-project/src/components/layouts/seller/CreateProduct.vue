<template>
  <MessageDialog
    :visible="showCreatedDialog"
    :message="'¡Producto creado exitosamente!'"
    @accept="changeDialogVisibility"
  />
  <div class="create-product-container">
    <h2>Crear Nuevo Producto</h2>
    <form @submit.prevent="createProduct" class="product-form">
      <div class="form-group">
        <label for="name">Nombre del Producto</label>
        <input
          type="text"
          id="name"
          v-model="productData.name"
          required
          placeholder="Ingrese el nombre del producto"
        />
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea
          id="description"
          v-model="productData.description"
          placeholder="Describa el producto"
        ></textarea>
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? "Creando..." : "Crear Producto" }}
      </button>
    </form>
    <div v-if="error" class="error-message">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axiosInstance from "@/lib/axios";
import MessageDialog from "@/components/common/MessageDialog.vue";

const productData = ref({
  name: "",
  description: "",
});

const loading = ref(false);
const error = ref(null);
const showCreatedDialog = ref(false);

const changeDialogVisibility = () => {
  showCreatedDialog.value = !showCreatedDialog.value;
};

const createProduct = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axiosInstance.post("/product", productData.value);
    productData.value = {
      name: "",
      description: "",
    };
    console.log("se creó con exito");
    changeDialogVisibility();
    console.log("la función changeDialogVisibility se ejecutó");
  } catch (err) {
    error.value = err.response?.data?.message || "Error al crear el producto";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.create-product-container {
  max-width: 800px;
  width: 20%;
  margin: 2rem auto;
  padding: 2rem;
  background-color: var(--color-secondary);
  border-radius: 8px;
}

h2 {
  color: var(--color-text);
  margin-bottom: 2rem;
  text-align: center;
}

.product-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

label {
  color: var(--color-text);
  font-weight: bold;
}

input,
textarea {
  padding: 0.75rem;
  border: 1px solid var(--color-primary);
  border-radius: 4px;
  background-color: var(--color-primary);
  color: var(--color-text);
}

textarea {
  min-height: 100px;
  resize: vertical;
}

button {
  padding: 1rem;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  transition: opacity 0.3s;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-message {
  color: #ff4444;
  margin-top: 1rem;
  text-align: center;
}
</style>
