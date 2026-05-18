<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const users = ref<any[]>([])
const specialites = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingUser = ref<any>(null)
const currentPage = ref(1)
const hasNextPage = ref(false)
const filters = ref({ role: '', search: '' })

const form = ref({
  name: '', prenom: '', email: '', password: '', role: 'etudiant',
  specialite_id: null as number | null, annee_universitaire: ''
})

async function fetchUsers() {
  loading.value = true
  try {
    const params: any = { page: currentPage.value, ...filters.value }
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
    const res = await api.get('/users', { params })
    users.value = Array.isArray(res.data.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
    hasNextPage.value = !!res.data.next_page_url
  } catch (e) {
    console.error('Erreur chargement utilisateurs:', e)
  } finally {
    loading.value = false
  }
}

async function fetchSpecialites() {
  try {
    const res = await api.get('/specialites')
    specialites.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}

function openCreateModal() {
  editingUser.value = null
  form.value = { name: '', prenom: '', email: '', password: '', role: 'etudiant', specialite_id: null, annee_universitaire: '' }
  showModal.value = true
}

function openEditModal(user: any) {
  editingUser.value = user
  form.value = { ...user, password: '' }
  showModal.value = true
}
async function submitUser() {
  try {
    const payload: any = { ...form.value }
    if (!payload.password) delete payload.password
    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, payload)
    } else {
      await api.post('/users', payload)
    }
    showModal.value = false
    await fetchUsers()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}
async function deleteUser(id: number) {
  if (!confirm('Supprimer cet utilisateur ?')) return
  try {
    await api.delete(`/users/${id}`)
    await fetchUsers()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function importUsers() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.csv'
  input.onchange = async (e: any) => {
    const file = e.target.files[0]
    const fd = new FormData()
    fd.append('file', file)
    try {
      await api.post('/users/import', fd)
      alert('Import lancé')
      await fetchUsers()
    } catch {
      alert('Erreur import')
    }
  }
  input.click()
}

function getRoleLabel(role: string) {
  const labels: Record<string, string> = {
    etudiant: 'Étudiant', professeur: 'Professeur',
    rup_specialite: 'RUP Spé.', rup_projet: 'RUP Projet'
  }
  return labels[role] || role
}

function getRoleClass(role: string) {
  const classes: Record<string, string> = {
    etudiant: 'badge-info', professeur: 'badge-success',
    rup_specialite: 'badge-warning', rup_projet: 'badge-danger'
  }
  return classes[role] || 'badge-info'
}

onMounted(() => {
  fetchUsers()
  fetchSpecialites()
})
</script>

<template>
  <div class="users-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Gestion des utilisateurs</h2>
        <p class="text-muted text-sm">Administration des comptes</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" @click="importUsers">📂 Importer CSV</button>
        <button class="btn btn-primary btn-sm" @click="openCreateModal">+ Nouvel utilisateur</button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
      <div class="flex gap-4 items-end flex-wrap">
        <div class="form-group">
          <label class="form-label">Rôle</label>
          <select v-model="filters.role" class="form-input">
            <option value="">Tous</option>
            <option value="etudiant">Étudiant</option>
            <option value="professeur">Professeur</option>
            <option value="rup_specialite">RUP Spécialité</option>
            <option value="rup_projet">RUP Projet</option>
          </select>
        </div>
        <div class="form-group flex-1">
          <label class="form-label">Recherche</label>
          <input v-model="filters.search" type="text" class="form-input" placeholder="Nom, prénom ou email" />
        </div>
        <button class="btn btn-primary btn-sm" @click="fetchUsers">Filtrer</button>
      </div>
    </div>

    <!-- Tableau -->
    <div class="card">
      <div v-if="loading" class="text-center py-4">Chargement...</div>
      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Spécialité</th>
              <th>Année</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td class="font-medium">{{ u.prenom }} {{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td><span :class="`badge ${getRoleClass(u.role)}`">{{ getRoleLabel(u.role) }}</span></td>
              <td>{{ u.specialite?.nom || '-' }}</td>
              <td>{{ u.annee_universitaire || '-' }}</td>
              <td>
                <button class="btn btn-ghost btn-icon btn-sm" @click="openEditModal(u)">✏️</button>
                <button class="btn btn-ghost btn-icon btn-sm text-danger" @click="deleteUser(u.id)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card card">
        <h3>{{ editingUser ? 'Modifier' : 'Nouvel' }} utilisateur</h3>
        <form @submit.prevent="submitUser" class="mt-4">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Nom</label>
              <input v-model="form.name" type="text" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Prénom</label>
              <input v-model="form.prenom" type="text" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Mot de passe {{ editingUser ? '(laisser vide)' : '' }}</label>
              <input v-model="form.password" type="password" class="form-input" :required="!editingUser" />
            </div>
            <div class="form-group">
              <label class="form-label">Rôle</label>
              <select v-model="form.role" class="form-input" required>
                <option value="etudiant">Étudiant</option>
                <option value="professeur">Professeur</option>
                <option value="rup_specialite">RUP Spécialité</option>
                <option value="rup_projet">RUP Projet</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Spécialité</label>
              <select v-model="form.specialite_id" class="form-input">
                <option :value="null">-- Aucune --</option>
                <option v-for="s in specialites" :key="s.id" :value="s.id">{{ s.nom }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Année universitaire</label>
              <input v-model="form.annee_universitaire" type="text" class="form-input" placeholder="ex: 2024/2025" />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="btn btn-secondary btn-sm" @click="showModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.users-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1400px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-default); font-size: var(--text-sm); }
.data-table th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 11px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { width: 100%; max-width: 600px; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
</style>