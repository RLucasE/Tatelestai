<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";
import { computed } from "vue";

const route = useRoute();
const router = useRouter();

// Props
const props = defineProps({
  id: {
    type: String,
    required: true,
  },
});

const toLocalISOString = (date) => {
  const offset = date.getTimezoneOffset() * 40000; // offset en milisegundos
  const localDate = new Date(date.getTime() - offset);
  return localDate.toISOString().slice(0, 16);
};

const minDate = computed(() => {
  let date = new Date();
  console.log(toLocalISOString(date));
  return toLocalISOString(date);
});

// Reactive data
const offer = ref({});

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(false);

// Form validation
const errors = ref({});

// Fetch offer data
const fetchOffer = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axiosInstance.get(`/offer/${props.id}`);
    const offerData = response.data.data || response.data;
    console.log(offerData);
    offer.value = {
      title: offerData.title || "",
      description: offerData.description || "",
      expiration_datetime: offerData.expiration_datetime
        ? toLocalISOString(new Date(offerData.expiration_datetime))
        : "",
      products: offerData.products || [],
    };
    console.log(offer);
  } catch (err) {
    console.error("Error fetching offer:", err);
    error.value = "Error al cargar la oferta";
  } finally {
    loading.value = false;
  }
};

// Validate form
const validateForm = () => {
  errors.value = {};

  if (!offer.value.title.trim()) {
    errors.value.title = "El título es requerido";
  }

  if (!offer.value.description.trim()) {
    errors.value.description = "La descripción es requerida";
  }

  if (!offer.value.expiration_datetime) {
    errors.value.expiration_datetime = "La fecha de expiración es requerida";
  } else {
    const expirationDate = new Date(offer.value.expiration_datetime);
    const now = new Date();
    if (expirationDate <= now) {
      errors.value.expiration_datetime =
        "La fecha de expiración debe ser futura";
    }
  }

  return Object.keys(errors.value).length === 0;
};

// Update offer
const updateOffer = async () => {
  if (!validateForm()) {
    return;
  }

  try {
    saving.value = true;
    error.value = null;

    const updateData = {
      title: offer.value.title.trim(),
      description: offer.value.description.trim(),
      expiration_datetime: offer.value.expiration_datetime,
    };

    await axiosInstance.put(`/offers/${props.id}`, updateData);

    success.value = true;
    setTimeout(() => {
      success.value = false;
    }, 3000);
  } catch (err) {
    console.error("Error updating offer:", err);
    error.value =
      err.response?.data?.message || "Error al actualizar la oferta";
  } finally {
    saving.value = false;
  }
};

// Navigation
const goBack = () => {
  router.push({ name: "my-offers" });
};

// Lifecycle
onMounted(() => {
  fetchOffer();
});
</script>

<template>
  <div class="edit-offer-container">
    <!-- Header -->
    <div class="header-section">
      <button @click="goBack" class="back-btn">← Volver a Mis Ofertas</button>
      <h1 class="page-title">Editar Oferta</h1>
      <p class="page-subtitle">Modifica los detalles de tu oferta</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando oferta...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button @click="fetchOffer" class="retry-btn">Reintentar</button>
    </div>

    <!-- Edit Form -->
    <div v-else class="form-container">
      <!-- Success Message -->
      <div v-if="success" class="success-message">
        ✅ Oferta actualizada exitosamente
      </div>

      <div class="products-conainer">
        <div v-for="product in offer.products" :key="product.id">
          <div class="product">
            <div class="leftDetail textContainer">
              {{ product.name }} x{{ product.pivot.quantity }}
            </div>
            <div class="rightDetail textContainer">
              ${{ product.pivot.price }}
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="updateOffer" class="edit-form">
        <!-- Title Field -->
        <div class="form-group">
          <label for="title" class="form-label">Título de la Oferta</label>
          <input
            id="title"
            v-model="offer.title"
            type="text"
            class="form-input"
            :class="{ error: errors.title }"
            placeholder="Ingresa el título de tu oferta"
            maxlength="255"
          />
          <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
        </div>

        <!-- Description Field -->
        <div class="form-group">
          <label for="description" class="form-label">Descripción</label>
          <textarea
            id="description"
            v-model="offer.description"
            class="form-textarea"
            :class="{ error: errors.description }"
            placeholder="Describe tu oferta en detalle"
            rows="4"
            maxlength="1000"
          ></textarea>
          <span v-if="errors.description" class="error-text">{{
            errors.description
          }}</span>
        </div>

        <!-- Expiration Date Field -->
        <div class="form-group">
          <label for="expiration" class="form-label">Fecha de Expiración</label>
          <input
            id="expiration"
            v-model="offer.expiration_datetime"
            :min="minDate"
            type="datetime-local"
            class="form-input"
            :class="{ error: errors.expiration_datetime }"
          />
          <span v-if="errors.expiration_datetime" class="error-text">{{
            errors.expiration_datetime
          }}</span>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions">
          <button
            type="button"
            @click="goBack"
            class="cancel-btn"
            :disabled="saving"
          >
            Cancelar
          </button>
          <button type="submit" class="submit-btn" :disabled="saving">
            <span v-if="saving">Guardando...</span>
            <span v-else>Actualizar Oferta</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.edit-offer-container {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
  background-color: var(--color-bg);
  min-height: 100vh;
}

/* Header Section */
.header-section {
  margin-bottom: 30px;
  text-align: center;
}

.products-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.product {
  height: 3rem;
  margin: 2rem 0;
  justify-content: space-between;
  display: flex;
  background-color: var(--color-darkest);
  border-radius: 8px;
}

.product .leftDetail {
  text-align: start;
  display: inline-block;
  width: 65%;
}

.product .rightDetail {
  display: inline-block;
  width: 25%;
}

.product .textContainer {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.ack-btn {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  background-color: var(--color-secondary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  margin-bottom: 20px;
  text-decoration: none;
}

.back-btn:hover {
  background-color: var(--color-focus);
  transform: translateY(-2px);
}

.page-title {
  font-size: 2.5em;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 10px 0;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  font-size: 1.1em;
  color: var(--color-text);
  opacity: 0.8;
  margin: 0;
  font-weight: 400;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  text-align: center;
  color: var(--color-text);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  text-align: center;
  color: var(--color-text);
  background-color: var(--color-darkest);
  border-radius: 16px;
  margin: 20px;
  border: 2px solid var(--color-secondary);
}

.retry-btn {
  padding: 12px 24px;
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  font-size: 1em;
  margin-top: 15px;
}

.retry-btn:hover {
  background-color: var(--color-focus);
  transform: translateY(-2px);
}

/* Success Message */
.success-message {
  background-color: #10b981;
  color: white;
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
  font-weight: 600;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Form Container */
.form-container {
  background-color: var(--color-secondary);
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Form Groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-weight: 600;
  color: var(--color-text);
  font-size: 1em;
}

.form-input,
.form-textarea {
  padding: 12px 16px;
  border: 2px solid var(--color-focus);
  border-radius: 8px;
  background-color: var(--color-focus);
  color: var(--color-text);
  font-size: 1em;
  transition: all 0.3s ease;
  resize: vertical;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.1);
}

.form-input.error,
.form-textarea.error {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-text {
  color: #ef4444;
  font-size: 0.875em;
  font-weight: 500;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  margin-top: 20px;
}

.cancel-btn,
.submit-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  font-size: 1em;
  min-width: 120px;
}

.cancel-btn {
  background-color: var(--color-darkest);
  color: var(--color-text);
}

.cancel-btn:hover:not(:disabled) {
  background-color: var(--color-secondary);
  transform: translateY(-2px);
}

.submit-btn {
  background-color: var(--color-primary);
  color: var(--color-text);
}

.submit-btn:hover:not(:disabled) {
  background-color: var(--color-focus);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.submit-btn:disabled,
.cancel-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Responsive Design */
@media (max-width: 768px) {
  .edit-offer-container {
    padding: 15px;
  }

  .form-container {
    padding: 20px;
  }

  .page-title {
    font-size: 2em;
  }

  .form-actions {
    flex-direction: column;
  }

  .cancel-btn,
  .submit-btn {
    width: 100%;
  }
}
</style>
