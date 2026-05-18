<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import api from '@/services/api'
import { useNotificationsStore } from '@/magasins/notifications'

const notifications = useNotificationsStore()
const loading = ref(true)
const defenses = ref<any[]>([])
const showModal = ref(false)
const projects = ref<any[]>([])
const filteredProjects = computed(() => {
  return projects.value.filter(p => p.statut === 'valide')
})
const allStaff = ref<any[]>([])

// Formulaire
const form = ref({
  project_id: '',
  titre: 'Soutenance de Projet',
  date_heure: '',
  lieu: '',
  president_id: '',
  membres_ids: [] as string[],
  piece_jointe: null as File | null
})

onMounted(async () => {
  await fetchData()
  await fetchResources()
})

async function fetchData() {
  loading.value = true
  try {
    const res = await api.get('/meetings')
    defenses.value = res.data.filter((m: any) => m.type === 'soutenance')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function fetchResources() {
  try {
    const [pRes, uRes] = await Promise.all([
      api.get('/projects'),
      api.get('/users')
    ])
    projects.value = pRes.data
    // On inclut les professeurs ET les RUP Spécialité (qui sont aussi des profs)
    allStaff.value = uRes.data.filter((u: any) => u.role === 'professeur' || u.role === 'rup_specialite')
  } catch (e) {
    console.error(e)
  }
}

async function scheduleDefense() {
  if (!form.value.project_id || !form.value.president_id || !form.value.date_heure) {
    alert('Veuillez remplir les informations obligatoires (Projet, Président, Date).')
    return
  }

  try {
    const project = projects.value.find(p => p.id === form.value.project_id)
    const groups = project?.groupes || []
    
    if (groups.length === 0) {
      alert("Ce projet n'a pas encore d'étudiants inscrits (groupes). Impossible de programmer une soutenance.")
      return
    }

    const formData = new FormData()
    formData.append('project_id', form.value.project_id)
    formData.append('titre', form.value.titre)
    formData.append('date_heure', form.value.date_heure)
    formData.append('lieu', form.value.lieu || 'Non défini')
    formData.append('type', 'soutenance')
    
    groups.forEach((g: any, index: number) => {
      formData.append(`group_ids[${index}]`, g.id.toString())
    })
    
    if (form.value.piece_jointe) {
      formData.append('piece_jointe', form.value.piece_jointe)
    }

    const mRes = await api.post('/meetings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    await api.post('/juries', {
      meeting_id: mRes.data.id,
      president_id: form.value.president_id,
      membres_ids: form.value.membres_ids,
      salle: form.value.lieu || 'Non défini',
      date_heure: form.value.date_heure
    })

    showModal.value = false
    await fetchData()
    alert('Soutenance programmée avec succès et convocation envoyée !')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur lors de la programmation')
  }
}

async function publishToRupSpe(defense: any) {
  if (!confirm('Voulez-vous transmettre ces résultats au RUP Spécialité pour publication ?')) return
  try {
    await api.post(`/meetings/${defense.id}/grade/publish`)
    defense.published_to_rup_spe = true
    alert('Résultats transmis !')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.value.piece_jointe = target.files[0]
  } else {
    form.value.piece_jointe = null
  }
}
</script>

<template>
  <div class="defenses-page">
    <div class="page-container">
      <!-- Header -->
      <header class="page-header">
        <div class="title-section">
          <div class="accent"></div>
          <div>
            <h1>Gestion des Soutenances</h1>
            <p>Planification des sessions et composition des jurys.</p>
          </div>
        </div>
        <button @click="showModal = true" class="btn-primary">
          <i class="pi pi-plus-circle"></i>
          Programmer une soutenance
        </button>
      </header>

      <!-- Dashboard Stats -->
      <div class="stats-row">
        <div class="stat-item card">
          <div class="icon primary"><i class="pi pi-calendar"></i></div>
          <div class="details">
            <span class="val">{{ defenses.length }}</span>
            <span class="lab">Total</span>
          </div>
        </div>
        <div class="stat-item card">
          <div class="icon success"><i class="pi pi-check"></i></div>
          <div class="details">
            <span class="val">{{ defenses.filter(d => d.statut_soutenance === 'termine').length }}</span>
            <span class="lab">Terminées</span>
          </div>
        </div>
        <div class="stat-item card">
          <div class="icon info"><i class="pi pi-play"></i></div>
          <div class="details">
            <span class="val">{{ defenses.filter(d => d.statut_soutenance === 'en_cours').length }}</span>
            <span class="lab">En cours</span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <main class="main-content">
        <div v-if="loading" class="loader">
          <i class="pi pi-spin pi-spinner"></i>
          Chargement des données...
        </div>

        <div v-else-if="defenses.length === 0" class="empty-view card">
          <i class="pi pi-calendar-times"></i>
          <h2>Aucune soutenance programmée</h2>
          <p>Le calendrier est vide pour le moment.</p>
          <button @click="showModal = true" class="btn-primary">Nouvelle Soutenance</button>
        </div>

        <div v-else class="defense-list">
          <div v-for="def in defenses" :key="def.id" class="defense-card card">
            <div class="card-left">
              <div :class="['status-indicator', def.statut_soutenance]">
                <i :class="def.statut_soutenance === 'termine' ? 'pi pi-check' : 'pi pi-clock'"></i>
              </div>
              <div class="info">
                <div class="top">
                  <h3>{{ def.titre }}</h3>
                  <span v-if="def.statut_soutenance === 'en_cours'" class="live-pill">LIVE</span>
                </div>
                <div class="meta">
                  <span><i class="pi pi-calendar"></i> {{ new Date(def.date_heure).toLocaleDateString() }}</span>
                  <span><i class="pi pi-clock"></i> {{ new Date(def.date_heure).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) }}</span>
                  <span><i class="pi pi-map-marker"></i> {{ def.lieu || 'Salle Virtuelle' }}</span>
                </div>
              </div>
            </div>

            <div class="card-actions">
              <div v-if="def.published_to_rup_spe" class="published-tag">
                <i class="pi pi-send"></i> Transmis
              </div>
              <button v-if="def.statut_soutenance === 'termine' && !def.published_to_rup_spe" 
                      @click="publishToRupSpe(def)" class="btn-outline">
                Transmettre
              </button>
              <router-link :to="'/soutenance-live/' + def.id" class="btn-view">
                Accéder au Live
              </router-link>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Modal Modal -->
    <Transition name="modal">
      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-box">
          <div class="modal-header">
            <h2>Planifier une session</h2>
            <button @click="showModal = false" class="btn-close"><i class="pi pi-times"></i></button>
          </div>

          <div class="modal-body">
            <div class="form-grid">
              <div class="field">
                <label>Projet Académique</label>
                <select v-model="form.project_id" class="select-field">
                  <option value="" disabled>Choisir un projet</option>
                  <option v-for="p in filteredProjects" :key="p.id" :value="p.id">{{ p.titre }}</option>
                </select>
              </div>
              <div class="field">
                <label>Lieu / Salle</label>
                <input v-model="form.lieu" type="text" class="input-field" placeholder="Ex: Salle 202 ou Lien Zoom">
              </div>
              <div class="field">
                <label>Date & Heure</label>
                <input v-model="form.date_heure" type="datetime-local" class="input-field">
              </div>
              <div class="field full">
                <label>Convocation / Consignes (Fiche PDF)</label>
                <input type="file" @change="handleFileUpload" class="input-field file-input" accept=".pdf,.doc,.docx">
                <small class="text-muted mt-1">Ce document sera automatiquement envoyé aux étudiants.</small>
              </div>
            </div>

            <div class="jury-config">
              <h3><i class="pi pi-users"></i> Composition du Jury</h3>
              
              <div class="field full">
                <label>Président du Jury (Modérateur)</label>
                <select v-model="form.president_id" class="select-field president">
                  <option value="" disabled>Choisir un président</option>
                  <option v-for="prof in allStaff" :key="prof.id" :value="prof.id">
                    {{ (prof.prenom + ' ' + prof.name).trim() }} ({{ prof.role === 'rup_specialite' ? 'RUP' : 'Prof' }})
                  </option>
                </select>
              </div>

              <div class="field full">
                <label>Autres Examinateurs</label>
                <div class="staff-picker">
                  <div v-for="prof in allStaff.filter(p => p.id !== form.president_id)" :key="prof.id" 
                       :class="['staff-item', { picked: form.membres_ids.includes(prof.id) }]"
                       @click="form.membres_ids.includes(prof.id) ? form.membres_ids = form.membres_ids.filter(id => id !== prof.id) : form.membres_ids.push(prof.id)">
                    <div class="check">
                      <i v-if="form.membres_ids.includes(prof.id)" class="pi pi-check"></i>
                    </div>
                    <span class="name">{{ (prof.prenom + ' ' + prof.name).trim() }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="showModal = false" class="btn-cancel">Annuler</button>
            <button @click="scheduleDefense" class="btn-confirm">Confirmer la programmation</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.defenses-page {
  min-height: 100vh;
  background-color: var(--bg-base);
  color: var(--text-primary);
  font-family: var(--font-sans);
}

.page-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 3rem;
}

.title-section {
  display: flex;
  gap: 1rem;
}

.accent {
  width: 6px;
  background-color: var(--color-primary);
  border-radius: 6px;
}

h1 {
  font-size: 2rem;
  font-weight: 800;
  text-transform: uppercase;
  margin: 0;
  color: var(--color-primary);
}

.page-header p {
  color: var(--text-muted);
  margin-top: 0.25rem;
}

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.5rem;
}

.stat-item .icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.icon.primary { background: var(--color-primary-subtle); color: var(--color-primary); }
.icon.success { background: rgba(16, 185, 129, 0.1); color: var(--color-success); }
.icon.info { background: rgba(59, 130, 246, 0.1); color: var(--color-info); }

.stat-item .val {
  display: block;
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1;
}

.stat-item .lab {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
}

/* List */
.defense-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.defense-card {
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: transform 0.2s;
}

.defense-card:hover {
  transform: translateY(-2px);
  border-color: var(--color-primary-light);
}

.card-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.status-indicator {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.status-indicator.planifie { background: var(--text-muted); }
.status-indicator.en_cours { background: var(--color-info); animation: pulse 2s infinite; }
.status-indicator.termine { background: var(--color-success); }

.info .top {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.25rem;
}

.info h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0;
}

.live-pill {
  background: var(--color-info);
  color: white;
  font-size: 0.6rem;
  font-weight: 900;
  padding: 0.1rem 0.5rem;
  border-radius: 4px;
}

.meta {
  display: flex;
  gap: 1.25rem;
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
}

.meta span { display: flex; align-items: center; gap: 0.4rem; }
.meta i { color: var(--color-primary); }

.card-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-box {
  background: white;
  width: 100%;
  max-width: 800px;
  border-radius: 1.5rem;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-default);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 { font-size: 1.25rem; font-weight: 800; text-transform: uppercase; margin: 0; }

.btn-close {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.25rem;
  cursor: pointer;
}

.modal-body {
  padding: 2rem;
  overflow-y: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.field { display: flex; flex-direction: column; gap: 0.5rem; }
.field.full { grid-column: span 2; }

.field label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
}

.input-field, .select-field {
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-default);
  border-radius: 0.75rem;
  font-family: var(--font-sans);
  font-size: 0.95rem;
  outline: none;
}

.select-field.president {
  background-color: var(--color-primary);
  color: white;
  font-weight: 700;
  border: none;
}

.jury-config {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--border-default);
}

.jury-config h3 {
  font-size: 0.9rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--color-primary);
  margin-bottom: 1.5rem;
}

.staff-picker {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.staff-item {
  padding: 0.75rem;
  border: 1px solid var(--border-default);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.staff-item.picked {
  background: var(--color-primary-subtle);
  border-color: var(--color-primary);
}

.staff-item .check {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 4px;
  background: var(--bg-base);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.7rem;
}

.staff-item.picked .check { background: var(--color-primary); }
.staff-item .name { font-size: 0.85rem; font-weight: 600; }

.modal-footer {
  padding: 1.5rem 2rem;
  background: var(--bg-elevated);
  border-top: 1px solid var(--border-default);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Buttons */
.btn-primary {
  background-color: var(--color-primary);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-view {
  background-color: var(--text-primary);
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  text-decoration: none;
}

.btn-outline {
  background: none;
  border: 1px solid var(--color-primary);
  color: var(--color-primary);
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-confirm {
  background-color: var(--color-primary);
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-cancel {
  background: none;
  border: none;
  color: var(--text-muted);
  font-weight: 700;
  cursor: pointer;
}

.published-tag {
  color: var(--color-success);
  font-size: 0.75rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

/* Animations */
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.05); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}

.empty-view {
  padding: 5rem;
  text-align: center;
}

.empty-view i { font-size: 3rem; color: var(--border-default); margin-bottom: 1.5rem; display: block; }

.loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 5rem;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .page-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1.25rem;
    margin-bottom: 2rem;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .page-header .btn-primary {
    justify-content: center;
    width: 100%;
  }

  .stats-row {
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .defense-card {
    flex-direction: column;
    align-items: stretch;
    gap: 1.25rem;
    padding: 1.25rem;
  }

  .card-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .status-indicator {
    width: 2.5rem;
    height: 2.5rem;
  }

  .meta {
    flex-direction: column;
    gap: 0.5rem;
  }

  .card-actions {
    justify-content: flex-end;
    width: 100%;
    border-top: 1px solid var(--border-default);
    padding-top: 1rem;
  }

  .modal-box {
    max-height: 95vh;
    border-radius: 1rem;
  }

  .modal-header {
    padding: 1rem 1.5rem;
  }

  .modal-body {
    padding: 1.25rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .field.full {
    grid-column: span 1;
  }

  .staff-picker {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .modal-footer {
    padding: 1rem 1.25rem;
    flex-direction: column-reverse;
    gap: 0.75rem;
  }

  .modal-footer button {
    width: 100%;
    text-align: center;
    padding: 0.75rem;
  }
}
</style>
