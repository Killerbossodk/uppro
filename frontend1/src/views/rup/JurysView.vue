<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const jurys = ref<any[]>([])
const meetings = ref<any[]>([])

onMounted(async () => {
  await Promise.all([fetchJurys(), fetchMeetings()])
  loading.value = false
})

async function fetchJurys() {
  try {
    const res = await api.get('/juries')
    jurys.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}

async function fetchMeetings() {
  try {
    const res = await api.get('/meetings')
    meetings.value = Array.isArray(res.data) ? res.data.filter((m: any) => m.type === 'soutenance') : []
  } catch {}
}


async function deleteJury(id: number) {
  if (!confirm('Supprimer ce jury ?')) return
  try {
    await api.delete(`/juries/${id}`)
    await fetchJurys()
  } catch {}
}

async function notifyJury(id: number) {
  try {
    await api.post(`/juries/${id}/notify`)
    alert('Notifications envoyées !')
  } catch {}
}
</script>

<template>
  <div class="jurys-page">
      <div class="page-header">
        <h2 class="page-title-h2">Consultation des Jurys</h2>
        <p class="text-muted text-sm">{{ jurys.length }} jury(s)</p>
      </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="jurys.length === 0" class="card text-center py-8">
      <p class="text-muted">Aucun jury</p>
    </div>

    <div v-else class="jurys-grid">
      <div v-for="j in jurys" :key="j.id" class="card jury-card">
        <div class="flex items-center gap-3 mb-3">
          <div class="jury-icon"><i class="pi pi-star" /></div>
          <div>
            <h3 class="font-bold">{{ j.meeting?.titre || 'Soutenance' }}</h3>
            <p class="text-xs text-muted">{{ new Date(j.date_heure).toLocaleString('fr-FR') }} · {{ j.salle }}</p>
          </div>
        </div>
        <p class="text-sm"><strong>Président :</strong> {{ j.president?.prenom }} {{ j.president?.name }}</p>
        <p class="text-sm"><strong>Membres :</strong> {{ j.membres?.length || 0 }}</p>
        <div class="flex gap-2 mt-3">
          <button class="btn btn-primary btn-sm" @click="notifyJury(j.id)">📢 Notifier</button>
          <button class="btn btn-danger btn-sm" @click="deleteJury(j.id)">🗑️</button>
        </div>
      </div>
    </div>


  </div>
</template>

<style scoped>
.jurys-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.jurys-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1rem; }
.jury-icon { width: 44px; height: 44px; border-radius: var(--radius-md); background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; }
</style>