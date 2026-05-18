<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import ChatThread from '@/components/ChatThread.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const users = ref<any[]>([])
const groups = ref<any[]>([])
const selectedType = ref<'private' | 'group'>('private')
const selectedId = ref<number | null>(null)
const selectedName = ref('')
const loading = ref(true)
const search = ref('')

async function fetchData() {
  loading.value = true
  try {
    const [usersRes, groupsRes] = await Promise.all([
      api.get('/chat/contacts'),
      api.get('/groups')
    ])
    users.value = usersRes.data.filter((u: any) => u.id !== auth.user?.id)
    groups.value = groupsRes.data
    
    // Handle initial selection from query
    if (route.query.userId) {
      selectedType.value = 'private'
      selectedId.value = Number(route.query.userId)
      const u = users.value.find(u => u.id === selectedId.value)
      if (u) selectedName.value = u.prenom + ' ' + u.name
    } else if (route.query.groupId) {
      selectedType.value = 'group'
      selectedId.value = Number(route.query.groupId)
      const g = groups.value.find(g => g.id === selectedId.value)
      if (g) selectedName.value = g.nom
    }
  } catch (e) {
    console.error('Erreur chargement données messagerie:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

function selectPrivate(user: any) {
  selectedType.value = 'private'
  selectedId.value = user.id
  selectedName.value = user.prenom + ' ' + user.name
  router.replace({ query: { userId: user.id } })
}

function selectGroup(group: any) {
  selectedType.value = 'group'
  selectedId.value = group.id
  selectedName.value = group.nom
  router.replace({ query: { groupId: group.id } })
}

const filteredUsers = ref<any[]>([])
watch([users, search], () => {
  if (!search.value) {
    filteredUsers.value = users.value
  } else {
    const s = search.value.toLowerCase()
    filteredUsers.value = users.value.filter(u => 
      u.name.toLowerCase().includes(s) || 
      u.prenom.toLowerCase().includes(s) || 
      u.email.toLowerCase().includes(s)
    )
  }
}, { immediate: true })

function getInitials(name: string) {
  return name.split(' ').map(n => n[0] || '').join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <div class="messages-view">
    <div class="messages-sidebar card">
      <div class="sidebar-header">
        <h2 class="title">Messagerie</h2>
        <div class="tabs">
          <button 
            class="tab-btn" 
            :class="{ active: selectedType === 'private' }" 
            @click="selectedType = 'private'"
          >
            Privé
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: selectedType === 'group' }" 
            @click="selectedType = 'group'"
          >
            Groupes
          </button>
        </div>
        <div class="search-box">
          <i class="pi pi-search" />
          <input v-model="search" type="text" placeholder="Rechercher..." />
        </div>
      </div>

      <div class="sidebar-list">
        <div v-if="loading" class="loading">
          <i class="pi pi-spin pi-spinner" />
        </div>

        <template v-else-if="selectedType === 'private'">
          <div 
            v-for="u in filteredUsers" 
            :key="u.id" 
            class="list-item"
            :class="{ active: selectedType === 'private' && selectedId === u.id }"
            @click="selectPrivate(u)"
          >
            <div class="avatar">{{ getInitials(u.prenom + ' ' + u.name) }}</div>
            <div class="item-info">
              <p class="name">{{ u.prenom }} {{ u.name }}</p>
              <p class="subtext">{{ u.role }}</p>
            </div>
          </div>
        </template>

        <template v-else>
          <div 
            v-for="g in groups" 
            :key="g.id" 
            class="list-item"
            :class="{ active: selectedType === 'group' && selectedId === g.id }"
            @click="selectGroup(g)"
          >
            <div class="avatar group"><i class="pi pi-users" /></div>
            <div class="item-info">
              <p class="name">{{ g.nom }}</p>
              <p class="subtext">Groupe de projet</p>
            </div>
          </div>
        </template>
      </div>
    </div>

    <div class="messages-content card">
      <template v-if="selectedId">
        <ChatThread 
          :key="`${selectedType}-${selectedId}`"
          :group-id="selectedType === 'group' ? selectedId : 0"
          :group-name="selectedType === 'group' ? selectedName : ''"
          :private-member="selectedType === 'private' ? { id: selectedId, name: selectedName } : null"
        />
      </template>
      <div v-else class="empty-state">
        <div class="empty-illustration">
          <i class="pi pi-comments" />
        </div>
        <h3>Vos conversations</h3>
        <p>Sélectionnez une discussion dans la liste pour commencer à échanger.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.messages-view {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 1.5rem;
  height: calc(100vh - 140px);
  max-width: 1600px;
  margin: 0 auto;
}

.messages-sidebar {
  display: flex;
  flex-direction: column;
  padding: 0;
  overflow: hidden;
}

.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid var(--border-default);
}

.title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 800;
  margin-bottom: 1rem;
}

.tabs {
  display: flex;
  background: var(--bg-elevated);
  padding: 0.25rem;
  border-radius: var(--radius-md);
  margin-bottom: 1rem;
}

.tab-btn {
  flex: 1;
  padding: 0.5rem;
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-size: var(--text-xs);
  font-weight: 600;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: all 0.2s;
}

.tab-btn.active {
  background: var(--bg-card);
  color: var(--color-primary-light);
  box-shadow: var(--shadow-sm);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 0.75rem;
  color: var(--text-muted);
  font-size: 0.8rem;
}

.search-box input {
  width: 100%;
  padding: 0.5rem 1rem 0.5rem 2rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-full);
  font-size: var(--text-sm);
  outline: none;
}

.sidebar-list {
  flex: 1;
  overflow-y: auto;
}

.list-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid var(--border-default);
}

.list-item:hover {
  background: var(--bg-hover);
}

.list-item.active {
  background: var(--color-primary-subtle);
  border-left: 3px solid var(--color-primary);
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--bg-elevated);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-primary);
  flex-shrink: 0;
}

.avatar.group {
  background: var(--gradient-primary);
  color: white;
}

.item-info {
  min-width: 0;
}

.name {
  font-size: var(--text-sm);
  font-weight: 600;
  margin-bottom: 0.1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.subtext {
  font-size: 11px;
  color: var(--text-muted);
  text-transform: capitalize;
}

.messages-content {
  padding: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 3rem;
  color: var(--text-muted);
}

.empty-illustration {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--bg-elevated);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.empty-illustration i {
  font-size: 2.5rem;
  opacity: 0.3;
}

.empty-state h3 {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
  color: var(--color-primary);
}

@media (max-width: 1024px) {
  .messages-view {
    grid-template-columns: 240px 1fr;
  }
}

@media (max-width: 768px) {
  .messages-view {
    grid-template-columns: 1fr;
  }
  .messages-sidebar {
    display: v-bind(selectedId ? 'none' : 'flex');
  }
  .messages-content {
    display: v-bind(selectedId ? 'flex' : 'none');
  }
}
</style>
