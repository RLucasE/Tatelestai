<template>
  <div v-if="visible" class="modal-overlay">
    <div class="product-modal">
      <h2>Crear Nuevo Producto</h2>

      <form @submit.prevent="handleSubmit" class="product-form">
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

        <div class="modal-actions">
          <button type="submit" :disabled="loading">
            {{ loading ? "Creando..." : "Crear Producto" }}
          </button>
          <button type="button" @click="closeModal" class="cancel-button">
            Cancelar
          </button>
        </div>
      </form>
      <div v-if="error" class="error-message">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  visible: Boolean,
});

const emit = defineEmits(["update:visible", "success"]);

const productData = ref({
  name: "",
  description: "",
});

const loading = ref(false);
const error = ref(null);

const closeModal = () => {
  emit("update:visible", false);
  // Resetear el formulario al cerrar
  productData.value = {
    name: "",
    description: "",
  };
  error.value = null;
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axiosInstance.post("/product", productData.value);
    console.log("Producto creado:", response.data);
    emit("success", response.data.product, "create");
    closeModal();
  } catch (err) {
    error.value = err.response?.data?.message || "Error al crear el producto";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.product-modal {
  background-color: var(--color-secondary);
  border-radius: 8px;
  padding: 2rem;
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
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

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  flex: 1;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

button[type="submit"] {
  background-color: var(--color-primary);
  color: var(--color-text);
}

.cancel-button {
  background-color: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-primary);
}

.error-message {
  color: #ff4444;
  margin-top: 1rem;
  text-align: center;
}
</style>
