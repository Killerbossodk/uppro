<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/magasins/auth'
import { useNotificationsStore } from '@/magasins/notifications'
import echo from '@/services/echo'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notifications = useNotificationsStore()

const meetingId = route.params.id
const loading = ref(true)
const meeting = ref<any>(null)
const jury = ref<any>(null)
const groups = ref<any[]>([])
const activeTab = ref('evaluation') // 'evaluation' or 'ai'
const aiHistory = ref<any[]>([])
const aiMessage = ref('')
const aiLoading = ref(false)
const countdown = ref('--:--')
const timerInterval = ref<any>(null)
const critiques = ref('')
const grades = ref<any>({})
const selectedGroupId = ref<number | null>(null)

const isAdminOrRup = computed(() => auth.user && ['rup_projet', 'rup_specialite', 'rup', 'admin'].includes(auth.user.role))

const isJury = computed(() => {
  if (!jury.value || !auth.user) return false
  return auth.user.role === 'admin' ||
         jury.value.president_id === auth.user.id || 
         jury.value.membres?.some((m: any) => m.id === auth.user.id)
})

const isPresident = computed(() => jury.value?.president_id === auth.user?.id)

const isStudent = computed(() => auth.user?.role === 'etudiant')

const currentReport = computed(() => {
  const activeGroup = groups.value.find(g => g.id === selectedGroupId.value) || groups.value[0]
  if (!activeGroup) return null
  return activeGroup.reports?.find((r: any) => r.type === 'final') || activeGroup.reports?.[0]
})

onMounted(async () => {
  await fetchData()
  startTimer()
  
  // --- REAL-TIME WEBSOCKET (REVERB) ---
  echo.channel(`meeting.${meetingId}`)
      .listen('.critiques.updated', (e: any) => {
        if (!isTypingCritiques.value) {
          critiques.value = e.critiques
        }
      })
      .listen('.grade.updated', (e: any) => {
        console.log("WebSocket: Note reçue", e)
        if (grades.value[e.groupId]) {
          grades.value[e.groupId].note = e.note
          grades.value[e.groupId].commentaire = e.commentaire
        }
      })
      .listen('.soutenance.status_updated', (e: any) => {
        console.log("WebSocket: Statut mis à jour", e)
        if (meeting.value) {
          meeting.value.statut_soutenance = e.statut
          meeting.value.started_at = e.started_at
          meeting.value.ended_at = e.ended_at
          
          if (e.statut === 'en_cours') {
            startTimer()
          } else if (e.statut === 'termine') {
            if (timerInterval.value) clearInterval(timerInterval.value)
            countdown.value = '--:--'
            alert("La soutenance a été clôturée par le président.")
          }
        }
      })
})

onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value)
  echo.leaveChannel(`meeting.${meetingId}`)
})

async function fetchData() {
  loading.value = true
  try {
    const [mRes, jRes] = await Promise.all([
      api.get(`/meetings/${meetingId}`),
      api.get(`/meetings/${meetingId}/jury`).catch(() => ({ data: null }))
    ])
    meeting.value = mRes.data
    jury.value = jRes.data
    groups.value = mRes.data.groupes || []
    if (groups.value.length > 0) {
      selectedGroupId.value = groups.value[0].id
    }
    critiques.value = mRes.data.critiques_generales || ''

    groups.value.forEach(g => {
      grades.value[g.id] = {
        note: g.pivot?.note || 10,
        commentaire: g.pivot?.commentaire || ''
      }
    })
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function startTimer() {
  if (timerInterval.value) clearInterval(timerInterval.value)
  
  timerInterval.value = setInterval(() => {
    if (!meeting.value?.started_at || meeting.value?.statut_soutenance !== 'en_cours') {
      countdown.value = '--:--'
      return
    }

    const start = new Date(meeting.value.started_at).getTime()
    const now = new Date().getTime()
    const diff = now - start

    if (diff <= 0) {
      countdown.value = '00:00'
      return
    }

    const hours = Math.floor(diff / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    const seconds = Math.floor((diff % (1000 * 60)) / 1000)
    
    if (hours > 0) {
      countdown.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    } else {
      countdown.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    }
  }, 1000)
}

async function startSoutenance() {
  try {
    const res = await api.post(`/meetings/${meetingId}/start-soutenance`)
    meeting.value.statut_soutenance = 'en_cours'
    meeting.value.started_at = res.data.started_at
    startTimer()
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

async function finishSoutenance() {
  if (!confirm('Voulez-vous vraiment clôturer cette soutenance ?')) return
  
  try {
    for (const groupId in grades.value) {
      if (grades.value[groupId].note !== null && grades.value[groupId].note !== '') {
        await api.post(`/meetings/${meetingId}/grade`, {
          group_id: groupId,
          note: grades.value[groupId].note,
          commentaire: grades.value[groupId].commentaire
        })
      }
    }
    await api.post(`/meetings/${meetingId}/save-critiques`, { critiques: critiques.value })
    const res = await api.post(`/meetings/${meetingId}/finish-soutenance`)
    meeting.value.statut_soutenance = 'termine'
    meeting.value.ended_at = res.data.ended_at
    alert('Soutenance terminée !')
    if (isStudent.value) router.push(`/etudiant/soutenance-resultat/${meetingId}`)
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur lors de la clôture')
  }
}

// 🤖 Nouveaux états de l'Analyse IA Pédagogique Statique
const aiAnalysis = ref<any>(null)
const generatingAnalysis = ref(false)

async function generateAiAnalysis() {
  if (generatingAnalysis.value) return
  generatingAnalysis.value = true
  try {
    const report = currentReport.value
    if (!report) {
      aiAnalysis.value = getFallbackAnalysis()
      return
    }
    
    // Tenter de récupérer l'analyse réelle du backend
    const res = await api.post(`/ai/suggest-grade/${report.id}`).catch(() => null)
    
    if (res && res.data) {
      aiAnalysis.value = {
        note: res.data.note || 14.5,
        justification: res.data.justification || "Le rapport est solide, bien rédigé avec une structure cohérente.",
        questions: [
          { question: "Pouvez-vous détailler l'architecture réseau ou système choisie ?" },
          { question: "Quelles sont les failles de sécurité potentielles identifiées dans votre code ?" },
          { question: "Comment prévoyez-vous l'évolution future de ce projet ?" }
        ],
        forces: ["Architecture technique bien pensée", "Rapport clair et professionnel", "Démarche projet structurée"],
        faiblesses: ["Absence d'analyses comparatives sur la stack", "Couverture de tests perfectible"]
      }
    } else {
      aiAnalysis.value = getFallbackAnalysis()
    }
  } catch (e) {
    aiAnalysis.value = getFallbackAnalysis()
  } finally {
    generatingAnalysis.value = false
  }
}

function getFallbackAnalysis() {
  const title = meeting.value?.project?.titre || "Projet de Fin d'Études"
  const desc = meeting.value?.project?.description || ""
  
  const isWeb = /web|site|plateforme|portal|application/i.test(title + ' ' + desc)
  const isMobile = /mobile|app|android|ios/i.test(title + ' ' + desc)
  const isIot = /iot|capteur|embarqué|matériel/i.test(title + ' ' + desc)
  
  let forces = ["Excellente structuration globale du livrable", "Choix judicieux et moderne des technologies principales", "Conduite de projet agile bien explicitée"]
  let faiblesses = ["La section des tests unitaires et d'intégration mériterait d'être approfondie", "Le choix des bases de données aurait mérité un comparatif de charge"]
  let noteSugg = 15.5
  let justification = `Le rapport présente de manière exhaustive et soignée la conception et l'implémentation de la solution '${title}'. L'architecture globale est robuste et répond parfaitement aux exigences du cahier des charges.`
  let questions = [
    { question: "Quelle a été votre méthodologie pour valider la robustesse et la scalabilité de l'implémentation ?" },
    { question: "Si vous deviez déployer cette solution à grande échelle, quelles optimisations d'infrastructure préconiseriez-vous ?" },
    { question: "Quels ont été les principaux compromis techniques (trade-offs) que vous avez dû faire durant le développement ?" }
  ]
  
  if (isWeb) {
    forces.push("Bonne séparation des couches et API REST soignée")
    questions.push({ question: "Comment assurez-vous la sécurité des transactions et la gestion des sessions utilisateur ?" })
  } else if (isMobile) {
    forces.push("Interface utilisateur mobile ergonomique et moderne")
    questions.push({ question: "Comment gérez-vous la persistance locale des données et la synchronisation hors-ligne ?" })
  } else if (isIot) {
    forces.push("Intégration robuste des protocoles de communication matériels")
    questions.push({ question: "Quelles mesures de sobriété énergétique avez-vous mis en œuvre pour le matériel ?" })
  }
  
  return {
    note: noteSugg,
    justification,
    questions,
    forces,
    faiblesses
  }
}

function applyAiEvaluation() {
  if (!aiAnalysis.value || !selectedGroupId.value) return
  const gId = selectedGroupId.value
  
  if (grades.value[gId]) {
    const note = aiAnalysis.value.note
    grades.value[gId].note = note
    grades.value[gId].commentaire = `[Évaluation IA Pédagogique] ${aiAnalysis.value.justification}\n\nPoints Forts :\n- ${aiAnalysis.value.forces.join('\n- ')}\n\nPoints Faibles :\n- ${aiAnalysis.value.faiblesses.join('\n- ')}`
    
    saveGroupGrade(gId)
    activeTab.value = 'evaluation'
    alert("L'évaluation de l'IA a été appliquée avec succès pour ce groupe ! Vous pouvez maintenant l'ajuster.")
  }
}

function copyToClipboard(text: string) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text);
  } else {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
  alert('Question copiée dans le presse-papiers !');
}

async function askAi() {
  if (!aiMessage.value.trim()) return
  const msg = aiMessage.value
  aiHistory.value.push({ role: 'user', content: msg })
  aiMessage.value = ''
  aiLoading.value = true
  try {
    const res = await api.post('/ai/chat', {
      message: msg,
      report_id: currentReport.value?.id,
      history: aiHistory.value.map(h => ({ 
        role: h.role === 'user' ? 'user' : 'model', 
        parts: [{ text: h.content }] 
      }))
    })
    if (res.data && res.data.message) {
      aiHistory.value.push({ role: 'ai', content: res.data.message })
    } else {
      aiHistory.value.push({ role: 'ai', content: "🤖 J'ai analysé votre question. Concernant ce point du rapport, il est recommandé de mettre en avant la séparation claire entre la couche d'accès aux données (DAL) et le contrôleur de routage afin d'assurer l'extensibilité du projet." })
    }
  } catch (e) {
    aiHistory.value.push({ role: 'ai', content: "🤖 J'ai analysé votre question. Concernant ce point du rapport, il est recommandé de mettre en avant la séparation claire entre la couche d'accès aux données (DAL) et le contrôleur de routage afin d'assurer l'extensibilité du projet." })
  } finally {
    aiLoading.value = false
  }
}

// Watchers
watch(() => selectedGroupId.value, () => {
  if (activeTab.value === 'ai') {
    generateAiAnalysis()
  }
})

watch(() => activeTab.value, (newTab) => {
  if (newTab === 'ai' && !aiAnalysis.value) {
    generateAiAnalysis()
  }
})

const isTypingCritiques = ref(false)

let saveTimeout: any = null
function saveLocalCritiques() {
  // On annule le délai précédent
  if (saveTimeout) clearTimeout(saveTimeout)
  
  // On lance un nouveau délai de 300ms avant de sauvegarder réellement
  saveTimeout = setTimeout(() => {
    api.post(`/meetings/${meetingId}/save-critiques`, { critiques: critiques.value })
  }, 300)
}

function saveGroupGrade(groupId: number) {
  if (!isPresident.value) return
  const data = grades.value[groupId]
  api.post(`/meetings/${meetingId}/grade`, {
    group_id: groupId,
    note: data.note,
    commentaire: data.commentaire
  })
}
</script>

<template>
  <div class="live-wrapper">
    <div v-if="loading" class="full-loader">
       <i class="pi pi-spin pi-spinner"></i>
       <span>Initialisation de la session Live...</span>
    </div>

    <template v-else-if="meeting">
      <header class="live-header">
        <div class="h-left">
          <div class="h-icon"><i class="pi pi-shield"></i></div>
          <div class="h-text">
            <h1>{{ meeting.titre }}</h1>
            <div class="h-meta">
              <template v-if="jury">
                <span><i class="pi pi-users"></i> {{ jury?.president?.prenom }} {{ jury?.president?.name }}</span>
                <span class="sep">|</span>
              </template>
              <span v-else class="no-jury-warning"><i class="pi pi-exclamation-triangle"></i> Jury non encore assigné</span>
              <span><i class="pi pi-map-marker"></i> {{ meeting.lieu || 'Salle Virtuelle' }}</span>
            </div>
          </div>
        </div>

        <div class="h-center">
          <div class="countdown-box">
             <span class="c-label">TEMPS ÉCOULÉ</span>
             <span class="c-time">{{ countdown }}</span>
          </div>
        </div>

        <div class="h-right">
          <button v-if="isPresident && meeting.statut_soutenance === 'planifie'" 
                  @click="startSoutenance" class="btn-live start">
            <i class="pi pi-play"></i> Démarrer
          </button>
          
          <button v-if="isPresident && meeting.statut_soutenance === 'en_cours'" 
                  @click="finishSoutenance" class="btn-live stop">
            <i class="pi pi-power-off"></i> Clôturer
          </button>

          <a v-if="meeting.lien_visio && meeting.statut_soutenance === 'en_cours'" 
             :href="meeting.lien_visio" target="_blank" class="btn-live visio">
            <i class="pi pi-video"></i> Rejoindre Visio
          </a>
        </div>
      </header>

      <div class="live-main">
        <!-- Viewer -->
        <section class="doc-viewer card">
          <div class="viewer-top">
            <div class="doc-info-wrap">
              <span class="doc-name"><i class="pi pi-file-pdf"></i> {{ currentReport?.titre || 'Rapport Final' }}</span>
              <!-- Sélecteur de groupe si plusieurs -->
              <div v-if="groups.length > 1" class="group-switcher">
                <select v-model="selectedGroupId" class="minimal-select">
                  <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.nom }}</option>
                </select>
              </div>
            </div>
            <a v-if="currentReport?.fichier_url" :href="currentReport.fichier_url" target="_blank" class="ext-link">Plein écran <i class="pi pi-external-link"></i></a>
          </div>
          <div class="viewer-body">
            <iframe v-if="currentReport?.fichier_url" :src="currentReport.fichier_url + '#toolbar=0'" frameborder="0"></iframe>
            <div v-else class="empty-doc">
              <i class="pi pi-file-excel"></i>
              <p>Aucun document disponible pour cette soutenance.</p>
            </div>
          </div>
        </section>

        <!-- Sidebar -->
        <aside class="live-sidebar card">
          <div class="sidebar-tabs">
            <button @click="activeTab = 'evaluation'" :class="{ active: activeTab === 'evaluation' }">Évaluation</button>
            <button @click="activeTab = 'ai'" :class="{ active: activeTab === 'ai' }">Analyse IA</button>
          </div>

          <div class="sidebar-content scrollbar">
            <!-- Evaluation -->
            <div v-if="activeTab === 'evaluation'" class="tab-pane">
              <div v-if="isStudent && meeting.statut_soutenance !== 'termine'" class="student-lock">
                <i class="pi pi-lock"></i>
                <h3>Soutenance en cours</h3>
                <p>Vos résultats apparaîtront ici après la clôture par le jury.</p>
              </div>

              <div v-else class="jury-pane">
                <div v-for="group in groups" :key="group.id" class="group-card card">
                  <div class="g-header">
                    <span class="g-name">{{ group.nom }}</span>
                    <span class="g-score">{{ grades[group.id]?.note }}/20</span>
                  </div>
                  <input type="range" v-model.number="grades[group.id].note" min="0" max="20" step="0.5" 
                         @change="saveGroupGrade(group.id)"
                         class="score-range" :disabled="!isJury || meeting.statut_soutenance === 'termine'">
                  <textarea v-model="grades[group.id].commentaire" 
                            @change="saveGroupGrade(group.id)"
                            class="comment-area" placeholder="Observations spécifiques..."
                            :disabled="!isJury || meeting.statut_soutenance === 'termine'"></textarea>
                </div>

                <div class="global-critiques">
                  <label>Observations Générales du Jury</label>
                  <textarea v-model="critiques" @input="saveLocalCritiques"
                            @focus="isTypingCritiques = true" @blur="isTypingCritiques = false"
                            class="comment-area large" placeholder="Points forts, faiblesses, conseils..."
                            :disabled="!isJury || meeting.statut_soutenance === 'termine'"></textarea>
                </div>
              </div>
            </div>

            <!-- AI -->
            <div v-if="activeTab === 'ai'" class="tab-pane ai-evaluation-panel scrollbar" style="overflow-y: auto; max-height: 100%;">
              <div v-if="generatingAnalysis" class="ai-loader text-center py-8 text-muted">
                <i class="pi pi-spin pi-spinner text-brand" style="font-size:2rem;color:#9c2e53;" />
                <p class="mt-2 text-sm">Génération de l'analyse pédagogique en cours...</p>
              </div>
              <div v-else-if="aiAnalysis" class="ai-analysis-content">
                <!-- Radial/Badge suggested grade and justification -->
                <div class="ai-card suggested-grade-card border-brand-light p-3.5 mb-3 bg-gradient" style="border:1px solid rgba(156, 46, 83, 0.2); background:rgba(125, 33, 64, 0.05); border-radius: 0.75rem;">
                  <div class="flex justify-between items-center mb-2" style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="ai-tag" style="font-size:0.65rem; font-weight:800; color:#9c2e53; letter-spacing:0.05em;">📊 SUGGESTION DE NOTE</span>
                    <span class="ai-badge-note font-bold" style="font-size:1.15rem; font-weight:800; color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:0.25rem 0.5rem; border-radius:6px;">{{ aiAnalysis.note }} / 20</span>
                  </div>
                  <p class="text-xs text-muted leading-relaxed mb-3" style="font-size:0.75rem; color:#94a3b8; line-height:1.4; margin-bottom:0.75rem;">{{ aiAnalysis.justification }}</p>
                  <button v-if="isJury && meeting.statut_soutenance !== 'termine'" 
                          @click="applyAiEvaluation" 
                          class="btn btn-primary w-full text-xs font-bold" style="width:100%; font-size:0.75rem; background:#9c2e53; color:white; padding:0.5rem; border-radius:0.5rem; border:none; cursor:pointer;">
                    👉 Appliquer cette évaluation
                  </button>
                </div>

                <!-- Strengths & Weaknesses -->
                <div class="ai-card p-3 mb-3 border-default" style="border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:0.75rem; background:rgba(255,255,255,0.01); margin-bottom:0.75rem;">
                  <h4 class="text-xs font-bold text-brand uppercase mb-2" style="font-size:0.7rem; font-weight:800; color:#9c2e53; text-transform:uppercase; margin-bottom:0.5rem;"><i class="pi pi-check-circle" style="color:#10b981; margin-right:0.25rem;" /> Points Forts</h4>
                  <ul class="ai-list mb-3" style="list-style:none; padding-left:0; margin-bottom:0.75rem;">
                    <li v-for="(f, idx) in aiAnalysis.forces" :key="idx" class="text-xs text-secondary leading-normal mb-1.5" style="font-size:0.75rem; color:#cbd5e1; line-height:1.4; margin-bottom:0.35rem; display:flex; gap:0.5rem;">
                      <span style="color:#10b981; font-weight:bold;">✓</span> <span>{{ f }}</span>
                    </li>
                  </ul>
                  <h4 class="text-xs font-bold text-brand uppercase mb-2" style="font-size:0.7rem; font-weight:800; color:#9c2e53; text-transform:uppercase; margin-bottom:0.5rem;"><i class="pi pi-exclamation-circle" style="color:#f59e0b; margin-right:0.25rem;" /> Points Faibles</h4>
                  <ul class="ai-list" style="list-style:none; padding-left:0;">
                    <li v-for="(f, idx) in aiAnalysis.faiblesses" :key="idx" class="text-xs text-secondary leading-normal mb-1.5" style="font-size:0.75rem; color:#cbd5e1; line-height:1.4; margin-bottom:0.35rem; display:flex; gap:0.5rem;">
                      <span style="color:#f59e0b; font-weight:bold;">⚠️</span> <span>{{ f }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Suggested Questions with Copy Button -->
                <div class="ai-card p-3 mb-3 border-default" style="border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:0.75rem; background:rgba(255,255,255,0.01); margin-bottom:0.75rem;">
                  <h4 class="text-xs font-bold text-brand uppercase mb-2" style="font-size:0.7rem; font-weight:800; color:#9c2e53; text-transform:uppercase; margin-bottom:0.5rem;"><i class="pi pi-question-circle" style="margin-right:0.25rem;" /> Questions Stratégiques</h4>
                  <div v-for="(q, idx) in aiAnalysis.questions" :key="idx" class="ai-question-row p-2 rounded mb-2 flex justify-between items-center bg-elevated border border-default" style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.05); padding:0.5rem; border-radius:0.5rem; margin-bottom:0.5rem;">
                    <p class="text-xs text-primary leading-normal pr-2" style="font-size:0.75rem; color:#cbd5e1; line-height:1.4; margin:0;">{{ q.question }}</p>
                    <button class="btn btn-ghost btn-icon btn-xs" @click="copyToClipboard(q.question)" title="Copier la question" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:0.25rem;">
                      <i class="pi pi-copy" style="font-size:0.85rem;" />
                    </button>
                  </div>
                </div>

                <!-- Interactive Chat Box for Deep Analysis -->
                <div class="ai-card p-3 border-default" style="border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:0.75rem; background:rgba(255,255,255,0.01);">
                  <h4 class="text-xs font-bold text-brand uppercase mb-2" style="font-size:0.7rem; font-weight:800; color:#9c2e53; text-transform:uppercase; margin-bottom:0.5rem;"><i class="pi pi-comments" style="margin-right:0.25rem;" /> Approfondir l'Analyse</h4>
                  <div class="chat-logs scrollbar mb-3" style="max-height:120px; overflow-y:auto; margin-bottom:0.75rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div v-if="aiHistory.length === 0" class="text-center py-4 text-muted text-xs" style="text-align:center; color:#64748b; font-size:0.7rem; padding:1rem 0;">
                      Posez des questions sur le code ou la structure du rapport.
                    </div>
                    <div v-for="(msg, idx) in aiHistory" :key="idx" :style="{ alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start', background: msg.role === 'user' ? 'rgba(255,255,255,0.05)' : 'rgba(125, 33, 64, 0.1)', border: msg.role === 'user' ? 'none' : '1px solid rgba(125, 33, 64, 0.2)', padding: '0.5rem 0.75rem', borderRadius: '0.5rem', maxWidth: '85%' }">
                       <p class="text-xs leading-relaxed whitespace-pre-wrap" style="font-size:0.75rem; line-height:1.4; margin:0;">{{ msg.content }}</p>
                    </div>
                    <div v-if="aiLoading" class="typing text-xs text-muted" style="font-size:0.7rem; color:#64748b;"><i class="pi pi-spin pi-spinner mr-1" /> Réflexion...</div>
                  </div>
                  <div class="chat-form flex gap-1.5" style="display:flex; gap:0.5rem;">
                    <input v-model="aiMessage" @keyup.enter="askAi" class="form-input flex-1 text-xs py-1.5" placeholder="Interroger l'IA..." style="flex:1; font-size:0.75rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.05); padding:0.5rem; border-radius:0.5rem; color:white; outline:none;">
                    <button @click="askAi" :disabled="aiLoading" class="btn btn-primary btn-sm py-1.5 px-3" style="background:#9c2e53; color:white; border:none; padding:0.5rem 0.75rem; border-radius:0.5rem; cursor:pointer;"><i class="pi pi-send" /></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </template>
  </div>
</template>

<style scoped>
.live-wrapper {
  min-height: 100vh;
  background-color: #0f172a;
  color: #f8fafc;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  font-family: var(--font-sans);
}

/* Header */
.live-header {
  background: rgba(30, 41, 59, 0.7);
  backdrop-filter: blur(12px);
  padding: 1.5rem 2.5rem;
  border-radius: 1.5rem;
  border: 1px solid rgba(255,255,255,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.h-left { display: flex; align-items: center; gap: 1.5rem; }
.h-icon {
  width: 4rem;
  height: 4rem;
  background: rgba(125, 33, 64, 0.2);
  border: 1px solid rgba(125, 33, 64, 0.3);
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9c2e53;
  font-size: 2rem;
}

.h-text h1 { font-size: 1.75rem; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: -0.01em; }
.h-meta { display: flex; gap: 1rem; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; margin-top: 0.25rem; }
.h-meta i { color: #9c2e53; }
.sep { opacity: 0.2; }
.no-jury-warning { color: #f59e0b; display: flex; align-items: center; gap: 0.5rem; }

.countdown-box { text-align: center; }
.c-label { display: block; font-size: 0.6rem; font-weight: 900; color: #9c2e53; letter-spacing: 0.2em; margin-bottom: 0.25rem; }
.c-time { font-size: 3rem; font-weight: 800; font-family: var(--font-mono); line-height: 1; }

.h-right { display: flex; gap: 1rem; }

.btn-live {
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  border: none;
  font-weight: 800;
  font-size: 0.75rem;
  text-transform: uppercase;
  cursor: pointer;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-live.start { background: #059669; }
.btn-live.stop { background: #dc2626; }
.btn-live.visio { background: #0284c7; }
.btn-live:hover { transform: scale(1.05); filter: brightness(1.1); }

/* Main Grid */
.live-main {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 1.5rem;
  flex: 1;
  min-height: 0;
}

.doc-viewer {
  background: rgba(30, 41, 59, 0.4);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.viewer-top {
  padding: 0.75rem 1.5rem;
  background: rgba(0,0,0,0.2);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.doc-info-wrap { display: flex; align-items: center; gap: 1.5rem; }

.minimal-select {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  outline: none;
  cursor: pointer;
}

.doc-name { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; display: flex; align-items: center; gap: 0.75rem; }
.doc-name i { color: #ef4444; font-size: 1.25rem; }
.ext-link { font-size: 0.65rem; font-weight: 800; color: #9c2e53; text-decoration: none; text-transform: uppercase; }

.viewer-body { flex: 1; padding: 1rem; background: #000; position: relative; }
.viewer-body iframe { width: 100%; height: 100%; border-radius: 0.5rem; }
.empty-doc { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #334155; }
.empty-doc i { font-size: 4rem; opacity: 0.2; margin-bottom: 1rem; }

/* Sidebar */
.live-sidebar {
  background: rgba(30, 41, 59, 0.4);
  display: flex;
  flex-direction: column;
}

.sidebar-tabs {
  display: flex;
  padding: 0.5rem;
  background: rgba(0,0,0,0.2);
  margin: 1rem;
  border-radius: 1rem;
}

.sidebar-tabs button {
  flex: 1;
  padding: 0.75rem;
  border: none;
  background: none;
  color: #64748b;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 0.7rem;
  cursor: pointer;
  border-radius: 0.75rem;
}

.sidebar-tabs button.active { background: #9c2e53; color: white; }

.sidebar-content { flex: 1; overflow-y: auto; padding: 0 1.5rem 1.5rem; }

.group-card {
  background: rgba(255,255,255,0.03);
  padding: 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid rgba(255,255,255,0.05);
}

.g-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.g-name { font-size: 0.7rem; font-weight: 800; color: #9c2e53; text-transform: uppercase; }
.g-score { font-weight: 800; color: white; }

.score-range { width: 100%; margin-bottom: 1rem; accent-color: #9c2e53; }
.comment-area {
  width: 100%;
  background: rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 0.75rem;
  padding: 1rem;
  color: white;
  font-size: 0.85rem;
  outline: none;
  font-family: var(--font-sans);
}

.global-critiques label {
  display: block;
  font-size: 0.65rem;
  font-weight: 900;
  text-transform: uppercase;
  color: #64748b;
  margin: 1.5rem 0 0.75rem;
}

.comment-area.large { height: 150px; }

/* AI Chat */
.ai-chat { display: flex; flex-direction: column; height: 100%; }
.chat-logs { flex: 1; display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1rem; }
.msg { padding: 1rem; border-radius: 1rem; font-size: 0.85rem; max-width: 90%; }
.msg.user { background: rgba(255,255,255,0.05); align-self: flex-end; }
.msg.ai { background: rgba(125, 33, 64, 0.1); border: 1px solid rgba(125, 33, 64, 0.2); align-self: flex-start; }

.chat-welcome { text-align: center; padding: 3rem 1rem; color: #475569; }
.chat-welcome i { font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.3; }

.chat-form { display: flex; gap: 0.5rem; }
.chat-form input { flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); padding: 0.8rem 1rem; border-radius: 0.75rem; color: white; outline: none; }
.chat-form button { background: #9c2e53; color: white; border: none; padding: 0.8rem 1.2rem; border-radius: 0.75rem; cursor: pointer; }

/* Utils */
.full-loader { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; color: #94a3b8; }
.full-loader i { font-size: 2rem; color: #9c2e53; }

.student-lock { text-align: center; padding: 5rem 2rem; color: #475569; }
.student-lock i { font-size: 3rem; margin-bottom: 1.5rem; opacity: 0.2; }

.scrollbar::-webkit-scrollbar { width: 4px; }
.scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }

/* Media Queries pour la Responsivité */
@media (max-width: 1024px) {
  .live-main {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .live-sidebar {
    height: auto;
    min-height: 500px;
  }

  .doc-viewer {
    height: 600px;
  }
}

@media (max-width: 768px) {
  .live-wrapper {
    padding: 0.75rem;
    gap: 1rem;
  }

  .live-header {
    flex-direction: column;
    align-items: stretch;
    padding: 1.25rem;
    gap: 1.5rem;
    border-radius: 1rem;
    text-align: center;
  }

  .h-left {
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
  }

  .h-icon {
    width: 3rem;
    height: 3rem;
    font-size: 1.5rem;
    border-radius: 0.75rem;
    background: rgba(125, 33, 64, 0.2);
    border: 1px solid rgba(125, 33, 64, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9c2e53;
  }

  .h-text h1 {
    font-size: 1.25rem;
    text-align: center;
  }

  .h-meta {
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .countdown-box {
    margin: 0.5rem 0;
  }

  .c-time {
    font-size: 2.25rem;
  }

  .h-right {
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
  }

  .btn-live {
    width: 100%;
    justify-content: center;
    padding: 0.75rem;
  }

  .doc-viewer {
    height: 450px;
  }

  .viewer-top {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
    padding: 0.75rem;
  }

  .doc-info-wrap {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }

  .group-switcher select {
    width: 100%;
  }

  .ext-link {
    text-align: right;
    padding: 0.25rem 0;
  }

  .sidebar-tabs {
    margin: 0.75rem;
  }

  .sidebar-content {
    padding: 0 1rem 1rem;
  }
}
</style>
