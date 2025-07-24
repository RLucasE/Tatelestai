<script>
export default {
  name: "OfferSellerCard",
  props: {
    offer: {
      type: Object,
      required: true,
    },
  },
  computed: {
    formattedDate() {
      return new Date(this.offer.expiration_datetime).toLocaleDateString();
    },
    feormattedTime() {
      return new Date(this.offer.expiration_datetime).toLocaleTimeString();
    },
    isExpired() {
      return new Date(this.offer.expiration_datetime) < new Date();
    },
  },
};
</script>

<template>
  <div class="customer-card">
    <div class="card-header" :class="{ expired: isExpired }">
      <h3 class="offer-title">{{ offer.title }}</h3>
    </div>

    <div class="card-content" :class="{ expired: isExpired }">
      <p class="offer-description">{{ offer.description }}</p>
    </div>

    <!-- Footer de la tarjeta -->
    <div class="card-footer">
      <div class="expiration-info">
        <span class="expiration-label">Válida hasta:</span>
        <span class="expiration-date">
          {{ formattedDate }}
        </span>
        <span class="expiration-time">
          {{ feormattedTime }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.customer-card {
  background: var(--color-primary);
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(34, 32, 31, 0.3);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
  border: 2px solid transparent;
  cursor: pointer;
  color: var(--color-text);
}

.customer-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(34, 32, 31, 0.4);
  border-color: var(--color-text);
  background: var(--color-secondary);
}

/* Header */
.card-header {
  padding: 20px 20px 10px;
  border-bottom: 1px solid var(--color-focus);
  background: var(--color-secondary);
}

.card-header.expired {
  background: var(--color-darkest);
}

.card-header .offer-title {
  margin: 0 0 8px 0;
  font-size: 1.4em;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.3;
}

.establishment-name {
  font-size: 0.9em;
  color: var(--color-text);
  font-weight: 500;
  opacity: 0.8;
}

/* Content */
.card-content {
  padding: 15px 20px;
}

.card-content.expired {
  background: var(--color-darkest);
}

.offer-description {
  margin: 0 0 15px 0;
  color: var(--color-text);
  line-height: 1.5;
  font-size: 0.95em;
  opacity: 0.9;
}

.products-info h4 {
  margin: 0 0 10px 0;
  font-size: 1em;
  color: var(--color-text);
  font-weight: 600;
}

.products-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 0;
  border-bottom: 1px solid var(--color-focus);
  font-size: 0.9em;
}

.product-item:last-child {
  border-bottom: none;
}

.product-name {
  flex: 1;
  color: var(--color-text);
  font-weight: 500;
}

.product-quantity {
  color: var(--color-text);
  margin: 0 10px;
  opacity: 0.7;
}

.product-price {
  color: var(--color-text);
  font-weight: 600;
  background: var(--color-focus);
  padding: 2px 6px;
  border-radius: 4px;
}

/* Footer */
.card-footer {
  padding: 15px 20px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-darkest);
}

.expiration-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.expiration-label {
  font-size: 0.8em;
  color: var(--color-text);
  font-weight: 500;
  opacity: 0.7;
}

.expiration-date {
  font-size: 0.9em;
  color: var(--color-text);
  font-weight: 600;
}

.view-offer-btn {
  padding: 8px 16px;
  background-color: var(--color-focus);
  color: var(--color-text);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s;
  font-size: 0.9em;
}

.view-offer-btn:hover {
  background-color: var(--color-darkest);
  transform: scale(1.05);
}
</style>
