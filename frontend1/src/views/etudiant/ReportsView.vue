<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'primevue/usetoast'
import ReportDropzone from '@/components/ReportDropzone.vue'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const myReports = ref<any[]>([])
const myGroup = ref<any>(null)
const showDropzone = ref<number | null>(null)

async function fetchMyGroup() {
  try {
    const res = await api.get('/groups')
    const groups = Array.isArray(res.data) ? res.data : []
    if (groups.length > 0) {
      myGroup.value = groups[0]
      await fetchReports()
    }
  } catch (error) {
    console.error('Erreur chargement groupe:', error)
  } finally {
    loading.value = false
  }
}

async function fetchReports() {
  if (!myGroup.value) return
  try {
    const res = await api.get(`/groups/${myGroup.value.id}/reports`)
    myReports.value = Array.isArray(res.data) ? res.data : []
  } catch (error) {
    console.error('Erreur chargement rapports:', error)
  }
}

const statusConfig: Record<string, { label: string; cls: string; icon: string }> = {
  soumis: { label: 'En attente', cls: 'badge-warning', icon: 'pi-clock' },
  valide: { label: 'Validé', cls: 'badge-success', icon: 'pi-check-circle' },
  rejete: { label: 'Rejeté', cls: 'badge-danger', icon: 'pi-times-circle' },
}

async function onFileUploaded(file: File, reportType: string) {
  if (!myGroup.value) return
  const formData = new FormData()
  formData.append('group_id', myGroup.value.id.toString())
  formData.append('titre', `Rapport ${reportType === 'intermediaire' ? 'intermédiaire' : reportType === 'final' ? 'final' : 'corrigé'}`)
  formData.append('type', reportType)
  formData.append('fichier', file)
  try {
    await api.post('/reports', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Rapport déposé', detail: 'Votre rapport a été envoyé avec succès.', life: 3000 })
    await fetchReports()
    showDropzone.value = null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.message || 'Impossible de déposer le rapport', life: 4000 })
  }
}

function voirRapport(reportId: number) { router.push(`/rapports/${reportId}`) }
function getReportStatus(type: string) { return myReports.value.find(r => r.type === type)?.statut || null }
function getReportId(type: string) { return myReports.value.find(r => r.type === type)?.id || null }

const reportTypes = [
  { id: 1, title: 'Rapport intermédiaire', type: 'intermediaire', desc: 'Avancement à mi-parcours du projet' },
  { id: 2, title: 'Rapport final', type: 'final', desc: 'Version complète et définitive' },
  { id: 3, title: 'Rapport corrigé', type: 'corrige', desc: 'Version corrigée après retours' },
]

onMounted(() => { fetchMyGroup() })
</script>

<template>
  <div class="student-reports">

    <!-- EN-TÊTE -->
    <div class="page-header animate-fade-in">
      <div>
        <h2 class="page-title-h2">Mes Rapports</h2>
        <p class="text-muted text-sm">Déposez et suivez vos rapports de projet.</p>
      </div>
    </div>

    <!-- BANNIÈRE INFO -->
    <div class="info-banner animate-fade-in" style="animation-delay:60ms">
      <i class="pi pi-info-circle info-banner__icon" />
      <p>
        <strong>Formats acceptés :</strong> PDF, DOCX &nbsp;·&nbsp;
        <strong>Taille max :</strong> 10 Mo par fichier.
        Chaque rapport déposé est envoyé à votre encadrant.
      </p>
    </div>

    <!-- CHARGEMENT -->
    <div v-if="loading" class="card empty-state">
      <i class="pi pi-spin pi-spinner empty-state__icon" style="color:var(--color-primary)" />
      <p class="text-muted mt-2">Chargement...</p>
    </div>

    <!-- PAS DE GROUPE -->
    <div v-else-if="!myGroup" class="card empty-state">
      <i class="pi pi-users empty-state__icon" style="opacity:0.3" />
      <p class="text-muted mt-2">Vous n'êtes dans aucun groupe.</p>
    </div>

    <!-- LISTE DES RAPPORTS -->
    <div v-else class="reports-grid animate-fade-in" style="animation-delay:120ms">
      <div v-for="rt in reportTypes" :key="rt.id" class="report-card card">

        <!-- Ligne supérieure : icône + méta + badge -->
        <div class="rc-header">
          <div class="rc-icon" :class="`rc-icon--${getReportStatus(rt.type) || 'pending'}`">
            <i :class="`pi ${statusConfig[getReportStatus(rt.type) || '']?.icon || 'pi-upload'}`" />
          </div>

          <div class="rc-meta">
            <h3 class="rc-title">{{ rt.title }}</h3>
            <p class="rc-desc text-muted text-xs">{{ rt.desc }}</p>
          </div>

          <div class="rc-badge-wrap">
            <span v-if="getReportStatus(rt.type)" :class="`badge ${statusConfig[getReportStatus(rt.type)]?.cls}`">
              {{ statusConfig[getReportStatus(rt.type)]?.label }}
            </span>
            <span v-else class="badge badge-info">À déposer</span>
          </div>
        </div>

        <!-- Séparateur -->
        <div class="rc-divider" />

        <!-- Feedback rejet -->
        <div v-if="getReportStatus(rt.type) === 'rejete'" class="rc-feedback">
          <div class="rc-feedback__header">
            <i class="pi pi-comment" />
            <span class="text-sm font-medium">Feedback encadrant</span>
          </div>
          <p class="rc-feedback__body">Votre rapport a été rejeté. Veuillez le corriger et le soumettre à nouveau.</p>
        </div>

        <!-- Zone de dépôt -->
        <Transition name="slide-down">
          <div v-if="showDropzone === rt.id" class="rc-dropzone">
            <ReportDropzone
              accept=".pdf,.doc,.docx"
              :max-size-mb="10"
              :label="`Déposer ${rt.title}`"
              @upload="(file) => onFileUploaded(file, rt.type)"
            />
          </div>
        </Transition>

        <!-- Actions -->
        <div class="rc-actions">
          <button
            v-if="getReportStatus(rt.type) !== 'valide'"
            class="btn btn-sm"
            :class="showDropzone === rt.id ? 'btn-secondary' : 'btn-primary'"
            @click="showDropzone = showDropzone === rt.id ? null : rt.id"
          >
            <i :class="`pi ${showDropzone === rt.id ? 'pi-times' : 'pi-upload'}`" />
            {{ showDropzone === rt.id ? 'Annuler' : (getReportStatus(rt.type) ? 'Nouvelle version' : 'Déposer') }}
          </button>

          <button
            v-if="getReportId(rt.type)"
            class="btn btn-ghost btn-sm"
            @click="voirRapport(getReportId(rt.type))"
          >
            <i class="pi pi-eye" /> Voir
          </button>

          <span v-else-if="getReportStatus(rt.type) === 'valide'" class="validated-label">
            <i class="pi pi-check-circle" /> Rapport validé
          </span>
        </div>
      </div>
    </div>

    <!-- CONSEILS -->
    <div class="tips-card card animate-fade-in" style="animation-delay:200ms">
      <div class="tips-card__header">
        <i class="pi pi-lightbulb tips-card__icon" />
        <h4 class="tips-card__title">Conseils pour la rédaction</h4>
      </div>
      <div class="tips-card__divider" />
      <ul class="tips-list">
        <li><i class="pi pi-check" /><span>Respectez le template fourni par votre encadrant</span></li>
        <li><i class="pi pi-check" /><span>Incluez un sommaire et une bibliographie complète</span></li>
        <li><i class="pi pi-check" /><span>Exportez en PDF avant de soumettre</span></li>
        <li><i class="pi pi-check" /><span>Vérifiez l'orthographe avec un correcteur</span></li>
      </ul>
    </div>

  </div>
</template>

<style scoped>
/* ===== LAYOUT GLOBAL ===== */
.student-reports {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 860px;
  margin: 0 auto;
}

/* ===== EN-TÊTE ===== */
.page-header {
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--border-default);
}
.page-title-h2 {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
  margin-bottom: 0.25rem;
}

/* ===== BANNIÈRE INFO ===== */
.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  background: rgba(99,102,241,0.07);
  border: 1px solid rgba(99,102,241,0.18);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.65;
  color: var(--text-secondary);
}
.info-banner__icon {
  color: var(--color-primary);
  font-size: 1.1rem;
  margin-top: 1px;
  flex-shrink: 0;
}

/* ===== ÉTATS VIDES ===== */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}
.empty-state__icon {
  font-size: 2.5rem;
  display: block;
  margin: 0 auto 0.5rem;
}

/* ===== GRILLE RAPPORTS ===== */
.reports-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ===== CARTE RAPPORT ===== */
.report-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0;
  transition: box-shadow var(--transition-fast), border-color var(--transition-fast);
}
.report-card:hover {
  border-color: var(--color-primary);
}

/* Header de la carte */
.rc-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Icône ronde */
.rc-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
  transition: background var(--transition-fast);
}
.rc-icon--valide   { background: rgba(16,185,129,0.12); color: var(--color-success); }
.rc-icon--soumis   { background: rgba(245,158,11,0.12);  color: var(--color-warning); }
.rc-icon--rejete   { background: rgba(239,68,68,0.1);    color: var(--color-danger);  }
.rc-icon--pending  { background: rgba(59,130,246,0.1);   color: var(--color-info);    }

/* Méta */
.rc-meta { flex: 1; min-width: 0; }
.rc-title {
  font-size: var(--text-base);
  font-weight: 700;
  margin: 0 0 0.2rem;
}
.rc-desc { margin: 0; }

/* Badge */
.rc-badge-wrap { flex-shrink: 0; }

/* Séparateur interne */
.rc-divider {
  height: 1px;
  background: var(--border-default);
  margin: 1.1rem 0;
  opacity: 0.6;
}

/* Feedback rejet */
.rc-feedback {
  padding: 0.875rem 1rem;
  background: rgba(239,68,68,0.05);
  border-left: 3px solid var(--color-danger);
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  margin-bottom: 1rem;
}
.rc-feedback__header {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--color-danger);
  font-size: var(--text-sm);
  font-weight: 600;
  margin-bottom: 0.4rem;
}
.rc-feedback__body {
  font-size: var(--text-sm);
  color: var(--text-secondary);
  line-height: 1.55;
  margin: 0;
}

/* Zone dropzone */
.rc-dropzone {
  margin-bottom: 1rem;
  border-radius: var(--radius-md);
  overflow: hidden;
}

/* Actions */
.rc-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.validated-label {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: var(--text-sm);
  color: var(--color-success);
  font-weight: 500;
}

/* ===== TRANSITION ===== */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.28s ease;
  overflow: hidden;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 420px;
}

/* ===== CONSEILS ===== */
.tips-card {
  padding: 1.25rem 1.5rem;
}
.tips-card__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.1rem;
}
.tips-card__title {
  font-size: var(--text-sm);
  font-weight: 700;
  margin: 0;
}
.tips-card__icon {
  color: var(--color-warning);
  font-size: 1rem;
}
.tips-card__divider {
  height: 1px;
  background: var(--border-default);
  margin: 0.875rem 0;
  opacity: 0.5;
}
.tips-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}
.tips-list li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: var(--text-sm);
  color: var(--text-secondary);
}
.tips-list .pi-check {
  color: var(--color-success);
  font-size: 0.75rem;
  flex-shrink: 0;
}

/* ===== UTILITAIRES ===== */
.text-muted    { color: var(--text-muted); }
.text-sm       { font-size: var(--text-sm); }
.text-xs       { font-size: var(--text-xs); }
.font-medium   { font-weight: 500; }
.mt-2          { margin-top: 0.5rem; }

@media (max-width: 640px) {
  .rc-header { flex-wrap: wrap; }
  .rc-badge-wrap { margin-left: calc(48px + 1rem); }
}
</style>