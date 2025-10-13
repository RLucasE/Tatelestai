<template>
  <div class="history-card">
    <div class="card-header">
      <div class="purchase-info">
        <span class="purchase-date">{{ formatDate(purchase.created_at) }}</span>
        <span class="purchase-status">
          <svg class="check-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </svg>
          Entregado
        </span>
      </div>
      <div class="establishment-info">
        <span class="establishment-name">{{ purchase.establishment.name }}</span>
        <span class="establishment-address">{{ purchase.establishment.address }}</span>
      </div>
    </div>

    <div class="card-content">
      <div class="purchase-details">
        <div v-for="(offer, index) in purchase.offers" :key="index" class="detail-row">
          <div class="detail-main">
            <span class="product-name">{{ offer.product_name }}</span>
            <span class="product-description">{{ offer.product_description }}</span>
          </div>
          <div class="detail-info">
            <span class="offer-multiplier">x{{ offer.offer_quantity }}</span>
            <span class="product-quantity">Cantidad: {{ offer.product_quantity }}</span>
            <span class="product-price">Precio: ${{ offer.product_price }}</span>
            <span class="subtotal">Subtotal: ${{ (offer.offer_quantity * offer.product_quantity * offer.product_price).toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <div class="total-section">
        <span class="total-label">Total de la compra:</span>
        <span class="total-amount">${{ purchase.total_price || calculateTotal() }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "HistoryCard",
  props: {
    purchase: {
      type: Object,
      required: true,
    },
  },
  methods: {
    formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('es-AR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    calculateTotal() {
      if (!this.purchase.offers) return 0;
      return this.purchase.offers.reduce((total, offer) => {
        return total + (offer.offer_quantity * offer.product_quantity * offer.product_price);
      }, 0).toFixed(2);
    },
  },
};
</script>

<style scoped>
.history-card {
  background: var(--color-primary);
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(34, 32, 31, 0.3);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
  border: 2px solid transparent;
  color: var(--color-text);
  margin-bottom: 20px;
}

.history-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(34, 32, 31, 0.4);
  border-color: var(--color-text);
  background: var(--color-secondary);
}

.card-header {
  padding: 20px 20px 15px;
  border-bottom: 1px solid var(--color-focus);
  background: var(--color-secondary);
}

.purchase-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.purchase-date {
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.purchase-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.95em;
  color: #4ade80;
  font-weight: 600;
  background: var(--color-focus);
  padding: 6px 12px;
  border-radius: 6px;
}

.check-icon {
  width: 18px;
  height: 18px;
  color: #4ade80;
}

.establishment-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.establishment-name {
  font-size: 1em;
  font-weight: 600;
  color: var(--color-text);
}

.establishment-address {
  font-size: 0.85em;
  color: var(--color-text);
  opacity: 0.7;
}

.card-content {
  padding: 15px 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding: 12px;
  background: var(--color-focus);
  border-radius: 8px;
  border: 1px solid var(--color-darkest);
}

.detail-main {
  flex: 2;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-info {
  flex: 3;
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
}

.product-name {
  font-weight: 600;
  font-size: 1.1em;
  color: var(--color-text);
}

.product-description {
  font-size: 0.9em;
  color: var(--color-text);
  opacity: 0.8;
}

.detail-info span {
  font-size: 0.85em;
  color: var(--color-text);
  background: var(--color-darkest);
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
}

.offer-multiplier {
  font-size: 1em !important;
  font-weight: 700 !important;
  background: var(--color-secondary) !important;
  border: 2px solid var(--color-text) !important;
  color: var(--color-text) !important;
}

.card-footer {
  padding: 15px 20px;
  border-top: 1px solid var(--color-focus);
  background: var(--color-secondary);
}

.total-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.total-amount {
  font-size: 1.4em;
  font-weight: 700;
  color: var(--color-text);
  background: var(--color-focus);
  padding: 8px 16px;
  border-radius: 8px;
  border: 2px solid var(--color-text);
}

/* Responsive Design */
@media (max-width: 768px) {
  .detail-row {
    flex-direction: column;
    gap: 12px;
  }

  .detail-info {
    width: 100%;
    justify-content: flex-start;
  }

  .purchase-info {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }
}
</style>

