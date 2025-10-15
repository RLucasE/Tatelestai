<template>
  <div class="chart-container">
    <h3 class="chart-title">Usuarios Activos</h3>
    <div class="chart-wrapper">
      <Doughnut
        :data="chartData"
        :options="chartOptions"
        :key="chartKey"
      />
    </div>
    <div class="user-stats">
      <div class="stat-row">
        <div class="stat-item active">
          <div class="stat-indicator"></div>
          <span class="stat-label">Activos</span>
          <span class="stat-value">{{ usersData.active }}</span>
        </div>
        <div class="stat-item inactive">
          <div class="stat-indicator"></div>
          <span class="stat-label">Inactivos</span>
          <span class="stat-value">{{ usersData.inactive }}</span>
        </div>
      </div>
      <div class="stat-row">
        <div class="stat-item pending">
          <div class="stat-indicator"></div>
          <span class="stat-label">Pendientes</span>
          <span class="stat-value">{{ usersData.pending }}</span>
        </div>
        <div class="stat-item total">
          <div class="stat-indicator"></div>
          <span class="stat-label">Total</span>
          <span class="stat-value">{{ totalUsers }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend)

export default {
  name: 'ActiveUsersChart',
  components: {
    Doughnut
  },
  data() {
    return {
      chartKey: 0,
      // Datos falsos para demostración
      usersData: {
        active: 342,
        inactive: 28,
        pending: 15
      }
    }
  },
  computed: {
    totalUsers() {
      return this.usersData.active + this.usersData.inactive + this.usersData.pending
    },
    chartData() {
      return {
        labels: ['Activos', 'Inactivos', 'Pendientes'],
        datasets: [
          {
            data: [this.usersData.active, this.usersData.inactive, this.usersData.pending],
            backgroundColor: [
              '#6f6c69',
              '#5f5c59',
              '#3e3b39'
            ],
            borderColor: [
              '#ffffff',
              '#ffffff',
              '#ffffff'
            ],
            borderWidth: 2,
            hoverBackgroundColor: [
              '#7f7c79',
              '#6f6c69',
              '#4e4b49'
            ]
          }
        ]
      }
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#22201f',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#6f6c69',
            borderWidth: 1,
            callbacks: {
              label: function(context) {
                const percentage = ((context.parsed / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                return `${context.label}: ${context.parsed} (${percentage}%)`;
              }
            }
          }
        },
        cutout: '60%'
      }
    }
  },
  mounted() {
    this.chartKey++
  }
}
</script>

<style scoped>
.chart-container {
  background: var(--color-bg);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  height: 100%;
}

.chart-title {
  color: var(--color-text);
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1rem;
  text-align: center;
}

.chart-wrapper {
  height: 250px;
  position: relative;
  margin-bottom: 1rem;
}

.user-stats {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-row {
  display: flex;
  gap: 0.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
}

.stat-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.stat-item.active .stat-indicator {
  background: #6f6c69;
}

.stat-item.inactive .stat-indicator {
  background: #5f5c59;
}

.stat-item.pending .stat-indicator {
  background: #3e3b39;
}

.stat-item.total .stat-indicator {
  background: var(--color-text);
}

.stat-label {
  color: var(--color-text);
  font-size: 0.875rem;
  flex: 1;
}

.stat-value {
  color: var(--color-text);
  font-weight: 600;
  font-size: 1rem;
}
</style>
