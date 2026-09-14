<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
});

const router = useRouter();

const loading = ref(true);
const error = ref(null);
const offer = ref(null);
const deleting = ref(false);
const saving = ref(false);
const actionMessage = ref("");

// Edición de cupos y precio
const editForm = ref({
  quantity: 1,
  price: 0,
  minimum_value: 0,
});

const isExpired = computed(() => {
  if (!offer.value?.expiration_datetime) return false;
  return new Date(offer.value.expiration_datetime) < new Date();
});

const isInactive = computed(() => {
  return offer.value?.state === "inactive";
});

const isActive = computed(() => {
  return offer.value?.state === "active" && !isExpired.value;
});

const discountPercentage = computed(() => {
  const price = Number(offer.value?.price || 0);
  const minVal = Number(offer.value?.minimum_value || 0);
  if (!price || !minVal || minVal <= price) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const formattedPickupWindow = computed(() => {
  if (!offer.value?.pickup_start_datetime && !offer.value?.expiration_datetime) return "-";
  try {
    const start = offer.value.pickup_start_datetime ? new Date(offer.value.pickup_start_datetime) : null;
    const end = offer.value.expiration_datetime ? new Date(offer.value.expiration_datetime) : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday
        ? "Hoy"
        : start.toLocaleDateString("es-AR", { weekday: "short", day: "numeric", month: "short" });
      const startTime = start.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
      const endTime = end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
      return `${dateLabel}, ${startTime} - ${endTime} hs`;
    }
    return "-";
  } catch (e) {
    return "-";
  }
});

const fetchOffer = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get(`/my-packs/${props.id}`);
    offer.value = response.data?.data ?? response.data;
    if (offer.value) {
      editForm.value.quantity = offer.value.quantity;
      editForm.value.price = offer.value.price;
      editForm.value.minimum_value = offer.value.minimum_value;
    }
    error.value = null;
  } catch (err) {
    console.error("Error al cargar la oferta:", err);
    error.value = "No se pudo cargar la oferta";
    offer.value = null;
  } finally {
    loading.value = false;
  }
};

const goBack = () => router.push({ name: "my-offers" });

const onUpdatePack = async () => {
  if (!offer.value) return;
  try {
    saving.value = true;
    actionMessage.value = "";
    await axiosInstance.patch(`/packs/${offer.value.id}`, {
      quantity: Number(editForm.value.quantity),
      price: Number(editForm.value.price),
      minimum_value: Number(editForm.value.minimum_value),
    });
    actionMessage.value = "Pack actualizado correctamente";
    await fetchOffer();
  } catch (err) {
    console.error("Error al actualizar oferta:", err);
    alert(err.response?.data?.message || "No se pudo actualizar el pack");
  } finally {
    saving.value = false;
  }
};

const onDeleteOffer = async () => {
  if (!offer.value) return;
  const confirmed = window.confirm(
    "¿Seguro que deseas dar de baja este pack? La oferta dejará de estar disponible para los clientes."
  );
  if (!confirmed) return;
  try {
    deleting.value = true;
    await axiosInstance.delete(`/packs/${offer.value.id}`);
    await fetchOffer();
  } catch (err) {
    console.error("Error al deshabilitar pack:", err);
    alert("No se pudo deshabilitar el pack");
  } finally {
    deleting.value = false;
  }
};

onMounted(fetchOffer);
</script>

<template>
  <div class="edit-pack-container">
    <!-- Header -->
    <div class="page-header">
      <button class="back-btn" @click="goBack">← Volver a mis ofertas</button>
      <div class="header-actions">
        <button
          v-if="!isInactive"
          class="danger-btn"
          @click="onDeleteOffer"
          :disabled="deleting"
        >
          {{ deleting ? "Dando de baja..." : "Deshabilitar Pack" }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando detalles de la oferta...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button class="retry-btn" @click="fetchOffer">Reintentar</button>
    </div>

    <!-- Contenido -->
    <div v-else-if="offer" class="content-grid">
      <!-- Tarjeta Principal de Información -->
      <section class="main-card">
        <div class="card-top">
          <div>
            <span class="pack-type-badge">🛍️ Bolsa Sorpresa</span>
            <h1 class="pack-title">{{ offer.title }}</h1>
          </div>
          <div class="status-wrapper">
            <span v-if="isInactive" class="status-badge inactive">Inactiva</span>
            <span v-else-if="isExpired" class="status-badge expired">Expirada</span>
            <span v-else class="status-badge active">Activa</span>
          </div>
        </div>

        <p class="pack-description">{{ offer.description }}</p>

        <!-- Alérgenos -->
        <div v-if="offer.allergens?.length" class="section-row">
          <span class="row-label">Alérgenos e ingredientes:</span>
          <div class="allergens-chips">
            <span v-for="(alg, idx) in offer.allergens" :key="idx" class="allergen-chip">
              {{ alg }}
            </span>
          </div>
        </div>

        <!-- Peso estimado -->
        <div v-if="offer.estimated_weight_kg" class="section-row">
          <span class="row-label">Peso estimado:</span>
          <span class="row-value">~{{ offer.estimated_weight_kg }} kg</span>
        </div>

        <!-- Ventana de retiro -->
        <div class="pickup-info-box">
          <span class="pickup-icon">🕒</span>
          <div>
            <div class="pickup-title">Ventana de Retiro</div>
            <div class="pickup-timing">{{ formattedPickupWindow }}</div>
          </div>
        </div>

        <!-- Precios y Descuento -->
        <div class="pricing-metrics">
          <div class="metric-card">
            <span class="metric-label">Precio al cliente</span>
            <span class="metric-value">${{ Number(offer.price).toLocaleString("es-AR") }}</span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Valor mínimo original</span>
            <span class="metric-value text-gray-400">
              ${{ Number(offer.minimum_value).toLocaleString("es-AR") }}
            </span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Ahorro para el cliente</span>
            <span class="metric-value text-emerald-400">
              {{ discountPercentage }}% OFF
            </span>
          </div>
        </div>
      </section>

      <!-- Panel de Ajustes Rápidos -->
      <aside class="side-card">
        <h3 class="side-title">Modificar Cupos y Precios</h3>
        <p class="side-description">
          Ajusta la cantidad disponible o el precio de esta oferta mientras esté activa.
        </p>

        <div v-if="actionMessage" class="success-alert">
          {{ actionMessage }}
        </div>

        <form @submit.prevent="onUpdatePack" class="update-form">
          <div class="form-group">
            <label class="form-label">Cupos Disponibles</label>
            <input
              v-model.number="editForm.quantity"
              type="number"
              min="0"
              class="form-input"
              :disabled="isInactive"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Precio ($ ARS)</label>
            <input
              v-model.number="editForm.price"
              type="number"
              min="1"
              class="form-input"
              :disabled="isInactive"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Valor Original ($ ARS)</label>
            <input
              v-model.number="editForm.minimum_value"
              type="number"
              min="1"
              class="form-input"
              :disabled="isInactive"
            />
          </div>

          <button
            type="submit"
            class="save-btn"
            :disabled="saving || isInactive"
          >
            {{ saving ? "Guardando..." : "Actualizar Oferta" }}
          </button>
        </form>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.edit-pack-container {
  padding: 1.5rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
  color: var(--color-text, #e8eaf6);
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.back-btn {
  background: transparent;
  color: #b3acc0;
  border: 1px solid #4a4058;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.back-btn:hover {
  color: #ffffff;
  border-color: #7c3aed;
  background: rgba(124, 58, 237, 0.1);
}

.danger-btn {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid #ef4444;
  color: #fca5a5;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.danger-btn:hover:not(:disabled) {
  background: #dc2626;
  color: #ffffff;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: #b3acc0;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #3d3450;
  border-top: 4px solid #7c3aed;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.6rem 1.2rem;
  background: #7c3aed;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

/* Layout */
.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 1024px) {
  .content-grid {
    grid-template-columns: 2fr 1.2fr;
    align-items: start;
  }
}

.main-card,
.side-card {
  background: var(--color-primary, #2d2438);
  border: 1px solid #3d3450;
  border-radius: 14px;
  padding: 1.75rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.pack-type-badge {
  font-size: 0.8rem;
  font-weight: 700;
  color: #c4b5fd;
  background: rgba(124, 58, 237, 0.2);
  border: 1px solid rgba(124, 58, 237, 0.4);
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
}

.pack-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0.5rem 0 0 0;
}

.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  text-transform: uppercase;
}

.status-badge.active {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid #10b981;
}

.status-badge.expired {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid #ef4444;
}

.status-badge.inactive {
  background: rgba(156, 163, 175, 0.2);
  color: #9ca3af;
  border: 1px solid #6b7280;
}

.pack-description {
  font-size: 1rem;
  line-height: 1.5;
  color: #b3acc0;
  margin-bottom: 1.5rem;
}

.section-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.row-label {
  font-size: 0.9rem;
  color: #8b8399;
  font-weight: 500;
}

.row-value {
  font-size: 0.95rem;
  font-weight: 600;
  color: #ffffff;
}

.allergens-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.allergen-chip {
  font-size: 0.75rem;
  background: #1f1a2b;
  border: 1px solid #4a4058;
  color: #d8b4fe;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.pickup-info-box {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: #1f1a2b;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  border: 1px solid #3d3450;
  margin: 1.5rem 0;
}

.pickup-icon {
  font-size: 1.5rem;
}

.pickup-title {
  font-size: 0.8rem;
  color: #a39cb2;
  text-transform: uppercase;
  font-weight: 600;
}

.pickup-timing {
  font-size: 1.05rem;
  font-weight: 700;
  color: #f3e8ff;
}

.pricing-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.metric-card {
  background: #1a1625;
  border: 1px solid #3d3450;
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metric-label {
  font-size: 0.75rem;
  color: #a39cb2;
}

.metric-value {
  font-size: 1.3rem;
  font-weight: 800;
  color: #ffffff;
}

/* Side Card */
.side-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.35rem 0;
}

.side-description {
  font-size: 0.85rem;
  color: #b3acc0;
  margin-bottom: 1.5rem;
  line-height: 1.4;
}

.success-alert {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid #10b981;
  color: #34d399;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1.2rem;
}

.form-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #b3acc0;
  margin-bottom: 0.35rem;
}

.form-input {
  width: 100%;
  background: #1a1625;
  border: 1.5px solid #3d3450;
  color: #ffffff;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  font-size: 0.95rem;
  outline: none;
}

.form-input:focus {
  border-color: #7c3aed;
}

.save-btn {
  width: 100%;
  padding: 0.85rem;
  background: #7c3aed;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 0.5rem;
}

.save-btn:hover:not(:disabled) {
  background: #6d28d9;
}

.save-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
