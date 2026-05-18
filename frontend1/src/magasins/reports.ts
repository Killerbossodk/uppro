// src/magasins/reports.ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export interface Report {
  id: number
  group_id: number
  deposant_id: number
  titre: string
  fichier_url: string
  version: number
  type: 'intermediaire' | 'final' | 'corrige'
  statut: 'soumis' | 'valide' | 'rejete'
  feedback_ia: string | null
  score_plagiat: number | null
  created_at: string
  updated_at: string
  group?: {
    id: number
    nom: string
    project?: {
      id: number
      titre: string
      superviseur?: { name: string; prenom: string }
    }
  }
  deposant?: { id: number; name: string; prenom: string }
}

export const useReportsStore = defineStore('reports', () => {
  const reports = ref<Report[]>([])
  const currentReport = ref<Report | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchReports(params?: Record<string, any>) {
    loading.value = true
    try {
      const res = await api.get('/reports', { params })
      reports.value = res.data
    } catch (e: any) {
      error.value = 'Impossible de charger les rapports.'
    } finally {
      loading.value = false
    }
  }

  async function fetchReport(id: number) {
    loading.value = true
    try {
      const res = await api.get(`/reports/${id}`)
      currentReport.value = res.data
    } catch {
      error.value = 'Rapport introuvable.'
    } finally {
      loading.value = false
    }
  }

  async function fetchGroupReports(groupId: number) {
    loading.value = true
    try {
      const res = await api.get(`/groups/${groupId}/reports`)
      return res.data
    } catch {
      error.value = 'Impossible de charger les rapports du groupe.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function storeReport(formData: FormData) {
    loading.value = true
    try {
      const res = await api.post('/reports', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      return res.data
    } catch (e: any) {
      error.value = e?.response?.data?.message || 'Erreur lors du dépôt'
      return null
    } finally {
      loading.value = false
    }
  }

  async function deleteReport(id: number) {
    try {
      await api.delete(`/reports/${id}`)
      reports.value = reports.value.filter(r => r.id !== id)
      return true
    } catch {
      return false
    }
  }

  async function validateReport(id: number) {
    try {
      await api.post(`/reports/${id}/validate`)
      const idx = reports.value.findIndex(r => r.id === id)
      if (idx !== -1) reports.value[idx].statut = 'valide'
      return true
    } catch {
      return false
    }
  }

  async function rejectReport(id: number, feedback: string) {
    try {
      await api.post(`/reports/${id}/reject`, { feedback_ia: feedback })
      const idx = reports.value.findIndex(r => r.id === id)
      if (idx !== -1 && reports.value[idx]) {
        reports.value[idx].statut = 'rejete'
      }
      return true
    } catch {
      return false
    }
  }

  return {
    reports,
    currentReport,
    loading,
    error,
    fetchReports,
    fetchReport,
    fetchGroupReports,
    storeReport,
    deleteReport,
    validateReport,
    rejectReport,
  }
})