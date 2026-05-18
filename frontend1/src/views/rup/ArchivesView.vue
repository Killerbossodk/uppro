<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Chart from 'chart.js/auto'

const toast = useToast()

const chartCanvas1 = ref<HTMLCanvasElement | null>(null)
const chartCanvas2 = ref<HTMLCanvasElement | null>(null)

onMounted(() => {
  if (chartCanvas1.value) {
    new Chart(chartCanvas1.value, {
      type: 'bar',
      data: {
        labels: ['2023', '2024', '2025', '2026'],
        datasets: [{
          label: 'Nombre de projets achevés',
          data: [42, 48, 55, 60],
          backgroundColor: 'rgba(125, 33, 64, 0.8)',
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } }
      }
    })
  }

  if (chartCanvas2.value) {
    new Chart(chartCanvas2.value, {
      type: 'line',
      data: {
        labels: ['2023', '2024', '2025', '2026'],
        datasets: [{
          label: 'Moyenne générale de promotion',
          data: [13.2, 13.8, 14.1, 14.5],
          borderColor: 'rgba(16, 185, 129, 1)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { min: 10, max: 20, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } }
      }
    })
  }
})

const showArchiveModal = ref(false)
const confirmText = ref('')

function archiveYear() {
  if (confirmText.value === 'CLOTURE 2026') {
    toast.add({ severity: 'success', summary: 'Année clôturée', detail: 'Toutes les données 2025/2026 ont été archivées. La plateforme est prête pour l\'année prochaine.', life: 5000 })
    showArchiveModal.value = false
    confirmText.value = ''
  } else {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le texte de confirmation est incorrect.', life: 3000 })
  }
}
</script>

<template>
  <div class="archives-page">
    <div class="page-header animate-fade-in">
      <div>
        <h2 class="page-title-h2">Archives & Historique</h2>
        <p class="text-secondary text-sm">Comparez les performances inter-années et clôturez l'année universitaire.</p>
      </div>
      <button class="btn btn-danger" @click="showArchiveModal = true">
        <i class="pi pi-lock" /> Clôturer l'année 2025/2026
      </button>
    </div>

    <!-- Comparative Stats -->
    <div class="stats-grid animate-fade-in">
      <div class="card stat-card">
        <h3 class="stat-title">Projets réalisés</h3>
        <p class="stat-val text-primary">+9%</p>
        <p class="text-xs text-muted">Par rapport à 2025</p>
      </div>
      <div class="card stat-card">
        <h3 class="stat-title">Taux de réussite</h3>
        <p class="stat-val text-success">98.5%</p>
        <p class="text-xs text-muted">Constant depuis 2025</p>
      </div>
      <div class="card stat-card">
        <h3 class="stat-title">Moyenne globale</h3>
        <p class="stat-val text-warning">14.5/20</p>
        <p class="text-xs text-muted">+0.4 pt par rapport à 2025</p>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid animate-fade-in" style="animation-delay: 100ms">
      <div class="card chart-card">
        <h3 class="text-lg font-bold mb-4">Évolution du nombre de projets</h3>
        <div class="chart-container">
          <canvas ref="chartCanvas1"></canvas>
        </div>
      </div>
      
      <div class="card chart-card">
        <h3 class="text-lg font-bold mb-4">Évolution des notes moyennes</h3>
        <div class="chart-container">
          <canvas ref="chartCanvas2"></canvas>
        </div>
      </div>
    </div>

    <!-- Archive Modal -->
    <Transition name="page">
      <div v-if="showArchiveModal" class="modal-overlay" @click.self="showArchiveModal = false">
        <div class="modal-card card">
          <div class="modal-header">
            <h3 class="text-danger"><i class="pi pi-exclamation-triangle" /> Clôture de l'année</h3>
            <button class="btn btn-ghost btn-icon btn-sm" @click="showArchiveModal = false"><i class="pi pi-times" /></button>
          </div>
          <div class="modal-body">
            <p class="text-sm mb-4">Vous êtes sur le point de clôturer et d'archiver toutes les données de l'année universitaire <strong>2025/2026</strong>. Cette action est irréversible et passera tous les projets, groupes et rapports en "lecture seule".</p>
            <p class="text-sm font-bold text-danger mb-2">Pour confirmer, veuillez taper "CLOTURE 2026" ci-dessous :</p>
            <input v-model="confirmText" type="text" class="form-input" placeholder="Taper ici..." />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showArchiveModal = false">Annuler</button>
            <button class="btn btn-danger" @click="archiveYear" :disabled="confirmText !== 'CLOTURE 2026'">Confirmer la clôture</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.archives-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; margin-bottom: 0.3rem; }

.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.stat-card { padding: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
.stat-title { font-size: var(--text-sm); font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.stat-val { font-family: var(--font-display); font-size: var(--text-3xl); font-weight: 800; }

.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.chart-card { padding: 1.5rem; }
.chart-container { position: relative; height: 300px; width: 100%; }

.btn-danger {
  background: rgba(239, 68, 68, 0.15);
  color: var(--color-danger);
  border: 1px solid rgba(239, 68, 68, 0.3);
}
.btn-danger:hover { background: rgba(239, 68, 68, 0.25); }
.text-danger { color: var(--color-danger); }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(6px); }
.modal-card { width: 100%; max-width: 480px; padding: 0; border: 1px solid var(--color-danger); box-shadow: 0 0 30px rgba(239,68,68,0.2); }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-default); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 1.5rem; }
.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-default); display: flex; justify-content: flex-end; gap: 0.75rem; }

@media (max-width: 900px) {
  .stats-grid, .charts-grid { grid-template-columns: 1fr; }
}
</style>
