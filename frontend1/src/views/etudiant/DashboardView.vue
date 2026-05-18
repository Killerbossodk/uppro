<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const auth = useAuthStore()
const router = useRouter()

const myProject = ref<any>(null)
const myGroup = ref<any>(null)
const myReports = ref<any[]>([])
const meetings = ref<any[]>([])

const isYearNotStartedYet = ref(false)
const academicYearLabel = ref('')

// 🤖 IA
const showAIChat = ref(false)
const showAITools = ref(false)
const aiMessage = ref('')
const aiHistory = ref<{ role: string; content: string }[]>([])
const aiLoading = ref(false)
const selectedReportForAI = ref<any>(null)
const aiText = ref('')
const aiResult = ref('')
const aiToolLoading = ref(false)
const activeAITool = ref<string | null>(null)
const showFullResult = ref(false)

onMounted(async () => {
  try {
    // 1. Charger les paramètres d'année académique
    const settingsRes = await api.get('/settings')
    const settings = settingsRes.data
    academicYearLabel.value = settings.academic_year_label || ''

    if (!settings.academic_year_label || settings.is_year_closed) {
      isYearNotStartedYet.value = true
    } else {
      isYearNotStartedYet.value = false
    }

    // Si l'année n'a pas commencé, on ne charge rien d'autre
    if (isYearNotStartedYet.value) {
      return
    }

    const grpRes = await api.get('/groups')
    const groups = Array.isArray(grpRes.data) ? grpRes.data : []
    if (groups.length > 0) {
      myGroup.value = groups[0]
      myProject.value = groups[0].project
      const repRes = await api.get(`/groups/${groups[0].id}/reports`)
      myReports.value = Array.isArray(repRes.data) ? repRes.data : []
    }
    const meetRes = await api.get('/meetings')
    meetings.value = Array.isArray(meetRes.data) ? meetRes.data : []
  } catch (e) {
    console.error('Erreur chargement:', e)
  }
})

const reportStatus = (s: string) => {
  const m: Record<string, { label: string; cls: string }> = {
    soumis: { label: 'Soumis', cls: 'badge-primary' },
    valide: { label: 'Validé', cls: 'badge-success' },
    rejete: { label: 'Rejeté', cls: 'badge-danger' },
  }
  return m[s] ?? { label: s, cls: 'badge-info' }
}

function toggleAIChat() { showAIChat.value = !showAIChat.value }

async function sendAIMessage() {
  if (!aiMessage.value.trim() || aiLoading.value) return
  const userMsg = aiMessage.value
  aiHistory.value.push({ role: 'user', content: userMsg })
  aiMessage.value = ''
  aiLoading.value = true
  try {
    const res = await api.post('/ai/chat', { message: userMsg, history: aiHistory.value, report_id: selectedReportForAI.value?.id || null })
    aiHistory.value.push({ role: 'assistant', content: res.data.message })
  } catch {
    aiHistory.value.push({ role: 'assistant', content: 'Erreur IA.' })
  } finally { aiLoading.value = false }
}

function openAITool(tool: string, report: any = null) {
  activeAITool.value = tool; selectedReportForAI.value = report
  showFullResult.value = false; aiText.value = ''; aiResult.value = ''; showAITools.value = true
}

async function executeAITool() {
  if (!aiText.value.trim() && activeAITool.value !== 'analyze-report') return
  aiToolLoading.value = true; aiResult.value = ''
  try {
    let res: any
    switch (activeAITool.value) {
      case 'analyze-report':
        if (selectedReportForAI.value) { res = await api.post(`/ai/analyze-report/${selectedReportForAI.value.id}`); aiResult.value = res.data.analysis || res.data }; break
      case 'correct-grammar': res = await api.post('/ai/correct-grammar', { content: aiText.value.substring(0, 3000) }); aiResult.value = res.data.corrected; break
      case 'summarize':
        if (selectedReportForAI.value && !aiText.value) { const t = await api.get(`/reports/${selectedReportForAI.value.id}/text`); res = await api.post('/ai/summarize', { content: (t.data.text || '').substring(0, 3000) }) }
        else { res = await api.post('/ai/summarize', { content: aiText.value.substring(0, 3000) }) }; aiResult.value = res.data.summary; break
      case 'rephrase': res = await api.post('/ai/rephrase', { content: aiText.value.substring(0, 3000) }); aiResult.value = res.data.rephrased; break
      case 'suggest-improvements': res = await api.post('/ai/suggest-improvements', { content: aiText.value.substring(0, 3000) }); aiResult.value = res.data.suggestions; break
      case 'expand-content': res = await api.post('/ai/expand-content', { content: aiText.value.substring(0, 2000) }); aiResult.value = res.data.expanded; break
    }
  } catch (e: any) { aiResult.value = 'Erreur : ' + (e.response?.data?.error || e.message) }
  finally { aiToolLoading.value = false }
}

function openAIToolWithReport(tool: string, report: any) {
  activeAITool.value = tool; selectedReportForAI.value = report; aiText.value = ''; aiResult.value = ''; showAITools.value = true
}
</script>

<template>
  <div class="student-dashboard" v-if="!isYearNotStartedYet">
    <div class="welcome-section">
      <div>
        <h2 class="welcome-title">Bonjour, <span class="text-brand">{{ auth.user?.prenom || auth.user?.name }}</span> 🎓</h2>
        <p class="welcome-subtitle">Suivi de ton projet — {{ myGroup?.nom || 'Aucun groupe' }}</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary" @click="toggleAIChat">🤖 Assistant IA</button>
        <RouterLink to="/etudiant/critiques-encadreurs" class="btn btn-secondary"><i class="pi pi-star" /> Avis Encadreur</RouterLink>
        <RouterLink to="/etudiant/rapports" class="btn btn-primary"><i class="pi pi-upload" /> Déposer un rapport</RouterLink>
      </div>
    </div>

    <div v-if="myProject" class="project-overview card">
      <div class="po-header">
        <div><h3 class="po-title">{{ myProject.titre }}</h3><p class="text-muted text-sm">{{ myProject.description?.substring(0, 200) }}</p></div>
        <span class="badge badge-info">{{ myProject.statut }}</span>
      </div>
      <div class="po-meta">
        <div class="meta-item"><i class="pi pi-user" /><span>{{ myProject.superviseur?.prenom }} {{ myProject.superviseur?.name }}</span></div>
        <div class="meta-item"><i class="pi pi-users" /><span>{{ myGroup?.nom }}</span></div>
        <div class="meta-item"><i class="pi pi-bookmark" /><span>{{ myProject.niveau }} · {{ myProject.specialite?.nom || 'Mélangé' }}</span></div>
      </div>
    </div>

    <div class="student-grid">
      <div class="card group-card">
        <div class="section-header"><h3 class="section-title"><i class="pi pi-users" /> Mon groupe</h3><span class="badge badge-info">{{ myGroup?.membres?.length || 0 }} membres</span></div>
        <div class="members-list">
          <div v-for="m in (myGroup?.membres || [])" :key="m.id" class="member-item">
            <div class="avatar">{{ (m.prenom?.[0] || '') + (m.name?.[0] || '') }}</div>
            <div class="member-info"><p class="member-name">{{ m.prenom }} {{ m.name }}</p><p class="member-role text-muted text-sm">{{ m.pivot?.est_chef ? 'Chef' : 'Membre' }}</p></div>
            <span v-if="m.pivot?.est_chef" class="badge badge-warning" style="font-size:10px;">Chef</span>
          </div>
        </div>
        <button class="btn btn-primary btn-sm w-full mt-3" @click="router.push(`/etudiant/groupe?id=${myGroup?.id}`)"><i class="pi pi-comments" /> Chat du groupe</button>
      </div>

      <div class="card reports-card">
        <div class="section-header"><h3 class="section-title"><i class="pi pi-file" /> Mes rapports</h3><RouterLink to="/etudiant/rapports" class="btn btn-ghost btn-sm">Gérer <i class="pi pi-arrow-right" /></RouterLink></div>
        <div class="reports-list">
          <div v-for="r in myReports" :key="r.id" class="report-item">
            <div class="report-icon"><i class="pi pi-file-pdf" /></div>
            <div class="report-info">
              <p class="report-title">{{ r.titre }}</p><p class="report-date text-muted text-sm">{{ r.created_at?.split('T')[0] }}</p>
            </div>
            <div class="report-right"><span :class="`badge ${reportStatus(r.statut).cls}`">{{ reportStatus(r.statut).label }}</span></div>
          </div>
        </div>
      </div>

      <div class="card jury-card">
        <div class="section-header"><h3 class="section-title"><i class="pi pi-calendar" /> Prochains RDV</h3><RouterLink to="/etudiant/agenda" class="btn btn-ghost btn-sm">Agenda</RouterLink></div>
        <div v-if="meetings.length === 0" class="text-muted text-sm text-center py-2">Aucun RDV</div>
        <div v-else v-for="m in meetings.slice(0, 3)" :key="m.id" class="rdv-item">
          <div class="flex justify-between items-center">
            <div>
              <p class="font-semibold text-sm">{{ m.titre }}</p>
              <p class="text-xs text-muted">{{ new Date(m.date_heure).toLocaleString('fr-FR') }}</p>
            </div>
            <router-link v-if="m.type === 'soutenance'" :to="`/etudiant/fiche-soutenance/${m.id}`" class="btn btn-primary btn-xs">
              Fiche
            </router-link>
          </div>
        </div>
        <!-- Section ressources déplacée vers la page rapport -->
      </div>
    </div>

    <!-- MODAL CHAT IA -->
    <div v-if="showAIChat" class="modal-overlay" @click.self="showAIChat = false">
      <div class="modal-card card" style="max-width:600px;height:450px;display:flex;flex-direction:column;">
        <div class="flex justify-between items-center mb-3"><h3>🤖 Assistant IA</h3><button class="btn btn-ghost btn-icon btn-sm" @click="showAIChat = false"><i class="pi pi-times" /></button></div>
        <div class="ai-messages" style="flex:1;overflow-y:auto;margin-bottom:1rem;">
          <div v-if="aiHistory.length === 0" class="text-muted text-sm text-center py-4">Posez une question...</div>
          <div v-for="(msg, i) in aiHistory" :key="i" :class="`ai-msg ${msg.role}`"><strong>{{ msg.role === 'user' ? 'Vous' : 'IA' }} :</strong> {{ msg.content }}</div>
          <div v-if="aiLoading" class="ai-msg assistant"><i class="pi pi-spin pi-spinner" /> Réflexion...</div>
        </div>
        <div class="flex gap-2"><input v-model="aiMessage" type="text" class="form-input flex-1" placeholder="Posez votre question..." @keydown.enter="sendAIMessage" :disabled="aiLoading" /><button class="btn btn-primary btn-sm" @click="sendAIMessage" :disabled="aiLoading || !aiMessage.trim()"><i class="pi pi-send" /></button></div>
      </div>
    </div>

    <!-- MODAL OUTILS IA -->
    <div v-if="showAITools" class="modal-overlay" @click.self="showAITools = false">
      <div class="modal-card card" style="max-width:700px;max-height:80vh;overflow-y:auto;">
        <div class="flex justify-between items-center mb-3"><h3>🔧 Outil IA</h3><button class="btn btn-ghost btn-icon btn-sm" @click="showAITools = false"><i class="pi pi-times" /></button></div>
        <div v-if="activeAITool !== 'analyze-report'" class="form-group mb-3"><textarea v-model="aiText" class="form-input" rows="6" placeholder="Collez votre texte ici..."></textarea></div>
        <button class="btn btn-primary btn-sm mb-3" @click="executeAITool" :disabled="aiToolLoading"><i v-if="aiToolLoading" class="pi pi-spin pi-spinner" /> Lancer</button>
        <div v-if="aiResult">
          <div class="p-3" :class="{ 'ai-result-collapsed': !showFullResult && aiResult.length > 300 }" style="background:var(--bg-elevated);border-radius:var(--radius-md);white-space:pre-wrap;font-size:var(--text-sm);">{{ showFullResult ? aiResult : aiResult.substring(0, 300) + '...' }}</div>
          <button v-if="aiResult.length > 300" class="btn btn-ghost btn-xs mt-2" @click="showFullResult = !showFullResult">{{ showFullResult ? '▲ Voir moins' : '▼ Voir plus' }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Si l'année académique n'a pas encore commencé -->
  <div v-else class="not-started-container card">
    <div class="not-started-content">
      <div class="not-started-icon">
        <i class="pi pi-calendar-times" />
      </div>
      <h2 class="not-started-title">Année Académique Non Démarrée ⏳</h2>
      <p class="not-started-subtitle">
        L'année universitaire n'a pas encore été officiellement lancée pour votre promotion.
      </p>
      
      <div class="not-started-divider"></div>
      
      <div class="not-started-info-box">
        <i class="pi pi-info-circle" />
        <span>
          La direction et le Responsable RUP Projet préparent actuellement les configurations pour la nouvelle session. 
          Dès le démarrage de l'année, vous recevrez une notification officielle par email pour composer vos groupes, soumettre vos projets et suivre votre parcours.
        </span>
      </div>

      <p class="not-started-footer">Merci pour votre patience et à très bientôt ! 🎓</p>
    </div>
  </div>
</template>

<style scoped>
.student-dashboard { display: flex; flex-direction: column; gap: 2rem; max-width: 1300px; }
.welcome-section { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.welcome-title { font-family: var(--font-display); font-size: var(--text-3xl); font-weight: 800; margin-bottom: 0.4rem; }
.welcome-subtitle { color: var(--text-secondary); }
.project-overview { display: flex; flex-direction: column; gap: 1.25rem; }
.po-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.po-title { font-family: var(--font-display); font-size: var(--text-xl); font-weight: 700; margin-bottom: 0.5rem; }
.po-meta { display: flex; gap: 2rem; flex-wrap: wrap; }
.meta-item { display: flex; align-items: center; gap: 0.5rem; font-size: var(--text-sm); color: var(--text-secondary); }
.meta-item .pi { color: var(--color-primary-light); }
.student-grid { display: grid; grid-template-columns: 280px 1fr 1fr; gap: 1.5rem; }
.group-card { grid-row: 1 / 3; }
.jury-card { grid-column: 2 / 4; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-size: var(--text-base); font-weight: 700; }
.section-title .pi { color: var(--color-primary-light); }
.members-list { display: flex; flex-direction: column; gap: 0.75rem; }
.member-item { display: flex; align-items: center; gap: 0.875rem; }
.member-info { flex: 1; }
.member-name { font-size: var(--text-sm); font-weight: 600; }
.reports-list { display: flex; flex-direction: column; gap: 0.875rem; }
.report-item { display: flex; align-items: center; gap: 1rem; padding: 0.875rem; background: var(--bg-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-default); }
.report-icon { width: 40px; height: 40px; border-radius: var(--radius-md); background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; color: #ef4444; }
.report-info { flex: 1; }
.report-title { font-size: var(--text-sm); font-weight: 600; }
.report-right { display: flex; flex-direction: column; align-items: flex-end; }
.rdv-item { padding: 0.5rem 0; border-bottom: 1px solid var(--border-default); }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; padding: 1.5rem; }
.ai-msg { padding: 0.5rem 0.75rem; border-radius: var(--radius-md); margin-bottom: 0.5rem; font-size: var(--text-sm); }
.ai-msg.user { background: var(--color-primary-subtle); }
.ai-msg.assistant { background: var(--bg-elevated); }
.ai-result-collapsed { max-height: 120px; overflow: hidden; position: relative; }
.ai-result-collapsed::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: linear-gradient(transparent, var(--bg-elevated)); }
.btn-xs { padding: 0.15rem 0.5rem; font-size: 11px; }
@media (max-width: 1100px) { .student-grid { grid-template-columns: 1fr 1fr; } .group-card { grid-row: auto; } .jury-card { grid-column: 1 / -1; } }
@media (max-width: 640px) { .student-grid { grid-template-columns: 1fr; } }

/* Premium Academic Year Not Started Styles */
.not-started-container {
  max-width: 680px;
  margin: 4rem auto;
  padding: 3rem 2rem;
  text-align: center;
  border-radius: 2rem;
  border: 1px solid var(--border-default, #e2e8f0);
  background: var(--bg-card, #ffffff);
  box-shadow: 0 20px 50px rgba(0,0,0,0.03);
  animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.not-started-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.not-started-icon {
  width: 80px;
  height: 80px;
  border-radius: 1.5rem;
  background: rgba(156, 46, 83, 0.08);
  color: #9c2e53;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.not-started-title {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text-primary, #0f172a);
  margin: 0;
}

.not-started-subtitle {
  font-size: 0.95rem;
  color: var(--text-secondary, #475569);
  max-width: 480px;
  line-height: 1.5;
  margin: 0;
}

.not-started-divider {
  width: 60px;
  height: 4px;
  background: #9c2e53;
  border-radius: 10px;
  margin: 0.5rem 0;
}

.not-started-info-box {
  background: var(--bg-active, #f8fafc);
  border-left: 4px solid #9c2e53;
  padding: 1.25rem 1.5rem;
  border-radius: 1rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  text-align: left;
  max-width: 520px;
}

.not-started-info-box i {
  color: #9c2e53;
  font-size: 1.25rem;
  margin-top: 0.15rem;
}

.not-started-info-box span {
  font-size: 0.85rem;
  line-height: 1.6;
  color: var(--text-secondary, #475569);
}

.not-started-footer {
  font-size: 0.9rem;
  font-weight: 600;
  color: #9c2e53;
  margin-top: 1rem;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>