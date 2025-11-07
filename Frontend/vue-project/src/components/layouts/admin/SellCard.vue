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
  background: var(--color-darkest);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.sell-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--color-accent), var(--color-info));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.sell-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  transform: translateY(-2px);
}

.sell-card:hover::before {
  opacity: 1;
}

.card-header {
  padding: 1.75rem 2rem;
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.6), rgba(37, 43, 58, 0.4));
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sell-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.sell-date {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-accent-light);
  letter-spacing: -0.1px;
}

.participants {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  text-align: right;
}

.customer,
.establishment {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.7;
  font-weight: 400;
}

.card-content {
  padding: 2rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  margin-bottom: 1rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.3), rgba(37, 43, 58, 0.2));
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  transition: all 0.2s ease;
}

.detail-row:hover {
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.5), rgba(37, 43, 58, 0.4));
  border-color: rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-main {
  flex: 1;
  min-width: 0;
}

.detail-info {
  display: flex;
  gap: 1.25rem;
  align-items: center;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.product-name {
  font-weight: 600;
  font-size: 1.0625rem;
  color: var(--color-text);
  margin-bottom: 0.25rem;
  letter-spacing: -0.2px;
}

.product-description {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.6;
  line-height: 1.4;
}

.detail-info span {
  font-size: 0.875rem;
  color: var(--color-text);
  font-weight: 500;
  white-space: nowrap;
}

.offer-multiplier {
  font-weight: 700 !important;
  font-size: 0.9375rem !important;
  color: var(--color-accent-light) !important;
  background: rgba(99, 102, 241, 0.1);
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  border: 1px solid rgba(99, 102, 241, 0.25);
}

.product-quantity {
  opacity: 0.7;
}

.product-price {
  font-weight: 600;
  color: var(--color-success);
  font-size: 1rem;
}

.subtotal {
  font-weight: 700 !important;
  color: var(--color-success) !important;
  font-size: 1rem !important;
  background: rgba(16, 185, 129, 0.1);
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  border: 1px solid rgba(16, 185, 129, 0.25);
}

.card-footer {
  padding: 1.75rem 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(135deg, rgba(26, 31, 46, 0.6), rgba(37, 43, 58, 0.4));
}

.total-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 1rem;
  font-weight: 500;
  color: var(--color-text);
  opacity: 0.8;
}

.total-amount {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--color-success);
  letter-spacing: -0.5px;
}

@media (max-width: 1024px) {
  .detail-info {
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .card-header,
  .card-content,
  .card-footer {
    padding: 1.5rem;
  }

  .detail-row {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .detail-info {
    width: 100%;
    justify-content: flex-start;
    gap: 0.75rem;
  }

  .sell-info {
    flex-direction: column;
    align-items: flex-start;
  }

  .participants {
    text-align: left;
  }

  .total-amount {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .card-header,
  .card-content,
  .card-footer {
    padding: 1.25rem;
  }

  .detail-info {
    flex-direction: column;
    align-items: flex-start;
  }

  .detail-info span {
    width: 100%;
  }
}
</style>
