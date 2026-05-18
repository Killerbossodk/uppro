<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const groups = ref<any[]>([])
const projects = ref<any[]>([])
const showCreateModal = ref(false)
const newGroup = ref({ project_id: null as number | null, nom: '', capacite_max: 5 })

onMounted(async () => {
  await Promise.all([fetchGroups(), fetchProjects()])
})

async function fetchGroups() {
  loading.value = true
  try {
    const res = await api.get('/groups')
    groups.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement groupes:', e)
  } finally {
    loading.value = false
  }
}

async function fetchProjects() {
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data.filter((p: any) => p.statut === 'valide') : []
  } catch {}
}

async function createGroup() {
  try {
    await api.post('/groups', newGroup.value)
    showCreateModal.value = false
    newGroup.value = { project_id: null, nom: '', capacite_max: 5 }
    await fetchGroups()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function toggleInscriptions(groupId: number) {
  try {
    await api.post(`/groups/${groupId}/toggle-inscriptions`)
    await fetchGroups()
  } catch {}
}
</script>

<template>
  <div class="groups-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Groupes</h2>
        <p class="text-muted text-sm">{{ groups.length }} groupe(s)</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="pi pi-plus" /> Créer un groupe
      </button>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="groups.length === 0" class="card text-center py-8">
      <p class="text-muted">Aucun groupe</p>
    </div>

    <div v-else class="groups-list">
      <div v-for="g in groups" :key="g.id" class="card group-card">
        <div class="flex items-center gap-4">
          <div class="group-icon"><i class="pi pi-users" /></div>
          <div class="flex-1">
            <h3 class="font-bold">{{ g.nom }}</h3>
            <p class="text-muted text-sm">Projet : {{ g.project?.titre || 'Non défini' }}</p>
            <p class="text-muted text-sm">{{ g.membres?.length || 0 }} / {{ g.capacite_max }} membres</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span :class="`badge ${g.inscription_ouverte ? 'badge-success' : 'badge-danger'}`">
              {{ g.inscription_ouverte ? 'Ouvert' : 'Fermé' }}
            </span>
            <button class="btn btn-ghost btn-sm" @click="toggleInscriptions(g.id)">
              {{ g.inscription_ouverte ? 'Fermer' : 'Ouvrir' }}
            </button>
            <button class="btn btn-primary btn-sm" @click="router.push(`/professeur/groupes/${g.id}`)">
              Gérer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-card card">
        <h3 class="mb-3">Créer un groupe</h3>
        <form @submit.prevent="createGroup">
          <div class="form-group mb-3">
            <label class="form-label">Projet</label>
            <select v-model="newGroup.project_id" class="form-input" required>
              <option :value="null">Sélectionner...</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.titre }}</option>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="form-label">Nom du groupe</label>
            <input v-model="newGroup.nom" type="text" class="form-input" required />
          </div>
          <div class="form-group mb-3">
            <label class="form-label">Capacité max</label>
            <input v-model.number="newGroup.capacite_max" type="number" min="1" max="10" class="form-input" />
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
.groups-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1000px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.groups-list { display: flex; flex-direction: column; gap: 1rem; }
.group-card { }
.group-icon { width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--color-primary-subtle); color: var(--color-primary-light); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 450px; }
</style>