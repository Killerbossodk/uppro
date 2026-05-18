<script setup lang="ts">
import { computed } from 'vue'
import type { Milestone } from '@/magasins/groups'

const props = defineProps<{
  milestones: Milestone[]
  projectTitle?: string
}>()

const emit = defineEmits<{
  add: []
  update: [milestone: Milestone]
}>()

const statusConfig: Record<string, { label: string; color: string; icon: string; bg: string }> = {
  pending: { label: 'À faire', color: 'var(--text-muted)', icon: 'pi-circle', bg: 'rgba(107,114,128,0.1)' },
  in_progress: { label: 'En cours', color: 'var(--color-info)', icon: 'pi-sync', bg: 'rgba(59,130,246,0.12)' },
  done: { label: 'Terminé', color: 'var(--color-success)', icon: 'pi-check-circle', bg: 'rgba(16,185,129,0.12)' },
  late: { label: 'En retard', color: 'var(--color-danger)', icon: 'pi-exclamation-circle', bg: 'rgba(239,68,68,0.12)' },
}

const sortedMilestones = computed(() =>
  [...props.milestones].sort((a, b) => a.order - b.order)
)

const progress = computed(() => {
  if (!props.milestones.length) return 0
  const done = props.milestones.filter((m) => m.status === 'done').length
  return Math.round((done / props.milestones.length) * 100)
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function isLate(dateStr: string, status: string) {
  return status !== 'done' && new Date(dateStr) < new Date()
}
</script>

<template>
  <div class="timeline-view">
    <!-- Header progress -->
    <div class="timeline-header">
      <div class="tl-header-left">
        <h4 class="tl-title">
          <i class="pi pi-chart-bar" />
          Timeline — Jalons du projet
        </h4>
        <p v-if="projectTitle" class="tl-project">{{ projectTitle }}</p>
      </div>
      <div class="tl-progress-wrap">
        <div class="tl-progress-text">
          <span class="text-brand font-bold">{{ progress }}%</span>
          <span class="text-muted text-sm">complété</span>
        </div>
        <div class="tl-progress-ring">
          <svg viewBox="0 0 36 36" class="ring-svg">
            <circle class="ring-bg" cx="18" cy="18" r="15.9" />
            <circle
              class="ring-fill"
              cx="18" cy="18" r="15.9"
              :stroke-dasharray="`${progress} ${100 - progress}`"
              stroke-dashoffset="25"
            />
          </svg>
          <span class="ring-label">{{ progress }}%</span>
        </div>
      </div>
    </div>

    <!-- Overall progress bar -->
    <div class="tl-global-progress">
      <div class="progress-bar" style="height: 8px;">
        <div class="progress-fill" :style="{ width: `${progress}%` }" />
      </div>
    </div>

    <!-- Timeline items -->
    <div v-if="sortedMilestones.length === 0" class="tl-empty">
      <i class="pi pi-flag" />
      <p>Aucun jalon défini</p>
      <button class="btn btn-primary btn-sm" @click="emit('add')">
        <i class="pi pi-plus" /> Ajouter un jalon
      </button>
    </div>

    <div v-else class="timeline-items">
      <div
        v-for="(ms, index) in sortedMilestones"
        :key="ms.id"
        class="timeline-item"
        :class="{ done: ms.status === 'done', late: isLate(ms.due_date, ms.status) }"
      >
        <!-- Connector line -->
        <div class="tl-connector">
          <div class="tl-dot" :style="{ background: statusConfig[ms.status]?.color, boxShadow: `0 0 0 4px ${statusConfig[ms.status]?.bg}` }">
            <i :class="`pi ${statusConfig[ms.status]?.icon}`" />
          </div>
          <div v-if="index < sortedMilestones.length - 1" class="tl-line" :class="{ done: ms.status === 'done' }" />
        </div>

        <!-- Content -->
        <div class="tl-content">
          <div class="tl-card">
            <div class="tl-card-header">
              <h5 class="tl-ms-title">{{ ms.title }}</h5>
              <div class="tl-card-actions">
                <span
                  class="badge"
                  :style="{
                    background: statusConfig[ms.status]?.bg,
                    color: statusConfig[ms.status]?.color,
                    border: `1px solid ${statusConfig[ms.status]?.color}40`
                  }"
                >
                  <i :class="`pi ${statusConfig[ms.status]?.icon}`" />
                  {{ statusConfig[ms.status]?.label }}
                </span>
                <button class="btn btn-ghost btn-icon btn-sm" @click="emit('update', ms)">
                  <i class="pi pi-pencil" />
                </button>
              </div>
            </div>

            <p v-if="ms.description" class="tl-ms-desc">{{ ms.description }}</p>

            <div class="tl-ms-footer">
              <span class="tl-date" :class="{ late: isLate(ms.due_date, ms.status) }">
                <i class="pi pi-calendar" />
                {{ formatDate(ms.due_date) }}
                <span v-if="isLate(ms.due_date, ms.status)" class="late-chip">En retard</span>
              </span>
              <span class="tl-order text-muted text-xs">Jalon {{ index + 1 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add milestone button -->
    <button v-if="sortedMilestones.length > 0" class="btn btn-secondary btn-sm tl-add-btn" @click="emit('add')">
      <i class="pi pi-plus" />
      Ajouter un jalon
    </button>
  </div>
</template>

<style scoped>
.timeline-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.timeline-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.tl-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.3rem;
}

.tl-title .pi { color: var(--color-primary-light); }

.tl-project {
  font-size: var(--text-sm);
  color: var(--text-muted);
}

/* Progress ring */
.tl-progress-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.tl-progress-text {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.tl-progress-ring {
  position: relative;
  width: 60px;
  height: 60px;
}

.ring-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.ring-bg {
  fill: none;
  stroke: var(--bg-elevated);
  stroke-width: 3;
}

.ring-fill {
  fill: none;
  stroke: url(#gradient);
  stroke: var(--color-primary);
  stroke-width: 3;
  stroke-linecap: round;
  transition: stroke-dasharray 0.8s ease;
}

.ring-label {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--color-primary-light);
}

/* Timeline items */
.timeline-items {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.timeline-item {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
}

/* Connector */
.tl-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  padding-top: 2px;
}

.tl-dot {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9rem;
  z-index: 1;
  flex-shrink: 0;
  transition: all var(--transition-base);
}

.timeline-item.done .tl-dot {
  animation: pulse-done 2s ease infinite;
}

@keyframes pulse-done {
  0%, 100% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12); }
  50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.06); }
}

.tl-line {
  width: 2px;
  flex: 1;
  min-height: 40px;
  background: var(--border-default);
  margin: 4px 0;
  transition: background var(--transition-slow);
}

.tl-line.done {
  background: var(--color-success);
}

/* Content */
.tl-content {
  flex: 1;
  padding-bottom: 1.25rem;
}

.tl-card {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  padding: 1rem 1.25rem;
  transition: all var(--transition-fast);
}

.timeline-item.done .tl-card {
  border-color: rgba(16, 185, 129, 0.2);
}

.timeline-item.late .tl-card {
  border-color: rgba(239, 68, 68, 0.2);
}

.tl-card:hover {
  border-color: var(--border-hover);
}

.tl-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.tl-card-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tl-ms-title {
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--text-primary);
}

.tl-ms-desc {
  font-size: var(--text-xs);
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
  line-height: 1.5;
}

.tl-ms-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 0.625rem;
}

.tl-date {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: var(--text-xs);
  color: var(--text-muted);
}

.tl-date .pi { font-size: 0.7rem; }

.tl-date.late { color: var(--color-danger); }

.late-chip {
  background: rgba(239, 68, 68, 0.15);
  color: var(--color-danger);
  padding: 0.1rem 0.4rem;
  border-radius: var(--radius-full);
  font-size: 10px;
  font-weight: 600;
}

/* Empty */
.tl-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--text-muted);
  text-align: center;
}

.tl-empty .pi { font-size: 3rem; opacity: 0.3; }

/* Add button */
.tl-add-btn { align-self: flex-start; }

/* Progress bar */
.progress-bar { background: var(--bg-active); border-radius: var(--radius-full); overflow: hidden; }
.progress-fill { height: 100%; background: var(--gradient-primary); border-radius: var(--radius-full); transition: width 0.8s ease; }
</style>
