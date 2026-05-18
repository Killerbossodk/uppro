<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const reports = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const selectedReport = ref<any>(null)
const selectedReportContent = ref('')

async function fetchReports() {
  loading.value = true
  try {
    const res = await api.get('/rup-reports')
    reports.value = Array.isArray(res.data.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
  } catch (e) {
    console.error('Erreur chargement rapports:', e)
  } finally {
    loading.value = false
  }
}

function openModal(report: any) {
  selectedReport.value = report
  let data = report.data
  if (typeof data === 'string') {
    try { data = JSON.parse(data) } catch { selectedReportContent.value = data; showModal.value = true; return }
  }
  selectedReportContent.value = data?.report || data?.content || 'Aucun contenu'
  showModal.value = true
}

async function deleteNotification(id: number) {
  if (!confirm('Supprimer cette notification ?')) return
  try {
    await api.delete(`/notifications/${id}`)
    await fetchReports()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function deleteAllNotifications() {
  if (!confirm('Supprimer TOUTES les notifications ?')) return
  try {
    await api.delete('/notifications')
    await fetchReports()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function getField(report: any, field: string) {
  let data = report.data
  if (typeof data === 'string') {
    try { data = JSON.parse(data) } catch { return '' }
  }
  return data?.[field] || ''
}

function formatDate(date: string) {
  return new Date(date).toLocaleString('fr-FR')
}

onMounted(fetchReports)
</script>

<template>
  <div class="reports-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Rapports de suivi</h2>
        <p class="text-muted text-sm">Rapports envoyés par les professeurs</p>
      </div>
      <button v-if="reports.length > 0" class="btn btn-danger btn-sm" @click="deleteAllNotifications">🗑️ Tout supprimer</button>
    </div>

    <div class="card">
      <div v-if="loading" class="text-center py-4">Chargement...</div>
      <div v-else-if="reports.length === 0" class="text-muted text-center py-4">Aucun rapport</div>
      <div v-else class="divide-y">
        <div v-for="r in reports" :key="r.id" class="p-4 hover:bg-hover flex justify-between items-start">
          <div>
            <p class="font-medium">{{ getField(r, 'project') }} – Groupe {{ getField(r, 'group_name') }}</p>
            <p class="text-sm text-muted">Envoyé par {{ getField(r, 'professor') }} le {{ formatDate(r.created_at) }}</p>
          </div>
          <div class="flex gap-2">
            <button class="btn btn-primary btn-sm" @click="openModal(r)">👁️ Voir</button>
            <button class="btn btn-ghost btn-icon btn-sm text-danger" @click="deleteNotification(r.id)">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card card">
        <h3>Rapport de suivi</h3>
        <div class="p-4 bg-elevated rounded mt-3 whitespace-pre-wrap text-sm">{{ selectedReportContent }}</div>
        <p class="text-xs text-muted mt-2">Reçu le {{ formatDate(selectedReport?.created_at) }}</p>
        <div class="flex justify-end mt-4">
          <button class="btn btn-secondary btn-sm" @click="showModal = false">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.reports-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1100px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 700px; }
</style>