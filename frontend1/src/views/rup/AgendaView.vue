<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'  // ← TRÈS IMPORTANT !

const auth = useAuthStore()
const toast = useToast()

const reunions = ref<any[]>([])
const professeurs = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const isSaving = ref(false)

const form = ref({
  titre: '',
  description: '',
  date_heure: '',
  lieu: '',
  lien_visio: '',
  participants_ids: [] as number[]
})

// Récupérer TOUS les professeurs
async function fetchProfesseurs() {
  try {
    const res = await api.get('/users', { params: { role: 'professeur' } })
    professeurs.value = res.data
  } catch (error) {
    console.error('Erreur chargement professeurs:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger la liste des professeurs', life: 3000 })
  }
}

// Récupérer les réunions
async function fetchReunions() {
  loading.value = true
  try {
    const res = await api.get('/reunions')
    reunions.value = res.data
  } catch (error) {
    console.error('Erreur chargement réunions:', error)
  } finally {
    loading.value = false
  }
}

// ✅ Créer une réunion et notifier
async function saveReunion() {
  // Validation
  if (!form.value.titre) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez saisir un titre', life: 3000 })
    return
  }
  if (!form.value.description) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez saisir une description', life: 3000 })
    return
  }
  if (!form.value.date_heure) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez choisir une date et heure', life: 3000 })
    return
  }
  if (form.value.participants_ids.length === 0) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez sélectionner au moins un participant', life: 3000 })
    return
  }

  isSaving.value = true

  try {
    const payload = {
      titre: form.value.titre,
      description: form.value.description,
      date_heure: form.value.date_heure,
      lieu: form.value.lieu,
      lien_visio: form.value.lien_visio,
      participants_ids: form.value.participants_ids
    }

    console.log('📤 Envoi réunion:', payload)  // Debug

    const response = await api.post('/reunions', payload)
    
    console.log('✅ Réponse:', response.data)

    toast.add({ 
      severity: 'success', 
      summary: '✅ Réunion planifiée', 
      detail: `La réunion a été créée et ${form.value.participants_ids.length} participant(s) ont été notifiés.`, 
      life: 4000 
    })
    
    // Fermer le modal et réinitialiser le formulaire
    showModal.value = false
    form.value = { titre: '', description: '', date_heure: '', lieu: '', lien_visio: '', participants_ids: [] }
    
    // Recharger la liste des réunions
    await fetchReunions()
    
  } catch (error: any) {
    console.error('❌ Erreur:', error)
    const errorMsg = error.response?.data?.message || error.message || 'Erreur lors de la création'
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: errorMsg, 
      life: 5000 
    })
  } finally {
    isSaving.value = false
  }
}

// --- Calendrier ---
const today = new Date()
const currentYear = ref(today.getFullYear())
const currentMonth = ref(today.getMonth())
const selectedDay = ref<string | null>(null)

const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  let startDow = firstDay.getDay() - 1
  if (startDow < 0) startDow = 6
  
  const days: { date: Date | null; reunions: any[] }[] = []
  
  for (let i = 0; i < startDow; i++) {
    days.push({ date: null, reunions: [] })
  }
  
  for (let d = 1; d <= lastDay.getDate(); d++) {
    const date = new Date(year, month, d)
    const dateStr = date.toISOString().split('T')[0]
    const dayReunions = reunions.value.filter(r => r.date_heure?.startsWith(dateStr))
    days.push({ date, reunions: dayReunions })
  }
  
  return days
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

function isToday(date: Date | null) {
  if (!date) return false
  return date.toDateString() === today.toDateString()
}

function selectDay(date: Date | null) {
  if (!date) return
  selectedDay.value = date.toISOString().split('T')[0] ?? null
}

const selectedDayReunions = computed(() => {
  if (!selectedDay.value) return []
  return reunions.value.filter(r => r.date_heure?.startsWith(selectedDay.value!))
})

const upcomingReunions = computed(() => {
  const todayStr = today.toISOString().split('T')[0] ?? ''
  return reunions.value
    .filter(r => (r.date_heure?.split('T')[0] ?? '') >= todayStr)
    .sort((a, b) => (a.date_heure || '').localeCompare(b.date_heure || ''))
    .slice(0, 5)
})

function formatTime(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchReunions()
  fetchProfesseurs()
})
</script>

<template>
  <div class="calendar-page">
    <div class="page-header animate-fade-in">
      <div>
        <h2 class="page-title-h2">📅 Agenda & Réunions (RUP Spécialité)</h2>
        <p class="text-secondary text-sm">Planifiez des réunions avec les professeurs de votre spécialité.</p>
      </div>
      <button class="btn btn-primary" @click="showModal = true">
        <i class="pi pi-calendar-plus" /> Nouvelle réunion
      </button>
    </div>

    <!-- Calendrier + Sidebar -->
    <div class="calendar-layout animate-fade-in" style="animation-delay: 80ms">
      
      <!-- Calendrier -->
      <div class="calendar-main card">
        <div class="cal-nav">
          <button class="btn btn-ghost btn-icon" @click="prevMonth">
            <i class="pi pi-chevron-left" />
          </button>
          <h3 class="cal-title">{{ monthNames[currentMonth] }} {{ currentYear }}</h3>
          <button class="btn btn-ghost btn-icon" @click="nextMonth">
            <i class="pi pi-chevron-right" />
          </button>
        </div>

        <div class="cal-days-header">
          <div v-for="day in dayNames" :key="day" class="cal-day-name">{{ day }}</div>
        </div>

        <div class="cal-grid">
          <div
            v-for="(cell, i) in calendarDays"
            :key="i"
            class="cal-cell"
            :class="{
              empty: !cell.date,
              today: isToday(cell.date),
              selected: cell.date?.toISOString().split('T')[0] === selectedDay,
              'has-rdv': cell.reunions.length > 0
            }"
            @click="selectDay(cell.date)"
          >
            <span v-if="cell.date" class="cal-date">{{ cell.date.getDate() }}</span>
            <div v-if="cell.reunions.length" class="rdv-indicators">
              <div v-for="r in cell.reunions.slice(0, 3)" :key="r.id" class="rdv-dot" :title="r.titre" />
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="calendar-sidebar">
        
        <!-- Jour sélectionné -->
        <div class="card selected-day-card">
          <div v-if="selectedDay" class="selected-day-header">
            <h4 class="selected-day-title">{{ new Date(selectedDay).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }) }}</h4>
          </div>
          <div v-else class="selected-day-header">
            <h4 class="selected-day-title text-muted">📌 Sélectionnez un jour</h4>
          </div>

          <div v-if="selectedDay && selectedDayReunions.length > 0" class="day-rdv-list">
            <div v-for="r in selectedDayReunions" :key="r.id" class="day-rdv-item">
              <div class="day-rdv-time">{{ formatTime(r.date_heure) }}</div>
              <div class="day-rdv-info">
                <p class="day-rdv-group">{{ r.titre }}</p>
                <p class="text-muted text-xs">{{ r.description?.slice(0, 80) }}...</p>
              </div>
            </div>
          </div>

          <div v-else-if="selectedDay" class="day-empty">
            <i class="pi pi-calendar-times" />
            <p>Aucune réunion ce jour</p>
          </div>
        </div>

        <!-- Prochaines réunions -->
        <div class="card upcoming-card">
          <h4 class="upcoming-title"><i class="pi pi-clock" /> ⏰ Prochaines réunions</h4>
          <div v-if="loading" class="text-center py-4"><i class="pi pi-spin pi-spinner" /></div>
          <div v-else-if="upcomingReunions.length === 0" class="text-sm text-muted text-center py-4">Aucune réunion prévue.</div>
          <div v-else class="upcoming-list">
            <div v-for="r in upcomingReunions" :key="r.id" class="upcoming-item">
              <div class="upcoming-date-badge">
                <span class="udb-day">{{ new Date(r.date_heure).getDate() }}</span>
                <span class="udb-month">{{ monthNames[new Date(r.date_heure).getMonth()]?.slice(0, 3) || '' }}</span>
              </div>
              <div class="upcoming-info">
                <p class="upcoming-group">{{ r.titre }}</p>
                <p class="upcoming-meta text-muted text-xs">{{ formatTime(r.date_heure) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========== MODAL CRÉATION RÉUNION ========== -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card card">
        <div class="modal-header">
          <h3>📋 Planifier une réunion</h3>
          <button class="btn btn-ghost btn-icon" @click="showModal = false"><i class="pi pi-times" /></button>
        </div>

        <div class="modal-body">
          <!-- Titre -->
          <div class="form-group">
            <label class="form-label">Titre de la réunion *</label>
            <input v-model="form.titre" class="form-input" placeholder="Ex: Réunion de pilotage" />
          </div>

          <!-- Description / Objet -->
          <div class="form-group">
            <label class="form-label">Objet / Ordre du jour *</label>
            <textarea v-model="form.description" rows="4" class="form-input" placeholder="Détaillez les points à aborder..." />
          </div>

          <!-- Date, heure, lieu -->
          <div class="form-row" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group flex-1">
              <label class="form-label">Date et heure *</label>
              <input type="datetime-local" v-model="form.date_heure" class="form-input" />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Lieu</label>
              <input v-model="form.lieu" class="form-input" placeholder="Salle, amphi..." />
            </div>
          </div>

          <!-- Lien visio -->
          <div class="form-group">
            <label class="form-label">Lien visioconférence (optionnel)</label>
            <input v-model="form.lien_visio" class="form-input" placeholder="https://meet.google.com/..." />
          </div>

          <!-- ✅ Sélection des participants (SELECT MULTIPLE) -->
          <div class="form-group">
            <label class="form-label">👥 Participants (professeurs) *</label>
            <select v-model="form.participants_ids" multiple class="form-input participant-select" size="5">
              <option v-for="p in professeurs" :key="p.id" :value="p.id">
                👨‍🏫 {{ p.prenom }} {{ p.name }} — {{ p.email }}
              </option>
            </select>
            <p class="text-muted text-xs mt-1">
              💡 Maintenez la touche <kbd>Ctrl</kbd> (Windows) ou <kbd>Cmd</kbd> (Mac) pour sélectionner plusieurs professeurs.
            </p>
            <p class="text-muted text-xs mt-1">
              ✅ Sélectionnés : <strong>{{ form.participants_ids.length }}</strong> participant(s)
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showModal = false">Annuler</button>
          <button 
            class="btn btn-primary" 
            @click="saveReunion" 
            :disabled="!form.titre || !form.description || !form.date_heure || !form.participants_ids.length || isSaving"
          >
            <i v-if="isSaving" class="pi pi-spin pi-spinner" />
            <i v-else class="pi pi-send" />
            {{ isSaving ? 'Création...' : 'Planifier et notifier' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tes styles existants (garde les tiens) */
.calendar-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1300px;
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
}

.calendar-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.5rem;
  align-items: start;
}

.calendar-main {
  padding: 1.5rem;
}

.cal-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.cal-title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 800;
}

.cal-days-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 0.5rem;
  text-align: center;
  font-weight: 700;
  font-size: var(--text-xs);
  color: var(--text-muted);
  text-transform: uppercase;
}

.cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.cal-cell {
  aspect-ratio: 1;
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 0.5rem 0.25rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  position: relative;
  gap: 4px;
}

.cal-cell:not(.empty):hover {
  background: var(--bg-hover);
}

.cal-cell.empty {
  cursor: default;
}

.cal-cell.today {
  background: var(--color-primary-subtle);
}

.cal-cell.selected {
  background: var(--gradient-primary);
}

.cal-date {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-secondary);
}

.cal-cell.today .cal-date {
  color: var(--color-primary-light);
  font-weight: 800;
}

.cal-cell.selected .cal-date {
  color: white;
}

.rdv-indicators {
  display: flex;
  gap: 3px;
  flex-wrap: wrap;
  justify-content: center;
}

.rdv-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-primary);
}

/* Sidebar */
.calendar-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.selected-day-header {
  margin-bottom: 1rem;
}

.selected-day-title {
  font-size: var(--text-sm);
  font-weight: 700;
  text-transform: capitalize;
}

.day-rdv-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.day-rdv-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.75rem;
  background: var(--bg-elevated);
  border-radius: var(--radius-md);
  border-left: 3px solid var(--color-primary);
}

.day-rdv-time {
  font-size: var(--text-sm);
  font-weight: 700;
  white-space: nowrap;
}

.day-rdv-info {
  flex: 1;
}

.day-rdv-group {
  font-size: var(--text-sm);
  font-weight: 600;
}

.day-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  color: var(--text-muted);
}

.upcoming-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: var(--text-sm);
  font-weight: 700;
  margin-bottom: 1rem;
}

.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.upcoming-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.625rem;
  background: var(--bg-elevated);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-default);
}

.upcoming-date-badge {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md);
  background: var(--color-primary-subtle);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.udb-day {
  font-size: var(--text-lg);
  font-weight: 800;
  color: var(--color-primary);
  line-height: 1;
}

.udb-month {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--color-primary);
}

.upcoming-group {
  font-size: var(--text-sm);
  font-weight: 600;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-card {
  width: 100%;
  max-width: 550px;
  padding: 0;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-default);
}

.modal-header h3 {
  font-size: var(--text-lg);
  font-weight: 700;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-height: 60vh;
  overflow-y: auto;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  font-size: var(--text-sm);
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
}

.participant-select {
  min-height: 150px;
  cursor: pointer;
}

.participant-select option {
  padding: 0.5rem;
  border-bottom: 1px solid var(--border-default);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-default);
}

.flex-1 {
  flex: 1;
}

.text-muted {
  color: var(--text-muted);
}

.text-xs {
  font-size: var(--text-xs);
}

kbd {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  font-size: 0.7rem;
}

@media (max-width: 1000px) {
  .calendar-layout {
    grid-template-columns: 1fr;
  }
  .calendar-sidebar {
    flex-direction: row;
    flex-wrap: wrap;
  }
  .calendar-sidebar .card {
    flex: 1;
    min-width: 240px;
  }
}
</style>