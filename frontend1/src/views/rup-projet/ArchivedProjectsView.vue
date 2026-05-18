<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import { useToast } from 'primevue/usetoast'

const toast = useToast()
const loading = ref(true)
const projects = ref<any[]>([])

async function loadProjects() {
  loading.value = true
  try {
    const res = await api.get('/archived-projects')
    projects.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur chargement archives:', e)
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: 'Impossible de charger les projets archivés', 
      life: 3000 
    })
  } finally {
    loading.value = false
  }
}

async function restaurerProjet(id: number) {
  try {
    await api.post(`/projects/${id}/restore`)
    toast.add({ 
      severity: 'success', 
      summary: 'Restauré', 
      detail: 'Le projet a été restauré avec succès.', 
      life: 3000 
    })
    await loadProjects()
  } catch (e: any) {
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: e.response?.data?.message || 'Erreur lors de la restauration', 
      life: 4000 
    })
  }
}

async function supprimerProjet(id: number) {
  if (!confirm('⚠️ Supprimer DÉFINITIVEMENT ce projet ? Cette action est irréversible.')) return
  try {
    await api.delete(`/projects/${id}/force-delete`)
    toast.add({ 
      severity: 'success', 
      summary: 'Supprimé', 
      detail: 'Le projet a été supprimé définitivement.', 
      life: 3000 
    })
    await loadProjects()
  } catch (e: any) {
    const msg = e.response?.data?.message || e.message
    alert('Erreur : ' + msg)
  }
}

onMounted(() => {
  loadProjects()
})
</script>

<template>
  <div class="archives-page">
    <div class="page-header">
      <h2 class="page-title-h2">📦 Projets archivés</h2>
      <p class="text-muted text-sm">{{ projects.length }} projet(s) archivé(s)</p>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p class="text-muted mt-2">Chargement des archives...</p>
    </div>

    <!-- Aucun projet -->
    <div v-else-if="projects.length === 0" class="card text-center py-8">
      <i class="pi pi-archive" style="font-size:3rem;opacity:0.3;" />
      <p class="text-muted mt-2">Aucun projet archivé</p>
    </div>

    <!-- Tableau des projets -->
    <div v-else class="table-responsive card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Titre</th>
            <th>Niveau</th>
            <th>Année</th>
            <th>Professeur</th>
            <th>Spécialité</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in projects" :key="p.id">
            <td class="font-medium">{{ p.titre }}</td>
            <td>{{ p.niveau }}</td>
            <td>{{ p.annee_universitaire }}</td>
            <td>{{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</td>
            <td>{{ p.specialite?.nom || 'Mélangé' }}</td>
            <td class="text-right">
              <div class="flex gap-2 justify-end">
                <button class="btn btn-success btn-sm" @click="restaurerProjet(p.id)" title="Restaurer le projet">
                  🔄 Restaurer
                </button>
                <button class="btn btn-danger btn-sm" @click="supprimerProjet(p.id)" title="Supprimer définitivement">
                  🗑️ Supprimer
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.archives-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title-h2 {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
  margin-bottom: 0;
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.875rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-default);
  font-size: var(--text-sm);
}

.data-table th {
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  font-size: 11px;
  background: var(--bg-surface);
  letter-spacing: 0.05em;
}

.data-table tbody tr:hover {
  background: var(--bg-hover);
}

.text-right {
  text-align: right;
}

.flex {
  display: flex;
}

.gap-2 {
  gap: 0.5rem;
}

.justify-end {
  justify-content: flex-end;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-success {
  background: rgba(16, 185, 129, 0.15);
  color: var(--color-success);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
  background: rgba(16, 185, 129, 0.25);
}

.btn-danger {
  background: rgba(239, 68, 68, 0.15);
  color: var(--color-danger);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
  background: rgba(239, 68, 68, 0.25);
}

.text-center {
  text-align: center;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.mt-2 {
  margin-top: 0.5rem;
}
</style>