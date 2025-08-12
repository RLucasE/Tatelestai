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

const products = computed(() => (offer.value?.products ?? []));

const totalOfferPrice = computed(() => {
  const offerQty = Number(offer.value?.quantity ?? 0);
  return (offer.value?.products ?? []).reduce((accumulated, product) => {
    const price = Number(product.pivot?.price ?? 0);
    const qty = Number(product.pivot?.quantity ?? 0);
    return accumulated + price * qty * offerQty;
  }, 0);
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

const isPurchased = computed(() => {
  return offer.value?.state === "purchased";
});

const offerStateText = computed(() => {
  if (isInactive.value) return "Inactiva";
  if (isExpired.value) return "Expirada";
  return "Activa";
});

const offerStateClass = computed(() => {
  if (isInactive.value) return "badge-inactive";
  if (isExpired.value) return "badge-expired";
  return "badge-active";
});

const formattedDate = computed(() => {
  if (!offer.value?.expiration_datetime) return "-";
  return new Date(offer.value.expiration_datetime).toLocaleDateString();
});

const formattedTime = computed(() => {
  if (!offer.value?.expiration_datetime) return "-";
  return new Date(offer.value.expiration_datetime).toLocaleTimeString();
});

const fetchOffer = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get(`/offer/${props.id}`);
    offer.value = response.data?.data ?? response.data;
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

onMounted(fetchOffer);

const onDeleteOffer = async () => {
  if (!offer.value) return;
  const confirmed = window.confirm(
    "¿Seguro que quieres eliminar esta oferta? Esta acción no se puede deshacer."
  );
  if (!confirmed) return;
  try {
    deleting.value = true;
    await axiosInstance.delete(`/offer/${offer.value.id}`);
    fetchOffer();
  } catch (err) {
    console.error("Error al eliminar la oferta:", err);
    alert("No se pudo eliminar la oferta");
  } finally {
    deleting.value = false;
  }
};
</script>

<template>
  <div class="edit-offer-container">
    <div class="header">
      <button class="back-btn" @click="goBack">← Volver a mis ofertas</button>
      <h1 class="title">Detalles de la oferta</h1>
      <div class="spacer"></div>
      <button class="danger-btn" @click="onDeleteOffer" :disabled="deleting">
        {{ deleting ? 'Eliminando...' : 'Deshabilitar offerta' }}
      </button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Cargando oferta...</p>
    </div>

    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button class="retry-btn" @click="fetchOffer">Reintentar</button>
    </div>

    <div v-else-if="!offer" class="empty">
      <p>No se encontró la oferta</p>
    </div>

    <div v-else class="content">
      <!-- Info principal de la oferta -->
      <section class="offer-info" :class="{ expired: isExpired }">
        <div class="offer-header">
          <h2 class="offer-title">{{ offer.title }}</h2>
          <span v-if="isInactive" class="badge badge-inactive">Inactiva</span>
          <span v-else-if="isExpired" class="badge badge-expired">Expirada</span>
          <span v-else-if="isActive" class="badge badge-active">Activa</span>
          <span v-else class="badge badge-purchased">Agotada</span>
        </div>
        <p class="offer-description">{{ offer.description }}</p>

        <div class="meta">
          <div class="meta-item">
            <span class="meta-label">Válida hasta</span>
            <span class="meta-value">{{ formattedDate }} {{ formattedTime }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Cantidad</span>
            <span class="meta-value">{{ offer.quantity ?? '-' }}</span>
          </div>
        </div>
      </section>

      <!-- Listado de productos -->
      <section class="products">
        <h3 class="section-title">Productos en la oferta</h3>

        <div v-if="products.length === 0" class="empty-products">
          <p>Esta oferta no tiene productos asociados.</p>
        </div>

        <div v-else class="product-list">
          <div class="product-row header-row">
            <div>Producto</div>
            <div class="hide-sm">Descripción</div>
            <div>Cantidad</div>
            <div>Precio</div>
            <div>Subtotal</div>
          </div>

          <div
            class="product-row"
            v-for="product in products"
            :key="product.id"
          >
            <div class="product-name">{{ product.name }}</div>
            <div class="product-description hide-sm">{{ product.description }}</div>
            <div class="product-quantity">{{ product.pivot?.quantity ?? '-' }}</div>
            <div class="product-price">{{ product.pivot?.price ?? '-' }}</div>
            <div class="product-subtotal">{{ calcSubtotal(product.pivot?.price, product.pivot?.quantity, offer?.quantity) }}</div>
          </div>
          <div class="product-row footer-row">
            <div></div>
            <div class="hide-sm"></div>
            <div></div>
            <div class="total-label">Total oferta</div>
            <div class="total-value">{{ totalOfferPrice }}</div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    calcSubtotal(price, qty, offerQty) {
      const p = Number(price ?? 0);
      const q = Number(qty ?? 0);
      const oq = Number(offerQty ?? 0);
      return p * q * oq;
    },
  },
};
</script>

<style scoped>
.edit-offer-container {
  padding: 20px;
  max-width: 1100px;
  margin: 0 auto;
  color: var(--color-text);
}

.header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.spacer { flex: 1; }

.back-btn {
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
}

.back-btn:hover {
  background: var(--color-primary);
}

.danger-btn {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
}

.danger-btn[disabled] {
  opacity: 0.7;
  cursor: not-allowed;
}

.danger-btn:hover:not([disabled]) {
  background: var(--color-secondary);
}

.title {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-primary), var(--color-focus));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.loading,
.error,
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: var(--color-darkest);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 14px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.retry-btn {
  margin-top: 12px;
  padding: 10px 16px;
  background: var(--color-primary);
  color: var(--color-text);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.offer-info {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 16px;
}

.offer-info.expired {
  background: var(--color-darkest);
}

.offer-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.offer-title {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--color-text);
}

.badge {
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
}

.badge-active {
  background: var(--color-focus);
  color: var(--color-text);
}

.badge-expired {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
}

.badge-inactive {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
}

.badge-purchased {
  background: var(--color-darkest);
  color: var(--color-text);
  border: 1px solid var(--color-focus);
}

.offer-description {
  margin: 6px 0 12px;
  line-height: 1.5;
}

.meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  background: var(--color-primary);
  border: 1px solid var(--color-focus);
  border-radius: 10px;
  padding: 10px 12px;
}

.meta-label {
  font-size: 0.8rem;
  opacity: 0.8;
}

.meta-value {
  font-weight: 700;
}

.products {
  background: var(--color-secondary);
  border: 1px solid var(--color-focus);
  border-radius: 12px;
  padding: 16px;
}

.section-title {
  margin: 0 0 12px 0;
  font-size: 1.1rem;
}

.product-list {
  width: 100%;
}

.product-row {
  display: grid;
  grid-template-columns: 2fr 3fr 1fr 1fr 1fr;
  gap: 10px;
  padding: 10px 8px;
  border-bottom: 1px solid var(--color-focus);
  align-items: center;
}

.header-row {
  font-weight: 700;
  background: var(--color-primary);
  border-radius: 8px;
}

.product-name {
  font-weight: 600;
}

.product-description {
  opacity: 0.9;
}

.product-price,
.product-subtotal {
  font-weight: 700;
}

@media (max-width: 768px) {
  .product-row { grid-template-columns: 2fr 1fr 1fr 1fr; }
  .hide-sm { display: none; }
}
</style>
