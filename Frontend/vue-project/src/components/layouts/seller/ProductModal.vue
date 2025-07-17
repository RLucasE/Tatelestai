<template>
  <div v-if="visible" class="modal-overlay">
    <div class="product-modal">
      <h2>{{ mode === "edit" ? "Editar Producto" : "Eliminar Producto" }}</h2>

      <form
        v-if="mode === 'edit'"
        @submit.prevent="handleSubmit"
        class="product-form"
      >
        <div class="form-group">
          <label for="name">Nombre del Producto</label>
          <input
            type="text"
            id="name"
            v-model="productData.name"
            required
            placeholder="Nombre del producto"
          />
        </div>

        <div class="form-group">
          <label for="description">Descripción</label>
          <textarea
            id="description"
            v-model="productData.description"
            placeholder="Descripción del producto"
          ></textarea>
        </div>

        <div class="modal-actions">
          <button type="submit" :disabled="loading">
            {{ loading ? "Guardando..." : "Guardar Cambios" }}
          </button>
          <button type="button" @click="closeModal" class="cancel-button">
            Cancelar
          </button>
        </div>
      </form>

      <div v-else class="delete-confirmation">
        <p>¿Estás seguro de eliminar "{{ productData.name }}"?</p>
        <div class="modal-actions">
          <button
            @click="handleDelete"
            :disabled="loading"
            class="delete-button"
          >
            {{ loading ? "Eliminando..." : "Eliminar" }}
          </button>
          <button @click="closeModal" class="cancel-button">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  visible: Boolean,
  product: Object,
  mode: { type: String, default: "edit" }, // 'edit' or 'delete'
});

const emit = defineEmits(["update:visible", "success"]);

const productData = ref({
  id: null,
  name: "",
  description: "",
});

watch(
  () => props.product,
  (newProduct) => {
    if (newProduct) {
      productData.value = {
        id: newProduct.id,
        name: newProduct.name,
        description: newProduct.description,
      };
    }
  },
  { immediate: true, deep: true } // Añade estas opciones
);

const loading = ref(false);

const closeModal = () => {
  emit("update:visible", false);
};

const handleSubmit = async () => {
  loading.value = true;
  let response;
  try {
    response = await axiosInstance.patch(
      `/products/${productData.value.id}`,
      productData.value
    );
    console.log("Respuesta del servidor:", response.data.product);
    emit("success", { ...response.data.product }, "edit");
    closeModal();
  } catch (error) {
    console.error("Error al actualizar el producto:", error);
  } finally {
    loading.value = false;
  }
};

const handleDelete = async () => {
  loading.value = true;
  let response;
  try {
    response = await axiosInstance.delete(`/products/${productData.value.id}`);
    console.log(response.data);
    closeModal();
  } catch (error) {
    console.error("Error al eliminar el producto:", error);
  } finally {
    loading.value = false;
  }
};

const consoleLogObjectAfter3 = async () => {
  setTimeout(() => {
    console.log(productData.value);
  }, 3000);
};

consoleLogObjectAfter3();
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

.cancel-button {
  background-color: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-primary);
}

.delete-button {
  background-color: var(--color-darkest);
  color: white;
}

.delete-confirmation {
  text-align: center;
}

.delete-confirmation p {
  color: var(--color-text);
  margin-bottom: 2rem;
}
</style>
