<template>
  <CreateProductModal
    :visible="createModalVisivility"
    @update:visible="changeCreateModalVisibility"
    @success="handleProductsChange"
  >
  </CreateProductModal>

  <ProductModal
    :product="{ ...product }"
    :mode="modalMode"
    :visible="modalVisivility"
    @update:visible="(newValue) => (modalVisivility = newValue)"
    @success="handleProductsChange"
  ></ProductModal>

  <div class="product-list-container">
    <div class="actions">
      <!-- <RouterLink to="/seller/create-product" class="add-button">
        Agregar Nuevo Producto
      </RouterLink> -->
      <button @click="changeCreateModalVisibility" class="add-button">
        Agregar Otro Producto
      </button>
    </div>

    <div v-if="loading" class="loading">Cargando productos...</div>

    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-else-if="!products.length" class="no-products">
      No tienes productos registrados aún.
    </div>

    <div v-else class="products-grid">
      <div v-for="product in products" :key="product.id" class="product-card">
        <h3>{{ product.name }}</h3>
        <p class="description">
          {{ product.description || "Sin descripción" }}
        </p>
        <div class="actions">
          <button @click="editProduct(product)" class="edit-button">
            Editar
          </button>
          <button @click="deleteProduct(product)" class="delete-button">
            Quitar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axiosInstance from "@/lib/axios";
import ProductModal from "./ProductModal.vue";
import CreateProductModal from "./CreateProductModal.vue";

const products = ref([]);
const product = ref({});
const loading = ref(true);
const error = ref(null);
const modalMode = ref("");
const modalVisivility = ref(false);
const createModalVisivility = ref(false);

const fetchProducts = async () => {
  try {
    const response = await axiosInstance.get("/products");
    products.value = response.data.products;
  } catch (err) {
    error.value = "Error al cargar los productos";
    console.error("Error:", err);
  } finally {
    loading.value = false;
  }
};

const handleProductsChange = (product, mode) => {
  if (!product) return null;

  if (mode === "create") {
    products.value.unshift(product);
  }
  if (mode === "edit") {
    const index = products.value.findIndex((p) => p.id === product.id);
    if (index !== -1) {
      products.value[index] = product;
    }
  }
  if (mode === "delete") {
    const index = products.value.findIndex((p) => p.id === product.id);
    if (index !== -1) {
      products.value.splice(index, 1);
    }
  }
};

const changeCreateModalVisibility = (value) => {
  createModalVisivility.value = value;
};

const editProduct = (selected) => {
  modalMode.value = "edit";
  product.value = { ...selected }; // Asegura copia
  modalVisivility.value = true;
};

const deleteProduct = (selected) => {
  modalMode.value = "delete";
  product.value = { ...selected }; // Cambia a copia aquí también
  modalVisivility.value = true;
};

onMounted(() => {
  fetchProducts();
});
</script>

<style scoped>
.product-list-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

h2 {
  color: var(--color-text);
  margin-bottom: 2rem;
  text-align: center;
}

.actions {
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
}

.add-button {
  background-color: var(--color-primary);
  color: var(--color-text);
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  text-decoration: none;
  display: inline-block;
}

.products-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 2rem;
  overflow-x: auto;
  padding-bottom: 1rem;
}

.product-card {
  min-width: 300px;
  max-width: 30%;
  flex: 0 0 auto;
  background-color: var(--color-secondary);
  border-radius: 8px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
}

.product-card h3 {
  margin: 0 0 1rem 0;
  color: var(--color-text);
}

.description {
  color: var(--color-text);
  margin-bottom: 1.5rem;
  flex-grow: 1;
}

.product-card .actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 0;
}

button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  flex: 1;
}

.edit-button {
  background-color: var(--color-primary);
  color: var(--color-text);
}

.delete-button {
  background-color: var(--color-darkest);
  color: white;
}

.cancel-button {
  background-color: var(--color-secondary);
  color: var(--color-text);
}

.loading,
.error-message,
.no-products {
  text-align: center;
  padding: 2rem;
  color: var(--color-text);
}

.error-message {
  color: #ff4444;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background-color: var(--color-darkest);
  padding: 2rem;
  border-radius: 8px;
  max-width: 400px;
  width: 90%;
}

.modal h3 {
  color: var(--color-text);
  margin-top: 0;
}

.modal p {
  color: var(--color-text);
  margin-bottom: 1.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
