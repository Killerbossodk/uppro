<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useAuthStore } from '@/magasins/auth'
import { useToast } from 'primevue/usetoast'

const auth = useAuthStore()
const toast = useToast()

const activeTab = ref<'profile' | 'password' | 'preferences'>('profile')

// Profile form
const profile = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  phone: '+213 555 123 456',
  department: 'Département Informatique',
  bio: 'Enseignant-chercheur spécialisé en génie logiciel et développement web.',
})

// Password form
const passwords = reactive({
  current: '',
  next: '',
  confirm: '',
})
const showCurrent = ref(false)
const showNext = ref(false)

// Preferences
const prefs = reactive({
  emailNotif: true,
  pushNotif: true,
  rdvReminder: true,
  reportSubmit: true,
  theme: 'dark',
  language: 'fr',
})

function saveProfile() {
  // API call would go here
  toast.add({ severity: 'success', summary: 'Profil mis à jour', detail: 'Vos informations ont été sauvegardées.', life: 3000 })
}

function changePassword() {
  if (passwords.next !== passwords.confirm) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Les mots de passe ne correspondent pas.', life: 3000 })
    return
  }
  if (passwords.next.length < 8) {
    toast.add({ severity: 'warn', summary: 'Trop court', detail: 'Le mot de passe doit contenir au moins 8 caractères.', life: 3000 })
    return
  }
  toast.add({ severity: 'success', summary: 'Mot de passe modifié', detail: 'Votre mot de passe a été mis à jour.', life: 3000 })
  passwords.current = ''
  passwords.next = ''
  passwords.confirm = ''
}

function savePreferences() {
  toast.add({ severity: 'success', summary: 'Préférences sauvegardées', life: 3000 })
}

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const roleLabels: Record<string, string> = {
  professeur: 'Professeur',
  etudiant: 'Étudiant',
  rup: 'RUP',
}

const roleBadge: Record<string, string> = {
  professeur: 'badge-primary',
  etudiant: 'badge-success',
  rup: 'badge-warning',
}

const passwordStrength = computed(() => {
  const p = passwords.next
  if (!p) return 0
  let s = 0
  if (p.length >= 8) s++
  if (/[A-Z]/.test(p)) s++
  if (/[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})

const strengthLabel = computed(() => ['', 'Faible', 'Moyen', 'Bon', 'Fort'][passwordStrength.value] ?? '')
const strengthColor = computed(() => ['', 'var(--color-danger)', 'var(--color-warning)', 'var(--color-info)', 'var(--color-success)'][passwordStrength.value] ?? '')

import { computed } from 'vue'
</script>

<template>
  <div class="profile-page">
    <!-- Header -->
    <div class="profile-header card animate-fade-in">
      <div class="ph-avatar-section">
        <div class="ph-avatar-wrap">
          <div class="avatar avatar-xl">{{ getInitials(auth.user?.name ?? 'U') }}</div>
          <button class="avatar-upload-btn" title="Changer la photo">
            <i class="pi pi-camera" />
          </button>
        </div>
        <div class="ph-info">
          <h2 class="ph-name">{{ auth.user?.name }}</h2>
          <p class="ph-email text-secondary text-sm">{{ auth.user?.email }}</p>
          <span :class="`badge ${roleBadge[auth.user?.role ?? ''] ?? 'badge-info'}`" style="margin-top: 0.375rem; display: inline-flex;">
            {{ roleLabels[auth.user?.role ?? ''] ?? auth.user?.role }}
          </span>
        </div>
      </div>
      <div class="ph-stats">
        <div class="ph-stat">
          <span class="ph-stat-val text-brand">12</span>
          <span class="ph-stat-lbl">Projets</span>
        </div>
        <div class="ph-sep" />
        <div class="ph-stat">
          <span class="ph-stat-val text-brand">8</span>
          <span class="ph-stat-lbl">Groupes</span>
        </div>
        <div class="ph-sep" />
        <div class="ph-stat">
          <span class="ph-stat-val text-brand">24</span>
          <span class="ph-stat-lbl">Rapports</span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar animate-fade-in" style="animation-delay: 60ms">
      <button class="tab-btn" :class="{ active: activeTab === 'profile' }" @click="activeTab = 'profile'" id="tab-profile">
        <i class="pi pi-user" /> Informations
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'password' }" @click="activeTab = 'password'" id="tab-password">
        <i class="pi pi-lock" /> Sécurité
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'preferences' }" @click="activeTab = 'preferences'" id="tab-preferences">
        <i class="pi pi-cog" /> Préférences
      </button>
    </div>

    <Transition name="page" mode="out-in">
      <!-- Profile tab -->
      <div v-if="activeTab === 'profile'" key="profile" class="card form-card animate-fade-in">
        <h3 class="form-section-title">Informations personnelles</h3>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nom complet</label>
            <div class="form-input-icon">
              <i class="pi pi-user" />
              <input v-model="profile.name" type="text" class="form-input" id="profile-name" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <div class="form-input-icon">
              <i class="pi pi-envelope" />
              <input v-model="profile.email" type="email" class="form-input" id="profile-email" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Téléphone</label>
            <div class="form-input-icon">
              <i class="pi pi-phone" />
              <input v-model="profile.phone" type="tel" class="form-input" id="profile-phone" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Département</label>
            <div class="form-input-icon">
              <i class="pi pi-building" />
              <input v-model="profile.department" type="text" class="form-input" id="profile-dept" />
            </div>
          </div>
          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Biographie</label>
            <textarea v-model="profile.bio" class="form-input" style="min-height: 100px; resize: vertical;" id="profile-bio" />
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn-secondary">Annuler</button>
          <button class="btn btn-primary" id="btn-save-profile" @click="saveProfile">
            <i class="pi pi-check" /> Enregistrer
          </button>
        </div>
      </div>

      <!-- Password tab -->
      <div v-else-if="activeTab === 'password'" key="password" class="card form-card animate-fade-in">
        <h3 class="form-section-title">Changer le mot de passe</h3>
        <div class="form-col">
          <div class="form-group">
            <label class="form-label">Mot de passe actuel</label>
            <div class="form-input-icon password-field">
              <i class="pi pi-lock" />
              <input v-model="passwords.current" :type="showCurrent ? 'text' : 'password'" class="form-input" id="pw-current" />
              <button class="pw-toggle" @click="showCurrent = !showCurrent">
                <i :class="`pi ${showCurrent ? 'pi-eye-slash' : 'pi-eye'}`" />
              </button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Nouveau mot de passe</label>
            <div class="form-input-icon password-field">
              <i class="pi pi-lock" />
              <input v-model="passwords.next" :type="showNext ? 'text' : 'password'" class="form-input" id="pw-new" />
              <button class="pw-toggle" @click="showNext = !showNext">
                <i :class="`pi ${showNext ? 'pi-eye-slash' : 'pi-eye'}`" />
              </button>
            </div>
            <!-- Strength bar -->
            <div v-if="passwords.next" class="strength-wrap">
              <div class="strength-bar">
                <div
                  v-for="i in 4" :key="i"
                  class="strength-seg"
                  :style="{ background: i <= passwordStrength ? strengthColor : 'var(--bg-active)' }"
                />
              </div>
              <span class="strength-label" :style="{ color: strengthColor }">{{ strengthLabel }}</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer le nouveau mot de passe</label>
            <div class="form-input-icon">
              <i class="pi pi-lock" />
              <input v-model="passwords.confirm" type="password" class="form-input" id="pw-confirm" />
            </div>
            <p v-if="passwords.confirm && passwords.confirm !== passwords.next" class="pw-mismatch">
              <i class="pi pi-times-circle" /> Les mots de passe ne correspondent pas
            </p>
          </div>
        </div>
        <div class="form-actions">
          <button
            class="btn btn-primary" id="btn-change-pw"
            :disabled="!passwords.current || !passwords.next || passwords.next !== passwords.confirm"
            @click="changePassword"
          >
            <i class="pi pi-shield" /> Changer le mot de passe
          </button>
        </div>
      </div>

      <!-- Preferences tab -->
      <div v-else-if="activeTab === 'preferences'" key="preferences" class="card form-card animate-fade-in">
        <h3 class="form-section-title">Préférences</h3>

        <div class="prefs-section">
          <h4 class="prefs-subtitle">Notifications</h4>
          <div class="prefs-list">
            <label class="pref-item" for="pref-email">
              <div>
                <p class="pref-label">Notifications par email</p>
                <p class="pref-desc text-muted text-xs">Recevoir des emails pour les mises à jour importantes</p>
              </div>
              <input type="checkbox" id="pref-email" v-model="prefs.emailNotif" class="toggle" />
            </label>
            <label class="pref-item" for="pref-push">
              <div>
                <p class="pref-label">Notifications push</p>
                <p class="pref-desc text-muted text-xs">Alertes en temps réel dans l'application</p>
              </div>
              <input type="checkbox" id="pref-push" v-model="prefs.pushNotif" class="toggle" />
            </label>
            <label class="pref-item" for="pref-rdv">
              <div>
                <p class="pref-label">Rappels RDV</p>
                <p class="pref-desc text-muted text-xs">Rappel 30 minutes avant chaque rendez-vous</p>
              </div>
              <input type="checkbox" id="pref-rdv" v-model="prefs.rdvReminder" class="toggle" />
            </label>
            <label class="pref-item" for="pref-report">
              <div>
                <p class="pref-label">Soumission de rapports</p>
                <p class="pref-desc text-muted text-xs">Notifier quand un groupe dépose un rapport</p>
              </div>
              <input type="checkbox" id="pref-report" v-model="prefs.reportSubmit" class="toggle" />
            </label>
          </div>
        </div>

        <div class="prefs-section">
          <h4 class="prefs-subtitle">Interface</h4>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Thème</label>
              <select v-model="prefs.theme" class="form-input" id="pref-theme">
                <option value="dark">Sombre</option>
                <option value="light">Clair</option>
                <option value="auto">Automatique</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Langue</label>
              <select v-model="prefs.language" class="form-input" id="pref-lang">
                <option value="fr">Français</option>
                <option value="ar">Arabe</option>
                <option value="en">Anglais</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" id="btn-save-prefs" @click="savePreferences">
            <i class="pi pi-check" /> Sauvegarder les préférences
          </button>
        </div>
      </div>
    </Transition>

    <!-- Danger zone -->
    <div class="card danger-zone animate-fade-in" style="animation-delay: 200ms">
      <h4 class="danger-title"><i class="pi pi-exclamation-triangle" /> Zone dangereuse</h4>
      <div class="danger-actions">
        <div>
          <p class="text-sm font-medium">Supprimer mon compte</p>
          <p class="text-muted text-xs">Cette action est irréversible. Toutes vos données seront supprimées.</p>
        </div>
        <button class="btn btn-danger-outline btn-sm">
          <i class="pi pi-trash" /> Supprimer le compte
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-page { display: flex; flex-direction: column; gap: 1.5rem; max-width: 860px; }

/* Header */
.profile-header { display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap; }
.ph-avatar-section { display: flex; align-items: center; gap: 1.5rem; }
.ph-avatar-wrap { position: relative; }
.avatar-xl { width: 80px !important; height: 80px !important; font-size: var(--text-2xl) !important; }
.avatar-upload-btn {
  position: absolute; bottom: 0; right: 0;
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--color-primary); color: white; border: none;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; cursor: pointer; transition: all var(--transition-fast);
}
.avatar-upload-btn:hover { background: var(--color-primary-light); }
.ph-name { font-family: var(--font-display); font-size: var(--text-xl); font-weight: 800; margin-bottom: 0.2rem; }
.ph-stats { display: flex; align-items: center; gap: 0; }
.ph-stat { display: flex; flex-direction: column; align-items: center; gap: 0.1rem; padding: 0.75rem 1.5rem; }
.ph-stat-val { font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 800; line-height: 1; }
.ph-stat-lbl { font-size: var(--text-xs); color: var(--text-muted); }
.ph-sep { width: 1px; height: 40px; background: var(--border-default); }

/* Tabs */
.tabs-bar { display: flex; align-items: center; gap: 0.25rem; background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-lg); padding: 0.375rem; width: fit-content; }
.tab-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: var(--radius-md); border: none; background: transparent; color: var(--text-secondary); font-size: var(--text-sm); font-weight: 500; font-family: var(--font-sans); cursor: pointer; transition: all var(--transition-fast); }
.tab-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
.tab-btn.active { background: var(--color-primary-subtle); color: var(--color-primary-light); font-weight: 600; }

/* Form card */
.form-card { display: flex; flex-direction: column; gap: 1.5rem; }
.form-section-title { font-size: var(--text-base); font-weight: 700; padding-bottom: 1rem; border-bottom: 1px solid var(--border-default); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.form-col { display: flex; flex-direction: column; gap: 1.25rem; }
.form-input-icon { position: relative; }
.form-input-icon .pi { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; z-index: 1; }
.form-input-icon .form-input { padding-left: 2.5rem; }
.password-field .form-input { padding-right: 2.5rem; }
.pw-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.25rem; }
.pw-toggle:hover { color: var(--text-primary); }
.pw-mismatch { font-size: var(--text-xs); color: var(--color-danger); display: flex; align-items: center; gap: 0.3rem; margin-top: 0.375rem; }

/* Strength */
.strength-wrap { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
.strength-bar { display: flex; gap: 4px; flex: 1; }
.strength-seg { height: 4px; flex: 1; border-radius: var(--radius-full); transition: background 0.3s; }
.strength-label { font-size: var(--text-xs); font-weight: 600; white-space: nowrap; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem; }

/* Preferences */
.prefs-section { display: flex; flex-direction: column; gap: 1rem; }
.prefs-subtitle { font-size: var(--text-sm); font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.prefs-list { display: flex; flex-direction: column; gap: 0; border: 1px solid var(--border-default); border-radius: var(--radius-md); overflow: hidden; }
.pref-item { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; cursor: pointer; border-bottom: 1px solid var(--border-default); transition: background var(--transition-fast); }
.pref-item:last-child { border-bottom: none; }
.pref-item:hover { background: var(--bg-hover); }
.pref-label { font-size: var(--text-sm); font-weight: 500; margin-bottom: 0.15rem; }

/* Toggle */
.toggle {
  appearance: none; -webkit-appearance: none;
  width: 44px; height: 24px; border-radius: 999px;
  background: var(--bg-active); cursor: pointer;
  position: relative; transition: background 0.2s;
  flex-shrink: 0;
}
.toggle::after {
  content: ''; position: absolute; top: 3px; left: 3px;
  width: 18px; height: 18px; border-radius: 50%; background: white;
  transition: transform 0.2s;
}
.toggle:checked { background: var(--color-primary); }
.toggle:checked::after { transform: translateX(20px); }

/* Danger zone */
.danger-zone { border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.04); }
.danger-title { display: flex; align-items: center; gap: 0.5rem; font-size: var(--text-sm); font-weight: 700; color: var(--color-danger); margin-bottom: 1rem; }
.danger-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.btn-danger-outline { background: transparent; color: var(--color-danger); border: 1px solid var(--color-danger); }
.btn-danger-outline:hover { background: rgba(239,68,68,0.1); }

@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } .profile-header { flex-direction: column; align-items: flex-start; } }
</style>
