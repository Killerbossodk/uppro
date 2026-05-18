import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export type ProjectStatus = 'brouillon' | 'soumis' | 'valide' | 'refuse'

export interface Project {
  id: number
  titre: string
  description: string | null
  statut: ProjectStatus
  niveau: string
  annee_universitaire: string
  specialite_id: number | null
  superviseur_id: number
  est_archive: boolean
  superviseur?: { id: number; name: string; prenom: string }
  specialite?: { id: number; nom: string } | null
  groupes?: any[]
  created_at: string
  updated_at: string
}

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const currentProject = ref<Project | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const byStatus = computed(() => {
    const map: Record<string, Project[]> = { brouillon: [], soumis: [], valide: [], refuse: [] }
    for (const p of projects.value) {
      const arr = map[p.statut]
      if (arr) arr.push(p)
    }
    return map
  })

  async function fetchProjects(params?: Record<string, any>) {
    loading.value = true
    error.value = null
    try {
      const res = await api.get('/projects', { params })
      projects.value = Array.isArray(res.data) ? res.data : []
    } catch (e: any) {
      error.value = 'Impossible de charger les projets'
    } finally {
      loading.value = false
    }
  }

  async function fetchProject(id: number) {
    loading.value = true
    try {
      const res = await api.get(`/projects/${id}`)
      currentProject.value = res.data
    } catch {
      error.value = 'Projet introuvable'
    } finally {
      loading.value = false
    }
  }

  async function createProject(payload: { titre: string; description?: string; niveau: string; specialite_id?: number | null; annee_universitaire: string }) {
    try {
      const res = await api.post('/projects', payload)
      projects.value.unshift(res.data)
      return res.data
    } catch (e: any) {
      error.value = e?.response?.data?.message || 'Erreur création'
      return null
    }
  }

  async function updateProject(id: number, payload: Partial<Project>) {
    try {
      const res = await api.put(`/projects/${id}`, payload)
      const idx = projects.value.findIndex(p => p.id === id)
      if (idx !== -1) projects.value[idx] = res.data
      return res.data
    } catch {
      return null
    }
  }

  async function soumettreProjet(id: number) {
    try {
      const res = await api.post(`/projects/${id}/soumettre`)
      return res.data
    } catch {
      return null
    }
  }

  async function validerProjet(id: number) {
    try {
      const res = await api.post(`/projects/${id}/valider`)
      return res.data
    } catch {
      return null
    }
  }

  async function refuserProjet(id: number, motif: string) {
    try {
      const res = await api.post(`/projects/${id}/refuser`, { motif_refus: motif })
      return res.data
    } catch {
      return null
    }
  }

  async function archiverProjet(id: number) {
    try {
      await api.post(`/projects/${id}/archive`)
      return true
    } catch {
      return false
    }
  }

  return { projects, currentProject, loading, error, byStatus, fetchProjects, fetchProject, createProject, updateProject, soumettreProjet, validerProjet, refuserProjet, archiverProjet }
})