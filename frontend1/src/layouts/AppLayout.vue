<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/magasins/auth'
import Toast from 'primevue/toast'
import NotificationBell from '@/components/NotificationBell.vue'

const auth = useAuthStore()
const route = useRoute()
const sidebarCollapsed = ref(false)

const navItems = computed(() => {
  const role = auth.user?.role
  if (role === 'professeur') {
    return [
      { icon: 'pi-home', label: 'Tableau de bord', to: '/professeur/dashboard' },
      { icon: 'pi-briefcase', label: 'Mes Projets', to: '/professeur/projets' },
      { icon: 'pi-users', label: 'Groupes', to: '/professeur/groupes' },
      { icon: 'pi-file', label: 'Rapports', to: '/professeur/rapports' },
      { icon: 'pi-calendar', label: 'Agenda', to: '/professeur/agenda' },
      { icon: 'pi-star', label: 'Soutenances', to: '/professeur/soutenances' },
    ]
  }
  if (role === 'etudiant') {
    return [
      { icon: 'pi-home', label: 'Mon Espace', to: '/etudiant/dashboard' },
      { icon: 'pi-list', label: 'Choix de Projet', to: '/etudiant/choix-projet' },
      { icon: 'pi-briefcase', label: 'Mon Projet', to: '/etudiant/projet' },
      { icon: 'pi-users', label: 'Mon Groupe', to: '/etudiant/groupe' },
      { icon: 'pi-file-export', label: 'Mes Rapports', to: '/etudiant/rapports' },
      { icon: 'pi-calendar', label: 'Mon Agenda', to: '/etudiant/agenda' },
      
    ]
  }
  if (role === 'rup' || role === 'rup_specialite') {
  return [
    { icon: 'pi-home', label: 'Tableau de bord', to: '/rup/dashboard' },
    { icon: 'pi-star', label: 'Jurys', to: '/rup/jurys' },
    { icon: 'pi-chart-bar', label: 'Notes', to: '/rup/notes' },
    { icon: 'pi-megaphone', label: 'Annonces', to: '/rup/annonces' },
    { icon: 'pi-calendar', label: 'Agenda', to: '/rup/agenda' },
    { icon: 'pi-history', label: 'Archives', to: '/rup/archives' },
  ]
}
if (role === 'rup_projet') {
  return [
    { icon: 'pi-home', label: 'Tableau de bord', to: '/rup-projet/dashboard' },
    { icon: 'pi-users', label: 'Utilisateurs', to: '/rup-projet/utilisateurs' },
    { icon: 'pi-megaphone', label: 'Communiqués', to: '/rup-projet/annonces' },
    { icon: 'pi-chart-bar', label: 'Analytique', to: '/rup-projet/analytics' },
    { icon: 'pi-file', label: 'Rapports', to: '/rup-projet/rapports' },
    { icon: 'pi-star', label: 'Soutenances', to: '/rup-projet/soutenances' },
    { icon: 'pi-cog', label: 'Paramètres', to: '/rup-projet/parametres' },
    { icon: 'pi-chart-line', label: 'Analytics', to: '/analytics' },  // ✅ Ajouté ici
    { icon: 'pi-archive', label: 'Archives', to: '/rup-projet/archives' },
  ]
}

  return []
})

const sharedNav = [
  { icon: 'pi-book', label: 'Bibliothèque', to: '/bibliotheque' },
]

const roleLabel = computed(() => {
  const map: Record<string, string> = {
    professeur: 'Professeur',
    etudiant: 'Étudiant',
    rup: 'RUP Spécialité',
    rup_projet: 'RUP Projet',  // ✅ AJOUTÉ
    rup_specialite: 'RUP Spécialité',
  }
  return map[auth.user?.role ?? ''] ?? ''
})
const roleBadgeClass = computed(() => {
  const map: Record<string, string> = {
    professeur: 'badge-primary',
    etudiant: 'badge-success',
    rup: 'badge-warning',
    rup_projet: 'badge-danger',  // ✅ AJOUTÉ
    rup_specialite: 'badge-warning',
  }
  return map[auth.user?.role ?? ''] ?? 'badge-info'
})
function isActive(to: string) {
  return route.path.startsWith(to)
}
</script>

<template>
  <div class="app-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <Toast position="top-right" />

    <!-- ── Sidebar ─────────────────────────────── -->
    <aside class="sidebar">
      <!-- Brand -->
      <div class="sidebar-brand">
        <!--div class="brand-icon">
          <i class="pi pi-graduation-cap" />
        </div--->
        <Transition name="fade-text">
          <span v-if="!sidebarCollapsed" class="brand-name">
            UP<span class="brand-accent">PRO</span>
          </span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="nav-group">
          <p v-if="!sidebarCollapsed" class="nav-group-label">Menu</p>
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
            :title="sidebarCollapsed ? item.label : ''"
          >
            <i :class="`pi ${item.icon}`" />
            <Transition name="fade-text">
              <span v-if="!sidebarCollapsed">{{ item.label }}</span>
            </Transition>
            <span v-if="isActive(item.to)" class="nav-item-indicator" />
          </RouterLink>
        </div>

        <div class="nav-group">
          <p v-if="!sidebarCollapsed" class="nav-group-label">Ressources</p>
          <RouterLink
            v-for="item in sharedNav"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
          >
            <i :class="`pi ${item.icon}`" />
            <Transition name="fade-text">
              <span v-if="!sidebarCollapsed">{{ item.label }}</span>
            </Transition>
          </RouterLink>
        </div>
      </nav>

      <!-- User profile -->
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="avatar">
            <span>{{ auth.userInitials }}</span>
          </div>
          <Transition name="fade-text">
            <div v-if="!sidebarCollapsed" class="user-info">
              <p class="user-name">{{ auth.user?.name }}</p>
              <span :class="`badge ${roleBadgeClass}`">{{ roleLabel }}</span>
            </div>
          </Transition>
        </div>
        <button class="btn btn-ghost btn-icon sidebar-logout" @click="auth.logout()" title="Déconnexion">
          <i class="pi pi-sign-out" />
        </button>
      </div>
    </aside>

    <!-- ── Main content ──────────────────────── -->
    <div class="main-wrapper">
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <button
            class="btn btn-ghost btn-icon"
            id="sidebar-toggle"
            @click="sidebarCollapsed = !sidebarCollapsed"
          >
            <i :class="`pi ${sidebarCollapsed ? 'pi-arrow-right' : 'pi-arrow-left'}`" />
          </button>
          <h1 class="page-title">{{ route.meta.title || 'UP PRO' }}</h1>
        </div>

        <div class="topbar-right">
          <!-- Search -->
          <div class="topbar-search">
            <i class="pi pi-search" />
            <input type="text" placeholder="Rechercher..." class="search-input" />
          </div>

          <!-- Notification Bell (composant) -->
          <NotificationBell />

          <!-- Avatar → Profil -->
          <RouterLink to="/profil" class="topbar-avatar" id="topbar-avatar" title="Mon profil">
            <div class="avatar avatar-sm">
              <span>{{ auth.userInitials }}</span>
            </div>
          </RouterLink>
        </div>
      </header>

      <!-- Page -->
      <main class="page-content">
        <RouterView v-slot="{ Component }">
          <Transition name="page" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-base);
}

/* ── Sidebar ────────────────────────────────────── */
.sidebar {
  width: var(--sidebar-width);
  height: 100vh;
  background: var(--gradient-sidebar);
  border-right: 1px solid var(--border-default);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
  transition: width var(--transition-base);
  overflow: hidden;
}

.app-layout.sidebar-collapsed .sidebar {
  width: var(--sidebar-collapsed);
}

.sidebar-brand {
  height: var(--topbar-height);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0 1rem;
  border-bottom: 1px solid var(--border-default);
  flex-shrink: 0;
}

/*.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
  box-shadow: var(--shadow-glow-sm);
}*/

.brand-name {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 800;
  color: var(--text-primary);
  white-space: nowrap;
}

.brand-accent {
  color: var(--color-primary-light);
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 1.5rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  overflow-y: auto;
  overflow-x: hidden;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-group-label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 0 0.75rem;
  margin-bottom: 0.5rem;
  white-space: nowrap;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
  transition: all var(--transition-fast);
  white-space: nowrap;
  position: relative;
  overflow: hidden;
}

.nav-item i {
  font-size: 1.1rem;
  flex-shrink: 0;
  width: 20px;
  text-align: center;
}

.nav-item:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.nav-item.active {
  background: var(--color-primary-subtle);
  color: var(--color-primary-light);
}

.nav-item-indicator {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: var(--color-primary);
  border-radius: var(--radius-full) 0 0 var(--radius-full);
}

/* Footer */
.sidebar-footer {
  padding: 0.75rem;
  border-top: 1px solid var(--border-default);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-profile {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.user-info {
  min-width: 0;
  flex: 1;
}

.user-name {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-logout {
  flex-shrink: 0;
  color: var(--text-muted);
}

.sidebar-logout:hover {
  color: var(--color-danger) !important;
}

/* ── Main Wrapper ─────────────────────────────── */
.main-wrapper {
  flex: 1;
  margin-left: var(--sidebar-width);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left var(--transition-base);
}

.app-layout.sidebar-collapsed .main-wrapper {
  margin-left: var(--sidebar-collapsed);
}

/* ── Topbar ───────────────────────────────────── */
.topbar {
  height: var(--topbar-height);
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-default);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 50;
  gap: 1rem;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title {
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--text-primary);
  font-family: var(--font-display);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}

.topbar-search {
  position: relative;
  display: flex;
  align-items: center;
}

.topbar-search .pi {
  position: absolute;
  left: 0.75rem;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.search-input {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-full);
  padding: 0.5rem 1rem 0.5rem 2.25rem;
  color: var(--text-primary);
  font-size: var(--text-sm);
  font-family: var(--font-sans);
  outline: none;
  width: 220px;
  transition: all var(--transition-fast);
}

.search-input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-subtle);
}

.search-input::placeholder {
  color: var(--text-muted);
}

.notif-btn {
  position: relative;
}

.notif-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 18px;
  height: 18px;
  background: var(--color-danger);
  color: white;
  font-size: 10px;
  font-weight: 700;
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.notif-panel {
  position: absolute;
  top: calc(100% + 0.75rem);
  right: 0;
  width: 340px;
  max-height: 480px;
  display: flex;
  flex-direction: column;
  z-index: 200;
  padding: 0;
  overflow: hidden;
}

.notif-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-default);
}

.notif-panel-header h3 {
  font-size: var(--text-base);
  font-weight: 700;
}

.notif-list {
  overflow-y: auto;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  padding: 0.875rem 1.25rem;
  cursor: pointer;
  transition: background var(--transition-fast);
  border-bottom: 1px solid var(--border-default);
}

.notif-item:hover {
  background: var(--bg-hover);
}

.notif-item.unread {
  background: var(--color-primary-subtle);
}

.notif-icon {
  color: var(--color-primary-light);
  font-size: 1.1rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.notif-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.2rem;
}

.notif-msg {
  font-size: var(--text-xs);
  color: var(--text-secondary);
  line-height: 1.4;
}

.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.notif-empty .pi {
  font-size: 2rem;
}

/* ── Page Content ─────────────────────────────── */
.page-content {
  flex: 1;
  padding: 2rem;
  animation: fadeIn 0.3s ease;
}

/* ── Transitions ──────────────────────────────── */
.fade-text-enter-active,
.fade-text-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-text-enter-from,
.fade-text-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .main-wrapper {
    margin-left: 0;
  }
  .topbar-search {
    display: none;
  }
}
</style>