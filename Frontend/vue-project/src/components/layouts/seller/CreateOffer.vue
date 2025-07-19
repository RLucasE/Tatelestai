<template>
  <div class="create-offer-container">
    <div class="header">
      <h1>Crear Nueva Oferta</h1>
      <p>Crea ofertas para productos disponibles</p>
    </div>

    <div class="action-section">
      <button @click="openCreateModal" class="create-offer-btn">
        <span class="icon">+</span>
        Crear Oferta
      </button>
    </div>
    <CreateOfferModal
      :visible="createModalVisibility"
      :productId="selectedProductId"
      @update:visible="createModalVisibility = $event"
      @success="handleOfferCreated"
    />
  </div>
</template>

<script setup>
import { ref } from "vue";
import CreateOfferModal from "./CreateOfferModal.vue";

const createModalVisibility = ref(false);
const selectedProductId = ref(null);

const openCreateModal = () => {
  createModalVisibility.value = true;
};

const handleOfferCreated = (newOffer) => {
  console.log("Nueva oferta creada:", newOffer);
  createModalVisibility.value = false;
};
</script>

<style scoped>
.create-offer-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 3rem;
}

.header h1 {
  color: var(--color-text);
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.header p {
  color: var(--color-text);
  opacity: 0.8;
  font-size: 1.1rem;
}

.action-section {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
}

.create-offer-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.create-offer-btn:hover {
  background-color: var(--color-accent);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.create-offer-btn:active {
  transform: translateY(0);
}

.icon {
  font-size: 1.5rem;
  font-weight: bold;
}

/* Responsive design */
@media (max-width: 768px) {
  .create-offer-container {
    padding: 1rem;
  }

  .header h1 {
    font-size: 2rem;
  }

  .create-offer-btn {
    padding: 0.8rem 1.5rem;
    font-size: 1rem;
  }
}
</style>
