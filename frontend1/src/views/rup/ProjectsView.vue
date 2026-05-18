<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const searchQuery = ref('')
const filterStatus = ref('all')

const projects = ref([
  {
    id: 1, title: 'Système IoT Smart Home', group: 'Groupe Sigma', professor: 'Pr. Khelifa',
    level: 'Master 2', specialty: 'RT', submitted: '2026-04-20', status: 'en_attente',
    description: 'Infrastructure IoT pour la domotique intelligente avec capteurs et tableaux de bord temps réel.',
    members: 4,
  },
  {
    id: 2, title: 'App mobile e-santé', group: 'Groupe Omega', professor: 'Pr. Bouazza',
    level: 'Licence 3', specialty: 'SI', submitted: '2026-04-22', status: 'en_attente',
    description: 'Application de suivi médical avec module de téléconsultation et agenda médecin.',
    members: 3,
  },
  {
    id: 3, title: 'Plateforme RH distribuée', group: 'Groupe Pi', professor: 'Pr. Boudjemaa',
    level: 'Master 1', specialty: 'GL', submitted: '2026-04-24', status: 'en_attente',
    description: 'ERP RH multi-sites avec gestion des congés, compétences et évaluations.',
    members: 4,
  },
  {
    id: 4, title: 'Système de vote blockchain', group: 'Groupe Lambda', professor: 'Pr. Khelifa',
    level: 'Master 2', specialty: 'SRT', submitted: '2026-04-18', status: 'valide',
    description: 'Vote électronique décentralisé sur Ethereum avec audit trail.',
    members: 3,
  },
  {
    id: 5, title: 'Gestion de stock intelligent', group: 'Groupe Theta', professor: 'Pr. Hamid',
    level: 'Licence 3', specialty: 'GL', submitted: '2026-04-15', status: 'rejete',
    description: 'Système de gestion d\'entrepôt avec prédiction de réapprovisionnement.',
    members: 3,
  },
])

const statusConfig: Record<string, { label: string; cls: string; icon: string }> = {
  en_attente: { label: 'En attente', cls: 'badge-warning', icon: 'pi-clock' },
  valide: { label: 'Validé', cls: 'badge-success', icon: 'pi-check-circle' },
  rejete: { label: 'Rejeté', cls: 'badge-danger', icon: 'pi-times-circle' },
}

const filtered = computed(() => {
  let list = projects.value
  if (filterStatus.value !== 'all') list = list.filter((p) => p.status === filterStatus.value)
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(
      (p) => p.title.toLowerCase().includes(q) || p.group.toLowerCase().includes(q),
    )
  }
  return list
})

// Approval modal
const showModal = ref(false)
const selectedProject = ref<typeof projects.value[0] | null>(null)
const action = ref<'valide' | 'rejete'>('valide')
const comment = ref('')

function openAction(p: typeof projects.value[0], act: 'valide' | 'rejete') {
  selectedProject.value = p
  action.value = act
  comment.value = ''
  showModal.value = true
}

function confirmAction() {
  if (selectedProject.value) {
    const p = projects.value.find((x) => x.id === selectedProject.value!.id)
    if (p) p.status = action.value
  }
  showModal.value = false
}
</script>

<template>
  <div class="rup-projects-page">
    <!-- Header -->
    <div class="page-header animate-fade-in">
      <div>
        <h2 class="page-title-h2">Validation des projets</h2>
        <p class="text-secondary text-sm">
          {{ projects.filter(p => p.status === 'en_attente').length }} projets en attente · {{ projects.filter(p => p.status === 'valide').length }} validés
        </p>
      </div>
    </div>

    <!-- Status summary -->
    <div class="status-summary animate-fade-in" style="animation-delay: 60ms">
      <div class="ss-card pending">
        <i class="pi pi-clock" />
        <span class="ss-val">{{ projects.filter(p => p.status === 'en_attente').length }}</span>
        <span class="ss-lbl">En attente</span>
      </div>
      <div class="ss-card validated">
        <i class="pi pi-check-circle" />
        <span class="ss-val">{{ projects.filter(p => p.status === 'valide').length }}</span>
        <span class="ss-lbl">Validés</span>
      </div>
      <div class="ss-card rejected">
        <i class="pi pi-times-circle" />
        <span class="ss-val">{{ projects.filter(p => p.status === 'rejete').length }}</span>
        <span class="ss-lbl">Rejetés</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="card filters-bar animate-fade-in" style="animation-delay: 100ms">
      <div class="form-input-icon" style="flex: 1; max-width: 300px;">
        <i class="pi pi-search" />
        <input v-model="searchQuery" type="text" class="form-input" placeholder="Rechercher…" id="rup-search" />
      </div>
      <div class="filter-tabs">
        <button class="filter-tab" :class="{ active: filterStatus === 'all' }" @click="filterStatus = 'all'">
          Tous
        </button>
        <button class="filter-tab" :class="{ active: filterStatus === 'en_attente' }" @click="filterStatus = 'en_attente'">
          En attente
        </button>
        <button class="filter-tab" :class="{ active: filterStatus === 'valide' }" @click="filterStatus = 'valide'">
          Validés
        </button>
        <button class="filter-tab" :class="{ active: filterStatus === 'rejete' }" @click="filterStatus = 'rejete'">
          Rejetés
        </button>
      </div>
    </div>

    <!-- Projects list -->
    <div class="projects-list animate-fade-in" style="animation-delay: 160ms">
      <div v-for="(p, i) in filtered" :key="p.id" class="project-validation-card card">
        <!-- Left: info -->
        <div class="pvc-left">
          <div class="pvc-header">
            <h3 class="pvc-title">{{ p.title }}</h3>
            <span :class="`badge ${statusConfig[p.status]?.cls}`">
              <i :class="`pi ${statusConfig[p.status]?.icon}`" />
              {{ statusConfig[p.status]?.label }}
            </span>
          </div>
          <p class="pvc-desc text-secondary text-sm">{{ p.description }}</p>
          <div class="pvc-meta">
            <span><i class="pi pi-users" /> {{ p.group }}</span>
            <span><i class="pi pi-user" /> {{ p.professor }}</span>
            <span><i class="pi pi-bookmark" /> {{ p.level }} · {{ p.specialty }}</span>
            <span><i class="pi pi-users" /> {{ p.members }} membres</span>
            <span><i class="pi pi-calendar" /> Soumis le {{ p.submitted }}</span>
          </div>
        </div>

        <!-- Right: actions -->
        <div class="pvc-right">
          <template v-if="p.status === 'en_attente'">
            <button
              class="btn btn-success btn-sm"
              @click="openAction(p, 'valide')"
              :id="`btn-validate-${p.id}`"
            >
              <i class="pi pi-check" /> Valider
            </button>
            <button
              class="btn btn-danger btn-sm"
              @click="openAction(p, 'rejete')"
              :id="`btn-reject-${p.id}`"
            >
              <i class="pi pi-times" /> Rejeter
            </button>
            <button class="btn btn-ghost btn-sm">
              <i class="pi pi-eye" /> Voir le dossier
            </button>
          </template>
          <template v-else>
            <div class="final-status" :class="p.status">
              <i :class="`pi ${statusConfig[p.status]?.icon}`" />
              {{ statusConfig[p.status]?.label }}
            </div>
            <button class="btn btn-ghost btn-sm" @click="openAction(p, p.status === 'valide' ? 'rejete' : 'valide')">
              <i class="pi pi-refresh" /> Modifier
            </button>
          </template>
        </div>
      </div>

      <div v-if="filtered.length === 0" class="empty-state card">
        <i class="pi pi-check-circle" />
        <p>Aucun projet à afficher</p>
      </div>
    </div>

    <!-- Confirmation modal -->
    <Transition name="page">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card card animate-fade-in">
          <div class="modal-header" :class="action === 'valide' ? 'header-success' : 'header-danger'">
            <div class="modal-header-icon">
              <i :class="`pi ${action === 'valide' ? 'pi-check-circle' : 'pi-times-circle'}`" />
            </div>
            <h3>{{ action === 'valide' ? 'Valider ce projet' : 'Rejeter ce projet' }}</h3>
            <button class="btn btn-ghost btn-icon" @click="showModal = false">
              <i class="pi pi-times" />
            </button>
          </div>

          <div class="modal-body" v-if="selectedProject">
            <div class="modal-project-info">
              <p class="font-bold">{{ selectedProject.title }}</p>
              <p class="text-muted text-sm">{{ selectedProject.group }} — {{ selectedProject.professor }}</p>
            </div>

            <div class="form-group">
              <label class="form-label">
                Commentaire {{ action === 'rejete' ? '(obligatoire)' : '(optionnel)' }}
              </label>
              <textarea
                v-model="comment"
                class="form-input"
                style="min-height: 100px; resize: vertical;"
                :placeholder="action === 'valide' ? 'Félicitations ! Le projet est conforme…' : 'Motif du rejet : le cahier des charges est insuffisant…'"
                id="validation-comment"
              />
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showModal = false">Annuler</button>
            <button
              class="btn"
              :class="action === 'valide' ? 'btn-success' : 'btn-danger'"
              :disabled="action === 'rejete' && !comment.trim()"
              @click="confirmAction"
              id="btn-confirm-action"
            >
              <i :class="`pi ${action === 'valide' ? 'pi-check' : 'pi-times'}`" />
              {{ action === 'valide' ? 'Confirmer la validation' : 'Confirmer le rejet' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.rup-projects-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; margin-bottom: 0.3rem; }

/* Status summary */
.status-summary { display: flex; gap: 1rem; }

.ss-card {
  flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
  padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border-default);
  background: var(--bg-surface);
}

.ss-card .pi { font-size: 1.5rem; margin-bottom: 0.25rem; }
.ss-card.pending .pi { color: var(--color-warning); }
.ss-card.validated .pi { color: var(--color-success); }
.ss-card.rejected .pi { color: var(--color-danger); }

.ss-val { font-family: var(--font-display); font-size: var(--text-4xl); font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; }
.ss-lbl { font-size: var(--text-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

/* Filters */
.filters-bar { display: flex; align-items: center; gap: 1rem; padding: 0.875rem 1.25rem; flex-wrap: wrap; }
.form-input-icon { position: relative; }
.form-input-icon .pi { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
.form-input-icon .form-input { padding-left: 2.5rem; }
.filter-tabs { display: flex; gap: 0.25rem; }
.filter-tab { padding: 0.4rem 0.875rem; border-radius: var(--radius-full); border: 1px solid var(--border-default); background: transparent; color: var(--text-secondary); font-size: var(--text-sm); font-family: var(--font-sans); cursor: pointer; transition: all var(--transition-fast); }
.filter-tab:hover { background: var(--bg-hover); }
.filter-tab.active { background: var(--color-primary-subtle); color: var(--color-primary-light); border-color: var(--color-primary); }

/* Projects list */
.projects-list { display: flex; flex-direction: column; gap: 1rem; }

.project-validation-card {
  display: flex; align-items: flex-start; gap: 1.5rem;
  padding: 1.25rem 1.5rem; flex-wrap: wrap;
}

.pvc-left { flex: 1; min-width: 300px; }
.pvc-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.625rem; flex-wrap: wrap; }
.pvc-title { font-size: var(--text-base); font-weight: 700; }
.pvc-desc { line-height: 1.5; margin: 0.5rem 0 0.875rem; }
.pvc-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: var(--text-sm); color: var(--text-muted); }
.pvc-meta .pi { font-size: 0.7rem; }

.pvc-right { display: flex; flex-direction: column; gap: 0.625rem; flex-shrink: 0; min-width: 160px; }

.btn-success { background: rgba(16,185,129,0.15); color: var(--color-success); border: 1px solid rgba(16,185,129,0.3); }
.btn-success:hover { background: rgba(16,185,129,0.25); }
.btn-danger { background: rgba(239,68,68,0.15); color: var(--color-danger); border: 1px solid rgba(239,68,68,0.3); }
.btn-danger:hover { background: rgba(239,68,68,0.25); }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

.final-status {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.875rem; border-radius: var(--radius-md);
  font-size: var(--text-sm); font-weight: 600;
}
.final-status.valide { background: rgba(16,185,129,0.12); color: var(--color-success); }
.final-status.rejete { background: rgba(239,68,68,0.12); color: var(--color-danger); }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: var(--text-muted); text-align: center; }
.empty-state .pi { font-size: 3rem; opacity: 0.3; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-card { width: 100%; max-width: 520px; padding: 0; overflow: hidden; }
.modal-header { display: flex; align-items: center; gap: 0.875rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-default); }
.modal-header.header-success { background: rgba(16,185,129,0.08); }
.modal-header.header-danger { background: rgba(239,68,68,0.08); }
.modal-header-icon { width: 36px; height: 36px; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.header-success .modal-header-icon { background: rgba(16,185,129,0.15); color: var(--color-success); }
.header-danger .modal-header-icon { background: rgba(239,68,68,0.15); color: var(--color-danger); }
.modal-header h3 { font-size: var(--text-base); font-weight: 700; flex: 1; }
.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; }
.modal-project-info { padding: 1rem; background: var(--bg-elevated); border-radius: var(--radius-md); }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-default); }

@media (max-width: 768px) { .status-summary { flex-direction: row; flex-wrap: wrap; } .project-validation-card { flex-direction: column; } .pvc-right { flex-direction: row; width: 100%; } }
</style>
