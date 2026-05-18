import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export type NotifType = 'info' | 'success' | 'warning' | 'error' | 'message' | 'rdv'

export interface AppNotification {
  id: number | string
  type: NotifType
  title: string
  message: string
  read: boolean
  created_at: string
  link?: string
  data?: any
}

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<AppNotification[]>([])
  const unreadCount = ref(0)

  function addNotification(notif: Partial<AppNotification>) {
    const n: AppNotification = {
      id: notif.id || Date.now(),
      type: notif.type || 'info',
      title: notif.title || 'Notification',
      message: notif.message || '',
      read: notif.read || false,
      created_at: notif.created_at || new Date().toISOString(),
      link: notif.link,
      data: notif.data,
    }
    notifications.value.unshift(n)
    if (!n.read) unreadCount.value++
    return n
  }

  async function fetchNotifications() {
    const token = localStorage.getItem('token')
    if (!token) return
    
    try {
      const res = await api.get('/notifications')
      if (Array.isArray(res.data)) {
        notifications.value = res.data.map((n: any) => ({
          id: n.id,
          type: (n.type?.includes('rdv') ? 'rdv' : n.type?.includes('report') ? 'info' : 'info') as NotifType,
          title: n.type || 'Notification',
          message: n.message,
          read: n.lu || false,
          created_at: n.created_at,
          data: n.data ? (typeof n.data === 'string' ? JSON.parse(n.data) : n.data) : null,
        }))
        unreadCount.value = notifications.value.filter(n => !n.read).length
      }
    } catch {}
  }

  async function markAsRead(id: number) {
    try {
      await api.post(`/notifications/${id}/mark-read`)
      const n = notifications.value.find(x => x.id === id)
      if (n && !n.read) {
        n.read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch {}
  }

  async function markAllAsRead() {
    try {
      await api.post('/notifications/mark-all-read')
      notifications.value.forEach(n => n.read = true)
      unreadCount.value = 0
    } catch {}
  }

  function clearAll() {
    notifications.value = []
    unreadCount.value = 0
  }

  return { notifications, unreadCount, addNotification, fetchNotifications, markAsRead, markAllAsRead, clearAll }
})