<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import { useChatStore } from '@/magasins/chat'

const props = defineProps<{
  groupId: number
  groupName?: string
  privateMember?: { id: number; name: string } | null
}>()

const auth = useAuthStore()
const chatStore = useChatStore()
const messageInput = ref('')
const messagesEl = ref<HTMLElement | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)

let pollInterval: any

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)

onMounted(async () => {
  if (props.groupId > 0) {
    await chatStore.fetchMessages(props.groupId)
  } else if (props.privateMember) {
    await chatStore.fetchPrivateMessages(props.privateMember.id)
  }
  
  pollInterval = setInterval(async () => {
    const prevCount = chatStore.messages.length
    if (props.groupId > 0) {
      await chatStore.fetchMessages(props.groupId, true)
    } else if (props.privateMember) {
      await chatStore.fetchPrivateMessages(props.privateMember.id, true)
    }
    
    if (chatStore.messages.length > prevCount) {
      scrollToBottom()
    }
  }, 5000)

  scrollToBottom()
})

import { onUnmounted } from 'vue'

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

watch(
  () => chatStore.messages.length,
  async () => {
    await nextTick()
    scrollToBottom()
  }
)

function scrollToBottom() {
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

async function sendMessage() {
  const content = messageInput.value.trim()
  if (!content && !selectedFile.value) return
  if (chatStore.sending) return

  const text = messageInput.value
  const file = selectedFile.value
  
  messageInput.value = ''
  selectedFile.value = null
  if (fileInput.value) fileInput.value.value = ''

  if (props.groupId > 0) {
    await chatStore.sendMessage(props.groupId, text, file)
  } else if (props.privateMember) {
    await chatStore.sendPrivateMessage(props.privateMember.id, text, file)
  }
  
  inputEl.value?.focus()
}

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0]
  }
}

function triggerFileSelect() {
  fileInput.value?.click()
}

function removeFile() {
  selectedFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

function getFileIcon(url: string) {
  const ext = url.split('.').pop()?.toLowerCase()
  if (['jpg', 'jpeg', 'png', 'gif'].includes(ext || '')) return 'pi-image'
  if (['pdf'].includes(ext || '')) return 'pi-file-pdf'
  return 'pi-file'
}

function getFileName(url: string) {
  return url.split('/').pop() || 'Fichier'
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

function formatTime(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(today.getDate() - 1)
  if (d.toDateString() === today.toDateString()) return "Aujourd'hui"
  if (d.toDateString() === yesterday.toDateString()) return 'Hier'
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long' })
}

function getInitials(msg: any) {
  // Backend renvoie user.name et user.prenom
  if (msg.user?.prenom && msg.user?.name) {
    return (msg.user.prenom[0] + msg.user.name[0]).toUpperCase()
  }
  if (msg.user?.name) {
    return msg.user.name.split(' ').map((n: string) => n[0] || '').join('').toUpperCase().slice(0, 2)
  }
  return '??'
}

function getUserName(msg: any) {
  if (msg.user?.prenom && msg.user?.name) {
    return `${msg.user.prenom} ${msg.user.name}`
  }
  if (msg.user?.name) {
    return msg.user.name
  }
  return 'Utilisateur'
}

function isMine(msg: any) {
  return msg.user_id === auth.user?.id
}

function shouldShowAvatar(index: number) {
  if (index === 0) return true
  const curr = chatStore.messages[index]
  const prev = chatStore.messages[index - 1]
  return curr?.user_id !== prev?.user_id
}

function shouldShowDate(index: number) {
  if (index === 0) return true
  const currDate = chatStore.messages[index]?.created_at
  const prevDate = chatStore.messages[index - 1]?.created_at
  if (!currDate || !prevDate) return false
  return new Date(currDate).toDateString() !== new Date(prevDate).toDateString()
}
</script>

<template>
  <div class="chat-thread">
    <!-- Header -->
    <div class="chat-header">
      <div class="chat-header-left">
        <div class="chat-avatar" :class="{ private: !groupId }">
          <i v-if="groupId > 0" class="pi pi-users" />
          <i v-else class="pi pi-user" />
        </div>
        <div>
          <h4 class="chat-name">{{ groupName || privateMember?.name || `Chat #${groupId}` }}</h4>
          <p class="chat-status">{{ chatStore.messages.length }} messages</p>
        </div>
      </div>
    </div>

    <!-- Messages area -->
    <div class="messages-area" ref="messagesEl">
      <div v-if="chatStore.loading" class="chat-loading" style="text-align:center;padding:2rem;">
        <i class="pi pi-spin pi-spinner" style="font-size:1.5rem;color:var(--color-primary);" />
      </div>

      <template v-else-if="chatStore.messages.length > 0">
        <template v-for="(msg, i) in chatStore.messages" :key="msg.id || i">
          <!-- Date separator -->
          <div v-if="shouldShowDate(i)" class="date-separator">
            <span>{{ formatDate(msg.created_at) }}</span>
          </div>

          <!-- Message -->
          <div class="message-row" :class="{ mine: isMine(msg) }">
            <div v-if="!isMine(msg) && shouldShowAvatar(i)" class="msg-avatar avatar avatar-sm">
              {{ getInitials(msg) }}
            </div>
            <div v-else-if="!isMine(msg)" class="msg-avatar-spacer" />

            <div class="message-group">
              <p v-if="!isMine(msg) && shouldShowAvatar(i)" class="msg-sender">
                {{ getUserName(msg) }}
              </p>
              <div class="message-bubble" :class="{ mine: isMine(msg) }">
                <p v-if="msg.message" class="msg-text">{{ msg.message }}</p>
                
                <!-- Attachment -->
                <div v-if="msg.attachment_url" class="msg-attachment">
                  <a :href="msg.attachment_url" target="_blank" class="attachment-link">
                    <i class="pi" :class="getFileIcon(msg.attachment_url)" />
                    <span>{{ msg.attachment_name || getFileName(msg.attachment_url) }}</span>
                  </a>
                </div>

                <span class="msg-time">{{ formatTime(msg.created_at) }}</span>
              </div>
            </div>
          </div>
        </template>
      </template>

      <div v-else class="text-muted text-sm" style="text-align:center;padding:3rem;">
        <i class="pi pi-comments" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.5rem;" />
        Aucun message. Soyez le premier à écrire !
      </div>
    </div>

    <!-- Input area -->
    <div class="chat-input-area">
      <input 
        type="file" 
        ref="fileInput" 
        style="display:none" 
        @change="handleFileSelect"
      />
      
      <button 
        class="btn btn-icon attachment-btn" 
        @click="triggerFileSelect"
        :class="{ active: selectedFile }"
      >
        <i class="pi pi-paperclip" />
      </button>

      <div class="input-wrapper-container">
        <div v-if="selectedFile" class="selected-file-badge">
          <i class="pi pi-file" />
          <span>{{ selectedFile.name }}</span>
          <i class="pi pi-times remove-file" @click="removeFile" />
        </div>
        <div class="input-wrapper">
          <input
            ref="inputEl"
            v-model="messageInput"
            type="text"
            class="chat-input"
            :placeholder="selectedFile ? 'Ajouter une description...' : 'Écrire un message...'"
            :disabled="chatStore.sending"
            @keydown="onKeydown"
          />
        </div>
      </div>

      <button
        class="btn btn-primary btn-icon send-btn"
        :disabled="(!messageInput.trim() && !selectedFile) || chatStore.sending"
        @click="sendMessage"
      >
        <i v-if="chatStore.sending" class="pi pi-spin pi-spinner" />
        <i v-else class="pi pi-send" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.chat-thread {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 400px;
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-default);
  overflow: hidden;
}

.chat-header {
  display: flex;
  align-items: center;
  padding: 0.875rem 1.25rem;
  border-bottom: 1px solid var(--border-default);
  background: var(--bg-surface);
  flex-shrink: 0;
}

.chat-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.chat-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
}

.chat-name {
  font-size: var(--text-sm);
  font-weight: 700;
  margin-bottom: 0.1rem;
}

.chat-status {
  font-size: var(--text-xs);
  color: var(--text-muted);
}

.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-separator {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 0.75rem 0;
}

.date-separator::before,
.date-separator::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border-default);
}

.date-separator span {
  font-size: var(--text-xs);
  color: var(--text-muted);
  background: var(--bg-elevated);
  padding: 0.15rem 0.75rem;
  border-radius: var(--radius-full);
}

.message-row {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
}

.message-row.mine {
  flex-direction: row-reverse;
}

.msg-avatar {
  flex-shrink: 0;
  align-self: flex-end;
}

.msg-avatar-spacer {
  width: 28px;
  flex-shrink: 0;
}

.message-group {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  max-width: 70%;
}

.message-row.mine .message-group {
  align-items: flex-end;
}

.msg-sender {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--text-muted);
  padding-left: 0.5rem;
  margin-bottom: 0.1rem;
}

.message-bubble {
  padding: 0.5rem 0.75rem;
  border-radius: 12px 12px 12px 4px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
}

.message-bubble.mine {
  background: var(--gradient-primary);
  border: none;
  border-radius: 12px 12px 4px 12px;
}

.msg-text {
  font-size: var(--text-sm);
  color: var(--text-primary);
  line-height: 1.4;
  word-break: break-word;
}

.message-bubble.mine .msg-text {
  color: white;
}

.msg-time {
  font-size: 10px;
  color: var(--text-muted);
}

.message-bubble.mine .msg-time {
  color: rgba(255, 255, 255, 0.65);
}

.chat-avatar.private {
  background: var(--gradient-secondary, linear-gradient(135deg, #6366f1 0%, #a855f7 100%));
}

.msg-attachment {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: var(--radius-md);
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.mine .msg-attachment {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.attachment-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: var(--text-primary);
  font-size: var(--text-xs);
}

.mine .attachment-link {
  color: white;
}

.attachment-btn {
  color: var(--text-muted);
}

.attachment-btn.active {
  color: var(--color-primary);
}

.input-wrapper-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.selected-file-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: 11px;
  width: fit-content;
}

.remove-file {
  cursor: pointer;
  font-size: 10px;
  opacity: 0.6;
}

.remove-file:hover {
  opacity: 1;
  color: var(--color-error, #ef4444);
}

.chat-input-area {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-default);
  background: var(--bg-surface);
  flex-shrink: 0;
}

.input-wrapper {
  flex: 1;
}

.chat-input {
  width: 100%;
  padding: 0.5rem 1rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-full);
  font-size: var(--text-sm);
  font-family: var(--font-sans);
  outline: none;
  color: var(--text-primary);
}

.chat-input:focus {
  border-color: var(--color-primary);
}

.chat-input::placeholder {
  color: var(--text-muted);
}

.send-btn {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
}
</style>