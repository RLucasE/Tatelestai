<template>
  <div v-if="visible" class="modal-overlay">
    <div class="offer-modal">
      <h2>Crear Nueva Oferta</h2>

      <form @submit.prevent="handleSubmit" class="offer-form">
        <div class="form-group">
          <label for="title">Título de la Oferta</label>
          <input
              type="text"
              id="title"
              v-model="offerData.title"
              required
              placeholder="Título de la oferta"
          />
        </div>

        <div class="form-group">
          <label for="description">Descripción</label>
          <textarea
              id="description"
              v-model="offerData.description"
              placeholder="Descripción de la oferta"
          ></textarea>
        </div>

        <div class="form-group">
          <label for="expirationDate">Fecha de Expiración</label>
          <input
              type="datetime-local"
              id="expirationDate"
              v-model="offerData.expirationDate"
              :min="minExpirationDate"
              required
              placeholder="Fecha y hora de expiración"
          />
          <small class="field-hint"
          >La oferta debe expirar al menos 1 hora después de la fecha
            actual</small
          >
        </div>


        <!-- Sección de productos -->
        <div class="form-group">
          <div class="products-header">
            <label>Productos en la Oferta</label>
            <button
                type="button"
                @click="openAddProductModal"
                class="add-product-btn"
            >
              + Agregar Producto
            </button>
          </div>

          <!-- Lista de productos agregados -->
          <div v-if="selectedProducts.length > 0" class="products-list">
            <div
                v-for="(product, index) in selectedProducts"
                :key="product.id + '-' + index"
                class="product-item"
            >
              <div class="product-info">
                <h4>{{ product.name }}</h4>
                <p>{{ product.description }}</p>
                <div class="product-details">
                  <span>Cantidad: {{ product.quantity }}</span>
                  <span>Precio: ${{ product.price }}</span>
                  <span class="total">Total: ${{ product.total }}</span>
                </div>
              </div>
              <button
                  type="button"
                  @click="removeProduct(index)"
                  class="remove-btn"
              >
                quitar
              </button>
            </div>
          </div>

          <div v-else class="no-products">
            <p>No hay productos agregados a la oferta</p>
          </div>
        </div>

        <div class="form-group">
          <label for="offerQuantity">Cantidad de Ofertas</label>
          <input
              type="number"
              id="offerQuantity"
              v-model.number="offerData.quantity"
              min="1"
              placeholder="1"
              required
          />
          <small class="field-hint"
          >Número de ofertas disponibles para venta</small
          >
        </div>

        <!-- Precio total calculado -->
        <div class="form-group">
          <label>Precio Total de la Oferta</label>
          <div class="total-price">${{ totalOfferPrice.toFixed(2) }}</div>
        </div>

        <div class="modal-actions">
          <button
              type="submit"
              :disabled="loading || selectedProducts.length === 0"
          >
            {{ loading ? "Creando..." : "Crear Oferta" }}
          </button>
          <button type="button" @click="closeModal" class="cancel-button">
            Cancelar
          </button>
        </div>
      </form>
    </div>

    <!-- Modal para agregar productos -->
    <AddProductToOfferModal
        :visible="addProductModalVisible"
        :excludedProductIds="excludedProductIds"
        @update:visible="addProductModalVisible = $event"
        @productAdded="handleProductAdded"
    />
  </div>
</template>

<script setup>
import {ref, watch, computed} from "vue";
import axiosInstance from "@/lib/axios";
import AddProductToOfferModal from "./AddProductToOfferModal.vue";

const props = defineProps({
  visible: Boolean,
});

const emit = defineEmits(["update:visible", "success"]);

const offerData = ref({
  title: "",
  description: "",
  expirationDate: "",
  quantity: 0,
});

const selectedProducts = ref([]);
const loading = ref(false);
const addProductModalVisible = ref(false);

const minExpirationDate = computed(() => {
  const now = new Date();
  now.setHours(now.getHours() + 1);

  const offset = now.getTimezoneOffset() * 60000;
  const localTime = new Date(now.getTime() - offset);

  return localTime.toISOString().slice(0, 16); // Formato YYYY-MM-DDTHH:MM
});

// Computed para IDs de productos excluidos (ya agregados)
const excludedProductIds = computed(() => {
  return selectedProducts.value.map((product) => product.id);
});

// Computed para el precio total de la oferta
const totalOfferPrice = computed(() => {
  return selectedProducts.value.reduce((total, product) => {
    return total + product.total * offerData.value.quantity;
  }, 0);
});

const closeModal = () => {
  emit("update:visible", false);
  resetForm();
};

const resetForm = () => {
  offerData.value = {
    title: "",
    description: "",
    expirationDate: "",
    quantity: 0,
  };
  selectedProducts.value = [];
};

const openAddProductModal = () => {
  addProductModalVisible.value = true;
};

const handleProductAdded = (product) => {
  selectedProducts.value.push(product);
};

const removeProduct = (index) => {
  selectedProducts.value.splice(index, 1);
};

const handleSubmit = async () => {
  if (selectedProducts.value.length === 0) {
    alert("Debes agregar al menos un producto a la oferta");
    return;
  }

  const expirationDate = new Date(offerData.value.expirationDate);
  const minDate = new Date();
  minDate.setHours(minDate.getHours() + 1);

  loading.value = true;
  try {
    const offerPayload = {
      title: offerData.value.title,
      description: offerData.value.description,
      total_price: totalOfferPrice.value,
      expiration_date: offerData.value.expirationDate,
      quantity: offerData.value.quantity,
      products: selectedProducts.value.map((product) => ({
        id: product.id,
        quantity: product.quantity,
        price: product.price,
      })),
    };
    const response = await axiosInstance.post("/offer", offerPayload);
    console.log("Oferta creada:", response.data);
    emit("success", response.data.offer, "create");
    closeModal();
  } catch (error) {
    console.error("Error al crear la oferta:", error);
    //alert("Error al crear la oferta. Por favor, inténtalo de nuevo.");
  } finally {
    //closeModal();
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

.offer-modal {
  background-color: var(--color-secondary);
  border-radius: 8px;
  padding: 2rem;
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
  max-height: 90vh;
  overflow-y: auto;
}

h2 {
  color: var(--color-text);
  margin-bottom: 2rem;
  text-align: center;
}

.offer-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.add-product-btn {
  background-color: var(--color-accent);
  color: var(--color-text);
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.add-product-btn:hover {
  opacity: 0.8;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: var(--color-primary);
  padding: 1rem;
  border-radius: 4px;
  border: 1px solid var(--color-accent);
}

.product-info h4 {
  color: var(--color-text);
  margin: 0 0 0.5rem 0;
}

.product-info p {
  color: var(--color-text);
  opacity: 0.8;
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
}

.product-details {
  display: flex;
  gap: 1rem;
  font-size: 0.9rem;
  max-width: 50%;
}

.product-details span {
  color: var(--color-text);
}

.total {
  color: var(--color-accent) !important;
  font-weight: bold;
}

.remove-btn {
  background-color: var(--color-darkest);
  max-width: 50%;
  color: white;
  display: block;
  cursor: pointer;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-btn:hover {
  background-color: var(--color-focus);
}

.no-products {
  text-align: center;
  padding: 2rem;
  color: var(--color-text);
  opacity: 0.6;
}

.total-price {
  background-color: var(--color-primary);
  padding: 1rem;
  border-radius: 4px;
  font-size: 1.2rem;
  font-weight: bold;
  color: var(--color-accent);
  text-align: center;
  border: 2px solid var(--color-accent);
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

input[type="datetime-local"] {
  color-scheme: dark;
}

.field-hint {
  color: var(--color-text);
  opacity: 0.7;
  font-size: 0.8rem;
  margin-top: 0.25rem;
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

button[type="submit"] {
  background-color: var(--color-primary);
  color: var(--color-text);
}

/* Responsive */
@media (max-width: 768px) {
  .offer-modal {
    padding: 1rem;
    max-width: 95%;
  }

  .products-header {
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }

  .product-details {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
