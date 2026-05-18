<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const projects = ref<any[]>([])
const allUsers = ref<any[]>([])
const showCreateModal = ref(false)

function getAcademicYear() {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth() + 1
  return month >= 9 ? `${year}/${year + 1}` : `${year - 1}/${year}`
}

const newProject = ref({
  titre: '',
  description: '',
  niveau: 'TS2',
  specialite_id: null as number | null,
  annee_universitaire: getAcademicYear(),
  is_public: true,
  targeted_student_ids: [] as number[]
})
const specialites = ref<any[]>([])

onMounted(async () => {
  await Promise.all([fetchProjects(), fetchSpecialites(), fetchUsers()])
})

async function fetchProjects() {
  loading.value = true
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement projets:', e)
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

async function fetchUsers() {
  try {
    const res = await api.get('/users')
    allUsers.value = Array.isArray(res.data) ? res.data : []
  } catch {}
}

const eligibleStudents = computed(() => {
  let list = allUsers.value.filter((u: any) => u.role === 'etudiant')
  if (newProject.value.niveau && newProject.value.niveau !== 'Mélangé') {
    list = list.filter((u: any) => u.niveau === newProject.value.niveau)
  }
  if (newProject.value.specialite_id) {
    list = list.filter((u: any) => u.specialite_id === newProject.value.specialite_id)
  }
  return list
})

async function createProject() {
  try {
    // Si des étudiants sont ciblés, le projet est privé pour eux
    newProject.value.is_public = newProject.value.targeted_student_ids.length === 0
    await api.post('/projects', newProject.value)
    showCreateModal.value = false
    newProject.value = { 
      titre: '', 
      description: '', 
      niveau: 'TS2', 
      specialite_id: null, 
      annee_universitaire: getAcademicYear(),
      is_public: true,
      targeted_student_ids: []
    }
    await fetchProjects()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

async function soumettreProjet(id: number) {
  if (!confirm('Soumettre ce projet pour validation ?')) return
  try {
    await api.post(`/projects/${id}/soumettre`)
    await fetchProjects()
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  }
}

function modifierProjet(id: number) {
  router.push(`/professeur/projets/${id}/edit`)
}

function getStatusBadge(statut: string) {
  const map: Record<string, string> = {
    brouillon: 'badge-info',
    soumis: 'badge-warning',
    valide: 'badge-success',
    refuse: 'badge-danger',
  }
  return map[statut] || 'badge-info'
}

function getStatusLabel(statut: string) {
  const map: Record<string, string> = {
    brouillon: 'Brouillon',
    soumis: 'Soumis',
    valide: 'Validé',
    refuse: 'Refusé',
  }
  return map[statut] || statut
}
</script>

<template>
  <div class="projects-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Mes Projets</h2>
        <p class="text-muted text-sm">{{ projects.length }} projet(s)</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="pi pi-plus" /> Nouveau projet
      </button>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p class="text-muted mt-2">Chargement...</p>
    </div>

    <div v-else-if="projects.length === 0" class="card text-center py-8">
      <i class="pi pi-briefcase" style="font-size:3rem;opacity:0.3;color:var(--text-muted)" />
      <p class="text-muted mt-2">Aucun projet</p>
    </div>

    <div v-else class="projects-grid">
      <div v-for="p in projects" :key="p.id" class="card project-card">
        <div class="flex justify-between items-start mb-2">
          <h3 class="font-bold text-lg flex items-center gap-2">
            {{ p.titre }}
            <span v-if="!p.is_public" class="badge badge-warning" style="font-size:0.7rem;padding:0.15rem 0.4rem;display:inline-flex;align-items:center;gap:0.2rem;"><i class="pi pi-lock" style="font-size:0.7rem;" /> Réservé</span>
          </h3>
          <span :class="`badge ${getStatusBadge(p.statut)}`">{{ getStatusLabel(p.statut) }}</span>
        </div>
        <p class="text-muted text-sm mb-3">{{ p.description?.substring(0, 150) || 'Aucune description' }}...</p>
        <div class="flex gap-3 text-xs text-muted mb-3">
          <span><i class="pi pi-bookmark" /> {{ p.niveau }}</span>
          <span><i class="pi pi-tag" /> {{ p.specialite?.nom || 'Mélangé' }}</span>
          <span><i class="pi pi-calendar" /> {{ p.annee_universitaire }}</span>
        </div>
        <div class="flex gap-2">
          <button 
            v-if="p.statut === 'brouillon' && p.id" 
            class="btn btn-secondary btn-sm" 
            @click="modifierProjet(p.id)"
          >
            <i class="pi pi-pencil" /> Modifier
          </button>
          <button class="btn btn-ghost btn-sm" @click="router.push(`/professeur/projets/${p.id}`)">
            <i class="pi pi-eye" /> Voir
          </button>
          <button v-if="p.statut === 'brouillon'" class="btn btn-primary btn-sm" @click="soumettreProjet(p.id)">
            <i class="pi pi-send" /> Soumettre
          </button>
        </div>
      </div>
    </div>

    <!-- Modal création -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-card card">
        <h3 class="mb-3">Nouveau projet</h3>
        <form @submit.prevent="createProject">
          <div class="form-group mb-3">
            <label class="form-label">Titre *</label>
            <input v-model="newProject.titre" type="text" class="form-input" required />
          </div>
          
          <div class="form-group mb-3">
            <label class="form-label">Description</label>
            <textarea v-model="newProject.description" class="form-input" rows="3" placeholder="Description du projet..."></textarea>
          </div>
          
          <div class="form-group mb-3">
            <label class="form-label">Niveau *</label>
            <select v-model="newProject.niveau" class="form-input" required>
              <option value="TS1">TS1</option>
              <option value="TS2">TS2</option>
              <option value="TS3">TS3</option>
              <option value="Mélangé">Mélangé</option>
            </select>
          </div>
          
          <!-- Spécialité : affichée seulement si niveau ≠ Mélangé -->
          <div v-if="newProject.niveau !== 'Mélangé'" class="form-group mb-3">
            <label class="form-label">Spécialité</label>
            <select v-model="newProject.specialite_id" class="form-input">
              <option :value="null">-- Sélectionner --</option>
              <option v-for="s in specialites" :key="s.id" :value="s.id">{{ s.nom }}</option>
            </select>
          </div>
          
          <div class="form-group mb-3">
            <label class="form-label">Année universitaire *</label>
            <input v-model="newProject.annee_universitaire" type="text" class="form-input" placeholder="2026/2027" required />
          </div>

          <!-- Sélection d'étudiants cibles (crée directement le groupe) -->
          <div class="form-group mb-3">
            <label class="form-label font-bold text-brand">Eleves (Associer des étudiants facultatif - max 5)</label>
            <div class="students-selector card-interactive mt-1">
              <div v-if="eligibleStudents.length === 0" class="text-xs text-muted py-2 text-center">
                Aucun étudiant trouvé dans la base de données.
              </div>
              <div v-else class="flex flex-col gap-1 max-h-40 overflow-y-auto" style="max-height: 120px; overflow-y: auto;">
                <label v-for="std in eligibleStudents" :key="std.id" class="flex items-center gap-2 text-sm cursor-pointer p-1.5 rounded hover:bg-hover">
                  <input 
                    type="checkbox" 
                    :value="std.id" 
                    v-model="newProject.targeted_student_ids"
                    :disabled="newProject.targeted_student_ids.length >= 5 && !newProject.targeted_student_ids.includes(std.id)" 
                  />
                  <span class="font-medium text-xs">{{ std.prenom }} {{ std.name }} <span class="text-muted">({{ std.niveau }} - {{ std.specialite?.nom || 'Mélangé' }})</span></span>
                </label>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="showCreateModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="!newProject.titre">Créer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.projects-page { 
  display: flex; 
  flex-direction: column; 
  gap: 1.5rem; 
  max-width: 1200px; 
  margin: 0 auto;
}

.page-header { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title-h2 { 
  font-family: var(--font-display); 
  font-size: var(--text-2xl); 
  font-weight: 800; 
  margin-bottom: 0.25rem;
}

.projects-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
  gap: 1rem; 
}

.project-card { 
  display: flex; 
  flex-direction: column; 
  transition: transform 0.2s, box-shadow 0.2s;
}

.project-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.modal-overlay { 
  position: fixed; 
  inset: 0; 
  background: rgba(0,0,0,0.5); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 1000; 
  backdrop-filter: blur(4px);
}

.modal-card { 
  width: 100%; 
  max-width: 500px; 
  padding: 1.5rem;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--text-secondary);
}

.form-input {
  padding: 0.625rem 0.875rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-subtle);
}

.text-muted { color: var(--text-muted); }
.text-sm { font-size: var(--text-sm); }
.text-xs { font-size: var(--text-xs); }
.text-center { text-align: center; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.mt-2 { margin-top: 0.5rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.flex { display: flex; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.justify-between { justify-content: space-between; }
.items-start { align-items: flex-start; }
.font-bold { font-weight: 700; }

@media (max-width: 768px) {
  .projects-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>