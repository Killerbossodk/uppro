import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

// ✅ EXPORTER CES TYPES
export type KanbanStatus = 'backlog' | 'en_cours' | 'en_revue' | 'termine'

export interface KanbanCard {
  id: number
  title: string
  description?: string
  status: KanbanStatus
  assignee?: string | null
  priority: 'low' | 'medium' | 'high'
  due_date?: string | null
  tags?: string[]
}

export interface GroupMember {
  id: number
  name: string
  prenom: string | null
  email: string
  pivot?: { est_chef: boolean; joined_at: string }
}

export interface Group {
  id: number
  nom: string
  project_id: number
  capacite_max: number
  inscription_ouverte: boolean
  project?: { id: number; titre: string; superviseur?: { id: number; name: string; prenom: string } }
  membres: GroupMember[]
}

export const useGroupsStore = defineStore('groups', () => {
  const groups = ref<Group[]>([])
  const currentGroup = ref<Group | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchGroups() {
    loading.value = true
    try {
      const res = await api.get('/groups')
      groups.value = Array.isArray(res.data) ? res.data : []
    } catch {
      error.value = 'Impossible de charger les groupes'
    } finally {
      loading.value = false
    }
  }

  async function fetchGroup(id: number) {
    loading.value = true
    try {
      const res = await api.get(`/groups/${id}`)
      currentGroup.value = res.data
    } catch {
      error.value = 'Groupe introuvable'
    } finally {
      loading.value = false
    }
  }

  async function createGroup(payload: { project_id: number; nom: string; capacite_max?: number }) {
    try {
      const res = await api.post('/groups', payload)
      groups.value.unshift(res.data)
      return res.data
    } catch {
      return null
    }
  }

  async function addMember(groupId: number, userId: number) {
    try {
      const res = await api.post(`/groups/${groupId}/add-member`, { user_id: userId })
      return res.data
    } catch {
      return null
    }
  }

  async function removeMember(groupId: number, userId: number) {
    try {
      await api.delete(`/groups/${groupId}/remove-member`, { data: { user_id: userId } })
      return true
    } catch {
      return false
    }
  }

  async function selfAdd(groupId: number) {
    try {
      const res = await api.post(`/groups/${groupId}/self-add`)
      return res.data
    } catch {
      return null
    }
  }

  async function leaveGroup(groupId: number) {
    try {
      await api.post(`/groups/${groupId}/leave`)
      return true
    } catch {
      return false
    }
  }

  async function toggleInscriptions(groupId: number) {
    try {
      const res = await api.post(`/groups/${groupId}/toggle-inscriptions`)
      return res.data
    } catch {
      return null
    }
  }

  return {
    groups,
    currentGroup,
    loading,
    error,
    fetchGroups,
    fetchGroup,
    createGroup,
    addMember,
    removeMember,
    selfAdd,
    leaveGroup,
    toggleInscriptions,
  }
})