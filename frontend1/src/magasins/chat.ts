import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export interface ChatMessage {
  id: number
  group_id?: number
  user_id: number
  receiver_id?: number
  is_private?: boolean
  message: string
  attachment_url?: string
  attachment_name?: string
  lu: boolean
  created_at: string
  user?: { id: number; name: string; prenom: string }
  is_mine?: boolean
}

export const useChatStore = defineStore('chat', () => {
  const messages = ref<ChatMessage[]>([])
  const loading = ref(false)
  const sending = ref(false)

  async function fetchMessages(groupId: number, silent = false) {
    if (!silent) loading.value = true
    try {
      const res = await api.get(`/chat/${groupId}`)
      messages.value = Array.isArray(res.data) ? res.data : []
    } catch {
      if (!silent) messages.value = []
    } finally {
      if (!silent) loading.value = false
    }
  }

  async function fetchPrivateMessages(receiverId: number, silent = false) {
    if (!silent) loading.value = true
    try {
      const res = await api.get(`/chat/private/${receiverId}`)
      messages.value = Array.isArray(res.data) ? res.data : []
    } catch {
      if (!silent) messages.value = []
    } finally {
      if (!silent) loading.value = false
    }
  }

  async function sendMessage(groupId: number, content: string, attachment: File | null = null) {
    sending.value = true
    try {
      const formData = new FormData()
      formData.append('message', content)
      if (attachment) formData.append('attachment', attachment)
      
      const res = await api.post(`/chat/${groupId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      messages.value.push(res.data)
      return res.data
    } catch {
      return null
    } finally {
      sending.value = false
    }
  }

  async function sendPrivateMessage(receiverId: number, content: string, attachment: File | null = null) {
    sending.value = true
    try {
      const formData = new FormData()
      formData.append('message', content)
      if (attachment) formData.append('attachment', attachment)
      
      const res = await api.post(`/chat/private/${receiverId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      messages.value.push(res.data)
      return res.data
    } catch {
      return null
    } finally {
      sending.value = false
    }
  }

  function receiveMessage(msg: ChatMessage) {
    // Check if message already exists (to avoid duplicates from Echo + manual push)
    if (!messages.value.find(m => m.id === msg.id)) {
      messages.value.push(msg)
    }
  }

  return { 
    messages, 
    loading, 
    sending, 
    fetchMessages, 
    fetchPrivateMessages, 
    sendMessage, 
    sendPrivateMessage, 
    receiveMessage 
  }
})