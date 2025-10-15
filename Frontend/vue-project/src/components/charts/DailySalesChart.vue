<template>
  <div class="chart-container">
    <h3 class="chart-title">Ventas del día (últimas 24h)</h3>
    <div class="chart-wrapper" v-if="!loading">
      <Line
        :data="chartData"
        :options="chartOptions"
        :key="chartKey"
      />
    </div>
    <div v-else class="loading-container">
      <div class="loading-spinner"></div>
      <p>Cargando datos...</p>
    </div>
    <div class="chart-summary" v-if="!loading">
      <div class="summary-stats">
        <div class="stat">
          <span class="stat-label">Total del día:</span>
          <span class="stat-value">{{ totalSales }} ventas</span>
        </div>
        <div class="stat">
          <span class="stat-label">Pico máximo:</span>
          <span class="stat-value">{{ maxSales }} ventas</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'
import axiosInstance from "@/lib/axios.js";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const chartKey = ref(0)
const loading = ref(true)
const salesData = ref({
  labels: [],
  values: []
})

const totalSales = computed(() => {
  return salesData.value.values.reduce((sum, value) => sum + value, 0)
})

const maxSales = computed(() => {
  return salesData.value.values.length > 0 ? Math.max(...salesData.value.values) : 0
})

const chartData = computed(() => ({
  labels: salesData.value.labels,
  datasets: [
    {
      label: 'Ventas por intervalo de 2 horas',
      backgroundColor: 'rgba(111, 108, 105, 0.2)',
      borderColor: '#6f6c69',
      borderWidth: 3,
      data: salesData.value.values,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#3e3b39',
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 5,
      pointHoverRadius: 8
    }
  ]
}))

const chartOptions = computed(() => ({
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
      borderWidth: 1,
      callbacks: {
        label: function(context) {
          return `Ventas: ${context.parsed.y}`;
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
}))

const loadSalesData = async () => {
  try {
    loading.value = true
    const response = await axiosInstance.get('adm/last-sells')

    const intervals = response.data.data

    console.log(response.data);

    salesData.value.labels = intervals.map(interval => `${interval.from}-${interval.to}`)
    salesData.value.values = intervals.map(interval => interval.count)

  } catch (error) {
    console.error('Error loading sales data:', error)
    salesData.value.labels = ['Sin datos']
    salesData.value.values = [0]
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadSalesData()
  chartKey.value++
})
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

.loading-container {
  height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--color-text);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-secondary);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.chart-summary {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-secondary);
}

.summary-stats {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-label {
  color: var(--color-text);
  font-size: 0.875rem;
  opacity: 0.9;
}

.stat-value {
  color: var(--color-text);
  font-size: 1.125rem;
  font-weight: 700;
}
</style>
