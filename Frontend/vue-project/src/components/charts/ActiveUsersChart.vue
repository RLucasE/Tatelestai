<template>
  <div class="chart-container">
    <h3 class="chart-title">Usuarios por Estado</h3>
    <div v-if="loading" class="loading-state">
      <p>Cargando estadísticas...</p>
    </div>
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
    </div>
    <div v-else>
      <div class="chart-wrapper">
        <Doughnut
          :data="chartData"
          :options="chartOptions"
          :key="chartKey"
        />
      </div>
      <div class="user-stats">
        <div class="stat-grid">
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
          <div class="stat-item waiting">
            <div class="stat-indicator"></div>
            <span class="stat-label">Esperando</span>
            <span class="stat-value">{{ usersData.waiting }}</span>
          </div>
          <div class="stat-item denied">
            <div class="stat-indicator"></div>
            <span class="stat-label">Denegados</span>
            <span class="stat-value">{{ usersData.denied }}</span>
          </div>
          <div class="stat-item selecting">
            <div class="stat-indicator"></div>
            <span class="stat-label">Seleccionando</span>
            <span class="stat-value">{{ usersData.selecting }}</span>
          </div>
          <div class="stat-item registering">
            <div class="stat-indicator"></div>
            <span class="stat-label">Registrando</span>
            <span class="stat-value">{{ usersData.registering }}</span>
          </div>
          <div class="stat-item total">
            <div class="stat-indicator"></div>
            <span class="stat-label">Total</span>
            <span class="stat-value">{{ totalUsers }}</span>
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
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import axiosInstance from '@/lib/axios.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const chartKey = ref(0)
const loading = ref(true)
const error = ref(null)
const statsData = ref([])
const totalUsers = ref(0)

const usersData = computed(() => {
  const active = statsData.value.find(item => item.state === 'active')?.count || 0
  const inactive = statsData.value.find(item => item.state === 'inactive')?.count || 0
  const waiting = statsData.value.find(item => item.state === 'waiting_for_confirmation')?.count || 0
  const denied = statsData.value.find(item => item.state === 'denied_confirmation')?.count || 0
  const selecting = statsData.value.find(item => item.state === 'selecting')?.count || 0
  const registering = statsData.value.find(item => item.state === 'registering')?.count || 0

  return {
    active,
    inactive,
    waiting,
    denied,
    selecting,
    registering
  }
})

const fetchUserStats = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await axiosInstance.get('/adm/user-stats')

    if (response.data && response.data.data) {
      statsData.value = response.data.data
      totalUsers.value = response.data.total || 0
    } else {
      statsData.value = []
      totalUsers.value = 0
    }
  } catch (err) {
    console.error('Error al cargar estadísticas de usuarios:', err)
    error.value = 'Error al cargar las estadísticas de usuarios'
  } finally {
    loading.value = false
    chartKey.value++
  }
}

const chartData = computed(() => {
  return {
    labels: ['Activos', 'Inactivos', 'Esperando Confirmación', 'Denegados', 'Seleccionando', 'Registrando'],
    datasets: [
      {
        data: [
          usersData.value.active,
          usersData.value.inactive,
          usersData.value.waiting,
          usersData.value.denied,
          usersData.value.selecting,
          usersData.value.registering
        ],
        backgroundColor: [
          '#6f6c69',
          '#5f5c59',
          '#3e3b39',
          '#8f8c89',
          '#4a4745',
          '#aeaba8'
        ],
        borderColor: [
          '#ffffff',
          '#ffffff',
          '#ffffff',
          '#ffffff',
          '#ffffff',
          '#ffffff'
        ],
        borderWidth: 2,
        hoverBackgroundColor: [
          '#7f7c79',
          '#6f6c69',
          '#4e4b49',
          '#9f9c99',
          '#5a5755',
          '#bebbb8'
        ]
      }
    ]
  }
})

const chartOptions = computed(() => {
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
            const total = context.dataset.data.reduce((a, b) => a + b, 0)
            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0
            return `${context.label}: ${context.parsed} (${percentage}%)`
          }
        }
      }
    },
    cutout: '60%'
  }
})

onMounted(() => {
  fetchUserStats()
})
</script>

<style scoped>
.chart-container {
  background: var(--color-bg);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 4px 12px rgba(0, 0, 0, 0.2);
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
  height: 250px;
  position: relative;
  margin-bottom: 1rem;
}

.user-stats {
  display: flex;
  flex-direction: column;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
}

.stat-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.stat-item.active .stat-indicator {
  background: #6f6c69;
}

.stat-item.inactive .stat-indicator {
  background: #5f5c59;
}

.stat-item.waiting .stat-indicator {
  background: #3e3b39;
}

.stat-item.denied .stat-indicator {
  background: #8f8c89;
}

.stat-item.selecting .stat-indicator {
  background: #4a4745;
}

.stat-item.registering .stat-indicator {
  background: #aeaba8;
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

@media (max-width: 768px) {
  .stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>
