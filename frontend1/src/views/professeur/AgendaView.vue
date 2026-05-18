<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const meetings = ref<any[]>([])
const groups = ref<any[]>([])
const projects = ref<any[]>([])
const showCreateModal = ref(false)
const newMeeting = ref({
  project_id: null as number | null,
  titre: '',
  ordre_du_jour: '',
  date_heure: '',
  lieu: '',
  type: 'rdv_pilotage',
  lien_visio: '',
  group_ids: [] as number[]
})

const filteredGroups = computed(() => {
  if (!newMeeting.value.project_id) return []
  return groups.value.filter((g: any) => Number(g.project_id) === Number(newMeeting.value.project_id))
})

watch(() => newMeeting.value.project_id, () => {
  newMeeting.value.group_ids = []
})

onMounted(async () => {
  await Promise.all([fetchMeetings(), fetchGroups(), fetchProjects()])
})

async function fetchMeetings() {
  loading.value = true
  try {
    const res = await api.get('/meetings')
    meetings.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur meetings:', e)
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

async function fetchProjects() {
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data.filter((p: any) => p.statut === 'valide') : []
  } catch {}
}

async function createMeeting() {
  try {
    await api.post('/meetings', newMeeting.value)
    showCreateModal.value = false
    newMeeting.value = { project_id: null, titre: '', ordre_du_jour: '', date_heure: '', lieu: '', type: 'rdv_pilotage', lien_visio: '', group_ids: [] }
    await fetchMeetings()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function formatDate(date: string) {
  return new Date(date).toLocaleString('fr-FR')
}
</script>

<template>
  <div class="agenda-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Agenda & RDV</h2>
        <p class="text-muted text-sm">{{ meetings.length }} rendez-vous</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="pi pi-plus" /> Nouveau RDV
      </button>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="meetings.length === 0" class="card text-center py-8">
      <p class="text-muted">Aucun rendez-vous</p>
    </div>

    <div v-else class="meetings-list">
      <div v-for="m in meetings" :key="m.id" class="card meeting-card">
        <div class="flex gap-4">
          <div class="meeting-date">
            <span class="meeting-day">{{ new Date(m.date_heure).getDate() }}</span>
            <span class="meeting-month">{{ new Date(m.date_heure).toLocaleDateString('fr-FR', { month: 'short' }) }}</span>
          </div>
          <div class="flex-1">
            <h3 class="font-bold">{{ m.titre }}</h3>
            <p class="text-muted text-sm">{{ formatDate(m.date_heure) }}</p>
            <p class="text-muted text-sm" v-if="m.lieu">{{ m.lieu }}</p>
            <div class="flex justify-between items-center mt-2">
              <span :class="`badge ${m.type === 'soutenance' ? 'badge-warning' : 'badge-info'}`">{{ m.type }}</span>
              <router-link v-if="m.type === 'soutenance'" :to="`/soutenance-live/${m.id}`" class="btn btn-primary btn-xs">
                <i class="pi pi-external-link mr-1"></i> Rejoindre Live
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal création RDV -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-card card">
        <h3 class="mb-3">Nouveau RDV</h3>
        <form @submit.prevent="createMeeting">
          <div class="form-group mb-2">
            <label class="form-label">Projet</label>
            <select v-model="newMeeting.project_id" class="form-input" required>
              <option :value="null">Sélectionner...</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.titre }}</option>
            </select>
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Titre</label>
            <input v-model="newMeeting.titre" type="text" class="form-input" required />
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Date et heure</label>
            <input v-model="newMeeting.date_heure" type="datetime-local" class="form-input" required />
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Lieu</label>
            <input v-model="newMeeting.lieu" type="text" class="form-input" />
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Type</label>
            <select v-model="newMeeting.type" class="form-input">
              <option value="rdv_pilotage">RDV Pilotage</option>
              <option value="soutenance">Soutenance</option>
              <option value="depot_rapport">Dépôt rapport</option>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="form-label">Groupes (du projet)</label>
            <div v-if="!newMeeting.project_id" class="text-xs text-muted">Veuillez d'abord sélectionner un projet.</div>
            <div v-else-if="filteredGroups.length === 0" class="text-xs text-muted">Ce projet n'a aucun groupe.</div>
            <div v-else v-for="g in filteredGroups" :key="g.id" class="flex items-center gap-2 mt-1">
              <input type="checkbox" :value="g.id" v-model="newMeeting.group_ids" />
              <span class="text-sm">{{ g.nom }}</span>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="showCreateModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary btn-sm">Créer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.agenda-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 900px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.meetings-list { display: flex; flex-direction: column; gap: 1rem; }
.meeting-date { display: flex; flex-direction: column; align-items: center; background: var(--color-primary-subtle); border-radius: var(--radius-md); padding: 0.75rem; min-width: 65px; }
.meeting-day { font-size: var(--text-2xl); font-weight: 800; color: var(--color-primary); }
.meeting-month { font-size: var(--text-xs); color: var(--color-primary); text-transform: uppercase; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
</style>