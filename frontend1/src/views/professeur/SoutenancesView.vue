<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const defenses = ref<any[]>([])

onMounted(async () => {
  try {
    const res = await api.get('/meetings')
    defenses.value = res.data.filter((m: any) => m.type === 'soutenance')
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="soutenances-page p-6 max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-black uppercase text-slate-900 tracking-tight">Mes Soutenances</h1>
        <p class="text-slate-500">Liste des sessions où vous êtes membre du jury ou président.</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <i class="pi pi-spin pi-spinner text-3xl text-blue-600"></i>
    </div>

    <div v-else-if="defenses.length === 0" class="card p-20 text-center bg-slate-50 border-dashed border-2 border-slate-200 rounded-3xl">
      <i class="pi pi-calendar-times text-5xl text-slate-300 mb-4"></i>
      <h3 class="text-xl font-bold text-slate-400">Aucune soutenance prévue</h3>
    </div>

    <div v-else class="grid gap-4">
      <div v-for="def in defenses" :key="def.id" class="card bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-all flex justify-between items-center">
        <div class="flex gap-6 items-center">
          <div class="date-box bg-blue-50 text-blue-600 p-3 rounded-xl text-center min-w-[70px]">
             <span class="block text-2xl font-black">{{ new Date(def.date_heure).getDate() }}</span>
             <span class="block text-[10px] font-bold uppercase">{{ new Date(def.date_heure).toLocaleDateString('fr-FR', { month: 'short' }) }}</span>
          </div>
          <div>
            <h3 class="font-bold text-lg text-slate-900">{{ def.titre }}</h3>
            <div class="flex gap-4 text-sm text-slate-500 mt-1">
              <span><i class="pi pi-map-marker mr-1"></i> {{ def.lieu || 'Salle Virtuelle' }}</span>
              <span><i class="pi pi-clock mr-1"></i> {{ new Date(def.date_heure).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>
            <div class="mt-2 flex gap-2">
               <span v-for="g in def.groupes" :key="g.id" class="text-[10px] bg-slate-100 px-2 py-0.5 rounded font-bold">{{ g.nom }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-4">
           <div :class="['status-pill', def.statut_soutenance]">
              {{ def.statut_soutenance === 'en_cours' ? '🔴 EN DIRECT' : def.statut_soutenance === 'termine' ? 'TERMINÉ' : 'PROGRAMMÉ' }}
           </div>
           <router-link :to="'/soutenance-live/' + def.id" class="btn bg-blue-600 text-white px-5 py-2 rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors">
              Rejoindre Live
           </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.status-pill {
  font-size: 10px;
  font-weight: 900;
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 0.05em;
}
.status-pill.planifie { background: #f1f5f9; color: #64748b; }
.status-pill.en_cours { background: #fee2e2; color: #ef4444; }
.status-pill.termine { background: #dcfce7; color: #16a34a; }
</style>
