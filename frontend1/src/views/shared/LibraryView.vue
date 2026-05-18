<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const archivedProjects = ref<any[]>([])

onMounted(async () => {
  try {
    const res = await api.get('/archived-projects')
    archivedProjects.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Erreur bibliothèque:', e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="library-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Bibliothèque de projets</h2>
        <p class="text-muted text-sm">{{ archivedProjects.length }} projets archivés</p>
      </div>
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="archivedProjects.length === 0" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-book" style="font-size:3rem;opacity:0.3;" />
      <p class="text-muted">Aucun projet archivé</p>
    </div>

    <div v-else class="projects-grid">
      <div v-for="p in archivedProjects" :key="p.id" class="card project-card">
        <span class="badge badge-info">{{ p.annee_universitaire }}</span>
        <h3 class="project-title">{{ p.titre }}</h3>
        <p class="text-muted text-sm">{{ p.description?.substring(0, 150) }}...</p>
        <div class="project-footer">
          <span class="text-xs text-muted">{{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</span>
          <span class="text-xs text-muted">{{ p.niveau }}</span>
          <span class="text-xs text-muted">{{ p.specialite?.nom || 'Mélangé' }}</span>
        </div>
        <div class="mt-3 flex justify-end" v-if="p.validated_report_url">
          <a :href="p.validated_report_url" target="_blank" class="btn btn-primary btn-sm" title="Télécharger le rapport">
            <i class="pi pi-download" /> Télécharger le rapport
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.library-page { max-width: 1200px; }
.page-header { margin-bottom: 1.5rem; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.project-card { display: flex; flex-direction: column; gap: 0.75rem; }
.project-title { font-size: var(--text-base); font-weight: 700; }
.project-footer { display: flex; gap: 1rem; margin-top: auto; }
</style>