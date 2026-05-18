import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// ✅ Intercepteur d'authentification
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Pour FormData (upload de fichier)
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    }
    
    return config
  },
  (error) => Promise.reject(error)
)

// ✅ Intercepteur réponse (gestion 401)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

// 🔍 (Optionnel) Intercepteur de traçage - à supprimer en production
api.interceptors.request.use((config) => {
  if (config.url?.includes('announcements/carousel')) {
    console.log('📢 Appel à /announcements/carousel')
  }
  return config
})

export default api