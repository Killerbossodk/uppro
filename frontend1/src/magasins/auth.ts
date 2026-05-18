import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export type UserRole = 'professeur' | 'etudiant' | 'rup_specialite' | 'rup_projet'

export interface User {
  id: number
  name: string
  prenom: string | null
  email: string
  role: UserRole
  specialite_id: number | null
  specialite?: {
    id: number
    nom: string
    code?: string
  } | null
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isProf = computed(() => user.value?.role === 'professeur')
  const isStudent = computed(() => user.value?.role === 'etudiant')
  const isRup = computed(() => user.value?.role === 'rup_specialite' || user.value?.role === 'rup_projet')

  const userInitials = computed(() => {
    if (!user.value) return '?'
    const name = user.value.prenom 
      ? `${user.value.prenom} ${user.value.name}`
      : user.value.name
    return name.split(' ').map(n => n[0] || '').join('').toUpperCase().slice(0, 2)
  })

  async function login(email: string, password: string) {
    loading.value = true
    error.value = null
    try {
      const res = await api.post('/login', { email, password })
      const newToken = res.data.access_token || res.data.token
      
      if (!newToken) throw new Error('Token non reçu')
      
      token.value = newToken
      localStorage.setItem('token', newToken)
      api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
      
      user.value = res.data.user
      
      return true
    } catch (e: any) {
      error.value = e?.response?.data?.message || 'Identifiants incorrects'
      return false
    } finally {
      loading.value = false
    }
  }
  async function fetchMe() {
    if (!token.value) return false
    
    try {
      const res = await api.get('/me')
      user.value = res.data
      return true
    } catch (error: any) {
      // 🔥 Token invalide → nettoyage immédiat
      if (error.response?.status === 401) {
        token.value = null
        user.value = null
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
      }
      return false
    }
  }
  async function logout() {
    const currentToken = token.value || localStorage.getItem('token')
    
    // Tenter de se déconnecter du backend
    if (currentToken) {
      try {
        await api.post('/logout', {}, {
          headers: { Authorization: `Bearer ${currentToken}` }
        })
      } catch (e) {
        // Ignorer les erreurs
      }
    }
    
    // 🔥 NETTOYAGE COMPLET (peu importe la réponse du backend)
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    delete api.defaults.headers.common['Authorization']
    
    // 🔥 Force le rechargement pour éviter les résidus
    window.location.replace('/login')
  }

  return { user, token, loading, error, isAuthenticated, isProf, isStudent, isRup, userInitials, login, logout, fetchMe }
})