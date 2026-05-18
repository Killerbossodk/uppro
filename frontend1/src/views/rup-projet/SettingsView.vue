<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const settings = ref({ submissions_open: true, inscriptions_open: true })
const loading = ref(false)
const saving = ref(false)

async function fetchSettings() {
  loading.value = true
  try {
    const res = await api.get('/settings')
    settings.value = res.data
  } catch (e) {
    console.error('Erreur chargement paramètres:', e)
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await api.put('/settings', settings.value)
    alert('Paramètres enregistrés')
  } catch (e: any) {
    alert('Erreur : ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

onMounted(fetchSettings)
</script>

<template>
  <div class="settings-page">
    <div class="page-header">
      <h2 class="page-title-h2">Paramètres généraux</h2>
      <p class="text-muted text-sm">Gestion des périodes de soumission et d'inscription</p>
    </div>

    <div class="card">
      <div v-if="loading" class="text-center py-4">Chargement...</div>
      <div v-else class="space-y-4">
        <div class="setting-row">
          <div>
            <h3 class="font-medium">Soumissions de projets</h3>
            <p class="text-sm text-muted">Les professeurs peuvent soumettre de nouveaux projets</p>
          </div>
          <button 
            @click="settings.submissions_open = !settings.submissions_open"
            :class="settings.submissions_open ? 'bg-success' : 'bg-muted'"
            class="toggle-btn"
          >
            <span :class="settings.submissions_open ? 'translate-x-6' : 'translate-x-1'" class="toggle-dot" />
          </button>
        </div>

        <div class="setting-row">
          <div>
            <h3 class="font-medium">Inscriptions des étudiants</h3>
            <p class="text-sm text-muted">Les étudiants peuvent s'inscrire aux groupes</p>
          </div>
          <button 
            @click="settings.inscriptions_open = !settings.inscriptions_open"
            :class="settings.inscriptions_open ? 'bg-success' : 'bg-muted'"
            class="toggle-btn"
          >
            <span :class="settings.inscriptions_open ? 'translate-x-6' : 'translate-x-1'" class="toggle-dot" />
          </button>
        </div>

        <div class="flex justify-end mt-4">
          <button class="btn btn-primary btn-sm" :disabled="saving" @click="saveSettings">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.settings-page { max-width: 700px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; margin-bottom: 0.5rem; }
.setting-row { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid var(--border-default); border-radius: var(--radius-md); }
.toggle-btn { position: relative; width: 44px; height: 24px; border-radius: 999px; border: none; cursor: pointer; transition: background 0.2s; flex-shrink: 0; }
.toggle-dot { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: white; transition: transform 0.2s; display: block; }
.bg-success { background: var(--color-success); }
.bg-muted { background: var(--text-muted); }
</style>