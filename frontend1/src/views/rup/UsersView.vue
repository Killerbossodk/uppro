<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const activeTab = ref<'all' | 'etudiant' | 'professeur'>('all')

const users = ref([
  { id: 1, name: 'Amira Benali', email: 'a.benali@esi.dz', role: 'etudiant', level: 'GL3', status: 'actif' },
  { id: 2, name: 'Karim Meziani', email: 'k.meziani@esi.dz', role: 'etudiant', level: 'GL3', status: 'inactif' },
  { id: 3, name: 'Pr. Boudjemaa', email: 'boudjemaa@esi.dz', role: 'professeur', level: 'N/A', status: 'actif' },
  { id: 4, name: 'Sara Ouali', email: 's.ouali@esi.dz', role: 'etudiant', level: 'Master 1', status: 'actif' },
  { id: 5, name: 'Dr. Kone', email: 'kone@esi.dz', role: 'professeur', level: 'N/A', status: 'actif' },
])

const filteredUsers = computed(() => {
  if (activeTab.value === 'all') return users.value
  return users.value.filter(u => u.role === activeTab.value)
})

const showAddModal = ref(false)
const showImportModal = ref(false)

const newUser = ref({ name: '', email: '', role: 'etudiant', level: '' })

function addUser() {
  if (newUser.value.name && newUser.value.email) {
    users.value.push({
      id: Date.now(),
      ...newUser.value,
      status: 'actif'
    })
    showAddModal.value = false
    toast.add({ severity: 'success', summary: 'Utilisateur ajouté', detail: 'Un email d\'invitation lui a été envoyé.', life: 3000 })
    newUser.value = { name: '', email: '', role: 'etudiant', level: '' }
  }
}

function simulateImport() {
  toast.add({ severity: 'info', summary: 'Importation en cours', detail: 'Traitement du fichier CSV...', life: 2000 })
  setTimeout(() => {
    toast.add({ severity: 'success', summary: 'Import réussi', detail: '45 étudiants ont été importés et inscrits.', life: 3000 })
    showImportModal.value = false
  }, 2000)
}

function getRoleBadge(role: string) {
  if (role === 'professeur') return { label: 'Professeur', cls: 'badge-primary' }
  return { label: 'Étudiant', cls: 'badge-success' }
}

function getStatusBadge(status: string) {
  if (status === 'actif') return { label: 'Actif', cls: 'badge-success' }
  return { label: 'Inactif', cls: 'badge-danger' }
}
</script>

<template>
  <div class="users-page">
    <div class="page-header animate-fade-in">
      <div>
        <h2 class="page-title-h2">Gestion des Utilisateurs</h2>
        <p class="text-secondary text-sm">Gérez les inscriptions des étudiants et enseignants sur la plateforme.</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary" @click="showImportModal = true">
          <i class="pi pi-file-excel" /> Importer CSV
        </button>
        <button class="btn btn-primary" @click="showAddModal = true">
          <i class="pi pi-user-plus" /> Ajouter
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-nav card animate-fade-in">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'all' }"
        @click="activeTab = 'all'"
      >
        Tous ({{ users.length }})
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'etudiant' }"
        @click="activeTab = 'etudiant'"
      >
        Étudiants ({{ users.filter(u => u.role === 'etudiant').length }})
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'professeur' }"
        @click="activeTab = 'professeur'"
      >
        Professeurs ({{ users.filter(u => u.role === 'professeur').length }})
      </button>
    </div>

    <!-- Users Table -->
    <div class="card p-0 overflow-hidden animate-fade-in" style="animation-delay: 100ms">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom & Prénom</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Niveau/Filière</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar avatar-sm">{{ user.name.substring(0, 2) }}</div>
                  <span class="font-semibold">{{ user.name }}</span>
                </div>
              </td>
              <td class="text-muted">{{ user.email }}</td>
              <td>
                <span :class="`badge ${getRoleBadge(user.role).cls}`">{{ getRoleBadge(user.role).label }}</span>
              </td>
              <td>{{ user.level }}</td>
              <td>
                <span :class="`badge ${getStatusBadge(user.status).cls}`">{{ getStatusBadge(user.status).label }}</span>
              </td>
              <td class="text-right">
                <button class="btn btn-ghost btn-icon btn-sm"><i class="pi pi-pencil" /></button>
                <button class="btn btn-ghost btn-icon btn-sm text-danger"><i class="pi pi-trash" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add User Modal -->
    <Transition name="page">
      <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
        <div class="modal-card card">
          <div class="modal-header">
            <h3>Ajouter un utilisateur</h3>
            <button class="btn btn-ghost btn-icon btn-sm" @click="showAddModal = false"><i class="pi pi-times" /></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label">Nom et Prénom</label>
              <input v-model="newUser.name" type="text" class="form-input" placeholder="Ex: Jean Dupont" />
            </div>
            <div class="form-group">
              <label class="form-label">Adresse Email</label>
              <input v-model="newUser.email" type="email" class="form-input" placeholder="Ex: j.dupont@esi.dz" />
            </div>
            <div class="form-row flex gap-3">
              <div class="form-group flex-1">
                <label class="form-label">Rôle</label>
                <select v-model="newUser.role" class="form-input">
                  <option value="etudiant">Étudiant</option>
                  <option value="professeur">Professeur</option>
                </select>
              </div>
              <div class="form-group flex-1">
                <label class="form-label">Niveau (si étudiant)</label>
                <input v-model="newUser.level" type="text" class="form-input" placeholder="Ex: GL3" :disabled="newUser.role === 'professeur'" />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showAddModal = false">Annuler</button>
            <button class="btn btn-primary" @click="addUser" :disabled="!newUser.name || !newUser.email">Enregistrer</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Import CSV Modal -->
    <Transition name="page">
      <div v-if="showImportModal" class="modal-overlay" @click.self="showImportModal = false">
        <div class="modal-card card">
          <div class="modal-header">
            <h3>Importer depuis un CSV</h3>
            <button class="btn btn-ghost btn-icon btn-sm" @click="showImportModal = false"><i class="pi pi-times" /></button>
          </div>
          <div class="modal-body flex flex-col items-center justify-center py-8">
            <div class="import-icon mb-4">
              <i class="pi pi-cloud-upload text-4xl text-primary" />
            </div>
            <p class="text-center text-sm mb-4">Glissez-déposez votre fichier CSV ici ou cliquez pour parcourir.</p>
            <button class="btn btn-secondary btn-sm mb-4">Sélectionner un fichier</button>
            <p class="text-xs text-muted bg-surface p-3 rounded">Le fichier CSV doit contenir les colonnes : <strong>Nom, Email, Niveau, Filière</strong>.</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showImportModal = false">Annuler</button>
            <button class="btn btn-primary" @click="simulateImport"><i class="pi pi-upload" /> Lancer l'import</button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.page-title-h2 {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
  margin-bottom: 0.3rem;
}

.tabs-nav {
  display: flex;
  gap: 1rem;
  padding: 0.5rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
}

.tab-btn {
  padding: 0.75rem 1.5rem;
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.tab-btn:hover { background: var(--bg-hover); }
.tab-btn.active { background: var(--color-primary-subtle); color: var(--color-primary); font-weight: 700; }

.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border-default); font-size: var(--text-sm); }
.data-table th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; background: var(--bg-surface); }
.data-table tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover { background: var(--bg-hover); }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-card { width: 100%; max-width: 500px; padding: 0; }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-default); display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; }
.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-default); display: flex; justify-content: flex-end; gap: 0.75rem; }
.import-icon { width: 80px; height: 80px; background: var(--color-primary-subtle); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
</style>
