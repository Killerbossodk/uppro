```vue
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/magasins/notifications'
import { useAuthStore } from '@/magasins/auth'

const auth = useAuthStore()
const router = useRouter()
const notifStore = useNotificationsStore()
const isOpen = ref(false)
let pollInterval: any = null

onMounted(async () => {
  await notifStore.fetchNotifications()
  // Polling toutes les 30 secondes pour les notifications
  pollInterval = setInterval(() => {
    notifStore.fetchNotifications()
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

const typeIcon: Record<string, string> = {
  info: 'pi-info-circle', success: 'pi-check-circle', warning: 'pi-exclamation-triangle',
  error: 'pi-times-circle', message: 'pi-comments', rdv: 'pi-calendar',
}
const typeColor: Record<string, string> = {
  info: 'var(--color-info)', success: 'var(--color-success)', warning: 'var(--color-warning)',
  error: 'var(--color-danger)', message: 'var(--color-primary-light)', rdv: 'var(--color-accent)',
}
const typeBg: Record<string, string> = {
  info: 'rgba(59,130,246,0.12)', success: 'rgba(16,185,129,0.12)', warning: 'rgba(245,158,11,0.12)',
  error: 'rgba(239,68,68,0.12)', message: 'rgba(99,102,241,0.12)', rdv: 'rgba(236,72,153,0.12)',
}

function formatTime(d: string) {
  const mins = Math.floor((Date.now() - new Date(d).getTime()) / 60000)
  if (mins < 1) return "À l'instant"
  if (mins < 60) return `Il y a ${mins} min`
  const h = Math.floor(mins / 60)
  if (h < 24) return `Il y a ${h}h`
  return `Il y a ${Math.floor(h / 24)}j`
}

async function handleClick(notif: any) {
  if (!notif.lu) {
    await notifStore.markAsRead(notif.id as number)
  }
  isOpen.value = false

  let data = notif.data
  if (typeof data === 'string') {
    try { data = JSON.parse(data) } catch { data = {} }
  }

  const role = auth.user?.role

  if (data?.group_id) {
    router.push(`/messagerie?groupId=${data.group_id}`)
  } else if (data?.sender_id) {
    router.push(`/messagerie?userId=${data.sender_id}`)
  } else if (data?.meeting_id) {
    if (role === 'professeur') {
      router.push(`/soutenance-live/${data.meeting_id}`)
    } else if (role === 'etudiant') {
      if (data.is_grade) {
        router.push(`/etudiant/soutenance-resultat/${data.meeting_id}`)
      } else {
        router.push(`/etudiant/fiche-soutenance/${data.meeting_id}`)
      }
    } else if (role === 'rup_projet') {
      router.push(`/rup-projet/soutenances`)
    } else if (role === 'rup_specialite') {
      router.push(`/rup/jurys`)
    } else {
      router.push(`/soutenance-live/${data.meeting_id}`)
    }
  }
}
</script>

<template>
  <div class="notif-wrap">
    <button class="btn btn-ghost btn-icon notif-btn" :class="{ active: isOpen }" @click="isOpen = !isOpen">
      <i class="pi pi-bell" />
      <span v-if="notifStore.unreadCount > 0" class="notif-badge">{{ notifStore.unreadCount }}</span>
    </button>

    <Transition name="np">
      <div v-if="isOpen" class="notif-panel">
        <div class="np-head">
          <span class="np-title">Notifications</span>
          <button v-if="notifStore.unreadCount" class="btn btn-ghost btn-sm" @click="notifStore.markAllAsRead()">Tout lire</button>
        </div>
        <div class="np-list" v-if="notifStore.notifications.length">
          <div 
            v-for="n in notifStore.notifications" 
            :key="n.id" 
            class="np-item" 
            :class="{ unread: !n.read }" 
            @click="handleClick(n)"
          >
            <div class="np-icon" :style="{ background: typeBg[n.type] || typeBg.info, color: typeColor[n.type] || typeColor.info }">
              <i :class="`pi ${typeIcon[n.type] || 'pi-bell'}`" />
            </div>
            <div class="np-body">
              <p class="np-ntitle">{{ n.title }}</p>
              <p class="np-msg">{{ n.message }}</p>
              <p class="np-time">{{ formatTime(n.created_at) }}</p>
            </div>
            <span v-if="!n.read" class="np-dot" />
          </div>
        </div>
        <div v-else class="np-empty">
          <i class="pi pi-bell-slash" />
          <p>Aucune notification</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.notif-wrap { position: relative; display: inline-flex; }
.notif-btn { position: relative; width: 40px; height: 40px; }
.notif-btn.active { background: var(--color-primary-subtle); color: var(--color-primary-light); }
.notif-badge {
  position: absolute; top: 2px; right: 2px;
  min-width: 18px; height: 18px; border-radius: 999px;
  background: var(--color-danger); color: white;
  font-size: 10px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.notif-panel {
  position: absolute; top: 48px; right: -20px;
  width: 360px; max-height: 480px;
  background: var(--bg-card); border: 1px solid var(--border-default);
  border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
  display: flex; flex-direction: column; overflow: hidden; z-index: 200;
}
.np-enter-active, .np-leave-active { transition: all 0.2s ease; }
.np-enter-from, .np-leave-to { opacity: 0; transform: translateY(-8px) scale(0.95); }
.np-head { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid var(--border-default); }
.np-title { font-weight: 700; font-size: var(--text-sm); }
.np-list { overflow-y: auto; flex: 1; }
.np-item { display: flex; gap: 0.75rem; padding: 0.875rem 1rem; cursor: pointer; border-bottom: 1px solid var(--border-default); transition: background var(--transition-fast); }
.np-item:hover { background: var(--bg-hover); }
.np-item.unread { background: rgba(99,102,241,0.04); }
.np-icon { width: 36px; height: 36px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem; }
.np-body { flex: 1; min-width: 0; }
.np-ntitle { font-weight: 600; font-size: var(--text-xs); margin-bottom: 0.15rem; }
.np-msg { font-size: var(--text-xs); color: var(--text-muted); }
.np-time { font-size: 10px; color: var(--text-muted); margin-top: 0.25rem; }
.np-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary); flex-shrink: 0; margin-top: 4px; }
.np-empty { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: var(--text-muted); }
.np-empty .pi { font-size: 2.5rem; opacity: 0.3; }
</style>
```