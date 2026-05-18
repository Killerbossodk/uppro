<script setup lang="ts">
import { onMounted, ref, nextTick } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'

const auth = useAuthStore()
const loading = ref(true)

const stats = ref([
  { label: 'Projets actifs', value: '0', icon: 'pi-briefcase', trend: '', color: 'var(--color-primary)' },
  { label: 'Groupes suivis', value: '0', icon: 'pi-users', trend: '', color: 'var(--color-secondary)' },
  { label: 'Rapports à noter', value: '0', icon: 'pi-file', trend: '', color: 'var(--color-warning)' },
  { label: 'RDV cette semaine', value: '0', icon: 'pi-calendar', trend: '', color: 'var(--color-accent)' },
])

const recentProjects = ref<any[]>([])
const upcomingMeetings = ref<any[]>([])

const statusMap: Record<string, { label: string; class: string }> = {
  brouillon: { label: 'Brouillon', class: 'badge-info' },
  soumis: { label: 'Soumis', class: 'badge-warning' },
  valide: { label: 'Validé', class: 'badge-success' },
  refuse: { label: 'Rejeté', class: 'badge-danger' },
}

onMounted(async () => {
  try {
    const [projRes, grpRes, meetRes, repRes] = await Promise.all([
      api.get('/projects'),
      api.get('/groups'),
      api.get('/meetings'),
      api.get('/reports')
    ])

    const projets = Array.isArray(projRes.data) ? projRes.data : []
    const groupes = Array.isArray(grpRes.data) ? grpRes.data : []
    const meetings = Array.isArray(meetRes.data) ? meetRes.data : []
    const reports = Array.isArray(repRes.data) ? repRes.data : []

    recentProjects.value = projets.slice(0, 5).map((p: any) => ({
      id: p.id,
      title: p.titre,
      group: p.groupes?.length ? `${p.groupes.length} groupe(s)` : 'Aucun groupe',
      status: p.statut,
      progress: p.statut === 'valide' ? 100 : p.statut === 'soumis' ? 60 : 25,
    }))
    
    stats.value[0]!.value = String(projets.length)
    stats.value[0]!.trend = `${projets.filter((p: any) => p.statut === 'valide').length} validés`

    stats.value[1]!.value = String(groupes.length)
    stats.value[1]!.trend = `${groupes.reduce((a: number, g: any) => a + (g.membres?.length || 0), 0)} étudiants`

    stats.value[2]!.value = String(reports.length)
    stats.value[2]!.trend = `${reports.filter((r: any) => !r.note).length} non notés`

    stats.value[3]!.value = String(meetings.length)
    stats.value[3]!.trend = `${meetings.length} RDV`
    
    upcomingMeetings.value = meetings.slice(0, 3).map((m: any) => {
      const d = new Date(m.date_heure)
      return {
        id: m.id,
        type_raw: m.type,
        time: d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
        group: m.groupes?.[0]?.nom || 'Groupe',
        type: m.titre,
        avatar: (m.groupes?.[0]?.nom || 'GP').substring(0, 2).toUpperCase(),
      }
    })
  } catch (e) {
    console.error('Erreur dashboard:', e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="dashboard">
    <div class="welcome-section">
      <div>
        <h2 class="welcome-title">Bonjour, <span class="text-brand">{{ auth.user?.prenom || auth.user?.name }}</span></h2>
        <p class="welcome-subtitle">Voici un aperçu de votre activité</p>
      </div>
      <div class="flex gap-2">
        <RouterLink to="/professeur/projets" class="btn btn-primary"><i class="pi pi-plus" /> Nouveau projet</RouterLink>
      </div>
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p style="margin-top:1rem">Chargement...</p>
    </div>

    <template v-else>
      <div class="stats-grid">
        <div v-for="(s, i) in stats" :key="i" class="card stat-item" :style="{ animationDelay: `${i*80}ms` }">
          <div class="stat-top">
            <div class="stat-icon-wrap" :style="{ background: `${s.color}22`, color: s.color }"><i :class="`pi ${s.icon}`" /></div>
            <span class="stat-trend">{{ s.trend }}</span>
          </div>
          <p class="stat-val">{{ s.value }}</p>
          <p class="stat-lbl">{{ s.label }}</p>
        </div>
      </div>

      <div class="dashboard-grid">
        <!-- Colonne Principale -->
        <div class="card">
          <div class="section-header">
            <h3 class="section-title"><i class="pi pi-briefcase" /> Projets récents</h3>
            <RouterLink to="/professeur/projets" class="btn btn-ghost btn-sm">Voir tout</RouterLink>
          </div>
          <div v-if="recentProjects.length === 0" class="text-muted text-sm" style="padding:2rem;text-align:center">Aucun projet</div>
          <div v-else class="project-list">
            <div v-for="p in recentProjects" :key="p.id" class="project-row card-interactive">
              <div class="project-info">
                <p class="project-name">{{ p.title }}</p>
                <p class="project-group text-muted text-sm">{{ p.group }}</p>
              </div>
              <div class="project-meta">
                <span :class="`badge ${statusMap[p.status]?.class || 'badge-info'}`">{{ statusMap[p.status]?.label || p.status }}</span>
                <div class="progress-wrap">
                  <div class="progress-bar"><div class="progress-fill" :style="{ width: `${p.progress}%` }" /></div>
                  <span class="progress-label">{{ p.progress }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne Latérale -->
        <div class="sidebar-col">
          <!-- Carte Prochains RDV -->
          <div class="card">
            <div class="section-header">
              <h3 class="section-title"><i class="pi pi-calendar" /> Prochains RDV</h3>
              <RouterLink to="/professeur/agenda" class="btn btn-ghost btn-sm">Agenda</RouterLink>
            </div>
            <div v-if="upcomingMeetings.length === 0" class="text-muted text-sm" style="padding:2rem;text-align:center">Aucun RDV</div>
            <div v-else class="rdv-list">
              <div v-for="(m, i) in upcomingMeetings" :key="i">
                <RouterLink v-if="m.type_raw === 'soutenance'" :to="`/soutenance-live/${m.id}`" class="rdv-item card-interactive no-underline text-inherit">
                  <div class="avatar">{{ m.avatar }}</div>
                  <div class="rdv-info">
                    <p class="rdv-group">{{ m.group }}</p>
                    <p class="rdv-type text-muted text-sm">{{ m.type }}</p>
                  </div>
                  <span class="rdv-time badge badge-primary">{{ m.time }}</span>
                </RouterLink>
                <div v-else class="rdv-item">
                  <div class="avatar">{{ m.avatar }}</div>
                  <div class="rdv-info">
                    <p class="rdv-group">{{ m.group }}</p>
                    <p class="rdv-type text-muted text-sm">{{ m.type }}</p>
                  </div>
                  <span class="rdv-time badge badge-primary">{{ m.time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1400px; }
.welcome-section { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.welcome-title { font-family: var(--font-display); font-size: var(--text-3xl); font-weight: 800; color: var(--text-primary); margin-bottom: 0.4rem; }
.welcome-subtitle { color: var(--text-secondary); font-size: var(--text-base); }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.stat-item { display: flex; flex-direction: column; gap: 0.5rem; }
.stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem; }
.stat-icon-wrap { width: 44px; height: 44px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.stat-trend { font-size: var(--text-xs); color: var(--text-muted); }
.stat-val { font-family: var(--font-display); font-size: var(--text-4xl); font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
.stat-lbl { font-size: var(--text-sm); color: var(--text-secondary); font-weight: 500; }
.dashboard-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; }
.project-list { display: flex; flex-direction: column; gap: 0.75rem; }
.project-row { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: var(--bg-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-default); cursor: pointer; }
.project-row:hover { background: var(--bg-hover); transform: translateX(4px); }
.project-name { font-weight: 600; font-size: var(--text-sm); }
.project-meta { display: flex; align-items: center; gap: 1rem; }
.progress-wrap { display: flex; align-items: center; gap: 0.5rem; }
.progress-bar { width: 80px; height: 6px; background: var(--bg-active); border-radius: var(--radius-full); overflow: hidden; }
.progress-fill { height: 100%; background: var(--gradient-primary); }
.progress-label { font-size: var(--text-xs); color: var(--text-muted); }
.rdv-list { display: flex; flex-direction: column; gap: 0.75rem; }
.rdv-item { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem; background: var(--bg-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-default); }
.rdv-info { flex: 1; }
.rdv-group { font-weight: 600; font-size: var(--text-sm); }
.no-underline { text-decoration: none; }
.text-inherit { color: inherit; }
.card-interactive:hover { transform: translateX(4px); background: var(--bg-hover); }

/* ══════════════════════════════════════════
   AI ASSISTANT SIDEBAR CARD
   ══════════════════════════════════════════ */
.ai-assistant-card {
  position: relative;
  border: 1px solid rgba(139, 92, 246, 0.25);
  box-shadow: 0 8px 32px rgba(125, 33, 64, 0.08);
  display: flex;
  flex-direction: column;
  height: 480px;
  overflow: hidden;
  background: linear-gradient(180deg, var(--bg-surface) 0%, rgba(139, 92, 246, 0.03) 100%);
}

.ai-glow-dot {
  width: 8px;
  height: 8px;
  background-color: var(--color-primary-light);
  border-radius: 50%;
  display: inline-block;
  margin-right: 6px;
  box-shadow: 0 0 10px var(--color-primary-light);
  animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
  0% { transform: scale(0.9); opacity: 0.6; }
  50% { transform: scale(1.2); opacity: 1; box-shadow: 0 0 14px var(--color-primary-light); }
  100% { transform: scale(0.9); opacity: 0.6; }
}

.ai-tabs {
  display: flex;
  background: var(--bg-active);
  padding: 2px;
  border-radius: var(--radius-md);
  gap: 2px;
}

.ai-tab-btn {
  border: none;
  background: none;
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
  padding: 4px 10px;
  cursor: pointer;
  border-radius: calc(var(--radius-md) - 2px);
  transition: all 0.2s;
  text-transform: uppercase;
}

.ai-tab-btn.active {
  background: var(--color-primary);
  color: white;
}

.ai-panel-chat, .ai-panel-tools {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.ai-chat-history {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
  margin-bottom: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.ai-msg {
  padding: 8px 12px;
  border-radius: var(--radius-md);
  font-size: 12px;
  line-height: 1.4;
  border: 1px solid transparent;
}

.ai-msg.user {
  background: var(--color-primary-subtle);
  align-self: flex-end;
  color: var(--color-primary-dark);
  max-width: 85%;
}

.ai-msg.assistant {
  background: var(--bg-elevated);
  border-color: var(--border-default);
  align-self: flex-start;
  color: var(--text-primary);
  max-width: 90%;
}

.ai-msg strong {
  display: block;
  font-size: 10px;
  color: var(--color-primary-light);
  margin-bottom: 2px;
  text-transform: uppercase;
}

.ai-chat-quick-actions {
  padding: 4px 0;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.ai-tool-result {
  max-height: 120px;
  overflow-y: auto;
  line-height: 1.4;
}

.sidebar-col {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .dashboard-grid { grid-template-columns: 1fr; } }
</style>