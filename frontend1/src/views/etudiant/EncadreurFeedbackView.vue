<!-- src/views/etudiant/EncadreurFeedbackView.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const group = ref<any>(null)
const supervisor = ref<any>(null)

// Form fields
const rating = ref(0)
const ratingHover = ref(0)
const comment = ref('')
const isAnonymous = ref(false)
const submitting = ref(false)
const submittedSuccessfully = ref(false)

onMounted(async () => {
  try {
    const grpRes = await api.get('/groups')
    const groups = Array.isArray(grpRes.data) ? grpRes.data : []
    if (groups.length > 0) {
      group.value = groups[0]
      supervisor.value = groups[0].project?.superviseur
    }
  } catch (e) {
    console.error('Erreur chargement superviseur:', e)
  } finally {
    loading.value = false
  }
})

function setRating(val: number) {
  rating.value = val
}

async function submitFeedback() {
  if (!supervisor.value) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Aucun encadreur assigné.', life: 3000 })
    return
  }
  if (rating.value === 0) {
    toast.add({ severity: 'warn', summary: 'Note requise', detail: 'Veuillez attribuer une note entre 1 et 5 étoiles.', life: 3000 })
    return
  }
  if (!comment.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Commentaire requis', detail: 'Veuillez justifier votre note avec un commentaire.', life: 3000 })
    return
  }

  submitting.value = true
  try {
    await api.post('/feedbacks/encadreurs', {
      encadreur_id: supervisor.value.id,
      note_pedagogique: rating.value,
      commentaire: comment.value,
      anonyme: isAnonymous.value
    })
    submittedSuccessfully.value = true
    toast.add({ severity: 'success', summary: 'Avis transmis', detail: 'Votre retour a bien été envoyé au RUP Projet.', life: 4000 })
  } catch (e: any) {
    const msg = e.response?.data?.message || 'Erreur lors de l\'envoi de l\'avis.'
    toast.add({ severity: 'error', summary: 'Erreur', detail: msg, life: 4000 })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="feedback-page">
    <div class="flex items-center gap-2 mb-4">
      <button class="btn btn-ghost btn-icon" @click="router.back()">
        <i class="pi pi-arrow-left" />
      </button>
      <h2 class="page-title">Évaluer mon Encadreur</h2>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p class="text-muted mt-2">Chargement...</p>
    </div>

    <!-- Si aucun encadreur n'est assigné -->
    <div v-else-if="!supervisor" class="card text-center py-8">
      <i class="pi pi-exclamation-circle" style="font-size:3rem;color:var(--color-warning);opacity:0.8;" />
      <h3 class="mt-3 font-bold">Aucun encadreur assigné</h3>
      <p class="text-muted text-sm mt-1">Vous devez faire partie d'un groupe avec un projet validé pour évaluer un encadreur.</p>
      <button class="btn btn-primary btn-sm mt-4" @click="router.back()">Retour</button>
    </div>

    <!-- Formulaire de feedback -->
    <div v-else-if="!submittedSuccessfully" class="card feedback-card">
      <div class="supervisor-profile mb-4">
        <div class="profile-avatar">
          {{ supervisor.prenom?.[0] }}{{ supervisor.name?.[0] }}
        </div>
        <div class="profile-info">
          <h3 class="profile-name">{{ supervisor.prenom }} {{ supervisor.name }}</h3>
          <p class="profile-role text-muted">Votre encadreur de projet</p>
        </div>
      </div>

      <div class="alert-box mb-4">
        <i class="pi pi-info-circle text-brand" />
        <span class="text-xs">Votre évaluation pédagogique aide le RUP Projet à auditer et améliorer l'encadrement des étudiants.</span>
      </div>

      <form @submit.prevent="submitFeedback">
        <!-- Évaluation avec des étoiles -->
        <div class="form-group mb-4 text-center">
          <label class="form-label mb-2">Note Pédagogique (1 à 5 étoiles) *</label>
          <div class="stars-rating">
            <span 
              v-for="idx in 5" 
              :key="idx" 
              class="star-item"
              :class="{ 
                'star-active': idx <= (ratingHover || rating), 
                'star-hovered': idx <= ratingHover 
              }"
              @mouseenter="ratingHover = idx"
              @mouseleave="ratingHover = 0"
              @click="setRating(idx)"
            >
              ★
            </span>
          </div>
          <p class="rating-legend text-xs mt-1" v-if="rating || ratingHover">
            {{ ['Très médiocre', 'Passable', 'Moyen / Convenable', 'Très satisfaisant', 'Excellent ! Pédagogie remarquable'][(ratingHover || rating) - 1] }}
          </p>
        </div>

        <!-- Zone de texte pour commentaire -->
        <div class="form-group mb-4">
          <label class="form-label">Commentaires & Remarques *</label>
          <textarea 
            v-model="comment" 
            class="form-input" 
            rows="5" 
            placeholder="Détaillez votre avis sur la pédagogie, la disponibilité, les conseils reçus..."
            required
          ></textarea>
        </div>

        <!-- Toggle Anonymat -->
        <div class="form-group mb-4 flex items-start gap-3 p-3 rounded" style="background:var(--bg-elevated);border:1px solid var(--border-default);">
          <input 
            type="checkbox" 
            id="anonymous-toggle" 
            v-model="isAnonymous" 
            class="mt-1 cursor-pointer" 
          />
          <label for="anonymous-toggle" class="cursor-pointer">
            <span class="text-sm font-semibold block">Envoyer de manière anonyme</span>
            <span class="text-xs text-muted block mt-1">Si coché, votre prénom et votre nom seront masqués. Le RUP Projet verra uniquement que le commentaire provient d'un "Étudiant Anonyme".</span>
          </label>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" class="btn btn-secondary" @click="router.back()">Annuler</button>
          <button type="submit" class="btn btn-primary" :disabled="submitting || rating === 0 || !comment.trim()">
            <i class="pi pi-spin pi-spinner" v-if="submitting" />
            <i class="pi pi-send" v-else />
            Transmettre mon avis
          </button>
        </div>
      </form>
    </div>

    <!-- Écran de réussite -->
    <div v-else class="card text-center py-8 success-card animate-fade-in">
      <i class="pi pi-check-circle" style="font-size:4rem;color:var(--color-success);" />
      <h3 class="mt-4 font-bold text-xl">Merci pour votre retour !</h3>
      <p class="text-muted text-sm mt-2 max-w-md mx-auto" style="margin: 0.5rem auto 1.5rem auto;">
        Votre critique pédagogique sur l'encadreur <strong>{{ supervisor.prenom }} {{ supervisor.name }}</strong> a bien été enregistrée et transmise au RUP Projet.
      </p>
      <button class="btn btn-primary btn-sm" @click="router.push('/etudiant/dashboard')">
        Retourner au Tableau de Bord
      </button>
    </div>
  </div>
</template>

<style scoped>
.feedback-page {
  max-width: 650px;
  margin: 0 auto;
}

.page-title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 800;
  margin: 0;
}

.feedback-card {
  padding: 1.75rem;
}

.supervisor-profile {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-avatar {
  width: 50px;
  height: 50px;
  background: var(--gradient-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.25rem;
}

.profile-name {
  font-size: var(--text-base);
  font-weight: 700;
  margin: 0;
}

.profile-role {
  font-size: var(--text-xs);
  margin-top: 0.15rem;
}

.alert-box {
  background: var(--color-primary-subtle);
  border-radius: var(--radius-md);
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-primary);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-secondary);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

/* Star rating styles */
.stars-rating {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin: 0.5rem 0;
}

.star-item {
  font-size: 2.5rem;
  color: var(--border-default);
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  user-select: none;
}

.star-item:hover {
  transform: scale(1.15);
}

.star-active {
  color: #ffb800; /* Beautiful golden star color */
  text-shadow: 0 0 8px rgba(255, 184, 0, 0.4);
}

.rating-legend {
  font-weight: 600;
  color: var(--color-primary);
}

.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.block {
  display: block;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}
</style>
