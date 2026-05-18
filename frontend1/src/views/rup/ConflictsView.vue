<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const conflicts = ref<any[]>([])

onMounted(async () => {
  // Les conflits sont gérés via les signalements, pas de route dédiée
  // On affiche les groupes avec des problèmes potentiels
  loading.value = false
})

async function contacterGroupe(groupId: number) {
  alert('Fonctionnalité à implémenter : contacter le groupe ' + groupId)
}
</script>

<template>
  <div class="conflicts-page">
    <div class="page-header">
      <h2 class="page-title-h2">Conflits & Signalements</h2>
      <p class="text-muted text-sm">Gérez les problèmes signalés par les encadrants</p>
    </div>

    <div v-if="loading" class="card text-center py-8">
      <i class="pi pi-spin pi-spinner" style="font-size:2rem;color:var(--color-primary)" />
    </div>

    <div v-else class="card text-center py-8">
      <i class="pi pi-exclamation-triangle" style="font-size:3rem;opacity:0.3;color:var(--text-muted)" />
      <p class="text-muted mt-2">Aucun conflit en cours</p>
      <p class="text-xs text-muted mt-1">Les signalements des professeurs apparaîtront ici</p>
    </div>
  </div>
</template>

<style scoped>
.conflicts-page { max-width: 1000px; }
.page-title-h2 { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; margin-bottom: 0.5rem; }
</style>