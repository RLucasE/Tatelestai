<script setup>
import SellerSection from "./SellerSection.vue";
import {ref, onMounted} from "vue";
import axiosInstance from "@/lib/axios";

const establishments = ref([]);

const getCart = async () => {
  try {
    const response = await axiosInstance.get("/customer-cart");
    establishments.value = response.data;
    console.log(establishments.value);
  } catch (error) {
    console.error(error);
  }
};

const handleRemoveOffer = async ($index) => {
  console.log("removeOffer", $index);
  try {
    await axiosInstance.delete(`/customer-cart/${$index}`);
    await getCart();
  } catch (error) {
    console.error("Error removing offer:", error);
  }
};

function debounce(func, wait) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;

    clearTimeout(timeout);

    timeout = setTimeout(() => {
      func.apply(context, args);
    }, wait);
  };
}

const updateOfferQuantity = (offer, quantity) => {
  offer.quantity = quantity;
};
const updateQuantity = async (offer,quantity) => {
  try {
    await axiosInstance.put(`/customer-cart/${offer.offer_id}`, {quantity});
    await getCart();
  } catch (error) {
    console.error("Error updating quantity:", error);
  }
};
const handleQuantityChange = async (offer, quantity) => {
  const oldQuantity = offer.quantity;
  try{
    updateOfferQuantity(offer, quantity);
    debounceUpdateQuantity(offer, quantity);
  }catch(error){
    alert("Hubo un error al actualizar la cantidad del producto");
    updateOfferQuantity(offer,oldQuantity);
  }
};

const removeEstablishmentOffers = async (establishmentId) => {
  try {
    console.log("removeEstablishmentOffers", establishmentId);
    await axiosInstance.delete(`/customer-cart/establishment/${establishmentId}`);
    await getCart();
  } catch (error) {
    console.error("Error removing establishment offers:", error);
  }
};
const debounceUpdateQuantity = debounce(updateQuantity, 500);
onMounted(() => {
  getCart();
});
</script>

<template>
  <div class="customer-cart-container">
    <SellerSection
        v-for="offers in establishments"
        :offers="offers"
        @offerRemoved="handleRemoveOffer"
        @quantityUpdated="handleQuantityChange"
        @removeAllOffers="removeEstablishmentOffers"
    />
  </div>
</template>

<style scoped>
.customer-cart-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  padding: 2rem;
  max-width: 1800px;
  width: 95%;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

/* Título de la página */
.customer-cart-container::before {
  content: 'Mi Carrito';
  display: block;
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 1.5rem;
  text-align: center;
  letter-spacing: 0.05em;
  border-bottom: 3px solid var(--color-focus);
  padding-bottom: 1rem;
  width: 100%; /* Asegura que el título ocupe todo el ancho */
}

/* Estilos responsivos */
@media (max-width: 1600px) {
  .customer-cart-container {
    max-width: 95%; /* En pantallas más pequeñas, usar porcentaje */
  }
}

@media (max-width: 992px) {
  .customer-cart-container {
    padding: 1.5rem;
    width: 98%;
  }

  .customer-cart-container::before {
    font-size: 2rem;
  }
}

@media (max-width: 768px) {
  .customer-cart-container {
    padding: 1rem;
    width: 100%; /* Ocupa todo el ancho en móviles */
  }

  .customer-cart-container::before {
    font-size: 1.75rem;
    margin-bottom: 1rem;
  }
}

@media (max-width: 576px) {
  .customer-cart-container {
    padding: 0.75rem;
  }

  .customer-cart-container::before {
    font-size: 1.5rem;
  }
}
</style>
