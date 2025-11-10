<template>
  <div class="sell-card">
    <div class="card-header">
      <span class="sell-date">{{ formatDate(sell.created_at) }}</span>
      <span class="customer">{{ sell.customer_name }}</span>
      <span class="establishment">{{ sell.establishment_name }}</span>
    </div>

    <table class="details-table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Cant.</th>
          <th>Precio</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="detail in sell.sell_details" :key="detail.id">
          <td>
            <span class="product-name">{{ detail.product_name }}</span>
            <span v-if="detail.offer_quantity > 1" class="offer-mult">×{{ detail.offer_quantity }}</span>
          </td>
          <td class="quantity">{{ detail.product_quantity }}</td>
          <td class="price">${{ detail.product_price }}</td>
          <td class="subtotal">${{ (detail.offer_quantity * detail.product_quantity * detail.product_price).toFixed(2) }}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="total-label">Total</td>
          <td class="total-amount">${{ calculateTotal() }}</td>
        </tr>
      </tfoot>
    </table>
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
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.card-header {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border);
  font-size: 0.8125rem;
}

.sell-date {
  font-weight: 600;
  color: var(--color-text);
}

.customer,
.establishment {
  color: var(--color-text);
  opacity: 0.6;
}

.details-table {
  width: 100%;
  border-collapse: collapse;
}

.details-table thead th {
  text-align: left;
  padding: 0.625rem 1rem;
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.5;
  background: var(--color-bg);
}

.details-table tbody tr {
  border-bottom: 1px solid var(--color-border);
}

.details-table tbody tr:last-child {
  border-bottom: none;
}

.details-table tbody td {
  padding: 0.75rem 1rem;
  color: var(--color-text);
  font-size: 0.8125rem;
}

.product-name {
  color: var(--color-text);
}

.offer-mult {
  display: inline-block;
  margin-left: 0.5rem;
  color: var(--color-accent);
  font-weight: 600;
  font-size: 0.75rem;
}

.quantity,
.price,
.subtotal {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.details-table tfoot td {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-bg);
}

.total-label {
  font-weight: 600;
  color: var(--color-text);
  opacity: 0.6;
}

.total-amount {
  text-align: right;
  font-weight: 700;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}

@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .details-table {
    font-size: 0.75rem;
  }

  .details-table thead {
    display: none;
  }

  .details-table tbody tr {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
  }

  .details-table tbody td:first-child {
    grid-column: 1 / -1;
  }

  .details-table tbody td {
    padding: 0;
  }

  .quantity::before {
    content: "Cant: ";
    opacity: 0.5;
  }

  .price::before {
    content: "Precio: ";
    opacity: 0.5;
  }

  .subtotal {
    grid-column: 1 / -1;
    text-align: left;
    font-weight: 600;
  }

  .subtotal::before {
    content: "Subtotal: ";
    opacity: 0.5;
  }

  .details-table tfoot tr {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 1rem;
  }

  .details-table tfoot td {
    padding: 0;
  }
}
</style>
