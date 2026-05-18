<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(false)
const rawStats = ref<any>(null)
const specialites = ref<any[]>([])
const filters = ref({ annee: '2024/2025', specialite: 'all' })

const stats = computed(() => {
  if (!rawStats.value) return {}
  const data = rawStats.value
  return {
    ...data,
    total_projets: data.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0,
    projets_valides: data.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0,
  }
})

const maxProjects = computed(() => Math.max(...(stats.value.top_professeurs?.map((p: any) => p.total) || [1]), 1))

onMounted(async () => {
  await Promise.all([fetchStats(), fetchSpecialites()])
})

async function fetchStats() {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.annee !== 'all') params.annee = filters.value.annee
    if (filters.value.specialite !== 'all') params.specialite_id = filters.value.specialite
    const res = await api.get('/analytics', { params })
    rawStats.value = res.data
  } catch (e) {
    console.error('Erreur analytics:', e)
  } finally {
    loading.value = false
  }
}

async function fetchSpecialites() {
  try {
    const res = await api.get('/specialites')
    specialites.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}

function getPercentage(value: number) {
  const max = Math.max(...(stats.value.projets_par_statut?.map((s: any) => s.total) || [1]))
  return max > 0 ? Math.round((value / max) * 100) : 0
}

async function exportExcel() {
  try {
    const res = await api.get('/export/projects', { params: filters.value, responseType: 'blob' })
    downloadFile(res.data, 'projets.xlsx')
  } catch { alert('Erreur export Excel') }
}

async function exportPDF() {
  try {
    const res = await api.get('/export/analytics/pdf', { params: filters.value, responseType: 'blob' })
    downloadFile(res.data, 'statistiques.pdf')
  } catch { alert('Erreur export PDF') }
}

function downloadFile(data: Blob, filename: string) {
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url; link.download = filename; link.click(); link.remove()
}
</script>

<template>
  <div class="analytics-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Tableau de bord analytique</h2>
        <p class="text-muted text-sm">Statistiques et indicateurs</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-success btn-sm" @click="exportExcel">📊 Excel</button>
        <button class="btn btn-danger btn-sm" @click="exportPDF">📄 PDF</button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
      <div class="flex gap-4 items-end flex-wrap">
        <div class="form-group">
          <label class="form-label">Année</label>
          <select v-model="filters.annee" class="form-input">
            <option value="2024/2025">2024/2025</option>
            <option value="2023/2024">2023/2024</option>
            <option value="all">Toutes</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Spécialité</label>
          <select v-model="filters.specialite" class="form-input">
            <option value="all">Toutes</option>
            <option v-for="s in specialites" :key="s.id" :value="s.id">{{ s.nom }}</option>
          </select>
        </div>
        <button class="btn btn-primary btn-sm" @click="fetchStats">Appliquer</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-muted">Chargement...</div>

    <template v-else-if="rawStats">
      <!-- KPIs -->
      <div class="stats-grid mb-4">
        <div class="card kpi-card">
          <p class="text-sm text-muted">Total projets</p>
          <p class="kpi-value">{{ stats.total_projets }}</p>
        </div>
        <div class="card kpi-card">
          <p class="text-sm text-muted">Projets validés</p>
          <p class="kpi-value text-success">{{ stats.projets_valides }}</p>
        </div>
        <div class="card kpi-card">
          <p class="text-sm text-muted">Taux de dépôt</p>
          <p class="kpi-value text-info">{{ rawStats.taux_depot || 0 }}%</p>
        </div>
        <div class="card kpi-card">
          <p class="text-sm text-muted">Activité (30j)</p>
          <p class="kpi-value text-warning">{{ rawStats.activite_recente || 0 }}</p>
        </div>
      </div>

      <!-- Projets par statut / spécialité -->
      <div class="grid-2 mb-4">
        <div class="card">
          <h3 class="section-title">📊 Projets par statut</h3>
          <div v-for="item in rawStats.projets_par_statut" :key="item.statut" class="bar-row">
            <span class="bar-label">{{ item.statut }}</span>
            <div class="bar"><div class="bar-fill" :style="{ width: getPercentage(item.total) + '%' }" /></div>
            <span class="bar-val">{{ item.total }}</span>
          </div>
        </div>
        <div class="card">
          <h3 class="section-title">🏷️ Projets par spécialité</h3>
          <div v-for="item in rawStats.projets_par_specialite" :key="item.nom" class="bar-row">
            <span class="bar-label">{{ item.nom }}</span>
            <div class="bar"><div class="bar-fill bg-blue" :style="{ width: getPercentage(item.projects_count) + '%' }" /></div>
            <span class="bar-val">{{ item.projects_count }}</span>
          </div>
        </div>
      </div>

      <!-- Top profs / Moyenne notes -->
      <div class="grid-2">
        <div class="card">
          <h3 class="section-title">🏆 Top 5 professeurs</h3>
          <div v-for="(p, i) in rawStats.top_professeurs" :key="i" class="bar-row">
            <span class="bar-label">#{{ Number(i) + 1 }} {{ p.prenom }} {{ p.name }}</span>
            <div class="bar"><div class="bar-fill bg-blue" :style="{ width: (p.total / maxProjects) * 100 + '%' }" /></div>
            <span class="bar-val">{{ p.total }}</span>
          </div>
        </div>
        <div class="card">
          <h3 class="section-title">📝 Moyenne des notes</h3>
          <div v-for="item in rawStats.moyenne_notes" :key="item.nom" class="flex justify-between py-2 border-b last:border-0">
            <span>{{ item.nom }}</span>
            <span class="font-bold">{{ parseFloat(item.moyenne).toFixed(1) }}/20</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.analytics-page { max-width: 1400px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.kpi-card { text-align: center; padding: 1.5rem; }
.kpi-value { font-family: var(--font-display); font-size: var(--text-4xl); font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.section-title { font-weight: 700; margin-bottom: 1rem; }
.bar-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
.bar-label { width: 100px; font-size: var(--text-sm); text-transform: capitalize; flex-shrink: 0; }
.bar { flex: 1; height: 8px; background: var(--bg-active); border-radius: var(--radius-full); overflow: hidden; }
.bar-fill { height: 100%; background: var(--gradient-primary); border-radius: var(--radius-full); }
.bar-fill.bg-blue { background: var(--color-info); }
.bar-val { width: 40px; text-align: right; font-size: var(--text-sm); font-weight: 600; }
@media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .grid-2 { grid-template-columns: 1fr; } }
</style>