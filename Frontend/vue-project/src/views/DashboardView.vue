<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Panel de Control</h1>
      <p class="dashboard-subtitle">Estadísticas y métricas de la aplicación</p>
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

export default {
  name: 'DashboardView',
  components: {
    ExpiringOffersChart,
    DailySalesChart,
    ActiveUsersChart,
    ActiveOffersChart
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
  text-align: center;
  margin-bottom: 2rem;
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
