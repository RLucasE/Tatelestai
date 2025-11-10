<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="dashboard-title">Panel de Control</h1>
          <p class="dashboard-subtitle">Estadísticas y métricas clave de la aplicación.</p>
        </div>
        <button
          @click="downloadExcel"
          :disabled="isDownloading"
          class="download-btn"
        >
          <svg v-if="!isDownloading" class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
          </svg>
          <span v-if="!isDownloading">Descargar Reporte</span>
          <span v-else>Descargando...</span>
        </button>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-item span-2">
        <DailySalesChart />
      </div>
      <div class="chart-item">
        <ActiveUsersChart />
      </div>
      <div class="chart-item">
        <ActiveOffersChart />
      </div>
      <div class="chart-item span-2">
        <ExpiringOffersChart />
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
  background-color: var(--color-bg);
}

.dashboard-header {
  margin-bottom: 2.5rem;
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
  font-size: 2.25rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
}

.dashboard-subtitle {
  color: var(--color-text);
  font-size: 1.125rem;
  opacity: 0.7;
}

.download-btn {
  background-color: var(--color-accent);
  color: var(--vt-c-white);
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.download-btn .icon {
  width: 1.25rem;
  height: 1.25rem;
}

.download-btn:hover:not(:disabled) {
  background-color: var(--color-accent-hover);
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.download-btn:active:not(:disabled) {
  transform: translateY(0);
}

.download-btn:disabled {
  background-color: var(--color-secondary);
  opacity: 0.6;
  cursor: not-allowed;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.chart-item {
  display: flex;
  flex-direction: column;
  min-height: 420px;
}

.chart-item.span-2 {
  grid-column: span 2 / span 2;
}

/* Responsive design */
@media (max-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-item.span-2 {
    grid-column: span 1 / span 1;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1.5rem;
  }

  .dashboard-title {
    font-size: 1.875rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .download-btn {
    width: 100%;
    justify-content: center;
    padding: 0.875rem;
  }
}
</style>
