<template>
  <div class="purchase-card">
    <div class="card-header">
      <div class="purchase-info">
        <span class="purchase-date">{{ formatDate(purchase.created_at) }}</span>
        <span class="purchase-seller">Vendedor: {{ purchase.sold_by }}</span>
      </div>
    </div>

    <div class="card-content">
      <div class="purchase-details">
        <div v-for="detail in purchase.sell_details" :key="detail.id" class="detail-row">
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
        <span class="total-label">Total de la compra:</span>
        <span class="total-amount">${{ calculateTotal() }}</span>
      </div>
      <div class="pickup-code-section">
        <button @click="toggleCodeVisibility" class="toggle-code-btn">
          <span v-if="showCode">{{ formatPickupCode(purchase.pickup_code) }}</span>
          <span v-else class="code-hidden">
            Código de retiro:
            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12.5" r="3.5" stroke="currentColor" stroke-width="2"/>
            </svg>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PurchaseCard",
  props: {
    purchase: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      showCode: false,
    };
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
      return this.purchase.sell_details.reduce((total, detail) => {
        return total + (detail.offer_quantity * detail.product_quantity * detail.product_price);
      }, 0).toFixed(2);
    },
    toggleCodeVisibility() {
      this.showCode = !this.showCode;
    },
    formatPickupCode(code) {
      if (!code) return '***-***-***';
      return code.toUpperCase();
    },
  },
};
</script>

<style scoped>
.purchase-card {
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

.purchase-card:hover {
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

.purchase-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.purchase-date {
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-text);
}

.purchase-seller {
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

.pickup-code-section {
  margin-top: 15px;
  display: flex;
  justify-content: center;
}

.toggle-code-btn {
  background: var(--color-focus);
  color: var(--color-text);
  border: 2px solid var(--color-text);
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 1em;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
}

.toggle-code-btn span {
  font-size: 1.3em;
  letter-spacing: 2px;
}

.toggle-code-btn:hover {
  background: var(--color-darkest);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(34, 32, 31, 0.3);
}

.toggle-code-btn:active {
  transform: translateY(0);
}

.code-hidden {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1em !important;
  letter-spacing: normal !important;
}

.eye-icon {
  width: 24px;
  height: 24px;
  color: var(--color-text);
  transition: all 0.3s ease;
}

.toggle-code-btn:hover .eye-icon {
  transform: scale(1.1);
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

  .purchase-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }
}
</style>
