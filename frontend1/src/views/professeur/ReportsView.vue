<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'

const auth = useAuthStore()
const loading = ref(true)
const reports = ref<any[]>([])
const groups = ref<any[]>([])

// Modal Rapport RUP
const showRupModal = ref(false)
const selectedGroup = ref<any>(null)
const reportGenerated = ref(false)
const reportGeneratedByIA = ref(false)
const loadingRupReport = ref(false)
const sendingRupReport = ref(false)
const rupReportContent = ref('')

// ✅ Propriété calculée pour les droits de validation
const canValidateReport = computed(() => {
  const role = auth.user?.role
  return role === 'professeur' || role === 'rup_specialite' || role === 'rup_projet'
})

onMounted(async () => {
  await Promise.all([fetchReports(), fetchGroups()])
})

async function fetchReports() {
  loading.value = true
  try {
    const res = await api.get('/reports')
    reports.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement rapports:', e)
  } finally {
    loading.value = false
  }
}

async function fetchGroups() {
  try {
    const res = await api.get('/groups')
    groups.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}

function openRupModal(group: any) {
  selectedGroup.value = group
  reportGenerated.value = false
  reportGeneratedByIA.value = false
  rupReportContent.value = ''
  loadingRupReport.value = false
  showRupModal.value = true
}

async function generateWithIA() {
  if (!selectedGroup.value) return
  reportGeneratedByIA.value = true
  loadingRupReport.value = true
  try {
    const res = await api.post(`/groups/${selectedGroup.value.id}/report-to-rup`)
    rupReportContent.value = res.data.report || res.data
    reportGenerated.value = true
  } catch (e: any) {
    alert('Erreur IA : ' + (e.response?.data?.message || e.message))
  } finally {
    loadingRupReport.value = false
  }
}

function startManualReport() {
  reportGeneratedByIA.value = false
  rupReportContent.value = ''
  reportGenerated.value = true
}

function resetRupReport() {
  reportGenerated.value = false
  rupReportContent.value = ''
  reportGeneratedByIA.value = false
}

async function sendRupReport() {
  if (!selectedGroup.value || !rupReportContent.value.trim()) {
    alert('Le rapport ne peut pas être vide.')
    return
  }
  sendingRupReport.value = true
  try {
    await api.post(`/groups/${selectedGroup.value.id}/save-rup-report`, {
      report: rupReportContent.value
    })
    showRupModal.value = false
    alert('✅ Rapport envoyé au RUP avec succès !')
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  } finally {
    sendingRupReport.value = false
  }
}

async function validerRapport(id: number) {
  try {
    await api.post(`/reports/${id}/validate`)
    await fetchReports()
    alert('✅ Rapport validé avec succès')
  } catch (error: any) {
    alert('Erreur : ' + (error.response?.data?.message || error.message))
  }
}

async function rejeterRapport(id: number) {
  const feedback = prompt('Motif du rejet / feedback :')
  if (feedback) {
    try {
      await api.post(`/reports/${id}/reject`, { feedback_ia: feedback })
      await fetchReports()
      alert('❌ Rapport rejeté')
    } catch (error: any) {
      alert('Erreur : ' + (error.response?.data?.message || error.message))
    }
  }
}

// ✅ URL relative (le proxy Vite gère la redirection)
function getFileUrl(url: string) {
  if (!url) return '#'
  return url
}

function formatDate(date: string) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}
</script>

<template>
  <div class="reports-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Rapports</h2>
        <p class="text-muted text-sm">
          {{ reports.filter(r => r.statut === 'soumis').length }} en attente de validation
        </p>
      </div>
    </div>

    <!-- Groupes (pour envoyer rapport RUP) -->
    <div class="card mb-4">
      <h3 class="section-title"><i class="pi pi-send" /> Envoyer un rapport au RUP</h3>
      <p class="text-muted text-sm mb-3">Sélectionnez un groupe pour générer ou rédiger un rapport de suivi à envoyer au RUP.</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="g in groups"
          :key="g.id"
          class="btn btn-outline-primary btn-sm"
          @click="openRupModal(g)"
        >
          📋 {{ g.nom }} ({{ g.project?.titre }})
        </button>
      </div>
    </div>

    <!-- Liste des rapports étudiants -->
    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="reports.length === 0" class="card text-center py-8">
      <p class="text-muted">Aucun rapport déposé</p>
    </div>

    <div v-else class="reports-list">
      <div v-for="r in reports" :key="r.id" class="card report-card">
        <div class="flex items-start gap-4">
          <i class="pi pi-file-pdf" style="font-size:2rem;color:#ef4444" />
          <div class="flex-1">
            <h3 class="font-bold">{{ r.titre }}</h3>
            <p class="text-muted text-sm">Groupe : {{ r.group?.nom }} • v{{ r.version }}</p>
            <p class="text-muted text-sm">Par : {{ r.deposant?.prenom }} {{ r.deposant?.name }}</p>
            <span :class="`badge ${r.statut === 'valide' ? 'badge-success' : r.statut === 'rejete' ? 'badge-danger' : 'badge-warning'}`">
              {{ r.statut }}
            </span>
          </div>
          <div class="flex flex-col gap-2">
            <a v-if="r.fichier_url" :href="getFileUrl(r.fichier_url)" target="_blank" class="btn btn-secondary btn-sm">
              <i class="pi pi-download" /> Télécharger
            </a>
            <RouterLink :to="`/rapports/${r.id}`" class="btn btn-ghost btn-sm">
               Voir
            </RouterLink>

            <!-- ✅ AJOUT DES BOUTONS VALIDER / REJETER -->
            <div v-if="r.statut === 'soumis' && canValidateReport" class="flex gap-1 mt-2">
              <button @click="validerRapport(r.id)" class="btn btn-success btn-sm flex-1">
                <i class="pi pi-check" /> Valider
              </button>
              <button @click="rejeterRapport(r.id)" class="btn btn-danger btn-sm flex-1">
                <i class="pi pi-times" /> Rejeter
              </button>
            </div>          </div>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL RAPPORT RUP ==================== -->
    <div v-if="showRupModal" class="modal-overlay" @click.self="showRupModal = false">
      <div class="modal-card card" style="max-width:800px;max-height:90vh;overflow-y:auto;">
        <h3 class="mb-2">📋 Rapport pour le RUP</h3>
        <p class="text-muted text-sm mb-4">
          Groupe : <strong>{{ selectedGroup?.nom }}</strong> — Projet : {{ selectedGroup?.project?.titre }}
        </p>

        <!-- ÉTAPE 1 : Choix du mode -->
        <div v-if="!reportGenerated && !loadingRupReport">
          <p class="text-muted mb-4">Comment souhaitez-vous générer le rapport ?</p>
          <div class="grid-2 mb-4">
            <button @click="generateWithIA" class="card text-center p-4 hover:border-primary cursor-pointer">
              <div class="text-3xl mb-2">🤖</div>
              <div class="font-bold text-primary">Générer avec l'IA</div>
              <div class="text-xs text-muted mt-1">L'IA analyse les données du groupe et propose un rapport structuré que vous pourrez modifier.</div>
            </button>
            <button @click="startManualReport" class="card text-center p-4 hover:border-primary cursor-pointer">
              <div class="text-3xl mb-2">✍️</div>
              <div class="font-bold">Rédiger manuellement</div>
              <div class="text-xs text-muted mt-1">Vous rédigez vous-même le rapport de A à Z.</div>
            </button>
          </div>
          <div class="flex justify-end">
            <button class="btn btn-secondary btn-sm" @click="showRupModal = false">Annuler</button>
          </div>
        </div>

        <!-- ÉTAPE 2a : Chargement IA -->
        <div v-else-if="loadingRupReport" class="text-center py-8">
          <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
          <p class="text-muted mt-2">L'IA génère le rapport...</p>
        </div>

        <!-- ÉTAPE 2b : Rapport éditable -->
        <div v-else>
          <p class="text-sm text-muted mb-2">
            {{ reportGeneratedByIA ? "Vous pouvez modifier le rapport généré par l'IA avant de l'envoyer." : 'Rédigez votre rapport de suivi.' }}
          </p>
          <textarea
            v-model="rupReportContent"
            rows="15"
            class="form-input mb-4"
            style="font-family:monospace;font-size:var(--text-sm);"
            placeholder="Saisissez le rapport ici..."
          ></textarea>
          <div class="flex justify-end gap-2">
            <button class="btn btn-secondary btn-sm" @click="resetRupReport">↩️ Revenir au choix</button>
            <button class="btn btn-primary btn-sm" @click="sendRupReport" :disabled="sendingRupReport || !rupReportContent.trim()">
              {{ sendingRupReport ? 'Envoi...' : '📤 Envoyer au RUP' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.reports-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1100px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; margin-bottom: 0.5rem; }
.reports-list { display: flex; flex-direction: column; gap: 1rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.btn-outline-primary { background: transparent; color: var(--color-primary); border: 1px solid var(--color-primary); }
.btn-outline-primary:hover { background: var(--color-primary-subtle); }
</style>