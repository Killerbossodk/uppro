<template>
  <div class="rup-dashboard">
    <!-- ========== EN-TÊTE ========== -->
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Tableau de bord RUP Projet</h2>
        <p class="text-muted text-sm">Bonjour {{ auth.user?.prenom }} {{ auth.user?.name }}</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" @click="toggleAIChat">🤖 Assistant IA</button>
      </div>
    </div>

    <!-- ========== CYCLE ACADÉMIQUE / CLÔTURE DE L'ANNÉE ========== -->
    <div class="card year-control-card">
      <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
        <div>
          <h3 class="section-title flex items-center gap-2" style="margin:0;">
            <i class="pi pi-calendar" /> 
            <span>Cycle Universitaire Actuel</span>
            <span v-if="settings.is_year_closed" class="badge badge-danger">Clôturé</span>
            <span v-else class="badge badge-success">Actif</span>
          </h3>
          <p class="text-muted text-xs mt-1" v-if="settings.academic_year_label">
            Année courante : <strong>{{ settings.academic_year_label }}</strong> 
            (débutée le {{ new Date(settings.academic_year_start).toLocaleDateString('fr-FR') }})
          </p>
          <p class="text-muted text-xs mt-1" v-else>
            Aucune année universitaire déclarée actuellement.
          </p>
        </div>
        <button v-if="!settings.is_year_closed && settings.academic_year_label" class="btn btn-danger btn-sm" @click="closeAcademicYear">
          <i class="pi pi-lock" /> Clôturer l'année en cours
        </button>
      </div>

      <!-- Commencer une nouvelle année -->
      <div v-if="settings.is_year_closed || !settings.academic_year_label" class="new-year-form p-3 mt-2">
        <h4 class="font-bold text-sm mb-2 text-brand">Commencer un nouveau cycle universitaire</h4>
        <p class="text-muted text-xs mb-3">La clôture de l'année précédente a archivé tous les projets et rapports. Renseignez les détails ci-dessous pour lancer la nouvelle session.</p>
        <form @submit.prevent="startNewAcademicYear" class="flex gap-3 flex-wrap items-end">
          <div class="form-group" style="min-width:180px;">
            <label class="form-label text-xs">Libellé de l'année (ex: 2026/2027)</label>
            <input type="text" v-model="newYearLabel" class="form-input" placeholder="2026/2027" required />
          </div>
          <div class="form-group" style="min-width:180px;">
            <label class="form-label text-xs">Date de rentrée académique</label>
            <input type="date" v-model="newYearStart" class="form-input" required />
          </div>
          <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1">
            <i class="pi pi-play" /> Lancer l'année
          </button>
        </form>
      </div>
    </div>

    <!-- ========== MENU RAPIDE ========== -->
    <div class="quick-menu">
      <RouterLink to="/rup-projet/utilisateurs" class="card quick-card">
        <i class="pi pi-users" style="font-size:2rem;color:var(--color-primary);" />
        <span class="font-medium">Utilisateurs</span>
      </RouterLink>
      <RouterLink to="/rup-projet/parametres" class="card quick-card">
        <i class="pi pi-cog" style="font-size:2rem;color:var(--color-primary);" />
        <span class="font-medium">Paramètres</span>
      </RouterLink>
      <RouterLink to="/rup-projet/annonces" class="card quick-card">
        <i class="pi pi-megaphone" style="font-size:2rem;color:var(--color-primary);" />
        <span class="font-medium">Communiqués</span>
      </RouterLink>
      <RouterLink to="/rup-projet/analytics" class="card quick-card">
        <i class="pi pi-chart-bar" style="font-size:2rem;color:var(--color-primary);" />
        <span class="font-medium">Analytique</span>
      </RouterLink>
      <RouterLink to="/rup-projet/rapports" class="card quick-card">
        <i class="pi pi-file" style="font-size:2rem;color:var(--color-primary);" />
        <span class="font-medium">Rapports</span>
      </RouterLink>
    </div>

    <!-- ========== BOUTON STATS ========== -->
    <div class="flex justify-between items-center mb-4">
      <button class="btn btn-secondary btn-sm" @click="loadStats">
        {{ showStats ? 'Masquer' : 'Voir' }} les statistiques
      </button>
      <button v-if="showStats" class="btn btn-ghost btn-sm" @click="refreshStats">
        <i class="pi pi-refresh" /> Rafraîchir
      </button>
    </div>

    <!-- ========== VRAIES STATISTIQUES ========== -->
    <div v-if="showStats && stats" class="stats-container">
      
      <!-- Carte 1 : Projets -->
      <div class="card stat-card-primary">
     
        <div class="stat-content">
          <div class="stat-value-large">{{ totalProjets }}</div>
          <div class="stat-label">Projets au total</div>
          <div class="stat-detail">
            <span class="text-success"> {{ projetsValides }} validés</span>
            <span class="text-warning"> {{ projetsSoumisCount }} soumis</span>
            <span class="text-info">{{ projetsBrouillon }} brouillons</span>
            <span class="text-danger"> {{ projetsRefuses }} refusés</span>
          </div>
        </div>
      </div>

      <!-- Carte 2 : Rapports -->
      <div class="card stat-card-secondary">
        <div class="stat-content">
          <div class="stat-value-large">{{ stats.rapports_soumis || 0 }}</div>
          <div class="stat-label">Rapports soumis</div>
          <div class="stat-detail">
            <span>Taux de dépôt : <strong>{{ stats.taux_depot || 0 }}%</strong></span>
          </div>
        </div>
      </div>

      <!-- Carte 3 : Activité -->
      <div class="card stat-card-tertiary">
    
        <div class="stat-content">
          <div class="stat-value-large">{{ stats.activite_recente || 0 }}</div>
          <div class="stat-label">Actions récentes (30 jours)</div>
        </div>
      </div>

      <!-- Projets par spécialité -->
      <div class="card stat-card-full">
        <h4 class="stat-subtitle"> Projets par spécialité</h4>
        <div class="specialite-list">
          <div v-for="spec in stats.projets_par_specialite" :key="spec.nom" class="specialite-item">
            <span class="specialite-name">{{ spec.nom || 'Mélangé' }}</span>
            <div class="progress-bar-mini">
              <div class="progress-fill-mini" :style="{ width: getSpecialitePercent(spec.projects_count) + '%' }"></div>
            </div>
            <span class="specialite-count">{{ spec.projects_count }}</span>
          </div>
        </div>
      </div>

      <!-- Top professeurs -->
      <div class="card stat-card-full">
        <h4 class="stat-subtitle"> Nombre de projet par prof</h4>
        <div class="top-professeurs">
          <div v-for="(prof, idx) in stats.top_professeurs?.slice(0,5)" :key="idx" class="prof-item">
            <span class="prof-rank">{{ Number(idx) + 1 }}</span>
            <span class="prof-name">{{ prof.prenom }} {{ prof.name }}</span>
            <span class="prof-count">{{ prof.total }} projet(s)</span>
            <div class="prof-bar">
              <div class="prof-bar-fill" :style="{ width: (prof.total / maxProfProjects) * 100 + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Moyenne des notes par spécialité -->
      <div class="card stat-card-full" v-if="stats.moyenne_notes?.length">
        <h4 class="stat-subtitle">📝 Moyenne des notes par spécialité</h4>
        <div class="notes-list">
          <div v-for="note in stats.moyenne_notes" :key="note.nom" class="note-item">
            <span class="note-name">{{ note.nom }}</span>
            <span class="note-value" :class="getNoteClass(note.moyenne)">{{ note.moyenne }}/20</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Message si pas de stats -->
    <div v-if="showStats && !stats" class="card text-center py-4">
      <i class="pi pi-spin pi-spinner" />
      <p class="text-muted mt-2">Chargement des statistiques...</p>
    </div>

    <!-- ========== PROJETS EN ATTENTE ========== -->
    <div class="card mb-4">
      <h3 class="section-title"><i class="pi pi-clock" /> Projets en attente de validation</h3>
      <div v-if="projetsSoumis.length === 0" class="text-muted text-sm text-center py-4">Aucun projet en attente</div>
      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr><th>Titre</th><th>Niveau</th><th>Professeur</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in projetsSoumis" :key="p.id">
              <td class="font-medium">{{ p.titre }}</td>
              <td>{{ p.niveau }}</td>
              <td>{{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</td>
              <td>
                <div class="flex gap-2">
                  <button class="btn btn-success btn-sm" @click="validerProjet(p.id)">✅ Valider</button>
                  <button class="btn btn-danger btn-sm" @click="refuserProjet(p.id)">❌ Refuser</button>
                  <button class="btn btn-ghost btn-sm" @click="voirDetails(p)">👁️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== TOUS LES PROJETS ========== -->
    <div class="card mb-4">
      <h3 class="section-title"><i class="pi pi-briefcase" /> Tous les projets</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr><th>Titre</th><th>Niveau</th><th>Statut</th><th>Professeur</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in projects" :key="p.id">
              <td class="font-medium">
                {{ p.titre }}
                <span v-if="!p.is_public" class="badge badge-warning ml-2" style="font-size:0.65rem;padding:0.1rem 0.3rem;"><i class="pi pi-lock" /> Privé</span>
                <span v-if="p.est_archive" class="badge badge-info ml-2">📦 Archivé</span>
              </td>
              <td>{{ p.niveau }}</td>
              <td><span :class="`badge ${getStatusClass(p.statut)}`">{{ p.statut }}</span></td>
              <td>{{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</td>
              <td>
                <div class="flex gap-2">
                  <button v-if="!p.est_archive" class="btn btn-warning btn-sm" @click="archiverProjet(p.id)">📦</button>
                  <button v-if="p.statut === 'soumis'" class="btn btn-success btn-sm" @click="validerProjet(p.id)">✅</button>
                  <button v-if="p.statut === 'soumis'" class="btn btn-danger btn-sm" @click="refuserProjet(p.id)">❌</button>
                  <button class="btn btn-ghost btn-sm" @click="voirDetails(p)">👁️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== AUDIT & RETOURS DES ENCADREURS ========== -->
    <div class="card mb-4">
      <h3 class="section-title"><i class="pi pi-star" /> Audit Pédagogique des Encadreurs</h3>
      
      <div class="flex flex-col gap-5 mt-3">
        <!-- Si aucune statistique -->
        <div v-if="feedbacksStats.length === 0" class="text-muted text-sm text-center py-5">
          Aucune évaluation enregistrée pour le moment.
        </div>

        <template v-else>
          <!-- Synthèse de l'Audit : Widgets Metrics -->
          <div class="audit-metrics-grid">
            <div class="audit-metric-card">
              <div class="metric-icon rating"><i class="pi pi-star-fill" /></div>
              <div class="metric-info">
                <span class="metric-label">Note Globale Encadrement</span>
                <span class="metric-value">
                  {{ (feedbacksStats.reduce((acc, stat) => acc + Number(stat.moyenne), 0) / feedbacksStats.length).toFixed(2) }}
                  <small>/ 5</small>
                </span>
              </div>
            </div>
            
            <div class="audit-metric-card">
              <div class="metric-icon critiques"><i class="pi pi-comments" /></div>
              <div class="metric-info">
                <span class="metric-label">Total Avis Étudiants</span>
                <span class="metric-value">
                  {{ feedbacksStats.reduce((acc, stat) => acc + Number(stat.total_critiques), 0) }}
                  <small>critiques</small>
                </span>
              </div>
            </div>
          </div>

          <!-- Liste des cartes d'encadrants -->
          <div class="encadreur-audit-grid">
            <div v-for="stat in feedbacksStats" :key="stat.encadreur_id" class="encadreur-audit-card">
              <div class="encadreur-card-header">
                <div class="encadreur-profile-mini">
                  <div class="encadreur-avatar-mini">
                    {{ stat.encadreur?.prenom?.[0] || 'E' }}{{ stat.encadreur?.name?.[0] || 'N' }}
                  </div>
                  <span class="encadreur-name-mini">{{ stat.encadreur?.prenom }} {{ stat.encadreur?.name }}</span>
                </div>
                <div class="encadreur-score-badge">
                  <i class="pi pi-star-fill" /> {{ Number(stat.moyenne).toFixed(1) }} / 5
                </div>
              </div>

              <div class="encadreur-card-body">
                <div class="rating-bar-container">
                  <div class="rating-progress-bar">
                    <div class="rating-progress-fill" :style="{ width: (stat.moyenne * 20) + '%' }"></div>
                  </div>
                  <span class="rating-percentage-label">{{ (stat.moyenne * 20).toFixed(0) }}%</span>
                </div>
                <span class="encadreur-critique-count">{{ stat.total_critiques }} critique(s) reçue(s)</span>
              </div>
            </div>
          </div>

          <!-- Détail de toutes les critiques (Premium Feed) -->
          <div v-if="feedbacks.length > 0" class="audit-feedbacks-section">
            <h4 class="audit-feedbacks-title"><i class="pi pi-comment" /> Retours détaillés des étudiants</h4>
            <div class="feedbacks-feed max-h-80 overflow-y-auto" style="max-height: 400px; padding-right: 0.5rem;">
              <div v-for="fb in feedbacks" :key="fb.id" class="feedback-card-premium">
                <div class="feedback-header-premium">
                  <span class="feedback-target-label">
                    Avis sur : <span class="feedback-target-name">{{ fb.encadreur?.prenom }} {{ fb.encadreur?.name }}</span>
                  </span>
                  <div class="golden-stars-premium">
                    {{ '★'.repeat(fb.note_pedagogique) }}{{ '☆'.repeat(5 - fb.note_pedagogique) }}
                  </div>
                </div>
                
                <p class="feedback-comment-text">"{{ fb.commentaire }}"</p>
                
                <div class="feedback-footer-premium">
                  <span :class="['feedback-author-badge', { anonymous: fb.anonyme }]">
                    <i class="pi" :class="fb.anonyme ? 'pi-eye-slash' : 'pi-user'" />
                    {{ fb.anonyme ? 'Étudiant Anonyme' : (fb.student ? fb.student.prenom + ' ' + fb.student.name : 'Étudiant') }}
                  </span>
                  <span>{{ new Date(fb.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- ========== MODAL DÉTAILS PROJET ========== -->
    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-card card" style="max-width: 800px; max-height: 85vh; overflow-y: auto;">
        <h3>{{ selectedProject?.titre }}</h3>
        <div class="mt-3 space-y-2">
          <p><strong>Description :</strong></p>
          <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); white-space: pre-wrap; font-size: 0.95rem; border: 1px solid var(--border-default);">
            {{ selectedProject?.description || 'Aucune' }}
          </div>
          <div class="flex gap-4 mt-4 mb-4 flex-wrap">
            <p><strong>Niveau :</strong> {{ selectedProject?.niveau }}</p>
            <p><strong>Professeur :</strong> {{ selectedProject?.superviseur?.prenom }} {{ selectedProject?.superviseur?.name }}</p>
            <p><strong>Spécialité :</strong> {{ selectedProject?.specialite?.nom || 'Mélangé' }}</p>
            <p><strong>Statut :</strong> <span :class="`badge ${getStatusClass(selectedProject?.statut)}`">{{ selectedProject?.statut }}</span></p>
          </div>
          
          <!-- Affichage des groupes pour pouvoir dissoudre -->
          <div v-if="selectedProject?.groupes && selectedProject.groupes.length > 0" class="mt-4">
            <h4 class="font-bold mb-2">Groupes associés</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
              <div v-for="g in selectedProject.groupes" :key="g.id" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-default);">
                <div>
                  <span class="font-medium">{{ g.nom }}</span>
                  <span class="text-sm text-muted ml-2">({{ g.membres?.length || 0 }} membres)</span>
                </div>
                <button class="btn btn-danger btn-sm" @click="dissoudreGroupe(g.id)">
                  <i class="pi pi-trash" /> Dissoudre
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <button class="btn btn-secondary btn-sm" @click="showDetailModal = false">Fermer</button>
        </div>
      </div>
    </div>

    <!-- ========== MODAL CHAT IA ========== -->
    <div v-if="showAIChat" class="modal-overlay" @click.self="showAIChat = false">
      <div class="modal-card card" style="max-width:600px;height:450px;display:flex;flex-direction:column;">
        <div class="flex justify-between items-center mb-3">
          <h3>🤖 Assistant IA</h3>
          <button class="btn btn-ghost btn-icon btn-sm" @click="showAIChat = false"><i class="pi pi-times" /></button>
        </div>
        <div class="ai-messages" style="flex:1;overflow-y:auto;margin-bottom:1rem;">
          <div v-if="aiHistory.length === 0" class="text-muted text-sm text-center py-4">Posez une question...</div>
          <div v-for="(msg, i) in aiHistory" :key="i" :class="`ai-msg ${msg.role}`">
            <strong>{{ msg.role === 'user' ? 'Vous' : 'IA' }} :</strong> {{ msg.content }}
          </div>
          <div v-if="aiLoading" class="ai-msg assistant"><i class="pi pi-spin pi-spinner" /> Réflexion...</div>
        </div>
        <div class="flex gap-2">
          <input v-model="aiMessage" type="text" class="form-input flex-1" placeholder="Posez votre question..." @keydown.enter="sendAIMessage" :disabled="aiLoading" />
          <button class="btn btn-primary btn-sm" @click="sendAIMessage" :disabled="aiLoading || !aiMessage.trim()"><i class="pi pi-send" /></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'

const auth = useAuthStore()
const projects = ref<any[]>([])
const stats = ref<any>(null)
const showStats = ref(false)
const showDetailModal = ref(false)
const selectedProject = ref<any>(null)

// Cycle académique
const settings = ref({
  academic_year_start: '',
  academic_year_label: '',
  is_year_closed: false
})
const newYearStart = ref('')
const newYearLabel = ref('')

// Feedbacks
const feedbacks = ref<any[]>([])
const feedbacksStats = ref<any[]>([])

// ========== STATISTIQUES CALCULÉES ==========
const totalProjets = computed(() => {
  return stats.value?.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
})

const projetsValides = computed(() => {
  return stats.value?.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
})

const projetsSoumisCount = computed(() => {
  return stats.value?.projets_par_statut?.find((s: any) => s.statut === 'soumis')?.total || 0
})

const projetsBrouillon = computed(() => {
  return stats.value?.projets_par_statut?.find((s: any) => s.statut === 'brouillon')?.total || 0
})

const projetsRefuses = computed(() => {
  return stats.value?.projets_par_statut?.find((s: any) => s.statut === 'refuse')?.total || 0
})

const maxProfProjects = computed(() => {
  if (!stats.value?.top_professeurs?.length) return 1
  return Math.max(...stats.value.top_professeurs.map((p: any) => p.total))
})

const projetsSoumis = computed(() => projects.value.filter((p: any) => p.statut === 'soumis' && !p.est_archive))

// ========== MÉTHODES ==========
async function fetchProjects() {
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement projets:', e)
  }
}

async function fetchSettings() {
  try {
    const res = await api.get('/settings')
    settings.value = res.data
  } catch (e) {
    console.error('Erreur settings:', e)
  }
}

async function fetchFeedbacks() {
  try {
    const res = await api.get('/feedbacks/encadreurs')
    feedbacks.value = Array.isArray(res.data) ? res.data : []

    const statsRes = await api.get('/feedbacks/encadreurs/stats')
    feedbacksStats.value = Array.isArray(statsRes.data) ? statsRes.data : []
  } catch (e) {
    console.error('Erreur feedbacks:', e)
  }
}

async function loadStats() {
  if (!showStats.value) {
    await refreshStats()
  }
  showStats.value = !showStats.value
}

async function refreshStats() {
  try {
    const res = await api.get('/analytics')
    stats.value = res.data
  } catch (e) {
    console.error('Erreur chargement stats:', e)
  }
}

function getSpecialitePercent(count: number) {
  const max = Math.max(...(stats.value?.projets_par_specialite?.map((s: any) => s.projects_count) || [1]))
  return max > 0 ? (count / max) * 100 : 0
}

function getStatusClass(statut: string) {
  const classes: Record<string, string> = {
    'brouillon': 'badge-info',
    'soumis': 'badge-warning',
    'valide': 'badge-success',
    'refuse': 'badge-danger'
  }
  return classes[statut] || 'badge-info'
}

function getNoteClass(note: number) {
  if (note >= 16) return 'text-success'
  if (note >= 12) return 'text-info'
  if (note >= 10) return 'text-warning'
  return 'text-danger'
}

async function validerProjet(id: number) {
  try {
    await api.post(`/projects/${id}/valider`)
    await fetchProjects()
    await refreshStats()
  } catch (e: any) {
    alert('Erreur: ' + (e.response?.data?.message || e.message))
  }
}

async function refuserProjet(id: number) {
  const motif = prompt('Motif du refus :')
  if (motif) {
    try {
      await api.post(`/projects/${id}/refuser`, { motif_refus: motif })
      await fetchProjects()
      await refreshStats()
    } catch (e: any) {
      alert('Erreur: ' + (e.response?.data?.message || e.message))
    }
  }
}

async function archiverProjet(id: number) {
  if (!confirm('Archiver ce projet ?')) return
  try {
    await api.post(`/projects/${id}/archive`)
    await fetchProjects()
    await refreshStats()
  } catch (e: any) {
    alert('Erreur: ' + (e.response?.data?.message || e.message))
  }
}

function voirDetails(projet: any) {
  selectedProject.value = projet
  showDetailModal.value = true
}

async function dissoudreGroupe(groupId: number) {
  if (!confirm('Voulez-vous vraiment dissoudre ce groupe ? Toutes les données (phases, rapports, chat) seront perdues.')) return
  try {
    await api.post(`/groups/${groupId}/dissoudre`)
    if (selectedProject.value && selectedProject.value.groupes) {
      selectedProject.value.groupes = selectedProject.value.groupes.filter((g: any) => g.id !== groupId)
    }
    await fetchProjects()
    await refreshStats()
  } catch (e: any) {
    alert('Erreur: ' + (e.response?.data?.message || e.message))
  }
}

// Cycle académique
async function closeAcademicYear() {
  if (!confirm('ATTENTION DANGER : Voulez-vous vraiment CLÔTURER l\'année universitaire actuelle ? Tous les projets actifs seront définitivement archivés, et les étudiants seront libérés pour les nouveaux groupes de l\'année suivante.')) return
  try {
    const res = await api.post('/settings/close-year')
    alert(res.data.message)
    await fetchSettings()
    await fetchProjects()
    await refreshStats()
  } catch (e: any) {
    alert('Erreur: ' + (e.response?.data?.message || e.message))
  }
}

async function startNewAcademicYear() {
  if (!newYearStart.value || !newYearLabel.value) {
    alert('Veuillez renseigner toutes les informations requises.')
    return
  }
  try {
    const res = await api.post('/settings/start-year', {
      academic_year_start: newYearStart.value,
      academic_year_label: newYearLabel.value
    })
    alert(res.data.message)
    newYearStart.value = ''
    newYearLabel.value = ''
    await fetchSettings()
    await fetchProjects()
    await refreshStats()
  } catch (e: any) {
    alert('Erreur: ' + (e.response?.data?.message || e.message))
  }
}

// ========== IA ==========
const showAIChat = ref(false)
const aiMessage = ref('')
const aiHistory = ref<{ role: string; content: string }[]>([])
const aiLoading = ref(false)

function toggleAIChat() {
  showAIChat.value = !showAIChat.value
  
  if (showAIChat.value && stats.value) {
    const totalProjets = stats.value.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
    const projetsValides = stats.value.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
    const tauxDepot = stats.value.taux_depot || 0
    const rapportsSoumis = stats.value.rapports_soumis || 0
    const activite = stats.value.activite_recente || 0
    
    const welcomeMessage = `Bonjour ! Voici les statistiques RÉELLES du système :
    
📊 STATISTIQUES EN TEMPS RÉEL :
- Total projets : ${totalProjets}
- Projets validés : ${projetsValides}
- Taux de dépôt des rapports : ${tauxDepot}%
- Rapports soumis : ${rapportsSoumis}
- Activité (30j) : ${activite} actions

Posez-moi vos questions.`
    
    aiHistory.value.push({ role: 'assistant', content: welcomeMessage })
  }
}

async function sendAIMessage() {
  if (!aiMessage.value.trim() || aiLoading.value) return
  const msg = aiMessage.value
  aiHistory.value.push({ role: 'user', content: msg })
  aiMessage.value = ''
  aiLoading.value = true
  
  let statsContext = ''
  if (stats.value) {
    const totalProjets = stats.value.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
    const projetsValides = stats.value.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
    const tauxDepot = stats.value.taux_depot || 0
    const rapportsSoumis = stats.value.rapports_soumis || 0
    
    statsContext = `[CONTEXTE STATISTIQUES RÉELLES : 
- Total projets : ${totalProjets}
- Projets validés : ${projetsValides}
- Taux de dépôt : ${tauxDepot}%
- Rapports soumis : ${rapportsSoumis}
]
`
  }
  
  const finalMessage = statsContext + msg
  
  try {
    const res = await api.post('/ai/chat', { 
      message: finalMessage, 
      history: aiHistory.value 
    })
    aiHistory.value.push({ role: 'assistant', content: res.data.message })
  } catch {
    aiHistory.value.push({ role: 'assistant', content: 'Erreur de connexion avec l\'assistant IA.' })
  } finally { 
    aiLoading.value = false 
  }
}

onMounted(() => {
  fetchProjects()
  fetchSettings()
  fetchFeedbacks()
})
</script>

<style scoped>
/* ========== SETTINGS CYCLE ========== */
.year-control-card {
  border-left: 5px solid var(--color-primary);
  background: var(--bg-card);
}

.new-year-form {
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
}

.text-brand {
  color: var(--color-primary);
}

.students-selector {
  border: 1px solid var(--border-default);
  background: var(--bg-elevated);
  border-radius: var(--radius-md);
  padding: 0.75rem;
}

/* ========== STATISTIQUES ========== */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.stat-card-primary,
.stat-card-secondary,
.stat-card-tertiary,
.stat-card-full {
  padding: 1.25rem;
  display: flex;
  gap: 1rem;
  align-items: center;
}

.stat-card-full {
  grid-column: span 2;
  flex-direction: column;
  align-items: stretch;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 60px;
  background: var(--color-primary-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  color: var(--color-primary);
}

.stat-content {
  flex: 1;
}

.stat-value-large {
  font-family: var(--font-display);
  font-size: 2.8rem;
  font-weight: 800;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  line-height: 1;
}

.stat-label {
  font-size: var(--text-sm);
  color: var(--text-muted);
  margin-top: 0.25rem;
}

.stat-detail {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 0.75rem;
  font-size: var(--text-xs);
}

.stat-subtitle {
  font-weight: 700;
  margin-bottom: 1rem;
  font-size: var(--text-base);
}

/* Spécialités */
.specialite-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.specialite-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.specialite-name {
  width: 100px;
  font-size: var(--text-sm);
  font-weight: 500;
}

.progress-bar-mini {
  flex: 1;
  height: 8px;
  background: var(--bg-active);
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill-mini {
  height: 100%;
  background: var(--gradient-primary);
  border-radius: 10px;
}

.specialite-count {
  width: 40px;
  text-align: right;
  font-weight: 600;
}

/* Top professeurs */
.top-professeurs {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.prof-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.prof-rank {
  width: 28px;
  height: 28px;
  background: var(--bg-elevated);
  border-radius: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: var(--text-sm);
}

.prof-name {
  width: 140px;
  font-size: var(--text-sm);
}

.prof-count {
  width: 70px;
  font-size: var(--text-sm);
  font-weight: 500;
}

.prof-bar {
  flex: 1;
  height: 6px;
  background: var(--bg-active);
  border-radius: 10px;
  overflow: hidden;
}

.prof-bar-fill {
  height: 100%;
  background: var(--gradient-primary);
  border-radius: 10px;
}

/* Notes */
.notes-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.note-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--border-default);
}

.note-value {
  font-weight: 700;
  font-size: var(--text-lg);
}

/* Messages IA */
.ai-messages {
  padding: 0.5rem;
  background: var(--bg-surface);
  border-radius: var(--radius-md);
}

.ai-msg {
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-md);
  margin-bottom: 0.5rem;
  font-size: var(--text-sm);
}

.ai-msg.user {
  background: var(--color-primary-subtle);
}

.ai-msg.assistant {
  background: var(--bg-elevated);
}

/* ========== STYLES EXISTANTS ========== */
.rup-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1400px;
}

.page-title-h2 {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
}

.quick-menu {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1rem;
}

.quick-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.5rem;
  text-decoration: none;
  color: var(--text-primary);
  transition: all var(--transition-fast);
}

.quick-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-default);
  font-size: var(--text-sm);
}

.data-table th {
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  font-size: 11px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  width: 100%;
  max-width: 500px;
  padding: 1.5rem;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
}

.flex {
  display: flex;
}

.gap-2 {
  gap: 0.5rem;
}

.justify-between {
  justify-content: space-between;
}

.items-center {
  align-items: center;
}

.text-success {
  color: var(--color-success);
}

.text-warning {
  color: var(--color-warning);
}

.text-info {
  color: var(--color-info);
}

.text-danger {
  color: var(--color-danger);
}

.text-muted {
  color: var(--text-muted);
}

.text-sm {
  font-size: var(--text-sm);
}

.text-xs {
  font-size: var(--text-xs);
}

.text-center {
  text-align: center;
}

.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

@media (max-width: 900px) {
  .quick-menu {
    grid-template-columns: repeat(3, 1fr);
  }
  .stats-container {
    grid-template-columns: 1fr;
  }
  .stat-card-full {
    grid-column: span 1;
  }
}

/* ==========================================================================
   PREMIUM AUDIT PEDAGOGIQUE STYLES
   ========================================================================== */
.audit-metrics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
  .audit-metrics-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

.audit-metric-card {
  background: var(--bg-card, #ffffff);
  padding: 1.5rem;
  border-radius: 1.5rem;
  border: 1px solid var(--border-default, #f1f5f9);
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
}

.metric-icon {
  width: 52px;
  height: 52px;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.metric-icon.rating {
  background: rgba(255, 184, 0, 0.1);
  color: #ffb800;
}

.metric-icon.critiques {
  background: rgba(156, 46, 83, 0.1);
  color: #9c2e53;
}

.metric-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metric-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted, #94a3b8);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.metric-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text-primary, #0f172a);
}

.metric-value small {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-secondary, #64748b);
}

/* Encadreur Card */
.encadreur-audit-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.encadreur-audit-card {
  background: var(--bg-card, #ffffff);
  padding: 1.5rem;
  border-radius: 1.5rem;
  border: 1px solid var(--border-default, #f1f5f9);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  transition: all 0.25s ease;
  box-shadow: 0 4px 6px rgba(0,0,0,0.01);
}

.encadreur-audit-card:hover {
  transform: translateY(-3px);
  border-color: rgba(156, 46, 83, 0.2);
  box-shadow: 0 12px 24px rgba(156, 46, 83, 0.04);
}

.encadreur-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.encadreur-profile-mini {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.encadreur-avatar-mini {
  width: 48px;
  height: 48px;
  background: rgba(156, 46, 83, 0.08);
  color: #9c2e53;
  font-weight: 800;
  font-size: 1.1rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Montserrat', sans-serif;
}

.encadreur-name-mini {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-primary, #1e293b);
  font-family: 'Montserrat', sans-serif;
}

.encadreur-score-badge {
  background: rgba(255, 184, 0, 0.1);
  color: #d97706;
  font-weight: 800;
  font-size: 0.8rem;
  padding: 0.35rem 0.75rem;
  border-radius: 100px;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.encadreur-card-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.rating-bar-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.rating-progress-bar {
  flex: 1;
  height: 8px;
  background: var(--bg-active, #f1f5f9);
  border-radius: 10px;
  overflow: hidden;
}

.rating-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #9c2e53 0%, #c14d76 100%);
  border-radius: 10px;
}

.rating-percentage-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-secondary, #475569);
  min-width: 35px;
  text-align: right;
}

.encadreur-critique-count {
  font-size: 0.75rem;
  color: var(--text-muted, #94a3b8);
  font-weight: 600;
}

/* Feedbacks List */
.audit-feedbacks-section {
  background: var(--bg-surface, #f8fafc);
  padding: 2rem;
  border-radius: 2rem;
  border: 1px solid var(--border-default, #f1f5f9);
}

.audit-feedbacks-title {
  font-size: 1rem;
  font-weight: 800;
  color: var(--text-primary, #1e293b);
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Montserrat', sans-serif;
}

.feedbacks-feed {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.feedback-card-premium {
  background: var(--bg-card, #ffffff);
  padding: 1.5rem;
  border-radius: 1.5rem;
  border: 1px solid var(--border-default, #f1f5f9);
  border-left: 4px solid #9c2e53;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: all 0.2s ease;
}

.feedback-card-premium:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.feedback-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.feedback-target-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-secondary, #64748b);
}

.feedback-target-name {
  color: #9c2e53;
  font-weight: 800;
}

.golden-stars-premium {
  font-size: 1.1rem;
  color: #ffb800;
  letter-spacing: 2px;
}

.feedback-comment-text {
  font-size: 0.95rem;
  line-height: 1.6;
  color: var(--text-primary, #334155);
  font-style: italic;
}

.feedback-footer-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted, #94a3b8);
  border-top: 1px solid var(--border-default, #f8fafc);
  padding-top: 0.75rem;
}

.feedback-author-badge {
  background: var(--bg-active, #f1f5f9);
  color: var(--text-secondary, #475569);
  padding: 0.2rem 0.6rem;
  border-radius: 100px;
  font-size: 0.7rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.feedback-author-badge.anonymous {
  background: rgba(156, 46, 83, 0.08);
  color: #9c2e53;
}
</style>