<script setup>
import { computed } from "vue";

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

const isExpired = computed(() => {
  if (!props.offer.expiration_datetime) return false;
  return new Date(props.offer.expiration_datetime) < new Date();
});

const isInactive = computed(() => {
  return props.offer.state === "inactive";
});

const discountPercentage = computed(() => {
  const price = Number(props.offer.price || 0);
  const minVal = Number(props.offer.minimum_value || 0);
  if (!price || !minVal || minVal <= price) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const formattedPickupWindow = computed(() => {
  if (!props.offer.pickup_start_datetime && !props.offer.expiration_datetime) return "-";
  try {
    const start = props.offer.pickup_start_datetime ? new Date(props.offer.pickup_start_datetime) : null;
    const end = props.offer.expiration_datetime ? new Date(props.offer.expiration_datetime) : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday
        ? "Hoy"
        : start.toLocaleDateString("es-AR", { day: "numeric", month: "short" });
      const startTime = start.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
      const endTime = end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
      return `${dateLabel}, ${startTime} - ${endTime} hs`;
    }

    if (end) {
      return `Hasta ${end.toLocaleDateString("es-AR")} ${end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" })}`;
    }
    return "-";
  } catch (e) {
    return "-";
  }
});
</script>

<template>
  <div class="customer-card" :class="{ 'card-expired': isExpired, 'card-inactive': isInactive }">
    <!-- Header -->
    <div class="card-header">
      <div class="header-top">
        <span class="pack-badge">🛍️ Pack Sorpresa</span>
        <span v-if="isInactive" class="status-badge inactive">Inactiva</span>
        <span v-else-if="isExpired" class="status-badge expired">Expirada</span>
        <span v-else class="status-badge active">Activa</span>
      </div>
      <h3 class="offer-title">{{ offer.title }}</h3>
    </div>

    <!-- Contenido -->
    <div class="card-content">
      <p class="offer-description">{{ offer.description }}</p>

      <!-- Chips de alérgenos -->
      <div v-if="offer.allergens?.length" class="allergens-row">
        <span v-for="(alg, idx) in offer.allergens" :key="idx" class="allergen-tag">
          {{ alg }}
        </span>
      </div>

      <!-- Peso aproximado -->
      <div v-if="offer.estimated_weight_kg" class="weight-row">
        ⚖️ Aprox. {{ offer.estimated_weight_kg }} kg
      </div>
    </div>

    <!-- Horario y Cupos -->
    <div class="pickup-bar">
      <div class="pickup-schedule">
        <span class="schedule-icon">🕒</span>
        <span>{{ formattedPickupWindow }}</span>
      </div>
      <div class="stock-badge">
        {{ offer.quantity }} cupo{{ offer.quantity > 1 ? "s" : "" }}
      </div>
    </div>

    <!-- Footer con Precios -->
    <div class="card-footer">
      <div class="prices-group">
        <span class="current-price">${{ Number(offer.price || 0).toLocaleString("es-AR") }}</span>
        <span v-if="offer.minimum_value" class="original-price">
          ${{ Number(offer.minimum_value).toLocaleString("es-AR") }}
        </span>
      </div>
      <span v-if="discountPercentage > 0" class="savings-pill">
        -{{ discountPercentage }}%
      </span>
    </div>
  </div>
</template>

<style scoped>
.customer-card {
  background: var(--color-primary, #2d2438);
  border: 1px solid #3d3450;
  border-radius: 14px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
  transition: all 0.25s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
  color: var(--color-text, #e8eaf6);
}

.customer-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(124, 58, 237, 0.25);
  border-color: #7c3aed;
}

.card-expired {
  opacity: 0.7;
}

.card-inactive {
  opacity: 0.55;
  filter: grayscale(0.4);
}

/* Header */
.card-header {
  padding: 1.25rem 1.25rem 0.5rem;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}

.pack-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: #c4b5fd;
  background: rgba(124, 58, 237, 0.15);
  border: 1px solid rgba(124, 58, 237, 0.4);
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}

.status-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
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

.offer-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.3;
}

/* Content */
.card-content {
  padding: 0.5rem 1.25rem 1rem;
  flex-grow: 1;
}

.offer-description {
  color: #b3acc0;
  font-size: 0.9rem;
  line-height: 1.45;
  margin: 0 0 0.75rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.allergens-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}

.allergen-tag {
  font-size: 0.7rem;
  background: #1f1a2b;
  border: 1px solid #4a4058;
  color: #d8b4fe;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.weight-row {
  font-size: 0.8rem;
  color: #e8eaf6;
  font-weight: 500;
}

/* Pickup bar */
.pickup-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 1.25rem;
  background: #1f1a2b;
  border-top: 1px solid #3d3450;
  border-bottom: 1px solid #3d3450;
  font-size: 0.8rem;
}

.pickup-schedule {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: #e9d5ff;
}

.stock-badge {
  font-weight: 600;
  color: #10b981;
}

/* Footer */
.card-footer {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1a1625;
}

.prices-group {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.current-price {
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
}

.original-price {
  font-size: 0.9rem;
  color: #8b8399;
  text-decoration: line-through;
}

.savings-pill {
  background: #10b981;
  color: #064e3b;
  font-weight: 800;
  font-size: 0.8rem;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
}
</style>
