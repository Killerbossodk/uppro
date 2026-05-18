<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/magasins/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const meetingId = route.params.id

const loading = ref(true)
const meeting = ref<any>(null)
const jury = ref<any>(null)
const groups = ref<any[]>([])
const grades = ref<any>({}) // Stocker les notes par groupe

onMounted(async () => {
  await fetchData()
})

async function fetchData() {
  loading.value = true
  try {
    const [mRes, jRes] = await Promise.all([
      api.get(`/meetings/${meetingId}`),
      api.get(`/meetings/${meetingId}/jury`).catch(() => ({ data: null }))
    ])
    meeting.value = mRes.data
    jury.value = jRes.data
    groups.value = mRes.data.groupes || []

    // Initialiser les notes pour chaque groupe
    groups.value.forEach(g => {
      // Si on a déjà une note sauvegardée (ex: soutenance terminée)
      const savedNote = g.pivot?.note ? Number(g.pivot.note) : null
      const defaultVal = savedNote !== null ? savedNote : 10
      
      grades.value[g.id] = {
        note_technique: defaultVal,
        note_presentation: defaultVal,
        note_reponses: defaultVal,
        commentaire: g.pivot?.commentaire || '',
        saved_note: savedNote
      }
    })
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const isPresident = computed(() => {
  if (!meeting.value || !auth.user) return false
  
  // Si un jury est défini, le président du jury a la main
  if (jury.value && jury.value.president_id) {
    if (Number(jury.value.president_id) === Number(auth.user.id)) return true
  }
  
  // Par défaut, l'organisateur (le professeur qui a créé le RDV) peut aussi démarrer
  return Number(meeting.value.organisateur_id) === Number(auth.user.id)
})

async function startSoutenance() {
  try {
    await api.post(`/meetings/${meetingId}/start-soutenance`)
    meeting.value.statut_soutenance = 'en_cours'
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur')
  }
}

async function finishSoutenance() {
  if (!confirm('Voulez-vous vraiment clôturer cette soutenance et envoyer les notes au RUP ?')) return
  
  try {
    // Envoyer les notes pour tous les groupes concernés
    for (const groupId in grades.value) {
      const g = grades.value[groupId]
      const finalGrade = (g.note_technique * 0.4 + g.note_presentation * 0.3 + g.note_reponses * 0.3).toFixed(2)
      
      await api.post(`/meetings/${meetingId}/grade`, {
        group_id: groupId,
        note: finalGrade,
        commentaire: g.commentaire
      })
    }

    await api.post(`/meetings/${meetingId}/finish-soutenance`)
    meeting.value.statut_soutenance = 'termine'
    alert('Soutenance clôturée ! Les notes ont été transmises au RUP.')
    router.push('/professeur/agenda')
  } catch (e: any) {
    alert(e.response?.data?.message || 'Erreur lors de la clôture')
  }
}

function calculateAverage(g: any) {
  return (g.note_technique * 0.4 + g.note_presentation * 0.3 + g.note_reponses * 0.3).toFixed(2)
}
</script>

<template>
  <div class="soutenance-view p-4" v-if="!loading && meeting">
    <!-- Header -->
    <div class="card mb-4 border-l-4 border-primary shadow-lg" style="border-left-color: var(--color-primary)">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold">{{ meeting.titre }}</h1>
          <p class="text-muted">Jury : {{ jury?.president?.prenom }} {{ jury?.president?.nom }} (Président)</p>
        </div>
        <div class="flex gap-3">
          <button v-if="isPresident && meeting.statut_soutenance === 'planifie'" 
                  class="btn btn-primary" @click="startSoutenance">
            <i class="pi pi-play mr-2"></i> Démarrer la session
          </button>
          
          <button v-if="isPresident && meeting.statut_soutenance === 'en_cours'" 
                  class="btn btn-success" @click="finishSoutenance">
            <i class="pi pi-check-circle mr-2"></i> Terminer et Envoyer au RUP
          </button>

          <span v-if="meeting.statut_soutenance === 'termine'" class="badge badge-success text-lg p-3">
            <i class="pi pi-lock mr-2"></i> Soutenance Terminée
          </span>
        </div>
      </div>
    </div>

    <!-- Interface de notation -->
    <div v-if="meeting.statut_soutenance === 'en_cours' || meeting.statut_soutenance === 'termine'" class="grid gap-6">
      <div v-for="group in groups" :key="group.id" class="card evaluation-card shadow-md">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
          <i class="pi pi-users text-primary" style="color: var(--color-primary)"></i> Groupe : {{ group.nom }}
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
          <!-- Formulaire -->
          <div class="space-y-4">
            <div class="form-group">
              <div class="flex justify-between mb-2">
                <span>Qualité Technique (40%)</span>
                <span class="font-bold text-primary" style="color: var(--color-primary)">{{ grades[group.id].note_technique }}/20</span>
              </div>
              <input type="range" v-model.number="grades[group.id].note_technique" min="0" max="20" step="0.5" 
                     class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" 
                     :disabled="meeting.statut_soutenance === 'termine'"/>
            </div>

            <div class="form-group">
              <div class="flex justify-between mb-2">
                <span>Présentation Orale (30%)</span>
                <span class="font-bold text-primary" style="color: var(--color-primary)">{{ grades[group.id].note_presentation }}/20</span>
              </div>
              <input type="range" v-model.number="grades[group.id].note_presentation" min="0" max="20" step="0.5" 
                     class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                     :disabled="meeting.statut_soutenance === 'termine'"/>
            </div>

            <div class="form-group">
              <div class="flex justify-between mb-2">
                <span>Réponses aux questions (30%)</span>
                <span class="font-bold text-primary" style="color: var(--color-primary)">{{ grades[group.id].note_reponses }}/20</span>
              </div>
              <input type="range" v-model.number="grades[group.id].note_reponses" min="0" max="20" step="0.5" 
                     class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                     :disabled="meeting.statut_soutenance === 'termine'"/>
            </div>
          </div>

          <!-- Commentaire et Moyenne -->
          <div class="bg-gray-50 p-4 rounded-xl flex flex-col justify-between border border-gray-100">
            <div>
              <label class="block mb-2 font-semibold">Commentaires du Jury</label>
              <textarea v-model="grades[group.id].commentaire" class="form-input w-full h-32" 
                        placeholder="Points forts, points faibles..."
                        :disabled="meeting.statut_soutenance === 'termine'"></textarea>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
              <span class="text-lg font-bold">MOYENNE :</span>
              <span class="text-3xl font-black text-primary" style="color: var(--color-primary)">
                <template v-if="meeting.statut_soutenance === 'termine' && grades[group.id].saved_note !== null">
                  {{ Number(grades[group.id].saved_note).toFixed(2) }} / 20
                </template>
                <template v-else>
                  {{ calculateAverage(grades[group.id]) }} / 20
                </template>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Message attente -->
    <div v-else-if="meeting.statut_soutenance === 'planifie'" class="card text-center py-20 shadow-sm">
      <i class="pi pi-clock text-6xl text-muted mb-4" style="font-size: 4rem; color: #94a3b8"></i>
      <h2 class="text-2xl font-bold">La soutenance n'a pas encore commencé</h2>
      <p class="text-muted mt-2" v-if="isPresident">Cliquez sur le bouton "Démarrer" ci-dessus pour ouvrir la session.</p>
      <p class="text-muted mt-2" v-else>En attente du lancement par le président du jury.</p>
    </div>
  </div>

  <div v-else class="flex justify-center items-center h-64">
    <i class="pi pi-spin pi-spinner text-4xl text-primary" style="color: var(--color-primary)"></i>
  </div>
</template>

<style scoped>
.soutenance-view { max-width: 1000px; margin: 0 auto; }
.evaluation-card { transition: all 0.3s ease; }
.evaluation-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
</style>
