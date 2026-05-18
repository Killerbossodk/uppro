<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useAuthStore } from '@/magasins/auth'

const auth = useAuthStore()
const messages = ref<{ id: number; user: string; content: string; time: string; isMe: boolean }[]>([])
const newMsg = ref('')
const chatEl = ref<HTMLElement | null>(null)

// Stockage local (simulé pour le chat privé étudiant)
onMounted(() => {
  const saved = localStorage.getItem(`private_chat_${auth.user?.id}`)
  if (saved) {
    messages.value = JSON.parse(saved)
  }
  scrollBottom()
})

function sendMessage() {
  if (!newMsg.value.trim()) return
  messages.value.push({
    id: Date.now(),
    user: auth.user?.prenom || auth.user?.name || 'Étudiant',
    content: newMsg.value,
    time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
    isMe: true
  })
  localStorage.setItem(`private_chat_${auth.user?.id}`, JSON.stringify(messages.value))
  newMsg.value = ''
  nextTick(() => scrollBottom())
}

function scrollBottom() {
  if (chatEl.value) {
    chatEl.value.scrollTop = chatEl.value.scrollHeight
  }
}
</script>

<template>
  <div class="private-chat">
    <div class="chat-messages" ref="chatEl">
      <div v-if="messages.length === 0" class="text-center text-muted py-4 text-sm">
        <i class="pi pi-comments" style="font-size:2rem;opacity:0.3;" />
        <p class="mt-2">Espace de discussion entre étudiants</p>
        <p class="text-xs mt-1">Les messages sont stockés localement</p>
      </div>
      <div v-for="msg in messages" :key="msg.id" :class="['message', msg.isMe ? 'me' : 'other']">
        <p class="msg-user">{{ msg.isMe ? 'Moi' : msg.user }}</p>
        <div class="msg-bubble">
          <p>{{ msg.content }}</p>
          <span class="msg-time">{{ msg.time }}</span>
        </div>
      </div>
    </div>
    <div class="chat-input">
      <input
        v-model="newMsg"
        type="text"
        placeholder="Écrivez votre message..."
        @keydown.enter="sendMessage"
        class="form-input flex-1"
      />
      <button class="btn btn-primary btn-sm" @click="sendMessage">
        <i class="pi pi-send" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.private-chat {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.message {
  display: flex;
  flex-direction: column;
  max-width: 80%;
}
.message.me {
  align-self: flex-end;
}
.message.other {
  align-self: flex-start;
}
.msg-user {
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 2px;
  padding: 0 0.5rem;
}
.msg-bubble {
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  font-size: var(--text-sm);
}
.message.me .msg-bubble {
  background: var(--gradient-primary);
  color: white;
  border-radius: 12px 12px 4px 12px;
}
.message.other .msg-bubble {
  background: var(--bg-elevated);
  color: var(--text-primary);
  border: 1px solid var(--border-default);
  border-radius: 12px 12px 12px 4px;
}
.msg-time {
  font-size: 10px;
  opacity: 0.7;
  display: block;
  text-align: right;
  margin-top: 2px;
}
.chat-input {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem;
  border-top: 1px solid var(--border-default);
  background: var(--bg-surface);
}
</style>