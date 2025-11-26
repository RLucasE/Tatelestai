<template>
  <div class="purchase-card">
    <div class="card-header">
      <div class="header-info">
        <span class="purchase-date">{{ formatDate(purchase.created_at) }}</span>
        <span class="purchase-seller">{{ purchase.establishment?.name || purchase.sold_by }}</span>
        <router-link
          v-if="purchase.establishment?.address"
          :to="`/customer/establishment/${purchase.establishment.id}`"
          class="establishment-address"
        >
           {{ purchase.establishment.address }}
        </router-link>
      </div>
      <div class="status-badge" :class="getStatusClass(purchase.state)">
        {{ getStatusLabel(purchase.state) }}
      </div>
    </div>

    <div class="card-info-bar">
      <div class="info-item">
        <span class="info-label">Retirar antes de:</span>
        <span class="info-value" :class="{ 'expired': isExpired(purchase.max_pickup_datetime) }">
          {{ formatPickupDeadline(purchase.max_pickup_datetime) }}
        </span>
      </div>
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
    formatPickupDeadline(dateStr) {
      if (!dateStr) return 'No especificado';
      const date = new Date(dateStr);
      const now = new Date();

      // Si ya expiró
      if (date < now) {
        return `Expirado el ${date.toLocaleDateString('es-AR', {
          day: 'numeric',
          month: 'short',
          hour: '2-digit',
          minute: '2-digit'
        })}`;
      }

      // Calcular diferencia en horas
      const diffMs = date - now;
      const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
      const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

      if (diffHours < 1) {
        return `${diffMinutes} minutos`;
      } else if (diffHours < 24) {
        return `${diffHours}h ${diffMinutes}m`;
      } else {
        return date.toLocaleDateString('es-AR', {
          day: 'numeric',
          month: 'short',
          hour: '2-digit',
          minute: '2-digit'
        });
      }
    },
    isExpired(dateStr) {
      if (!dateStr) return false;
      return new Date(dateStr) < new Date();
    },
    getStatusLabel(state) {
      const labels = {
        'pending': 'Pendiente',
        'confirmed': 'Confirmado',
        'ready': 'Listo para retirar',
        'picked_up': 'Retirado',
        'cancelled': 'Cancelado',
        'expired': 'Expirado'
      };
      return labels[state] || state;
    },
    getStatusClass(state) {
      return `status-${state}`;
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

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
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

.establishment-address {
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.6;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.125rem;
  text-decoration: none;
  cursor: pointer;
  transition: opacity 0.2s ease, color 0.2s ease;
}

.establishment-address:hover {
  opacity: 1;
  color: var(--color-accent);
  text-decoration: underline;
}

.status-badge {
  padding: 0.375rem 0.875rem;
  border-radius: var(--border-radius);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.status-ready {
  background: #d1fae5;
  color: #065f46;
}

.status-picked_up {
  background: #d1d5db;
  color: #374151;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.status-expired {
  background: #fecaca;
  color: #7f1d1d;
}

.card-info-bar {
  padding: 0.875rem 1.25rem;
  background: var(--color-secondary);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: var(--color-text);
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.info-value {
  font-size: 0.875rem;
  color: var(--color-text);
  font-weight: 600;
}

.info-value.expired {
  color: #dc2626;
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
    gap: 0.75rem;
  }

  .header-info {
    width: 100%;
  }

  .status-badge {
    align-self: flex-start;
  }

  .card-info-bar {
    flex-direction: column;
    gap: 0.75rem;
  }
}
</style>
