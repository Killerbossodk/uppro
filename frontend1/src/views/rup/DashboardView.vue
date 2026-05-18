<template>
  <div class="rup-specialite-dashboard">
    <!-- ========== EN-TÊTE ========== -->
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Tableau de bord RUP Spécialité</h2>
        <p class="text-muted text-sm">Bonjour {{ auth.user?.prenom }} {{ auth.user?.name }}</p>
        <p class="text-muted text-xs">Spécialité : {{ auth.user?.specialite?.nom || 'Non définie' }}</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" @click="toggleAIChat">🤖 Assistant IA</button>
      </div>
    </div>

    <!-- ========== MENU RAPIDE ========== -->
    <section class="dashboard-section">
      <div class="quick-menu">
        <RouterLink to="/rup/jurys" class="card quick-card">
          <i class="pi pi-star" style="font-size:2rem;color:var(--color-primary);" />
          <span class="font-medium">Jurys</span>
        </RouterLink>
        <RouterLink to="/rup/notes" class="card quick-card">
          <i class="pi pi-chart-line" style="font-size:2rem;color:var(--color-primary);" />
          <span class="font-medium">Notes</span>
        </RouterLink>
      </div>
    </section>

    <!-- ========== BOUTON STATS ========== -->
    <section class="dashboard-section">
      <div class="section-toolbar">
        <button class="btn btn-secondary btn-sm" @click="loadStats">
          {{ showStats ? 'Masquer' : 'Voir' }} les statistiques de ma spécialité
        </button>
        <button v-if="showStats" class="btn btn-ghost btn-sm" @click="refreshStats">
          <i class="pi pi-refresh" /> Rafraîchir
        </button>
      </div>

      <!-- ========== VRAIES STATISTIQUES ========== -->
      <div v-if="showStats && stats" class="stats-container">
        <div class="card stat-card-primary">
          <div class="stat-icon"><i class="pi pi-briefcase" /></div>
          <div class="stat-content">
            <div class="stat-value-large">{{ totalProjets }}</div>
            <div class="stat-label">Projets dans ma spécialité</div>
            <div class="stat-detail">
              <span class="text-success">✓ {{ projetsValides }} validés</span>
              <span class="text-warning">⏳ {{ notesSaisies }} notes saisies</span>
            </div>
          </div>
        </div>

        <div class="card stat-card-secondary">
          <div class="stat-icon"><i class="pi pi-file" /></div>
          <div class="stat-content">
            <div class="stat-value-large">{{ stats.rapports_soumis || 0 }}</div>
            <div class="stat-label">Rapports soumis</div>
            <div class="stat-detail">
              <span>Taux de dépôt : <strong>{{ stats.taux_depot || 0 }}%</strong></span>
            </div>
          </div>
        </div>

        <div class="card stat-card-tertiary">
          <div class="stat-icon"><i class="pi pi-chart-line" /></div>
          <div class="stat-content">
            <div class="stat-value-large">{{ moyenneSpeciale }}</div>
            <div class="stat-label">Moyenne de ma spécialité</div>
            <div class="stat-detail">
              <span>sur {{ totalNotes }} rapport(s) noté(s)</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showStats && !stats" class="card text-center py-4">
        <i class="pi pi-spin pi-spinner" />
        <p class="text-muted mt-2">Chargement des statistiques...</p>
      </div>
    </section>

    <!-- ========== PROJETS DE LA SPÉCIALITÉ ========== -->
    <section class="dashboard-section">
      <div class="card">
        <h3 class="section-title"><i class="pi pi-briefcase" /> Projets de ma spécialité</h3>
        <div class="section-divider"></div>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr><th>Titre</th><th>Niveau</th><th>Statut</th><th>Professeur</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <tr v-for="p in projects" :key="p.id">
                <td class="font-medium project-title-cell" :title="p.titre">{{ p.titre }}<span v-if="p.est_archive" class="badge badge-info ml-2">📦 Archivé</span></td>
                <td>{{ p.niveau }}</td>
                <td><span :class="`badge ${getStatusClass(p.statut)}`">{{ p.statut }}</span></td>
                <td>{{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</td>
                <td>
                  <button class="btn btn-primary btn-sm" @click="voirDetails(p)">👁️ Voir</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ========== MODAL DÉTAILS ========== -->
    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-card card">
        <div class="modal-header">
          <h3>{{ selectedProject?.titre }}</h3>
        </div>
        <div class="section-divider"></div>
        <div class="modal-body space-y-2">
          <p><strong>Description :</strong> {{ selectedProject?.description || 'Aucune' }}</p>
          <p><strong>Niveau :</strong> {{ selectedProject?.niveau }}</p>
          <p><strong>Professeur :</strong> {{ selectedProject?.superviseur?.prenom }} {{ selectedProject?.superviseur?.name }}</p>
          <p><strong>Statut :</strong> {{ selectedProject?.statut }}</p>
        </div>
        <div class="section-divider"></div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="showDetailModal = false">Fermer</button>
        </div>
      </div>
    </div>

    <!-- ========== MODAL CHAT IA ========== -->
    <div v-if="showAIChat" class="modal-overlay" @click.self="showAIChat = false">
      <div class="modal-card card ai-chat-modal">
        <div class="modal-header">
          <h3>🤖 Assistant RUP Spécialité</h3>
          <button class="btn btn-ghost btn-icon btn-sm" @click="showAIChat = false"><i class="pi pi-times" /></button>
        </div>
        <div class="section-divider"></div>
        <div class="ai-messages">
          <div v-if="aiHistory.length === 0" class="text-muted text-sm text-center py-4">Posez une question...</div>
          <div v-for="(msg, i) in aiHistory" :key="i" :class="`ai-msg ${msg.role}`">
            <strong>{{ msg.role === 'user' ? 'Vous' : 'IA' }} :</strong> {{ msg.content }}
          </div>
          <div v-if="aiLoading" class="ai-msg assistant"><i class="pi pi-spin pi-spinner" /> Réflexion...</div>
        </div>
        <div class="section-divider"></div>
        <div class="ai-input-row">
          <input v-model="aiMessage" type="text" class="form-input flex-1" placeholder="Posez votre question..." @keydown.enter="sendAIMessage" :disabled="aiLoading" />
          <button class="btn btn-primary btn-sm" @click="sendAIMessage" :disabled="aiLoading || !aiMessage.trim()"><i class="pi pi-send" /></button>
        </div>
      </div>
    </div>

    <!-- ========== MODAL OUTILS IA ========== -->
    <div v-if="showAITools" class="modal-overlay" @click.self="showAITools = false">
      <div class="modal-card card ai-tools-modal">
        <div class="modal-header">
          <h3>🔧 Outil IA</h3>
          <button class="btn btn-ghost btn-icon btn-sm" @click="showAITools = false"><i class="pi pi-times" /></button>
        </div>
        <div class="section-divider"></div>
        <div class="ai-tools-tabs">
          <button class="btn btn-sm" :class="activeAITool === 'summarize' ? 'btn-primary' : 'btn-ghost'" @click="activeAITool = 'summarize'">📝 Résumer</button>
          <button class="btn btn-sm" :class="activeAITool === 'improve' ? 'btn-primary' : 'btn-ghost'" @click="activeAITool = 'improve'">💡 Améliorer</button>
          <button class="btn btn-sm" :class="activeAITool === 'expand' ? 'btn-primary' : 'btn-ghost'" @click="activeAITool = 'expand'">📖 Développer</button>
        </div>
        <div class="form-group">
          <textarea v-model="aiText" class="form-input" rows="6" placeholder="Collez votre texte ici..."></textarea>
        </div>
        <button class="btn btn-primary btn-sm" @click="executeAITool" :disabled="aiToolLoading || !aiText.trim()">
          <i v-if="aiToolLoading" class="pi pi-spin pi-spinner" /> Lancer
        </button>
        <div v-if="aiResult" class="ai-result">
          {{ aiResult }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// aucune modification du script
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'

const auth = useAuthStore()
const projects = ref<any[]>([])
const stats = ref<any>(null)
const showStats = ref(false)
const showDetailModal = ref(false)
const selectedProject = ref<any>(null)

const totalProjets = computed(() => {
  return stats.value?.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
})
const projetsValides = computed(() => {
  return stats.value?.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
})
const notesSaisies = computed(() => {
  return stats.value?.moyenne_notes?.length || 0
})
const moyenneSpeciale = computed(() => {
  const maSpecialite = auth.user?.specialite?.nom
  if (!maSpecialite || !stats.value?.moyenne_notes) return '0'
  const spec = stats.value.moyenne_notes.find((s: any) => s.nom === maSpecialite)
  return spec ? spec.moyenne.toFixed(1) : '0'
})
const totalNotes = computed(() => {
  return stats.value?.moyenne_notes?.reduce((a: number, s: any) => a + (s.nb_notes || 0), 0) || 0
})

async function fetchProjects() {
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data : []
  } catch (e) { console.error('Erreur chargement projets:', e) }
}
async function loadStats() {
  if (!showStats.value) await refreshStats()
  showStats.value = !showStats.value
}
async function refreshStats() {
  try {
    const res = await api.get('/analytics')
    stats.value = res.data
  } catch (e) { console.error('Erreur chargement stats:', e) }
}
function getStatusClass(statut: string) {
  const classes: Record<string, string> = {
    'brouillon': 'badge-info', 'soumis': 'badge-warning',
    'valide': 'badge-success', 'refuse': 'badge-danger'
  }
  return classes[statut] || 'badge-info'
}
function voirDetails(projet: any) {
  selectedProject.value = projet
  showDetailModal.value = true
}

const showAIChat = ref(false)
const showAITools = ref(false)
const aiMessage = ref('')
const aiHistory = ref<{ role: string; content: string }[]>([])
const aiLoading = ref(false)
const aiText = ref('')
const aiResult = ref('')
const aiToolLoading = ref(false)
const activeAITool = ref('')

function toggleAIChat() {
  showAIChat.value = !showAIChat.value
  if (showAIChat.value && stats.value) {
    const totalProjets = stats.value.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
    const projetsValides = stats.value.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
    const tauxDepot = stats.value.taux_depot || 0
    const rapportsSoumis = stats.value.rapports_soumis || 0
    const moyenne = moyenneSpeciale.value
    aiHistory.value.push({ role: 'assistant', content: `Bonjour ! Je suis votre assistant RUP Spécialité.\n\n📊 STATISTIQUES EN TEMPS RÉEL :\n- Total projets : ${totalProjets}\n- Projets validés : ${projetsValides}\n- Taux de dépôt : ${tauxDepot}%\n- Rapports soumis : ${rapportsSoumis}\n- Moyenne : ${moyenne}/20\n\nPosez-moi vos questions !` })
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
    statsContext = `[CONTEXTE : projets=${totalProjets}, validés=${projetsValides}, dépôt=${stats.value.taux_depot||0}%, rapports=${stats.value.rapports_soumis||0}, moyenne=${moyenneSpeciale.value}/20]\n`
  }
  try {
    const res = await api.post('/ai/chat', { message: statsContext + msg, history: aiHistory.value })
    aiHistory.value.push({ role: 'assistant', content: res.data.message })
  } catch {
    aiHistory.value.push({ role: 'assistant', content: 'Erreur IA.' })
  } finally { aiLoading.value = false }
}
function openAITool(tool: string) {
  activeAITool.value = tool
  aiText.value = ''
  aiResult.value = ''
  showAITools.value = true
}
async function executeAITool() {
  if (!aiText.value.trim()) return
  aiToolLoading.value = true
  try {
    let res: any
    switch (activeAITool.value) {
      case 'analyze': res = await api.post('/ai/analyze-section', { content: aiText.value }); aiResult.value = res.data.result; break
      case 'summarize': res = await api.post('/ai/summarize', { content: aiText.value }); aiResult.value = res.data.summary; break
      case 'improve': res = await api.post('/ai/suggest-improvements', { content: aiText.value }); aiResult.value = res.data.suggestions; break
      case 'expand': res = await api.post('/ai/expand-content', { content: aiText.value }); aiResult.value = res.data.expanded; break
    }
  } catch (e: any) {
    aiResult.value = 'Erreur: ' + (e.response?.data?.error || e.message)
  } finally { aiToolLoading.value = false }
}

onMounted(() => { fetchProjects() })
</script>

<style scoped>
/* ========== LAYOUT GÉNÉRAL ========== */
.rup-specialite-dashboard {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Chaque grande section a son propre espacement */
.dashboard-section {
  margin-bottom: 2rem;
}

/* Séparateur fin entre blocs internes */
.section-divider {
  height: 1px;
  background: var(--border-default);
  margin: 1rem 0;
  opacity: 0.6;
}

/* ========== EN-TÊTE ========== */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding-bottom: 1.5rem;
  margin-bottom: 2rem;
  border-bottom: 1px solid var(--border-default);
}

.page-title-h2 {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
  margin-bottom: 0.25rem;
}

/* ========== TOOLBAR STATS ========== */
.section-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

/* ========== STATISTIQUES ========== */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.25rem;
}

.stat-card-primary,
.stat-card-secondary,
.stat-card-tertiary {
  padding: 1.5rem;
  display: flex;
  gap: 1.25rem;
  align-items: center;
  border-radius: var(--radius-lg);
}

.stat-icon {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-primary-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  color: var(--color-primary);
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value-large {
  font-family: var(--font-display);
  font-size: 2.6rem;
  font-weight: 800;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  line-height: 1;
}

.stat-label {
  font-size: var(--text-sm);
  color: var(--text-muted);
  margin-top: 0.3rem;
}

.stat-detail {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 0.6rem;
  font-size: var(--text-xs);
  padding-top: 0.6rem;
  border-top: 1px solid var(--border-default);
  opacity: 0.85;
}

/* ========== MENU RAPIDE ========== */
.quick-menu {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.quick-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.5rem 1rem;
  text-decoration: none;
  color: var(--text-primary);
  transition: all var(--transition-fast);
  border-radius: var(--radius-lg);
}

.quick-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

/* ========== TABLE ========== */
.section-title {
  font-size: var(--text-lg);
  font-weight: 700;
  margin-bottom: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  padding: 0.8rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-default);
  font-size: var(--text-sm);
}

.data-table th {
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.04em;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: var(--bg-elevated);
}

/* ========== MODALS ========== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal-card {
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  padding: 0;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
}

.modal-header h3 {
  font-size: var(--text-lg);
  font-weight: 700;
  margin: 0;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  word-wrap: break-word;
  white-space: pre-wrap;
}

.modal-body p {
  font-size: var(--text-sm);
  line-height: 1.6;
}

.modal-footer {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: flex-end;
}

/* ========== CHAT IA ========== */
.ai-chat-modal {
  max-width: 600px;
  display: flex;
  flex-direction: column;
  height: 480px;
}

.ai-messages {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 1.5rem;
  background: var(--bg-surface);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ai-msg {
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.55;
}

.ai-msg.user {
  background: var(--color-primary-subtle);
  align-self: flex-end;
  max-width: 85%;
}

.ai-msg.assistant {
  background: var(--bg-elevated);
  align-self: flex-start;
  max-width: 85%;
}

.ai-input-row {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
}

/* ========== OUTILS IA ========== */
.ai-tools-modal {
  max-width: 700px;
  max-height: 80vh;
  overflow-y: auto;
}

.ai-tools-tabs {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  padding: 0 1.5rem;
  margin-bottom: 1rem;
}

.ai-tools-modal .form-group {
  padding: 0 1.5rem;
  margin-bottom: 1rem;
}

.ai-tools-modal .btn-primary {
  margin: 0 1.5rem 1rem;
}

.ai-result {
  margin: 0 1.5rem 1.5rem;
  padding: 1rem;
  background: var(--bg-elevated);
  border-radius: var(--radius-md);
  white-space: pre-wrap;
  font-size: var(--text-sm);
  line-height: 1.6;
  border-left: 3px solid var(--color-primary);
}

/* ========== UTILITAIRES ========== */
.flex { display: flex; }
.gap-2 { gap: 0.5rem; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }
.font-medium { font-weight: 500; }
.text-success { color: var(--color-success); }
.text-warning { color: var(--color-warning); }
.text-info { color: var(--color-info); }
.text-danger { color: var(--color-danger); }
.text-muted { color: var(--text-muted); }
.text-sm { font-size: var(--text-sm); }
.text-xs { font-size: var(--text-xs); }
.text-center { text-align: center; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.mt-2 { margin-top: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.ml-2 { margin-left: 0.5rem; }
.flex-1 { flex: 1; }
.space-y-2 > * + * { margin-top: 0.5rem; }
.project-title-cell {
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 900px) {
  .quick-menu { grid-template-columns: repeat(2, 1fr); }
  .stats-container { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; gap: 1rem; }
}
</style>