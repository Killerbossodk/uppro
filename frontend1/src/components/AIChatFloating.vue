<script setup lang="ts">
import { useRoute } from 'vue-router'
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'
import { marked } from 'marked'

const auth = useAuthStore()
const route = useRoute()
const isOpen = ref(false)

// Détecter si on est sur une page de rapport
const currentReportId = computed(() => {
  const path = route.path
  if (path.includes('/rapports/')) {
    const parts = path.split('/')
    const id = parts[parts.length - 1]
    return isNaN(Number(id)) ? null : Number(id)
  }
  return null
})

const message = ref('')
const history = ref<{ role: string; content: string }[]>([])
const loading = ref(false)

// Contexte par défaut selon le rôle
const roleContext: Record<string, string> = {
  etudiant: 'Tu es un assistant pédagogique pour un ÉTUDIANT. Aide-le à améliorer ses rapports, corriger ses fautes, reformuler ses textes.',
  professeur: 'Tu es un assistant pour un PROFESSEUR. Aide-le à analyser des rapports, suggérer des notes, générer des commentaires.',
  rup_projet: 'Tu es un assistant pour le RUP PROJET. Aide-le avec les statistiques globales, la validation des projets.',
  rup_specialite: 'Tu es un assistant pour le RUP SPÉCIALITÉ. Aide-le avec les jurys, les notes, les synthèses.',
}

const globalStats = ref<any>(null)

// --- PROFESSEUR STATS & REPORT ANALYZER ABILITIES ---
const profReports = ref<any[]>([])
const selectedReportId = ref<number | null>(null)
const analysisLoading = ref(false)
const profStats = ref({
  projectsCount: 0,
  validatedCount: 0,
  groupsCount: 0,
  studentsCount: 0,
  meetingsCount: 0,
  reportsCount: 0
})

async function fetchProfReports() {
  try {
    const res = await api.get('/reports')
    profReports.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement rapports prof:', e)
  }
}

async function fetchProfessorStats() {
  try {
    const [projRes, grpRes, meetRes, repRes] = await Promise.all([
      api.get('/projects'),
      api.get('/groups'),
      api.get('/meetings'),
      api.get('/reports')
    ])
    
    const projects = Array.isArray(projRes.data) ? projRes.data : []
    const groups = Array.isArray(grpRes.data) ? grpRes.data : []
    const meetings = Array.isArray(meetRes.data) ? meetRes.data : []
    const reports = Array.isArray(repRes.data) ? repRes.data : []
    
    profStats.value = {
      projectsCount: projects.length,
      validatedCount: projects.filter((p: any) => p.statut === 'valide').length,
      groupsCount: groups.length,
      studentsCount: groups.reduce((a: number, g: any) => a + (g.membres?.length || 0), 0),
      meetingsCount: meetings.length,
      reportsCount: reports.length
    }
  } catch (e) {
    console.error('Erreur stats prof:', e)
  }
}

async function analyzeSelectedReport(reportId: number) {
  if (!reportId) return
  analysisLoading.value = true
  
  // Ajouter un message système indiquant le début de l'analyse
  history.value.push({
    role: 'assistant',
    content: `⏳ **L'IA est en train d'analyser le rapport en profondeur...**\nCette opération peut prendre quelques secondes pour extraire et analyser l'intégralité du document PDF.`
  })
  
  try {
    const res = await api.post(`/ai/analyze-report/${reportId}`)
    const analysis = res.data.analysis || 'Aucune analyse générée.'
    
    history.value.push({
      role: 'assistant',
      content: `✅ **Analyse pédagogique terminée pour le rapport sélectionné :**\n\n${analysis}`
    })
  } catch (e: any) {
    console.error(e)
    history.value.push({
      role: 'assistant',
      content: `❌ **Erreur d'analyse** : Impossible de lire ou d'analyser le document. Assurez-vous que le fichier est un PDF lisible et réessayez.`
    })
  } finally {
    analysisLoading.value = false
  }
}
// ----------------------------------------------------

async function fetchStats() {
  if (['rup_projet', 'rup_specialite'].includes(auth.user?.role || '')) {
    try {
      const res = await api.get('/analytics')
      globalStats.value = res.data
    } catch (e) {
      console.error('Erreur chargement stats IA:', e)
    }
  }
}

function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    if (!globalStats.value) {
      fetchStats()
    }
    if (auth.user?.role === 'professeur') {
      fetchProfessorStats()
      fetchProfReports()
    }
  }
}

async function sendMessage(overrideMsg?: any) {
  const userMsg = (typeof overrideMsg === 'string') ? overrideMsg : message.value
  if (!userMsg || typeof userMsg !== 'string' || !userMsg.trim() || loading.value) return
  
  history.value.push({ role: 'user', content: userMsg })
  message.value = ''
  loading.value = true

  const lowerMsg = userMsg.toLowerCase().trim()

  // 📊 PROFESSEUR : STATISTIQUES RÉELRES
  if (auth.user?.role === 'professeur' && (lowerMsg === 'mes stat' || lowerMsg === 'mes stats' || lowerMsg === 'mes statistiques' || lowerMsg === 'stats')) {
    await fetchProfessorStats()
    setTimeout(() => {
      history.value.push({
        role: 'assistant',
        content: `📊 **Voici vos statistiques réelles pour l'année en cours :**
        
• 📁 **Projets supervisés :** ${profStats.value.projectsCount} (${profStats.value.validatedCount} validés)
• 👥 **Groupes actifs suivis :** ${profStats.value.groupsCount} (${profStats.value.studentsCount} étudiants)
• 📅 **Rendez-vous programmés :** ${profStats.value.meetingsCount} RDV
• 📄 **Rapports de vos groupes :** ${profStats.value.reportsCount} rapports déposés

Puis-je vous aider à planifier un nouveau rendez-vous ou à effectuer une analyse pédagogique sur l'un de ces rapports ?`
      })
      loading.value = false
    }, 600)
    return
  }

  // 📈 RUP PROJET & RUP SPÉCIALITÉ : STATISTIQUES RÉELLES GLOBALES
  if (['rup_projet', 'rup_specialite'].includes(auth.user?.role || '') && (lowerMsg.includes('stat') || lowerMsg.includes('résumé des stats') || lowerMsg === 'donne-moi un résumé des statistiques')) {
    try {
      const res = await api.get('/analytics')
      globalStats.value = res.data
      
      const g = globalStats.value
      const totalProjets = g.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
      const valides = g.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
      const soumis = g.projets_par_statut?.find((s: any) => s.statut === 'soumis')?.total || 0
      const brouillons = g.projets_par_statut?.find((s: any) => s.statut === 'brouillon')?.total || 0
      
      let specText = ''
      if (g.projets_par_specialite?.length) {
        specText = '\n\n📁 **Par Spécialité :**'
        g.projets_par_specialite.forEach((s: any) => {
          specText += `\n• **${s.nom}** : ${s.projects_count} projet(s)`
        })
      }
      
      let noteText = ''
      if (g.moyenne_notes?.length) {
        noteText = '\n\n⭐ **Moyenne des Notes par Spécialité :**'
        g.moyenne_notes.forEach((n: any) => {
          noteText += `\n• **${n.nom}** : ${Number(n.moyenne).toFixed(2)}/20`
        })
      }

      let profText = ''
      if (g.top_professeurs?.length) {
        profText = '\n\n👨‍🏫 **Top Professeurs Actifs :**'
        g.top_professeurs.forEach((p: any) => {
          profText += `\n• ${p.prenom || ''} ${p.name} (${p.total} projets)`
        })
      }

      setTimeout(() => {
        history.value.push({
          role: 'assistant',
          content: `📈 **Bilan des Statistiques Réelles de l'Année Académique (${auth.user?.role === 'rup_projet' ? 'RUP Projet' : 'RUP Spécialité'}) :**
          
• 📂 **Total des projets :** ${totalProjets} (${valides} validés, ${soumis} soumis, ${brouillons} brouillons)
• 📤 **Rapports déposés :** ${g.rapports_soumis || 0} rapports (Taux de dépôt : **${g.taux_depot || 0}%**)
• ⚡ **Activité récente :** ${g.activite_recente || 0} projet(s) créé(s) ces 30 derniers jours${specText}${noteText}${profText}

Puis-je vous assister avec la constitution des jurys, la validation des fiches ou la synthèse annuelle des notes ?`
        })
        loading.value = false
      }, 600)
      return
    } catch (err) {
      console.error(err)
      history.value.push({ role: 'assistant', content: 'Impossible de récupérer les statistiques en temps réel pour le moment.' })
      loading.value = false
      return
    }
  }

  // 🔍 PROFESSEUR : ANALYSE RAPPORT
  if (auth.user?.role === 'professeur' && lowerMsg.includes('analyser un rapport')) {
    await fetchProfReports()
    setTimeout(() => {
      history.value.push({
        role: 'assistant',
        content: `Très bien. Pourriez-vous me fournir le rapport étudiant que vous souhaitez que j'analyse ? Une fois que vous l'aurez partagé, je pourrai l'examiner en profondeur.`,
        isReportAnalyzer: true
      } as any)
      loading.value = false
    }, 600)
    return
  }

  let contextMsg = userMsg
  
  // Injecter les stats réelles si on est RUP et qu'on les a chargées
  if (globalStats.value && ['rup_projet', 'rup_specialite'].includes(auth.user?.role || '')) {
    const totalProjets = globalStats.value.projets_par_statut?.reduce((a: number, s: any) => a + s.total, 0) || 0
    const projetsValides = globalStats.value.projets_par_statut?.find((s: any) => s.statut === 'valide')?.total || 0
    const tauxDepot = globalStats.value.taux_depot || 0
    const rapportsSoumis = globalStats.value.rapports_soumis || 0
    
    contextMsg = `[CONTEXTE STATISTIQUES RÉELLES : 
- Total projets : ${totalProjets}
- Projets validés : ${projetsValides}
- Taux de dépôt : ${tauxDepot}%
- Rapports soumis : ${rapportsSoumis}
]
` + userMsg
  }

  try {
    const res = await api.post('/ai/chat', {
      message: contextMsg,
      history: history.value.map(h => ({ role: h.role, content: h.content })),
      report_id: currentReportId.value
    })
    history.value.push({ role: 'assistant', content: res.data.message })
  } catch {
    history.value.push({ role: 'assistant', content: 'Désolé, le service IA est temporairement indisponible.' })
  } finally {
    loading.value = false
  }
}

function getRoleLabel(): string {
  const map: Record<string, string> = {
    etudiant: 'Assistant Étudiant',
    professeur: 'Assistant Professeur',
    rup_projet: 'Assistant RUP Projet',
    rup_specialite: 'Assistant RUP Spécialité',
  }
  return map[auth.user?.role || ''] || 'Assistant UP-PRO'
}

function renderMarkdown(content: string) {
  if (!content) return ''
  return marked.parse(content, { breaks: true, gfm: true })
}
</script>

<template>
  <div class="ai-floating">
    <!-- Bouton flottant -->
    <button v-if="!isOpen" class="ai-fab" @click="toggleChat" title="Assistant IA">
      <i class="pi pi-comments" style="font-size:1.4rem;" />
    </button>

    <!-- Fenêtre de chat -->
    <div v-if="isOpen" class="ai-chat-window card">
      <!-- Header -->
      <div class="ai-chat-header">
        <div class="flex items-center gap-2">
          <i class="pi pi-robot" style="font-size:1.2rem;color:var(--color-primary-light);" />
          <div>
            <p class="font-bold text-sm">{{ getRoleLabel() }}</p>

          </div>
        </div>
        <button class="btn btn-ghost btn-icon btn-sm" @click="toggleChat">
          <i class="pi pi-times" />
        </button>
      </div>

      <!-- Messages -->
      <div class="ai-chat-messages" ref="msgContainer">
        <div v-if="history.length === 0" class="ai-welcome">
          <i class="pi pi-robot" style="font-size:2rem;color:var(--color-primary-light);opacity:0.5;" />
          <p class="text-sm text-muted">Bonjour ! Je suis votre assistant IA. Comment puis-je vous aider ?</p>
          <div class="ai-suggestions">
            <button v-if="auth.user?.role === 'etudiant'" class="btn btn-ghost btn-xs" @click="message = 'Peux-tu corriger mon texte ?'; sendMessage()">✍️ Corriger un texte</button>
            <button v-if="auth.user?.role === 'etudiant'" class="btn btn-ghost btn-xs" @click="message = 'Résume ce document pour moi'; sendMessage()">📝 Résumer</button>
            <button v-if="auth.user?.role === 'professeur'" class="btn btn-ghost btn-xs" @click="sendMessage('mes stat')">📊 Mes statistiques</button>
            <button v-if="auth.user?.role === 'professeur'" class="btn btn-ghost btn-xs" @click="sendMessage('Analyser un rapport étudiant en profondeur')">🔍 Analyser un rapport étudiant en profondeur</button>
            <button v-if="auth.user?.role?.includes('rup')" class="btn btn-ghost btn-xs" @click="message = 'Donne-moi un résumé des statistiques'; sendMessage()">📈 Statistiques</button>
          </div>
        </div>
        <div v-for="(msg, i) in history" :key="i" :class="`ai-msg ${msg.role}`">
          <strong>{{ msg.role === 'user' ? 'Vous' : '🤖 IA' }}</strong>
          <div class="markdown-content" v-html="renderMarkdown(msg.content)"></div>
          
          <!-- Interface d'Analyse Interactive de Rapport pour le Professeur -->
          <div v-if="(msg as any).isReportAnalyzer && auth.user?.role === 'professeur'" class="interactive-report-analyzer mt-3 p-3 rounded bg-surface border border-default" style="background:var(--bg-elevated);border:1px solid var(--border-default);border-radius:var(--radius-md);margin-top:0.75rem;padding:0.75rem;">
            <div class="text-xs text-muted font-semibold mb-1" style="font-size:0.75rem;margin-bottom:0.25rem;color:var(--text-muted);"><i class="pi pi-file mr-1" /> Choisir un rapport de vos groupes :</div>
            <div class="flex flex-col gap-2" style="display:flex;flex-direction:column;gap:0.5rem;">
              <select v-model="selectedReportId" class="form-input text-xs" :disabled="analysisLoading" style="width:100%;font-size:0.8rem;padding:0.4rem;border-radius:4px;border:1px solid var(--border-default);background:var(--bg-base);color:var(--text-primary);">
                <option :value="null">-- Sélectionner un rapport --</option>
                <option v-for="r in profReports" :key="r.id" :value="r.id">
                  Grp {{ r.group?.nom || 'N/A' }} : {{ r.titre }} (v{{ r.version }})
                </option>
              </select>
              <button 
                class="btn btn-primary btn-sm flex items-center justify-center gap-1.5" 
                :disabled="!selectedReportId || analysisLoading"
                @click="analyzeSelectedReport(selectedReportId!)"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.4rem;"
              >
                <i v-if="analysisLoading" class="pi pi-spin pi-spinner mr-1" />
                <i v-else class="pi pi-bolt mr-1" />
                Lancer l'analyse en profondeur
              </button>
            </div>
          </div>
        </div>
        <div v-if="loading" class="ai-msg assistant">
          <i class="pi pi-spin pi-spinner" style="margin-right:0.5rem;" /> L'IA réfléchit...
        </div>
      </div>

      <!-- Input -->
      <div class="ai-chat-input">
        <input
          v-model="message"
          type="text"
          placeholder="Écrivez votre message..."
          @keydown.enter="sendMessage"
          :disabled="loading"
          class="form-input flex-1"
        />
        <button class="btn btn-primary btn-sm" @click="sendMessage" :disabled="loading || !message.trim()">
          <i class="pi pi-send" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ai-floating {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 999;
}

.ai-fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--gradient-primary);
  color: white;
  border: none;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(125, 33, 64, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  animation: float 3s ease-in-out infinite;
}

.ai-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 12px 32px rgba(125, 33, 64, 0.5);
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.ai-chat-window {
  position: absolute;
  bottom: 70px;
  right: 0;
  width: 380px;
  height: 520px;
  display: flex;
  flex-direction: column;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.ai-chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.25rem;
  border-bottom: 1px solid var(--border-default);
  background: var(--bg-surface);
  flex-shrink: 0;
}

.ai-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.ai-welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  text-align: center;
  padding: 1.5rem;
}

.ai-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  justify-content: center;
}

.ai-msg {
  padding: 0.625rem 0.875rem;
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.5;
}

.ai-msg.user {
  background: var(--color-primary-subtle);
  align-self: flex-end;
  max-width: 80%;
}

.ai-msg.assistant {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  align-self: flex-start;
  max-width: 85%;
}

.ai-msg strong {
  display: block;
  font-size: var(--text-xs);
  margin-bottom: 0.25rem;
  color: var(--color-primary-light);
}

.ai-chat-input {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-default);
  background: var(--bg-surface);
  flex-shrink: 0;
}

.btn-xs {
  padding: 0.2rem 0.6rem;
  font-size: 11px;
}

@media (max-width: 480px) {
  .ai-chat-window {
    width: calc(100vw - 32px);
    right: -8px;
  }
}

/* Styles Markdown */
.markdown-content {
  font-size: 0.85rem;
  line-height: 1.5;
}

.markdown-content :deep(p) {
  margin-bottom: 0.75rem;
}

.markdown-content :deep(ul), .markdown-content :deep(ol) {
  margin-bottom: 0.75rem;
  padding-left: 1.25rem;
}

.markdown-content :deep(li) {
  margin-bottom: 0.25rem;
}

.markdown-content :deep(code) {
  background: var(--bg-hover);
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.8rem;
  color: var(--color-primary-light);
}

.markdown-content :deep(pre) {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 0.75rem;
  border-radius: 6px;
  overflow-x: auto;
  margin-bottom: 0.75rem;
}

.markdown-content :deep(pre code) {
  background: transparent;
  padding: 0;
  color: inherit;
}

.markdown-content :deep(h1), .markdown-content :deep(h2), .markdown-content :deep(h3) {
  font-size: 1rem;
  font-weight: 700;
  margin: 1rem 0 0.5rem;
}

.markdown-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 0.75rem;
  font-size: 0.75rem;
}

.markdown-content :deep(th), .markdown-content :deep(td) {
  border: 1px solid var(--border-default);
  padding: 0.4rem;
  text-align: left;
}

.markdown-content :deep(th) {
  background: var(--bg-hover);
}

.markdown-content :deep(blockquote) {
  border-left: 3px solid var(--color-primary);
  padding-left: 0.75rem;
  color: var(--text-muted);
  font-style: italic;
  margin-bottom: 0.75rem;
}
</style>