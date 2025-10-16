<template>
  <div class="chart-container">
    <h3 class="chart-title">Ofertas Activas (Últimos 7 días)</h3>
    <div v-if="loading" class="loading-state">
      <p>Cargando estadísticas...</p>
    </div>
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
    </div>
    <div v-else>
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
            <span class="summary-label">Tipos de Establecimientos</span>
            <span class="summary-value">{{ establishmentTypesCount }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Más Popular</span>
            <span class="summary-value">{{ mostPopularCategory }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Promedio por Tipo</span>
            <span class="summary-value">{{ averagePerType }}</span>
          </div>
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
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import axiosInstance from "@/lib/axios.js"

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

const chartKey = ref(0)
const loading = ref(true)
const error = ref(null)
const statsData = ref([])

const colors = [
  '#6f6c69', '#5f5c59', '#3e3b39', '#7f7c79',
  '#8f8c89', '#9f9c99', '#4a4745', '#aeaba8'
]

const fetchOfferStats = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await axiosInstance.get('/adm/offer-stats')

    if (response.data && response.data.data) {
      statsData.value = response.data.data
    } else {
      statsData.value = []
    }
  } catch (err) {
    console.error('Error al cargar estadísticas:', err)
    error.value = 'Error al cargar las estadísticas de ofertas'
  } finally {
    loading.value = false
    chartKey.value++
  }
}

const totalActiveOffers = computed(() => {
  return statsData.value.reduce((sum, item) => sum + item.count, 0)
})

const establishmentTypesCount = computed(() => {
  return statsData.value.length
})

const mostPopularCategory = computed(() => {
  if (statsData.value.length === 0) return 'N/A'
  const maxItem = statsData.value.reduce((max, item) =>
    item.count > max.count ? item : max
  , { count: 0, establishment_type: 'N/A' })
  return maxItem.establishment_type
})

const averagePerType = computed(() => {
  if (statsData.value.length === 0) return 0
  return Math.round(totalActiveOffers.value / statsData.value.length)
})

const chartData = computed(() => {
  return {
    labels: statsData.value.map(item => item.establishment_type),
    datasets: [
      {
        label: 'Ofertas activas',
        backgroundColor: statsData.value.map((_, index) => colors[index % colors.length]),
        borderColor: '#ffffff',
        borderWidth: 1,
        data: statsData.value.map(item => item.count),
        borderRadius: 6,
        borderSkipped: false,
      }
    ]
  }
})

const chartOptions = computed(() => {
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
            const total = context.dataset.data.reduce((a, b) => a + b, 0)
            const percentage = total > 0 ? ((context.parsed.x / total) * 100).toFixed(1) : 0
            return `${context.parsed.x} ofertas (${percentage}%)`
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
})

onMounted(() => {
  fetchOfferStats()
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

.loading-state,
.error-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
  color: var(--color-text);
}

.error-state p {
  color: #ff6b6b;
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

@media (max-width: 768px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
