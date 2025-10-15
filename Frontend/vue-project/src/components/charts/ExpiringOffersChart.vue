<template>
  <div class="chart-container">
    <h3 class="chart-title">Ofertas que caducan esta semana</h3>
    <div class="chart-wrapper">
      <Bar
        :data="chartData"
        :options="chartOptions"
        :key="chartKey"
      />
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
  name: 'ExpiringOffersChart',
  components: {
    Bar
  },
  data() {
    return {
      chartKey: 0,
      // Datos falsos para demostración
      offersData: {
        labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
        values: [12, 8, 15, 6, 20, 4, 3]
      }
    }
  },
  computed: {
    chartData() {
      return {
        labels: this.offersData.labels,
        datasets: [
          {
            label: 'Ofertas que caducan',
            backgroundColor: '#6f6c69',
            borderColor: '#5f5c59',
            borderWidth: 2,
            data: this.offersData.values,
            borderRadius: 8,
            borderSkipped: false,
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
            display: true,
            labels: {
              color: '#ffffff',
              font: {
                size: 14
              }
            }
          },
          tooltip: {
            backgroundColor: '#22201f',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#6f6c69',
            borderWidth: 1
          }
        },
        scales: {
          x: {
            ticks: {
              color: '#ffffff'
            },
            grid: {
              color: '#5f5c59'
            }
          },
          y: {
            ticks: {
              color: '#ffffff'
            },
            grid: {
              color: '#5f5c59'
            },
            beginAtZero: true
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
}
</style>
