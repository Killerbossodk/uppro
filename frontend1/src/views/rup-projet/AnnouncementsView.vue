<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const announcements = ref<any[]>([])
const groups = ref<any[]>([])
const users = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const form = ref({ title: '', content: '', target_type: 'all', target_id: null as number | null })

async function fetchAnnouncements() {
  loading.value = true
  try {
    const res = await api.get('/announcements')
    announcements.value = Array.isArray(res.data.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
  } catch (e) {
    console.error('Erreur chargement annonces:', e)
  } finally {
    loading.value = false
  }
}

async function fetchGroupsAndUsers() {
  try {
    const [grpRes, usrRes] = await Promise.all([api.get('/groups'), api.get('/users')])
    groups.value = Array.isArray(grpRes.data) ? grpRes.data : []
    users.value = Array.isArray(usrRes.data.data) ? usrRes.data.data : (Array.isArray(usrRes.data) ? usrRes.data : [])
  } catch {}
}

function openCreateModal() {
  form.value = { title: '', content: '', target_type: 'all', target_id: null }
  showModal.value = true
  fetchGroupsAndUsers()
}

async function submitAnnouncement() {
  try {
    await api.post('/announcements', form.value)
    showModal.value = false
    await fetchAnnouncements()
    alert('Communiqué publié')
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function deleteItem(id: number) {
  if (!confirm('Supprimer ce communiqué ?')) return
  try {
    await api.delete(`/announcements/${id}`)
    await fetchAnnouncements()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function getTargetLabel(type: string, id: number | null) {
  const labels: Record<string, string> = { all: 'Tous', professors: 'Professeurs', students: 'Étudiants', group: `Groupe #${id}`, user: `Utilisateur #${id}` }
  return labels[type] || type
}

function formatDate(date: string) {
  return new Date(date).toLocaleString('fr-FR')
}

onMounted(fetchAnnouncements)
async function deleteAllAnnouncements() {
  if (!confirm('Supprimer TOUS les communiqués ? Cette action est irréversible.')) return
  
  const total = announcements.value.length
  let deleted = 0
  let errors = 0

  for (const a of [...announcements.value]) {
    try {
      await api.delete(`/announcements/${a.id}`)
      deleted++
    } catch (e: any) {
      if (e.response?.status === 404) {
        deleted++ // Déjà supprimé, on compte quand même
      } else {
        errors++
      }
    }
  }

  await fetchAnnouncements()
  
  if (errors === 0) {
    alert(`${deleted} communiqué(s) supprimé(s) avec succès`)
  } else {
    alert(`${deleted} supprimé(s), ${errors} erreur(s)`)
  }
}
</script>

<template>
  <div class="announcements-page">
    <div class="page-header">
        <div>
            <h2 class="page-title-h2">Communiqués</h2>
            <p class="text-muted text-sm">Diffuser des messages aux utilisateurs</p>
        </div>
        <div class="flex gap-2">
            <button 
            v-if="announcements.length > 0"
            class="btn btn-danger btn-sm" 
            @click="deleteAllAnnouncements"
            >
            🗑️ Tout supprimer
            </button>
            <button class="btn btn-primary btn-sm" @click="openCreateModal">+ Nouveau communiqué</button>
        </div>
        </div>

    <div class="card">
      <div v-if="loading" class="text-center py-4">Chargement...</div>
      <div v-else-if="announcements.length === 0" class="text-muted text-center py-4">Aucun communiqué</div>
      <div v-else class="divide-y">
        <div v-for="a in announcements" :key="a.id" class="p-4 hover:bg-hover">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="font-medium">{{ a.title }}</h3>
              <p class="text-sm text-muted mt-1">{{ a.content }}</p>
              <p class="text-xs text-muted mt-2">
                Destinataires : {{ getTargetLabel(a.target_type, a.target_id) }} • {{ formatDate(a.created_at) }}
              </p>
            </div>
            <button class="btn btn-ghost btn-icon btn-sm text-danger" @click="deleteItem(a.id)">🗑️</button>
            
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card card">
        <h3>Nouveau communiqué</h3>
        <form @submit.prevent="submitAnnouncement" class="mt-4">
          <div class="form-group mb-3">
            <label class="form-label">Titre</label>
            <input v-model="form.title" type="text" class="form-input" required />
          </div>
          <div class="form-group mb-3">
            <label class="form-label">Message</label>
            <textarea v-model="form.content" rows="4" class="form-input" required></textarea>
          </div>

          <div class="form-group mb-3">
            <label class="form-label">Destinataires</label>
            <select v-model="form.target_type" class="form-input">
              <option value="all">Tous les utilisateurs</option>
              <option value="professors">Tous les professeurs</option>
              <option value="students">Tous les étudiants</option>
              <option value="group">Un groupe spécifique</option>
              <option value="user">Un utilisateur spécifique</option>
            </select>
          </div>
          <div v-if="form.target_type === 'group'" class="form-group mb-3">
            <label class="form-label">Groupe</label>
            <select v-model="form.target_id" class="form-input">
              <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.nom }}</option>
            </select>
          </div>
          <div v-if="form.target_type === 'user'" class="form-group mb-3">
            <label class="form-label">Utilisateur</label>
            <select v-model="form.target_id" class="form-input">
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.prenom }} {{ u.name }} ({{ u.role }})</option>
            </select>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="showModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary btn-sm">Publier</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.announcements-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 900px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 500px; }
</style>