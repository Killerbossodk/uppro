<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import type { KanbanCard, KanbanStatus } from '@/magasins/groups'

const props = defineProps<{
  cards: KanbanCard[],
  loading?: boolean
}>()

const emit = defineEmits<{
  move: [cardId: number, newStatus: KanbanStatus]
  add: [card: Omit<KanbanCard, 'id'>]
  generateAI: []
}>()

const toast = useToast()

// Modal d'ajout
const showAddModal = ref(false)
const newCard = ref({
  title: '',
  description: '',
  priority: 'medium' as 'low' | 'medium' | 'high',
  assignee: '',
  due_date: '',
  tags: [] as string[]
})
const tagInput = ref('')

// Colonnes
interface Column {
  id: KanbanStatus
  label: string
  icon: string
  color: string
  accent: string
}

const columns: Column[] = [
  { id: 'backlog', label: 'Backlog', icon: 'pi-list', color: 'var(--text-muted)', accent: 'rgba(107,114,128,0.15)' },
  { id: 'en_cours', label: 'En cours', icon: 'pi-sync', color: 'var(--color-info)', accent: 'rgba(59,130,246,0.12)' },
  { id: 'en_revue', label: 'En revue', icon: 'pi-eye', color: 'var(--color-warning)', accent: 'rgba(245,158,11,0.12)' },
  { id: 'termine', label: 'Terminé', icon: 'pi-check-circle', color: 'var(--color-success)', accent: 'rgba(16,185,129,0.12)' },
]

function getColumnCards(status: KanbanStatus) {
  return props.cards.filter((c) => c.status === status)
}

const priorityMap = {
  low: { label: 'Faible', class: 'badge-info' },
  medium: { label: 'Moyen', class: 'badge-warning' },
  high: { label: 'Haute', class: 'badge-danger' },
}

// Drag & Drop
let draggingId: number | null = null

function onDragStart(e: DragEvent, cardId: number) {
  draggingId = cardId
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(cardId))
  }
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
}

function onDrop(e: DragEvent, status: KanbanStatus) {
  e.preventDefault()
  const id = draggingId ?? Number(e.dataTransfer?.getData('text/plain'))
  if (id) {
    emit('move', id, status)
    draggingId = null
  }
}

function onDragEnter(e: DragEvent) {
  ;(e.currentTarget as HTMLElement)?.classList.add('drop-active')
}

function onDragLeave(e: DragEvent) {
  ;(e.currentTarget as HTMLElement)?.classList.remove('drop-active')
}

// ✅ Ajouter une carte
function addTag() {
  if (tagInput.value.trim() && !newCard.value.tags.includes(tagInput.value.trim())) {
    newCard.value.tags.push(tagInput.value.trim())
    tagInput.value = ''
  }
}

function removeTag(tag: string) {
  newCard.value.tags = newCard.value.tags.filter(t => t !== tag)
}

function submitCard() {
  if (!newCard.value.title.trim()) {
    toast.add({ severity: 'warn', summary: 'Champ requis', detail: 'Le titre est obligatoire', life: 3000 })
    return
  }

  emit('add', {
    title: newCard.value.title,
    description: newCard.value.description,
    status: 'backlog',
    priority: newCard.value.priority,
    assignee: newCard.value.assignee || undefined,
    due_date: newCard.value.due_date || undefined,
    tags: newCard.value.tags.length ? newCard.value.tags : undefined
  })

  // Réinitialiser le formulaire
  newCard.value = {
    title: '',
    description: '',
    priority: 'medium',
    assignee: '',
    due_date: '',
    tags: []
  }
  tagInput.value = ''
  showAddModal.value = false

  toast.add({ severity: 'success', summary: 'Carte ajoutée', detail: 'La tâche a été ajoutée au backlog', life: 3000 })
}

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}
</script>

<template>
  <div class="kanban-board">
    <!-- Bouton Ajouter -->
    <div class="kanban-header" style="gap: 0.5rem;">
      <button 
        class="btn btn-secondary btn-sm" 
        @click="emit('generateAI')" 
        :disabled="props.loading"
        title="L'IA générera automatiquement les phases du projet"
      >
        <i :class="props.loading ? 'pi pi-spin pi-spinner' : 'pi pi-sparkles'" /> 
        {{ props.loading ? 'Génération...' : 'Planifier avec l\'IA' }}
      </button>
      <button class="btn btn-primary btn-sm" @click="showAddModal = true">
        <i class="pi pi-plus" /> Ajouter une tâche
      </button>
    </div>

    <!-- Colonnes -->
    <div class="kanban-columns">
      <div
        v-for="col in columns"
        :key="col.id"
        class="kanban-column"
        @dragover="onDragOver"
        @drop="onDrop($event, col.id)"
        @dragenter="onDragEnter"
        @dragleave="onDragLeave"
      >
        <!-- Column header -->
        <div class="col-header" :style="{ borderTopColor: col.color }">
          <div class="col-title">
            <i :class="`pi ${col.icon}`" :style="{ color: col.color }" />
            <span>{{ col.label }}</span>
          </div>
          <span class="col-count" :style="{ background: col.accent, color: col.color }">
            {{ getColumnCards(col.id).length }}
          </span>
        </div>

        <!-- Cards -->
        <div class="col-cards">
          <div
            v-for="card in getColumnCards(col.id)"
            :key="card.id"
            class="kanban-card"
            draggable="true"
            @dragstart="onDragStart($event, card.id)"
          >
            <!-- Tags -->
            <div v-if="card.tags?.length" class="card-tags">
              <span
                v-for="tag in card.tags"
                :key="tag"
                class="card-tag"
              >{{ tag }}</span>
            </div>

            <!-- Title -->
            <p class="card-title">{{ card.title }}</p>

            <!-- Description -->
            <p v-if="card.description" class="card-desc">{{ card.description }}</p>

            <!-- Footer -->
            <div class="card-footer">
              <span :class="`badge ${priorityMap[card.priority].class}`" style="font-size: 10px;">
                {{ priorityMap[card.priority].label }}
              </span>
              <div class="card-meta">
                <span v-if="card.assignee" class="assignee-chip">{{ card.assignee.substring(0, 2).toUpperCase() }}</span>
                <span v-if="card.due_date" class="due-date">
                  <i class="pi pi-calendar" />
                  {{ formatDate(card.due_date) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="getColumnCards(col.id).length === 0" class="col-empty">
            <i class="pi pi-inbox" />
            <span>Glissez une carte ici</span>
          </div>

          <!-- Add card button (dans la colonne) -->
          <button class="add-card-btn" @click="showAddModal = true">
            <i class="pi pi-plus" />
            Ajouter
          </button>
        </div>
      </div>
    </div>

    <!-- Modal d'ajout -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal-card card">
        <div class="modal-header">
          <h3>➕ Nouvelle tâche</h3>
          <button class="btn btn-ghost btn-icon" @click="showAddModal = false">
            <i class="pi pi-times" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Titre *</label>
            <input v-model="newCard.title" class="form-input" placeholder="Ex: Implémenter l'authentification" />
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="newCard.description" rows="3" class="form-input" placeholder="Description détaillée..." />
          </div>

          <div class="form-row" style="display: flex; gap: 1rem;">
            <div class="form-group flex-1">
              <label class="form-label">Priorité</label>
              <select v-model="newCard.priority" class="form-input">
                <option value="low">Basse</option>
                <option value="medium">Moyenne</option>
                <option value="high">Haute</option>
              </select>
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Assigné à (initiale)</label>
              <input v-model="newCard.assignee" class="form-input" placeholder="Ex: AB" maxlength="2" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Date limite</label>
            <input type="date" v-model="newCard.due_date" class="form-input" />
          </div>

          <div class="form-group">
            <label class="form-label">Tags</label>
            <div class="tags-input">
              <input v-model="tagInput" class="form-input" placeholder="Ex: frontend, backend" @keyup.enter="addTag" />
              <button type="button" class="btn btn-secondary btn-sm" @click="addTag">+ Ajouter</button>
            </div>
            <div class="tags-list">
              <span v-for="tag in newCard.tags" :key="tag" class="tag-item">
                {{ tag }}
                <i class="pi pi-times-circle" @click="removeTag(tag)" />
              </span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showAddModal = false">Annuler</button>
          <button class="btn btn-primary" @click="submitCard" :disabled="!newCard.title">
            <i class="pi pi-check" /> Ajouter
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.kanban-board {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.kanban-header {
  display: flex;
  justify-content: flex-end;
}

.kanban-columns {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  align-items: start;
}

/* Column */
.kanban-column {
  background: var(--bg-surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-default);
  display: flex;
  flex-direction: column;
  gap: 0;
  transition: all var(--transition-fast);
  min-height: 300px;
}

.kanban-column.drop-active {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
}

.col-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1rem 0.875rem;
  border-top: 3px solid transparent;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.col-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--text-primary);
}

.col-count {
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 700;
}

.col-cards {
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  flex: 1;
}

/* Cards */
.kanban-card {
  background: var(--bg-card);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  padding: 0.875rem;
  cursor: grab;
  transition: all var(--transition-fast);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  user-select: none;
}

.kanban-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.kanban-card:active {
  cursor: grabbing;
  transform: scale(0.97);
  opacity: 0.8;
}

.card-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.card-tag {
  padding: 0.1rem 0.5rem;
  background: var(--bg-elevated);
  border-radius: var(--radius-full);
  font-size: 10px;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.card-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.4;
}

.card-desc {
  font-size: var(--text-xs);
  color: var(--text-muted);
  line-height: 1.4;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.assignee-chip {
  width: 26px;
  height: 26px;
  border-radius: var(--radius-full);
  background: var(--gradient-primary);
  color: white;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.due-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 10px;
  color: var(--text-muted);
}

.due-date .pi {
  font-size: 0.6rem;
}

/* Empty state */
.col-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.5rem;
  color: var(--text-muted);
  font-size: var(--text-xs);
}

.col-empty .pi {
  font-size: 1.5rem;
  opacity: 0.4;
}

/* Add card button */
.add-card-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.625rem;
  background: transparent;
  border: 1px dashed var(--border-default);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: var(--text-xs);
  font-family: var(--font-sans);
  cursor: pointer;
  transition: all var(--transition-fast);
  margin-top: 0.25rem;
}

.add-card-btn:hover {
  background: var(--bg-hover);
  color: var(--text-secondary);
  border-style: solid;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  width: 100%;
  max-width: 500px;
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
  gap: 1rem;
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

.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.tags-input {
  display: flex;
  gap: 0.5rem;
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.tag-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: var(--color-primary-subtle);
  color: var(--color-primary-light);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
}

.tag-item i {
  cursor: pointer;
  font-size: 0.7rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-default);
}

@media (max-width: 900px) {
  .kanban-columns {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 580px) {
  .kanban-columns {
    grid-template-columns: 1fr;
  }
}
</style>