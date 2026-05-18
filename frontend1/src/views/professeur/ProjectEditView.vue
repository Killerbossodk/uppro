<!-- src/views/professeur/ProjectEditView.vue -->
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const projectId = Number(route.params.id)
const loading = ref(true)
const specialites = ref<any[]>([])
const allUsers = ref<any[]>([])
const form = ref({
  titre: '',
  description: '',
  niveau: '',
  specialite_id: null as number | null,
  is_public: true,
  targeted_student_ids: [] as number[],
})

async function loadData() {
  loading.value = true
  try {
    const [projRes, specRes, userRes] = await Promise.all([
      api.get(`/projects/${projectId}`),
      api.get('/specialites'),
      api.get('/users')
    ])
    specialites.value = Array.isArray(specRes.data) ? specRes.data : []
    allUsers.value = Array.isArray(userRes.data) ? userRes.data : []
    
    const targetedIds = projRes.data.targeted_students ? projRes.data.targeted_students.map((u: any) => u.id) : []
    
    form.value = {
      titre: projRes.data.titre,
      description: projRes.data.description || '',
      niveau: projRes.data.niveau,
      specialite_id: projRes.data.specialite_id,
      is_public: projRes.data.is_public !== undefined ? Boolean(projRes.data.is_public) : true,
      targeted_student_ids: targetedIds,
    }
  } catch (error) {
    console.error('Erreur chargement:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Projet introuvable', life: 3000 })
    router.push('/professeur/projets')
  } finally {
    loading.value = false
  }
}

const eligibleStudents = computed(() => {
  let list = allUsers.value.filter((u: any) => u.role === 'etudiant')
  if (form.value.niveau && form.value.niveau !== 'Mélangé') {
    list = list.filter((u: any) => u.niveau === form.value.niveau)
  }
  if (form.value.specialite_id) {
    list = list.filter((u: any) => u.specialite_id === form.value.specialite_id)
  }
  return list
})

async function updateProject() {
  try {
    await api.put(`/projects/${projectId}`, form.value)
    toast.add({ severity: 'success', summary: 'Projet modifié', detail: 'Les modifications ont été enregistrées', life: 3000 })
    router.push('/professeur/projets')
  } catch (error: any) {
    const msg = error.response?.data?.message || 'Erreur lors de la modification'
    toast.add({ severity: 'error', summary: 'Erreur', detail: msg, life: 4000 })
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="project-edit">
    <div class="flex items-center gap-2 mb-4">
      <button class="btn btn-ghost btn-icon" @click="router.back()">
        <i class="pi pi-arrow-left" />
      </button>
      <h2 class="page-title">Modifier le projet</h2>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p class="text-muted mt-2">Chargement...</p>
    </div>

    <div v-else class="card">
      <form @submit.prevent="updateProject">
        <div class="form-group mb-3">
          <label class="form-label">Titre *</label>
          <input v-model="form.titre" type="text" class="form-input" required />
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Description</label>
          <textarea v-model="form.description" class="form-input" rows="4"></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Niveau *</label>
          <select v-model="form.niveau" class="form-input" required>
            <option value="TS1">TS1</option>
            <option value="TS2">TS2</option>
            <option value="TS3">TS3</option>
            <option value="Mélangé">Mélangé</option>
          </select>
        </div>

        <!-- Spécialité : cachée si niveau = Mélangé -->
        <div v-if="form.niveau !== 'Mélangé'" class="form-group mb-3">
          <label class="form-label">Spécialité</label>
          <select v-model="form.specialite_id" class="form-input">
            <option :value="null">-- Aucune --</option>
            <option v-for="s in specialites" :key="s.id" :value="s.id">{{ s.nom }}</option>
          </select>
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
                  v-model="form.targeted_student_ids"
                  :disabled="form.targeted_student_ids.length >= 5 && !form.targeted_student_ids.includes(std.id)" 
                />
                <span class="font-medium text-xs">{{ std.prenom }} {{ std.name }} <span class="text-muted">({{ std.niveau }} - {{ std.specialite?.nom || 'Mélangé' }})</span></span>
              </label>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button type="button" class="btn btn-secondary" @click="router.back()">Annuler</button>
          <button type="submit" class="btn btn-primary" :disabled="!form.titre">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.project-edit {
  max-width: 700px;
  margin: 0 auto;
}

.page-title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 800;
  margin: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-secondary);
}

.form-input {
  width: 100%;
  padding: 0.625rem 1rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
}

.students-selector {
  border: 1px solid var(--border-default);
  background: var(--bg-elevated);
  border-radius: var(--radius-md);
  padding: 0.75rem;
  margin-top: 0.25rem;
}

.hover\:bg-hover:hover {
  background: var(--bg-hover);
}
</style>