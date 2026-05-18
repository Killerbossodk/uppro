<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

const toast = useToast()
const loading = ref(true)
const projects = ref<any[]>([])
const myGroup = ref<any>(null)
const selectedProjectId = ref<number | null>(null)

// Modal Details
const showModal = ref(false)
const detailedProject = ref<any>(null)

onMounted(async () => {
  await loadData()
})

async function loadData() {
  loading.value = true
  try {
    // Charger les projets validés
    const projRes = await api.get('/projects')
    projects.value = Array.isArray(projRes.data) 
      ? projRes.data.filter((p: any) => p.statut === 'valide' && !p.est_archive)
      : []

    // Vérifier si l'étudiant est déjà dans un groupe
    const grpRes = await api.get('/groups')
    if (Array.isArray(grpRes.data) && grpRes.data.length > 0) {
      myGroup.value = grpRes.data[0]
      selectedProjectId.value = myGroup.value.project_id
    } else {
      myGroup.value = null
      selectedProjectId.value = null
    }
  } catch (e) {
    console.error('Erreur chargement projets:', e)
  } finally {
    loading.value = false
  }
}

function openDetails(project: any) {
  detailedProject.value = project
  showModal.value = true
}

// S'inscrire à un projet
async function inscrire(project: any) {
  if (!project.groupes || project.groupes.length === 0) {
    toast.add({ severity: 'error', summary: 'Aucun groupe', detail: 'Ce projet n\'a pas encore de groupe', life: 3000 })
    return
  }

  const groupeOuvert = project.groupes.find((g: any) => 
    g.inscription_ouverte && g.membres?.length < g.capacite_max
  )

  if (!groupeOuvert) {
    toast.add({ severity: 'error', summary: 'Groupes complets', detail: 'Tous les groupes de ce projet sont pleins', life: 3000 })
    return
  }

  try {
    const res = await api.post(`/groups/${groupeOuvert.id}/self-add`)
    toast.add({ severity: 'success', summary: 'Inscrit !', detail: res.data?.message || 'Vous avez rejoint le groupe', life: 3000 })
    showModal.value = false
    await loadData()
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: e?.response?.data?.message || 'Impossible de s\'inscrire', life: 3000 })
  }
}

// Quitter un groupe
async function quitterGroupe() {
  if (!myGroup.value) return
  
  if (!confirm('Voulez-vous vraiment quitter ce groupe ?')) return

  try {
    const res = await api.post(`/groups/${myGroup.value.id}/leave`)
    toast.add({ severity: 'success', summary: 'Désinscrit', detail: res.data?.message || 'Vous avez quitté le groupe', life: 3000 })
    await loadData()
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: e?.response?.data?.message || 'Impossible de quitter', life: 3000 })
  }
}
</script>

<template>
  <div class="project-selection">
    <!-- Bannière info -->
    <div class="card" style="background: var(--color-primary-subtle); border-color: rgba(125, 33, 64, 0.2);">
      <h2 style="font-family: var(--font-display); font-weight: 800; margin-bottom: 0.25rem;">Choix de Projet</h2>
      <p class="text-muted text-sm">
        Consultez les projets disponibles et inscrivez-vous. 
        <strong>Un étudiant = un seul projet.</strong>
      </p>
    </div>

    <!-- Si déjà inscrit -->
    <div v-if="myGroup" class="card highlight-card">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <div class="success-icon">
          <i class="pi pi-check" />
        </div>
        <div>
          <p class="font-bold text-sm" style="color: var(--color-success);">INSCRIPTION VALIDÉE</p>
          <p class="text-xl font-bold" style="color: var(--color-primary);">{{ myGroup.project?.titre }}</p>
          <div class="text-muted text-sm" style="display: flex; gap: 1rem; margin-top: 0.25rem;">
            <span><i class="pi pi-users" /> Groupe : {{ myGroup.nom }}</span>
            <span><i class="pi pi-user" /> {{ myGroup.membres?.length || 0 }}/{{ myGroup.capacite_max }} membres</span>
          </div>
        </div>
        <button class="btn btn-danger btn-sm ml-auto" @click="quitterGroupe">
          <i class="pi pi-sign-out" /> Quitter
        </button>
      </div>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="card" style="text-align: center; padding: 3rem;">
      <i class="pi pi-spin pi-spinner" style="font-size: 2rem; color: var(--color-primary);" />
      <p class="text-muted" style="margin-top: 1rem;">Chargement des projets...</p>
    </div>

    <!-- Liste des projets -->
    <div v-else class="projects-grid">
      <div v-for="p in projects" :key="p.id" class="card project-card" :class="{ 'is-selected': selectedProjectId === p.id }">
        <!-- En-tête -->
        <div class="card-header-row">
          <div class="tag">{{ p.specialite?.nom || 'Mélangé' }}</div>
          <span v-if="selectedProjectId === p.id" class="badge badge-success">
            <i class="pi pi-check" /> Inscrit
          </span>
        </div>

        <h3 class="project-title">{{ p.titre }}</h3>

        <!-- Description courte -->
        <p class="text-muted text-sm description-excerpt">
          {{ p.description || 'Pas de description fournie.' }}
        </p>

        <!-- Meta -->
        <div class="project-meta">
          <span class="prof"><i class="pi pi-user" /> {{ p.superviseur?.prenom }} {{ p.superviseur?.name }}</span>
          <span class="level">{{ p.niveau }}</span>
        </div>

        <!-- Footer Actions -->
        <div class="project-actions">
          <button @click="openDetails(p)" class="btn btn-outline-primary btn-sm flex-1">
            <i class="pi pi-info-circle" /> Détails
          </button>
          
          <button
            v-if="selectedProjectId === p.id"
            class="btn btn-success btn-sm flex-1"
            disabled
          >
            <i class="pi pi-check" /> Inscrit
          </button>
          <button
            v-else-if="!p.groupes || p.groupes.length === 0"
            class="btn btn-secondary btn-sm flex-1"
            disabled
          >
            Indisponible
          </button>
          <button
            v-else
            class="btn btn-primary btn-sm flex-1"
            @click="inscrire(p)"
            :disabled="selectedProjectId !== null"
          >
            S'inscrire
          </button>
        </div>
      </div>

      <div v-if="projects.length === 0" class="card empty-state">
        <i class="pi pi-briefcase" />
        <p>Aucun projet disponible pour le moment</p>
      </div>
    </div>

    <!-- Modal Détails -->
    <Transition name="fade">
      <div v-if="showModal && detailedProject" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-content card">
          <div class="modal-header">
            <div>
              <div class="tag">{{ detailedProject.specialite?.nom || 'Mélangé' }}</div>
              <h2>{{ detailedProject.titre }}</h2>
            </div>
            <button @click="showModal = false" class="close-btn"><i class="pi pi-times" /></button>
          </div>

          <div class="modal-body scrollbar">
            <div class="info-section">
              <h3><i class="pi pi-align-left" /> Description du projet</h3>
              <p class="full-description">{{ detailedProject.description || 'Aucune description détaillée.' }}</p>
            </div>

            <div class="info-grid">
              <div class="info-item">
                <label>Superviseur</label>
                <div class="val"><i class="pi pi-user" /> {{ detailedProject.superviseur?.prenom }} {{ detailedProject.superviseur?.name }}</div>
                <div class="email"><i class="pi pi-envelope" /> {{ detailedProject.superviseur?.email }}</div>
              </div>
              <div class="info-item">
                <label>Niveau / Année</label>
                <div class="val"><i class="pi pi-bookmark" /> {{ detailedProject.niveau }} ({{ detailedProject.annee_universitaire }})</div>
              </div>
            </div>

            <div class="groups-section">
              <h3><i class="pi pi-users" /> Groupes de travail</h3>
              <div class="groups-list">
                <div v-for="g in detailedProject.groupes" :key="g.id" class="group-detail-row">
                  <div class="g-info">
                    <span class="g-name">{{ g.nom }}</span>
                    <span class="g-members">{{ g.membres?.length || 0 }}/{{ g.capacite_max }} membres</span>
                  </div>
                  <div class="g-status">
                    <span v-if="g.inscription_ouverte && g.membres?.length < g.capacite_max" class="status-open">Ouvert</span>
                    <span v-else class="status-closed">Complet/Fermé</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="showModal = false" class="btn btn-text">Fermer</button>
            <button 
              v-if="!selectedProjectId && detailedProject.groupes?.some(g => g.inscription_ouverte && g.membres?.length < g.capacite_max)"
              @click="inscrire(detailedProject)" 
              class="btn btn-primary"
            >
              Confirmer l'inscription
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.project-selection {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1200px;
}

.highlight-card {
  border: 2px solid var(--color-success);
  background: rgba(16, 185, 129, 0.05);
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);
}

.success-icon {
  width: 3rem;
  height: 3rem;
  background: var(--color-success);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.project-card {
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  gap: 1rem;
  border: 1px solid var(--border-default);
  transition: all 0.3s ease;
}

.project-card:hover {
  transform: translateY(-4px);
  border-color: var(--color-primary);
  box-shadow: 0 8px 25px rgba(0,0,0,0.05);
}

.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tag {
  background: var(--bg-elevated);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
}

.project-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.2;
}

.description-excerpt {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
}

.project-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.5rem;
  border-top: 1px solid var(--border-default);
}

.prof { font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.prof i { color: var(--color-primary); }
.level { font-size: 0.7rem; font-weight: 800; background: var(--color-primary-subtle); color: var(--color-primary); padding: 0.2rem 0.5rem; border-radius: 4px; }

.project-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: auto;
}

.flex-1 { flex: 1; }

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 5rem;
  color: var(--text-muted);
}

.empty-state i { font-size: 4rem; opacity: 0.2; margin-bottom: 1.5rem; display: block; }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-content {
  width: 100%;
  max-width: 700px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  padding: 0;
  overflow: hidden;
  border-radius: 1.5rem;
}

.modal-header {
  padding: 2rem;
  background: var(--bg-elevated);
  border-bottom: 1px solid var(--border-default);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-header h2 { font-size: 1.5rem; font-weight: 800; margin-top: 0.5rem; }

.close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--text-muted);
}

.modal-body {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

.info-section h3, .groups-section h3 {
  font-size: 0.9rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--color-primary);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.full-description {
  line-height: 1.6;
  color: var(--text-secondary);
  background: var(--bg-base);
  padding: 1rem;
  border-radius: 1rem;
  margin-bottom: 2rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.info-item label {
  display: block;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}

.info-item .val { font-weight: 700; font-size: 1rem; }
.info-item .email { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; }

.groups-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.group-detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--bg-elevated);
  border-radius: 1rem;
}

.g-info { display: flex; flex-direction: column; }
.g-name { font-weight: 700; }
.g-members { font-size: 0.8rem; color: var(--text-muted); }

.status-open { color: var(--color-success); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
.status-closed { color: var(--text-muted); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }

.modal-footer {
  padding: 1.5rem 2rem;
  background: var(--bg-elevated);
  border-top: 1px solid var(--border-default);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-text { background: none; border: none; color: var(--text-muted); font-weight: 700; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.ml-auto { margin-left: auto; }
</style>