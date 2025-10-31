<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="dashboard-title">Panel de Control</h1>
          <p class="dashboard-subtitle">Estadísticas y métricas de la aplicación</p>
        </div>
        <button
          @click="downloadExcel"
          :disabled="isDownloading"
          class="download-btn"
        >
          <span v-if="!isDownloading">Descargar Excel</span>
          <span v-else>⏳ Descargando...</span>
        </button>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-item">
        <ExpiringOffersChart />
      </div>

      <div class="chart-item">
        <DailySalesChart />
      </div>

      <div class="chart-item">
        <ActiveUsersChart />
      </div>

      <div class="chart-item">
        <ActiveOffersChart />
      </div>
    </div>
  </div>
</template>

<script>
import ExpiringOffersChart from '@/components/charts/ExpiringOffersChart.vue'
import DailySalesChart from '@/components/charts/DailySalesChart.vue'
import ActiveUsersChart from '@/components/charts/ActiveUsersChart.vue'
import ActiveOffersChart from '@/components/charts/ActiveOffersChart.vue'
import axiosInstance from '@/lib/axios'

export default {
  name: 'DashboardView',
  components: {
    ExpiringOffersChart,
    DailySalesChart,
    ActiveUsersChart,
    ActiveOffersChart
  },
  data() {
    return {
      isDownloading: false
    }
  },
  methods: {
    async downloadExcel() {
      try {
        this.isDownloading = true

        const response = await axiosInstance.get('/adm/export-dashboard', {
          responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'dashboard-stats.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

      } catch (error) {
        console.error('Error al descargar el Excel:', error)
        alert('Error al descargar el archivo Excel')
      } finally {
        this.isDownloading = false
      }
    }
  }
}
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
  min-height: 100vh;
  background: linear-gradient(135deg, var(--color-darkest) 0%, var(--color-focus) 100%);
}

.dashboard-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-text {
  flex: 1;
}

.dashboard-title {
  color: var(--color-text);
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: var(--color-text);
  font-size: 1.125rem;
  opacity: 0.8;
}

.download-btn {
  background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.75rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  white-space: nowrap;
}

.download-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.download-btn:active:not(:disabled) {
  transform: translateY(0);
}

.download-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.chart-item {
  min-height: 400px;
}

/* Responsive design */
@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }

  .dashboard-title {
    font-size: 2rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .download-btn {
    width: 100%;
    padding: 1rem;
  }

  .charts-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .chart-item {
    min-height: 350px;
  }
}

@media (max-width: 480px) {
  .dashboard-title {
    font-size: 1.75rem;
  }

  .dashboard-subtitle {
    font-size: 1rem;
  }
}
</style>
