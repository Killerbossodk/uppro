<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const project = ref<any>(null)
const myGroup = ref<any>(null)
const groupes = ref<any[]>([])
const rapports = ref<any[]>([])

onMounted(async () => {
  try {
    let projectId = route.params.id as string

    // 1. Si pas d'ID, c'est l'étudiant : on cherche son groupe
    if (!projectId) {
      const grpRes = await api.get('/groups')
      const groups = Array.isArray(grpRes.data) ? grpRes.data : []
      if (groups.length > 0) {
        myGroup.value = groups[0]
        projectId = myGroup.value.project_id
      }
    }

    // 2. Charger le projet
    if (projectId) {
      const projRes = await api.get(`/projects/${projectId}`)
      project.value = projRes.data
      
      // 3. Groupes du projet
      if (project.value?.groupes) {
        groupes.value = project.value.groupes
      }

      // 4. Rapports (si on a un myGroup)
      if (myGroup.value) {
        try {
          const repRes = await api.get(`/groups/${myGroup.value.id}/reports`)
          rapports.value = Array.isArray(repRes.data) ? repRes.data : []
        } catch {
          rapports.value = []
        }
      }
    }
  } catch (e) {
    console.error('Erreur chargement projet:', e)
  } finally {
    loading.value = false
  }
})

function getStatusLabel(s: string) {
  const m: Record<string, string> = {
    brouillon: 'Brouillon', soumis: 'Soumis', valide: 'Validé', refuse: 'Rejeté'
  }
  return m[s] || s
}

function getStatusClass(s: string) {
  const m: Record<string, string> = {
    brouillon: 'badge-info', soumis: 'badge-warning', valide: 'badge-success', refuse: 'badge-danger'
  }
  return m[s] || 'badge-info'
}
</script>

<template>
  <div class="project-detail">
    <div v-if="loading" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p style="margin-top:1rem;color:var(--text-muted);">Chargement de votre projet...</p>
    </div>

    <div v-else-if="!project" class="card" style="text-align:center;padding:3rem;">
      <i class="pi pi-briefcase" style="font-size:3rem;opacity:0.3;" />
      <p class="text-muted" style="margin-top:1rem;">Projet introuvable ou non disponible.</p>
      <RouterLink v-if="!route.params.id" to="/etudiant/choix-projet" class="btn btn-primary btn-sm" style="margin-top:1rem;">
        Voir les projets disponibles
      </RouterLink>
    </div>

    <template v-else>
      <!-- En-tête -->
      <div class="card">
        <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;">
          <div class="project-icon"><i class="pi pi-briefcase" /></div>
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
              <h1 style="font-family:var(--font-display);font-size:var(--text-2xl);font-weight:800;">{{ project.titre }}</h1>
              <span :class="`badge ${getStatusClass(project.statut)}`">{{ getStatusLabel(project.statut) }}</span>
            </div>
            <p class="text-muted text-sm" style="margin-top:0.5rem;">{{ project.description }}</p>
            <div style="margin-top:0.75rem;display:flex;gap:1.5rem;flex-wrap:wrap;">
              <span class="text-sm"><i class="pi pi-user" style="color:var(--color-primary)" /> {{ project.superviseur?.prenom }} {{ project.superviseur?.name }}</span>
              <span class="text-sm"><i class="pi pi-bookmark" style="color:var(--color-primary)" /> {{ project.niveau }} · {{ project.specialite?.nom || 'Mélangé' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Mon groupe -->
      <div v-if="myGroup" class="card" style="margin-top:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
          <i class="pi pi-users" style="font-size:2rem;color:var(--color-primary);" />
          <div>
            <h3 style="font-weight:700;">Mon groupe : {{ myGroup.nom }}</h3>
            <p class="text-muted text-sm">{{ myGroup.membres?.length || 0 }} / {{ myGroup.capacite_max }} membres</p>
          </div>
          <RouterLink :to="`/etudiant/groupe?id=${myGroup.id}`" class="btn btn-primary btn-sm" style="margin-left:auto;">
            Voir le groupe
          </RouterLink>
        </div>
      </div>

      <!-- Rapports -->
      <div class="card" style="margin-top:1rem;">
        <h3 class="section-title"><i class="pi pi-file" /> Mes Rapports ({{ rapports.length }})</h3>
        <div v-if="rapports.length === 0" class="text-muted text-sm">Aucun rapport déposé</div>
        <div v-else>
          <div v-for="r in rapports" :key="r.id" style="display:flex;align-items:center;gap:1rem;padding:0.75rem;background:var(--bg-elevated);border-radius:var(--radius-md);margin-bottom:0.5rem;">
            <i class="pi pi-file-pdf" style="font-size:1.5rem;color:#ef4444;" />
            <div style="flex:1;">
              <p class="font-semibold text-sm">{{ r.titre }}</p>
              <p class="text-xs text-muted">v{{ r.version }} · {{ r.created_at?.split('T')[0] }}</p>
            </div>
            <span :class="`badge ${r.statut === 'valide' ? 'badge-success' : r.statut === 'rejete' ? 'badge-danger' : 'badge-warning'}`">
              {{ r.statut }}
            </span>
          </div>
        </div>
        <RouterLink to="/etudiant/rapports" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">
          <i class="pi pi-upload" /> Déposer un rapport
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.project-detail { max-width: 900px; display: flex; flex-direction: column; gap: 1rem; }
.project-icon { width: 60px; height: 60px; border-radius: var(--radius-lg); background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
.section-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 700; margin-bottom: 1rem; }
.section-title .pi { color: var(--color-primary-light); }
</style>