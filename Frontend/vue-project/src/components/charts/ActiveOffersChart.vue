<template>
  <div class="chart-container">
    <h3 class="chart-title">Ofertas Activas</h3>
    <div class="chart-wrapper">
      <Bar
        :data="chartData"
        :options="chartOptions"
        :key="chartKey"
      />
    </div>
    <div class="offers-summary">
      <div class="summary-grid">
        <div class="summary-item">
          <span class="summary-label">Total Activas</span>
          <span class="summary-value">{{ totalActiveOffers }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Nuevas Hoy</span>
          <span class="summary-value">{{ newTodayOffers }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Más Popular</span>
          <span class="summary-value">{{ mostPopularCategory }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Promedio Diario</span>
          <span class="summary-value">{{ averageDaily }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

export default {
  name: 'ActiveOffersChart',
  components: {
    Bar
  },
  data() {
    return {
      chartKey: 0,
      // Datos falsos para demostración por categorías
      offersData: {
        labels: ['Comida Rápida', 'Restaurantes', 'Cafeterías', 'Panaderías', 'Postres', 'Bebidas'],
        values: [45, 32, 28, 19, 15, 12],
        colors: ['#6f6c69', '#5f5c59', '#3e3b39', '#7f7c79', '#8f8c89', '#9f9c99']
      },
      newTodayOffers: 8,
      averageDaily: 23
    }
  },
  computed: {
    totalActiveOffers() {
      return this.offersData.values.reduce((sum, value) => sum + value, 0)
    },
    mostPopularCategory() {
      const maxIndex = this.offersData.values.indexOf(Math.max(...this.offersData.values))
      return this.offersData.labels[maxIndex]
    },
    chartData() {
      return {
        labels: this.offersData.labels,
        datasets: [
          {
            label: 'Ofertas activas',
            backgroundColor: this.offersData.colors,
            borderColor: '#ffffff',
            borderWidth: 1,
            data: this.offersData.values,
            borderRadius: 6,
            borderSkipped: false,
          }
        ]
      }
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
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
                const percentage = ((context.parsed.x / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                return `${context.parsed.x} ofertas (${percentage}%)`;
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              color: '#ffffff'
            },
            grid: {
              color: '#5f5c59'
            },
            beginAtZero: true
          },
          y: {
            ticks: {
              color: '#ffffff'
            },
            grid: {
              color: '#5f5c59'
            }
          }
        }
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
  height: 300px;
  position: relative;
  margin-bottom: 1rem;
}

.offers-summary {
  padding-top: 1rem;
  border-top: 1px solid var(--color-secondary);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
}

.summary-label {
  color: var(--color-text);
  font-size: 0.875rem;
  opacity: 0.9;
  margin-bottom: 0.25rem;
}

.summary-value {
  color: var(--color-text);
  font-size: 1.125rem;
  font-weight: 700;
}
</style>
