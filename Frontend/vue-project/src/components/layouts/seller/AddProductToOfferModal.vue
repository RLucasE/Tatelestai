<template>
  <div v-if="visible" class="modal-overlay">
    <div class="product-modal">
      <h2>Agregar Producto a la Oferta</h2>

      <form @submit.prevent="handleSubmit" class="product-form">
        <div class="form-group">
          <label for="productSelect">Seleccionar Producto</label>
          <select
            id="productSelect"
            v-model="selectedProductId"
            required
            class="product-select"
          >
            <option value="">Selecciona un producto</option>
            <option
              v-for="product in availableProducts"
              :key="product.id"
              :value="product.id"
            >
              {{ product.name }}
            </option>
          </select>
        </div>

        <div class="form-group" v-if="selectedProduct">
          <label>Producto Seleccionado</label>
          <div class="selected-product-info">
            <h4>{{ selectedProduct.name }}</h4>
            <p>{{ selectedProduct.description }}</p>
          </div>
        </div>

        <div class="form-group">
          <label for="quantity">Cantidad</label>
          <input
            type="number"
            id="quantity"
            v-model="quantity"
            required
            placeholder="Cantidad del producto"
            min="1"
          />
        </div>

        <div class="form-group">
          <label for="productPrice">Precio Unitario en la Oferta</label>
          <input
            type="number"
            id="productPrice"
            v-model="productPrice"
            required
            placeholder="Precio del producto en esta oferta"
            min="0"
            step="0.01"
          />
        </div>

        <div class="modal-actions">
          <button type="submit" :disabled="loading || !selectedProductId">
            {{ loading ? "Agregando..." : "Agregar Producto" }}
          </button>
          <button type="button" @click="closeModal" class="cancel-button">
            Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  visible: Boolean,
  excludedProductIds: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:visible", "productAdded"]);

// Variables reactivas
const selectedProductId = ref("");
const quantity = ref(1);
const productPrice = ref(0);
const loading = ref(false);
const products = ref([]);

// Computed para productos disponibles (excluyendo los ya agregados)
const availableProducts = computed(() => {
  return products.value.filter(
    (product) => !props.excludedProductIds.includes(product.id)
  );
});

// Computed para el producto seleccionado
const selectedProduct = computed(() => {
  return products.value.find(
    (product) => product.id == selectedProductId.value
  );
});

// Watch para actualizar el precio cuando se selecciona un producto
watch(selectedProduct, (newProduct) => {
  if (newProduct) {
    productPrice.value = newProduct.price;
  }
});

// Función para obtener productos
const fetchProducts = async () => {
  try {
    const response = await axiosInstance.get("/products");
    products.value = response.data.products || response.data;
  } catch (error) {
    console.error("Error al obtener productos:", error);
  }
};

// Función para cerrar el modal
const closeModal = () => {
  emit("update:visible", false);
  resetForm();
};

// Función para resetear el formulario
const resetForm = () => {
  selectedProductId.value = "";
  quantity.value = 1;
  productPrice.value = 0;
};

// Función para manejar el envío del formulario
const handleSubmit = async () => {
  if (!selectedProduct.value) return;

  const productToAdd = {
    id: selectedProduct.value.id,
    name: selectedProduct.value.name,
    description: selectedProduct.value.description,
    originalPrice: selectedProduct.value.price,
    quantity: quantity.value,
    price: productPrice.value,
    total: quantity.value * productPrice.value,
  };

  emit("productAdded", productToAdd);
  closeModal();
};

// Cargar productos al montar el componente
onMounted(() => {
  fetchProducts();
});
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
  max-height: 90vh;
  overflow-y: auto;
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
textarea,
select {
  padding: 0.75rem;
  border: 1px solid var(--color-primary);
  border-radius: 4px;
  background-color: var(--color-primary);
  color: var(--color-text);
}

.product-select {
  cursor: pointer;
}

.selected-product-info {
  background-color: var(--color-primary);
  padding: 1rem;
  border-radius: 4px;
  border: 1px solid var(--color-accent);
}

.selected-product-info h4 {
  color: var(--color-text);
  margin: 0 0 0.5rem 0;
}

.selected-product-info p {
  color: var(--color-text);
  opacity: 0.8;
  margin: 0 0 0.5rem 0;
}

.price {
  color: var(--color-accent);
  font-weight: bold;
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

button[type="submit"] {
  background-color: var(--color-primary);
  color: var(--color-text);
}
</style>
