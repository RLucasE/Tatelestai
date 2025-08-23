<template>
  <div class="sell-card">
    <div class="card-header">
      <div class="sell-info">
        <span class="sell-date">{{ formatDate(sell.created_at) }}</span>
        <div class="participants">
          <span class="customer">Cliente: {{ sell.customer_name }}</span>
          <span class="establishment">Establecimiento: {{ sell.establishment_name }}</span>
        </div>
      </div>
    </div>

    <div class="card-content">
      <div class="sell-details">
        <div v-for="detail in sell.sell_details" :key="detail.id" class="detail-row">
          <div class="detail-main">
            <span class="product-name">{{ detail.product_name }}</span>
            <span class="product-description">{{ detail.product_description }}</span>
          </div>
          <div class="detail-info">
            <span class="offer-multiplier">x{{ detail.offer_quantity }}</span>
            <span class="product-quantity">Cantidad: {{ detail.product_quantity }}</span>
            <span class="product-price">Precio: ${{ detail.product_price }}</span>
            <span class="subtotal">Subtotal: ${{ (detail.offer_quantity * detail.product_quantity * detail.product_price).toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <div class="total-section">
        <span class="total-label">Total de la venta:</span>
        <span class="total-amount">${{ calculateTotal() }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SellCard",
  props: {
    sell: {
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
      return this.sell.sell_details.reduce((total, detail) => {
        return total + (detail.offer_quantity * detail.product_quantity * detail.product_price);
      }, 0).toFixed(2);
    },
  },
};
</script>

<style scoped>
.sell-card {
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

.sell-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(34, 32, 31, 0.4);
  border-color: var(--color-text);
  background: var(--color-secondary);
}

.card-header {
  padding: 20px 20px 10px;
  border-bottom: 1px solid var(--color-focus);
  background: var(--color-secondary);
}

.sell-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 10px;
}

.sell-date {
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.participants {
  display: flex;
  flex-direction: column;
  gap: 5px;
  text-align: right;
}

.customer,
.establishment {
  font-size: 0.9em;
  color: var(--color-text);
  opacity: 0.8;
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
  font-size: 1em;
  font-weight: 500;
  color: var(--color-text);
}

.total-amount {
  font-size: 1.2em;
  font-weight: 700;
  color: var(--color-text);
}

@media (max-width: 768px) {
  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .detail-info {
    justify-content: flex-start;
  }

  .sell-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .participants {
    text-align: left;
  }
}
</style>
