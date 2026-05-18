<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const meetings = ref<any[]>([])
const groups = ref<any[]>([])
const showRequestModal = ref(false)
const requestForm = ref({
  group_id: null as number | null,
  objet: '',
  message: '',
  creneau1: '',
  creneau2: '',
  creneau3: ''
})

onMounted(async () => {
  try {
    const [meetRes, grpRes] = await Promise.all([
      api.get('/meetings'),
      api.get('/groups')
    ])
    meetings.value = Array.isArray(meetRes.data) ? meetRes.data : []
    groups.value = Array.isArray(grpRes.data) ? grpRes.data : []
    if (groups.value.length > 0) {
      requestForm.value.group_id = groups.value[0].id
    }
  } catch (e) {
    console.error('Erreur agenda:', e)
  } finally {
    loading.value = false
  }
})

async function submitRequest() {
  const creneaux = [requestForm.value.creneau1, requestForm.value.creneau2, requestForm.value.creneau3].filter(c => c)
  try {
    await api.post('/meetings/request', {
      group_id: requestForm.value.group_id,
      objet: requestForm.value.objet,
      message: requestForm.value.message,
      creneaux_proposes: creneaux
    })
    showRequestModal.value = false
    requestForm.value = { group_id: groups.value[0]?.id || null, objet: '', message: '', creneau1: '', creneau2: '', creneau3: '' }
    alert('Demande envoyée au professeur')
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function respondMeeting(meeting: any, status: string) {
  const motif = status === 'refuse' ? prompt('Motif du refus :') : null
  try {
    await api.post(`/meetings/${meeting.id}/respond`, {
      statut_reponse: status,
      motif_refus: motif
    })
    alert('Réponse enregistrée')
    const res = await api.get('/meetings')
    meetings.value = Array.isArray(res.data) ? res.data : []
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function formatDate(date: string) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="agenda-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Mon Agenda</h2>
        <p class="text-muted text-sm">Vos rendez-vous et soutenances</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="showRequestModal = true">
        📅 Demander un RDV
      </button>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="meetings.length === 0" class="card text-center py-8">
      <i class="pi pi-calendar" style="font-size:3rem;opacity:0.3;color:var(--text-muted)" />
      <p class="text-muted mt-2">Aucun rendez-vous</p>
    </div>

    <div v-else class="meetings-list">
      <div v-for="m in meetings" :key="m.id" class="card meeting-card">
        <div class="meeting-date-badge">
          <span class="meeting-day">{{ new Date(m.date_heure).getDate() }}</span>
          <span class="meeting-month">{{ new Date(m.date_heure).toLocaleDateString('fr-FR', { month: 'short' }) }}</span>
        </div>
        <div class="meeting-content">
          <h3 class="font-bold">{{ m.titre }}</h3>
          <p class="text-sm text-muted">{{ formatDate(m.date_heure) }}</p>
          <p class="text-sm text-muted" v-if="m.lieu">{{ m.lieu }}</p>
          <p class="text-sm text-muted">Organisé par : {{ m.organisateur?.prenom }} {{ m.organisateur?.name }}</p>
          <div class="flex gap-2 mt-2" v-if="m.type !== 'soutenance'">
            <button class="btn btn-success btn-sm" @click="respondMeeting(m, 'accepte')">✅ Accepter</button>
            <button class="btn btn-danger btn-sm" @click="respondMeeting(m, 'refuse')">❌ Refuser</button>
          </div>
          <div v-else class="mt-2 flex items-center justify-between">
            <span class="text-[10px] bg-slate-900 text-white px-2 py-1 rounded font-black uppercase tracking-widest">Soutenance Mandatée</span>
            <router-link :to="`/etudiant/fiche-soutenance/${m.id}`" class="btn btn-primary btn-xs">
              Voir la Fiche
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal demande RDV -->
    <div v-if="showRequestModal" class="modal-overlay" @click.self="showRequestModal = false">
      <div class="modal-card card">
        <h3 class="mb-3">Demander un RDV</h3>
        <form @submit.prevent="submitRequest">
          <div class="form-group mb-2">
            <label class="form-label">Groupe</label>
            <select v-model="requestForm.group_id" class="form-input">
              <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.nom }}</option>
            </select>
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Objet</label>
            <input v-model="requestForm.objet" type="text" class="form-input" required />
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Message</label>
            <textarea v-model="requestForm.message" class="form-input" rows="2"></textarea>
          </div>
          <div class="form-group mb-2">
            <label class="form-label">Créneaux proposés</label>
            <input v-model="requestForm.creneau1" type="datetime-local" class="form-input mb-1" />
            <input v-model="requestForm.creneau2" type="datetime-local" class="form-input mb-1" />
            <input v-model="requestForm.creneau3" type="datetime-local" class="form-input" />
          </div>
          <div class="flex justify-end gap-2 mt-3">
            <button type="button" class="btn btn-secondary btn-sm" @click="showRequestModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary btn-sm">Envoyer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.agenda-page { max-width: 900px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; margin-bottom: 0.5rem; }
.meetings-list { display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem; }
.meeting-card { display: flex; gap: 1.5rem; align-items: flex-start; }
.meeting-date-badge { display: flex; flex-direction: column; align-items: center; background: var(--color-primary-subtle); border-radius: var(--radius-md); padding: 0.75rem; min-width: 65px; }
.meeting-day { font-size: var(--text-2xl); font-weight: 800; color: var(--color-primary); }
.meeting-month { font-size: var(--text-xs); color: var(--color-primary); text-transform: uppercase; }
.meeting-content { flex: 1; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 480px; }
</style>