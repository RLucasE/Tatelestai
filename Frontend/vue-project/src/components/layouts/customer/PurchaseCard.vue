<template>
  <div class="purchase-card">
    <div class="card-header">
      <span class="purchase-date">{{ formatDate(purchase.created_at) }}</span>
      <span class="purchase-seller">{{ purchase.sold_by }}</span>
    </div>

    <div class="card-content">
      <table class="details-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="detail in purchase.sell_details" :key="detail.id">
            <td>
              <div class="product-info">
                <span class="product-name">{{ detail.product_name }}</span>
                <span class="product-description">{{ detail.product_description }}</span>
                <span class="offer-badge">x{{ detail.offer_quantity }}</span>
              </div>
            </td>
            <td class="quantity">{{ detail.product_quantity }}</td>
            <td class="price">${{ detail.product_price }}</td>
            <td class="subtotal">${{ (detail.offer_quantity * detail.product_quantity * detail.product_price).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card-footer">
      <div class="total-section">
        <span class="total-label">Total</span>
        <span class="total-amount">${{ calculateTotal() }}</span>
      </div>
      <div class="pickup-section">
        <button @click="toggleCodeVisibility" class="code-btn">
          <span v-if="showCode" class="code-visible">{{ formatPickupCode(purchase.pickup_code) }}</span>
          <span v-else class="code-label">Ver código de retiro</span>
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
  background: var(--color-darkest);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-primary);
}

.purchase-date {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.purchase-seller {
  font-size: 0.875rem;
  color: var(--color-text);
  opacity: 0.7;
}

.card-content {
  padding: 0;
}

.details-table {
  width: 100%;
  border-collapse: collapse;
}

.details-table thead th {
  text-align: left;
  padding: 0.75rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
  opacity: 0.7;
}

.details-table tbody tr {
  border-bottom: 1px solid var(--color-border);
}

.details-table tbody tr:last-child {
  border-bottom: none;
}

.details-table tbody td {
  padding: 0.875rem 1.25rem;
  color: var(--color-text);
  font-size: 0.875rem;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-name {
  font-weight: 500;
  color: var(--color-text);
}

.product-description {
  font-size: 0.75rem;
  opacity: 0.6;
}

.offer-badge {
  display: inline-block;
  background: var(--color-accent);
  color: var(--color-text);
  padding: 0.125rem 0.5rem;
  border-radius: var(--border-radius);
  font-size: 0.75rem;
  font-weight: 600;
  width: fit-content;
  margin-top: 0.25rem;
}

.quantity,
.price,
.subtotal {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.subtotal {
  font-weight: 600;
}

.card-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-primary);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.total-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.total-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text);
  opacity: 0.7;
}

.total-amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text);
}

.pickup-section {
  display: flex;
}

.code-btn {
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  cursor: pointer;
  min-width: 140px;
  text-align: center;
}

.code-btn:hover {
  background: var(--color-focus);
}

.code-visible {
  font-weight: 600;
  letter-spacing: 0.1em;
  font-family: monospace;
}

.code-label {
  font-weight: 500;
}

@media (max-width: 768px) {
  .details-table {
    display: block;
    overflow-x: auto;
  }

  .details-table thead {
    display: none;
  }

  .details-table tbody,
  .details-table tr,
  .details-table td {
    display: block;
  }

  .details-table tbody tr {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--color-border);
  }

  .details-table tbody td {
    padding: 0.25rem 0;
    text-align: left !important;
  }

  .details-table tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    display: inline-block;
    width: 100px;
    opacity: 0.7;
  }

  .card-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .total-section {
    justify-content: space-between;
  }

  .code-btn {
    width: 100%;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
