<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const stats = ref<any>(null)

onMounted(async () => {
  try {
    const res = await api.get('/analytics')
    stats.value = res.data
  } catch (e) {
    console.error('Erreur analytics:', e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="analytics-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Analytics</h2>
        <p class="text-muted text-sm">Statistiques globales de la plateforme</p>
      </div>
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p style="margin-top:1rem;">Chargement des statistiques...</p>
    </div>

    <template v-else-if="stats">
      <!-- KPIs -->
      <div class="kpi-grid">
        <div class="card kpi-card">
          <div class="kpi-icon" style="background:rgba(99,102,241,0.12);color:var(--color-primary-light)"><i class="pi pi-briefcase" /></div>
          <div class="kpi-info">
            <p class="kpi-label">Projets par statut</p>
            <div v-for="s in stats.projets_par_statut" :key="s.statut" class="text-sm">
              {{ s.statut }}: <strong>{{ s.total }}</strong>
            </div>
          </div>
        </div>

        <div class="card kpi-card">
          <div class="kpi-icon" style="background:rgba(16,185,129,0.12);color:var(--color-success)"><i class="pi pi-file" /></div>
          <div class="kpi-info">
            <p class="kpi-label">Taux de dépôt</p>
            <p class="kpi-value">{{ stats.taux_depot }}%</p>
            <p class="text-xs text-muted">{{ stats.rapports_soumis }} rapports soumis</p>
          </div>
        </div>

        <div class="card kpi-card">
          <div class="kpi-icon" style="background:rgba(245,158,11,0.12);color:var(--color-warning)"><i class="pi pi-star" /></div>
          <div class="kpi-info">
            <p class="kpi-label">Activité récente</p>
            <p class="kpi-value">{{ stats.activite_recente }}</p>
            <p class="text-xs text-muted">30 derniers jours</p>
          </div>
        </div>
      </div>

      <!-- Projets par spécialité -->
      <div class="card" style="margin-top:1.5rem;">
        <h3 class="section-title"><i class="pi pi-chart-bar" /> Projets par spécialité</h3>
        <div v-if="stats.projets_par_specialite?.length">
          <div v-for="s in stats.projets_par_specialite" :key="s.nom" style="display:flex;align-items:center;gap:1rem;padding:0.5rem 0;border-bottom:1px solid var(--border-default);">
            <span class="text-sm" style="flex:1;">{{ s.nom }}</span>
            <div style="width:200px;height:8px;background:var(--bg-active);border-radius:var(--radius-full);overflow:hidden;">
              <div :style="{ width: `${(s.projects_count / Math.max(...stats.projets_par_specialite.map((x:any) => x.projects_count))) * 100}%`, height:'100%', background:'var(--gradient-primary)' }" />
            </div>
            <span class="text-sm font-bold">{{ s.projects_count }}</span>
          </div>
        </div>
      </div>

      <!-- Moyenne des notes -->
      <div class="card" style="margin-top:1rem;">
        <h3 class="section-title"><i class="pi pi-star" /> Moyenne des notes</h3>
        <div v-if="stats.moyenne_notes?.length">
          <div v-for="n in stats.moyenne_notes" :key="n.nom">
            <span class="text-sm">{{ n.nom }} : <strong>{{ parseFloat(n.moyenne).toFixed(1) }}/20</strong></span>
          </div>
        </div>
      </div>

      <!-- Top professeurs -->
      <div class="card" style="margin-top:1rem;">
        <h3 class="section-title"><i class="pi pi-trophy" /> Top professeurs</h3>
        <div v-if="stats.top_professeurs?.length">
          <div v-for="(p, i) in stats.top_professeurs" :key="i" style="display:flex;align-items:center;gap:1rem;padding:0.5rem 0;">
            <span class="badge badge-primary">{{ Number(i) + 1 }}</span>
            <span class="text-sm">{{ p.prenom }} {{ p.name }}</span>
            <span class="text-sm font-bold">{{ p.total }} projets</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.analytics-page { max-width: 1200px; }
.page-header { margin-bottom: 1.5rem; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.kpi-card { display: flex; align-items: center; gap: 1rem; }
.kpi-icon { width: 48px; height: 48px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.kpi-info { flex: 1; }
.kpi-label { font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem; }
.kpi-value { font-family: var(--font-display); font-size: var(--text-3xl); font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; margin-bottom: 1rem; }
@media (max-width: 768px) { .kpi-grid { grid-template-columns: 1fr; } }
</style>