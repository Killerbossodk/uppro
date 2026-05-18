<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const announcements = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get('/announcements')
    announcements.value = Array.isArray(res.data.data) 
      ? res.data.data 
      : (Array.isArray(res.data) ? res.data : [])
  } catch (e) {
    console.error('Erreur annonces:', e)
  } finally {
    loading.value = false
  }
})

function getTargetLabel(type: string) {
  const labels: Record<string, string> = {
    all: 'Tous', professors: 'Professeurs', students: 'Étudiants',
    group: 'Groupe', user: 'Utilisateur'
  }
  return labels[type] || type
}

function formatDate(date: string) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR')
}
</script>

<template>
  <div class="announcements-page">
    <div class="page-header">
      <div>
        <h2 class="page-title-h2">Annonces & Communiqués</h2>
        <p class="text-muted text-sm">Messages diffusés par le RUP Projet</p>
      </div>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else-if="announcements.length === 0" class="card text-center py-8">
      <i class="pi pi-megaphone" style="font-size:3rem;opacity:0.3;color:var(--text-muted)" />
      <p class="text-muted mt-2">Aucune annonce pour le moment</p>
    </div>

    <div v-else class="announcements-list">
      <div v-for="a in announcements" :key="a.id" class="card announcement-card">
        <div class="flex items-start gap-3">
          <div class="announcement-icon">
            <i class="pi pi-megaphone" />
          </div>
          <div class="flex-1">
            <h3 class="font-bold">{{ a.title }}</h3>
            <p class="text-muted text-sm mt-1">{{ a.content }}</p>
            <div class="flex gap-3 mt-2 text-xs text-muted">
              <span><i class="pi pi-users" /> {{ getTargetLabel(a.target_type) }}</span>
              <span><i class="pi pi-clock" /> {{ formatDate(a.created_at) }}</span>
              <span>Par : {{ a.creator?.prenom }} {{ a.creator?.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.announcements-page { max-width: 800px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; }
.announcements-list { display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem; }
.announcement-icon { width: 44px; height: 44px; border-radius: var(--radius-md); background: var(--color-primary-subtle); color: var(--color-primary-light); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
</style>