<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const meetings = ref<any[]>([])
const grades = ref<any[]>([])

onMounted(async () => {
  await fetchData()
  loading.value = false
})

async function fetchData() {
  try {
    const meetRes = await api.get('/meetings')
    meetings.value = Array.isArray(meetRes.data) 
      ? meetRes.data.filter((m: any) => m.type === 'soutenance') 
      : []

    const allGrades: any[] = []
    for (const m of meetings.value) {
      try {
        const gradeRes = await api.get(`/meetings/${m.id}/grade`)
        if (gradeRes.data && !gradeRes.data.message) {
          allGrades.push({ ...gradeRes.data, meeting: m })
        }
      } catch {}
    }
    grades.value = allGrades
  } catch {}
}

async function validateGrade(gradeId: number) {
  try {
    await api.post(`/grades/${gradeId}/validate`)
    await fetchData()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function publishGrade(gradeId: number) {
  try {
    await api.post(`/grades/${gradeId}/publish`)
    await fetchData()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

// Saisir une note
const showGradeModal = ref(false)
const selectedMeeting = ref<any>(null)
const gradeForm = ref({ note: 10, commentaire: '' })

function openGradeModal(meeting: any) {
  selectedMeeting.value = meeting
  const existing = grades.value.find(g => g.meeting_id === meeting.id)
  gradeForm.value = { 
    note: existing?.note || 10, 
    commentaire: existing?.commentaire || '' 
  }
  showGradeModal.value = true
}

async function saveGrade() {
  if (!selectedMeeting.value) return
  try {
    await api.post(`/meetings/${selectedMeeting.value.id}/grade`, gradeForm.value)
    showGradeModal.value = false
    await fetchData()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}
</script>

<template>
  <div class="grades-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Gestion des Notes</h2>
        <p class="text-muted text-sm">{{ grades.length }} note(s)</p>
      </div>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <!-- Soutenances sans note → Saisir -->
    <div v-if="meetings.length > 0" class="card mb-4">
      <h3 class="section-title"><i class="pi pi-pencil" /> Soutenances à noter</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr><th>Soutenance</th><th>Projet</th><th>Date</th><th>Action</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in meetings" :key="m.id">
              <td class="font-medium">{{ m.titre }}</td>
              <td>{{ m.project?.titre }}</td>
              <td>{{ new Date(m.date_heure).toLocaleDateString('fr-FR') }}</td>
              <td>
                <button class="btn btn-primary btn-sm" @click="openGradeModal(m)">
                  {{ grades.find(g => g.meeting_id === m.id) ? '✏️ Modifier' : '📝 Noter' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Notes existantes -->
    <div class="card">
      <h3 class="section-title"><i class="pi pi-chart-bar" /> Notes saisies</h3>
      <div v-if="grades.length === 0" class="text-muted text-sm text-center py-4">Aucune note</div>
      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr><th>Soutenance</th><th>Note</th><th>Commentaire</th><th>Validée</th><th>Publiée</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="g in grades" :key="g.id">
              <td class="font-medium">{{ g.meeting?.titre }}</td>
              <td class="font-bold text-lg">{{ g.note }}/20</td>
              <td class="text-sm text-muted">{{ g.commentaire || '-' }}</td>
              <td><span :class="`badge ${g.valide_par_rup ? 'badge-success' : 'badge-warning'}`">{{ g.valide_par_rup ? 'Oui' : 'Non' }}</span></td>
              <td><span :class="`badge ${g.publiee ? 'badge-success' : 'badge-info'}`">{{ g.publiee ? 'Oui' : 'Non' }}</span></td>
              <td>
                <div class="flex gap-1">
                  <button v-if="!g.valide_par_rup" class="btn btn-success btn-sm" @click="validateGrade(g.id)">✅ Valider</button>
                  <button v-if="g.valide_par_rup && !g.publiee" class="btn btn-primary btn-sm" @click="publishGrade(g.id)">📢 Publier</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal saisie note -->
    <div v-if="showGradeModal" class="modal-overlay" @click.self="showGradeModal = false">
      <div class="modal-card card">
        <h3 class="mb-3">{{ selectedMeeting?.titre }}</h3>
        <div class="form-group mb-3">
          <label class="form-label">Note /20</label>
          <input v-model.number="gradeForm.note" type="range" min="0" max="20" step="0.5" class="w-full" />
          <div class="text-center font-bold text-2xl mt-2" style="color:var(--color-primary)">{{ gradeForm.note }}/20</div>
        </div>
        <div class="form-group mb-3">
          <label class="form-label">Commentaire</label>
          <textarea v-model="gradeForm.commentaire" class="form-input" rows="3"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button class="btn btn-secondary btn-sm" @click="showGradeModal = false">Annuler</button>
          <button class="btn btn-primary btn-sm" @click="saveGrade">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.grades-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; margin-bottom: 1rem; }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-default); font-size: var(--text-sm); }
.data-table th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 11px; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 450px; }
</style>