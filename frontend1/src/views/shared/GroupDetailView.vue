<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/magasins/auth'
import api from '@/services/api'
import KanbanBoard from '@/components/KanbanBoard.vue'
import ChatThread from '@/components/ChatThread.vue'
import type { KanbanCard, KanbanStatus } from '@/magasins/groups'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()

const groupId = computed(() => Number(route.query.id) || Number(route.params.id) || 0)
const activeTab = ref<'kanban' | 'chat' | 'members' | 'private_chat'>('kanban')
const selectedPrivateMember = ref<any>(null)
const group = ref<any>(null)
const kanbanCards = ref<KanbanCard[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

// ✅ AJOUTER UNE CARTE (Kanban)
async function onAddCard(newCard: Omit<KanbanCard, 'id'>) {
  try {
    // Essayer d'envoyer au backend (si route existe)
    const payload = {
      group_id: groupId.value,
      titre: newCard.title,
      description: newCard.description || '',
      avancement_pct: 0,
      est_jalon: newCard.priority === 'high',
      date_debut: new Date().toISOString().split('T')[0],
      date_fin: newCard.due_date || null
    }
    
    const response = await api.post(`/groups/${groupId.value}/phases`, payload)
    const newPhase = response.data
    
    // Convertir la phase en carte Kanban
    const card: KanbanCard = {
      id: newPhase.id,
      title: newPhase.titre,
      description: newPhase.description,
      status: 'backlog' as KanbanStatus,
      priority: newPhase.est_jalon ? 'high' : 'medium',
      due_date: newPhase.date_fin,
      assignee: newCard.assignee,
      tags: newCard.tags
    }
    
    kanbanCards.value.push(card)
    
    toast.add({
      severity: 'success',
      summary: 'Phase ajoutée',
      detail: `"${newCard.title}" a été ajoutée au backlog`,
      life: 3000
    })
  } catch (error: any) {
    console.error('Erreur ajout phase:', error)
    const msg = error.response?.data?.message || 'Impossible de créer la phase sur le serveur'
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: msg,
      life: 5000
    })
  }
}

// ✅ DÉPLACER UNE CARTE
async function onMoveCard(cardId: number, newStatus: KanbanStatus) {
  const card = kanbanCards.value.find(c => c.id === cardId)
  if (!card) return
  
  const oldStatus = card.status
  const oldAvancement = card.status === 'backlog' ? 0 : card.status === 'en_cours' ? 50 : card.status === 'en_revue' ? 75 : 100
  
  // Calculer le nouvel avancement selon le statut
  let newAvancement = 0
  if (newStatus === 'backlog') newAvancement = 0
  else if (newStatus === 'en_cours') newAvancement = 50
  else if (newStatus === 'en_revue') newAvancement = 75
  else if (newStatus === 'termine') newAvancement = 100
  
  // Mettre à jour l'interface localement
  card.status = newStatus
  
  try {
    await api.put(`/phases/${cardId}`, { avancement_pct: newAvancement })
    toast.add({ severity: 'success', summary: 'Déplacé', detail: `Phase "${card.title}" déplacée`, life: 2000 })
  } catch (error) {
    // En cas d'erreur, on restaure l'ancien statut
    card.status = oldStatus
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de déplacer la phase', life: 3000 })
  }
}

const isGeneratingPhases = ref(false)

async function generateAIPhases() {
  if (!group.value?.project) return
  if (kanbanCards.value.length > 0 && !confirm('L\'IA va générer de nouvelles phases. Continuer ?')) return

  isGeneratingPhases.value = true
  toast.add({ severity: 'info', summary: 'IA en action', detail: 'Génération du planning en cours...', life: 3000 })

  try {
    const res = await api.post('/ai/generate-phases', {
      title: group.value.project.titre,
      description: group.value.project.description
    })

    const suggestedPhases = Array.isArray(res.data) ? res.data : []
    
    if (suggestedPhases.length === 0) {
      throw new Error('L\'IA n\'a pas pu générer de phases.')
    }

    // Ajouter chaque phase
    for (const phase of suggestedPhases) {
      await onAddCard({
        title: phase.titre,
        description: phase.description,
        status: 'backlog',
        priority: phase.est_jalon ? 'high' : 'medium'
      })
    }

    toast.add({ severity: 'success', summary: 'Planning généré', detail: `${suggestedPhases.length} phases ajoutées au backlog`, life: 5000 })
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erreur IA', detail: e.message || 'Échec de la génération', life: 5000 })
  } finally {
    isGeneratingPhases.value = false
  }
}

async function chargerGroupe() {
  loading.value = true
  error.value = null
  
  try {
    const res = await api.get(`/groups/${groupId.value}`)
    group.value = res.data

    // Charger les phases
    try {
      const phasesRes = await api.get(`/groups/${groupId.value}/phases`)
      const phases = Array.isArray(phasesRes.data) ? phasesRes.data : []
      
      if (phases.length > 0) {
        kanbanCards.value = phases.map((p: any, idx: number) => ({
          id: p.id || idx + 1,
          title: p.titre || 'Sans titre',
          description: p.description || '',
          status: (p.avancement_pct >= 100 ? 'termine' : p.avancement_pct >= 75 ? 'en_revue' : p.avancement_pct > 0 ? 'en_cours' : 'backlog') as KanbanStatus,
          priority: (p.est_jalon ? 'high' : 'medium') as 'high' | 'medium',
          due_date: p.date_fin || null,
          assignee: undefined,
          tags: p.est_jalon ? ['jalon'] : undefined
        }))
      } else {
        kanbanCards.value = []
      }
    } catch (e) {
      console.error('Erreur chargement phases:', e)
      kanbanCards.value = []
    }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Groupe introuvable'
  } finally {
    loading.value = false
  }
}

function getInitials(name: string) {
  if (!name) return '??'
  return name.split(' ').map(n => n[0] || '').join('').toUpperCase().slice(0, 2)
}

async function nommerChef(memberId: number) {
  if (!confirm('Voulez-vous vraiment nommer ce membre comme chef ?')) return
  try {
    await api.post(`/groups/${groupId.value}/set-chef`, { user_id: memberId })
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Chef modifié avec succès', life: 3000 })
    await chargerGroupe() // Rafraîchir les données
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.message || 'Impossible de nommer ce chef', life: 3000 })
  }
}

onMounted(async () => {
  if (groupId.value > 0) {
    await chargerGroupe()
  } else {
    // Chercher le groupe de l'étudiant connecté
    try {
      const res = await api.get('/groups')
      const groups = Array.isArray(res.data) ? res.data : []
      if (groups.length > 0) {
        window.location.href = `/etudiant/groupe?id=${groups[0].id}`
      } else {
        loading.value = false
        error.value = 'Vous n\'êtes dans aucun groupe'
      }
    } catch {
      loading.value = false
      error.value = 'Impossible de charger vos groupes'
    }
  }
})
</script>

<template>
  <div class="group-detail">
    <div v-if="loading" class="card" style="text-align:center;padding:2rem;">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
      <p style="margin-top:0.5rem;color:var(--text-muted);">Chargement du groupe...</p>
    </div>

    <div v-else-if="error" class="card" style="text-align:center;padding:2rem;">
      <i class="pi pi-exclamation-triangle" style="font-size:2rem;opacity:0.3;" />
      <p class="text-muted">{{ error }}</p>
    </div>

    <template v-else-if="group">
      <div class="card">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
          <div class="group-icon"><i class="pi pi-users" /></div>
          <div style="flex:1;">
            <h2 style="font-family:var(--font-display);font-weight:800;">{{ group.nom }}</h2>
            <p class="text-muted text-sm">Projet : {{ group.project?.titre || 'Non défini' }}</p>
            <p class="text-muted text-sm">Encadrant : {{ group.project?.superviseur?.prenom }} {{ group.project?.superviseur?.name }}</p>
          </div>
          <span class="badge badge-info">{{ group.membres?.length || 0 }} membres</span>
        </div>
      </div>

      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <button class="btn btn-sm" :class="activeTab === 'kanban' ? 'btn-primary' : 'btn-secondary'" @click="activeTab = 'kanban'">
          <i class="pi pi-th-large" /> Kanban
        </button>
        <button class="btn btn-sm" :class="activeTab === 'members' ? 'btn-primary' : 'btn-secondary'" @click="activeTab = 'members'">
          <i class="pi pi-users" /> Membres ({{ group.membres?.length || 0 }})
        </button>
        <button class="btn btn-sm" :class="activeTab === 'chat' ? 'btn-primary' : 'btn-secondary'" @click="activeTab = 'chat'; selectedPrivateMember = null">
          <i class="pi pi-comments" /> Chat Groupe
        </button>
        <button v-if="group.project?.superviseur" class="btn btn-sm" :class="activeTab === 'private_chat' ? 'btn-primary' : 'btn-secondary'" @click="activeTab = 'private_chat'; selectedPrivateMember = group.project.superviseur">
          <i class="pi pi-user" /> Chat Professeur
        </button>
      </div>

      <div v-if="activeTab === 'kanban'">
        <KanbanBoard
          :cards="kanbanCards"
          :loading="isGeneratingPhases"
          @move="onMoveCard"
          @add="onAddCard"
          @generateAI="generateAIPhases"
        />
      </div>

      <div v-if="activeTab === 'members'" class="card">
        <h3 class="section-title"><i class="pi pi-users" /> Membres</h3>
        <div v-if="!group.membres?.length" class="text-muted text-sm">Aucun membre</div>
        <div v-else style="display:flex;flex-direction:column;gap:0.5rem;">
          <div v-for="m in group.membres" :key="m.id" style="display:flex;align-items:center;gap:1rem;padding:0.75rem;background:var(--bg-elevated);border-radius:var(--radius-md);">
            <div class="avatar">{{ getInitials(m.prenom ? `${m.prenom} ${m.name}` : m.name) }}</div>
            <div style="flex:1;">
              <p class="font-semibold text-sm">{{ m.prenom }} {{ m.name }}</p>
              <p class="text-xs text-muted">{{ m.email }}</p>
            </div>
            <span v-if="m.pivot?.est_chef" class="badge badge-warning"><i class="pi pi-crown" /> Chef</span>
            <div class="flex gap-2">
              <button 
                v-if="!m.pivot?.est_chef && (auth.user?.role === 'professeur' || group.membres?.some((me: any) => me.id === auth.user?.id))"
                class="btn btn-secondary btn-sm"
                @click="nommerChef(m.id)"
                title="Nommer ce membre comme chef"
              >
                Nommer Chef
              </button>
              <button 
                v-if="m.id !== auth.user?.id" 
                class="btn btn-ghost btn-icon btn-sm" 
                @click="activeTab = 'private_chat'; selectedPrivateMember = m"
                title="Chatter en privé"
              >
                <i class="pi pi-comments" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="activeTab === 'chat'" style="height:500px;">
        <ChatThread :group-id="groupId" :group-name="group.nom" />
      </div>

      <div v-if="activeTab === 'private_chat'" style="height:500px;">
        <ChatThread 
          :group-id="0" 
          :private-member="{ id: selectedPrivateMember.id, name: selectedPrivateMember.prenom + ' ' + selectedPrivateMember.name }" 
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.group-detail {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1400px;
}

.group-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.section-title .pi {
  color: var(--color-primary-light);
}

.btn-sm {
  padding: 0.375rem 0.875rem;
  font-size: var(--text-xs);
}
</style>