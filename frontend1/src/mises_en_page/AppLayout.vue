<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/magasins/auth'
import Toast from 'primevue/toast'
import NotificationBell from '@/components/NotificationBell.vue'
import api from '@/services/api'
import AIChatFloating from '@/components/AIChatFloating.vue'

const auth = useAuthStore()
const route = useRoute()
const isSidebarMini = ref(false)
const isMobileOpen = ref(false)

watch(() => route.path, () => {
  isMobileOpen.value = false
})

// ANNONCES TICKER
const announcements = ref<any[]>([])

async function fetchAnnouncements() {
  try {
    const res = await api.get('/announcements/carousel')
    if (Array.isArray(res.data) && res.data.length > 0) {
      announcements.value = res.data
    }
  } catch (e) {
    announcements.value = []
  }
}

onMounted(() => {
  // ✅ Un seul appel au chargement, PAS de setInterval
  fetchAnnouncements()
})

// ✅ MENU PAR RÔLE
const navItems = computed(() => {
  const role = auth.user?.role

  if (role === 'professeur') {
    return [
      { icon: 'pi-home', label: 'Tableau de bord', to: '/professeur/dashboard' },
      { icon: 'pi-briefcase', label: 'Mes Projets', to: '/professeur/projets' },
      { icon: 'pi-users', label: 'Groupes', to: '/professeur/groupes' },
      { icon: 'pi-file', label: 'Rapports', to: '/professeur/rapports' },
      { icon: 'pi-calendar', label: 'Agenda', to: '/professeur/agenda' },
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

  if (role === 'rup_specialite') {
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
    ]
  }

  return []
})

const sharedNav = [
  { icon: 'pi-comments', label: 'Messagerie', to: '/messagerie' },
  { icon: 'pi-book', label: 'Bibliothèque', to: '/bibliotheque' },
  { icon: 'pi-chart-line', label: 'Analytics', to: '/analytics' },
]

const roleLabel = computed(() => {
  const map: Record<string, string> = {
    professeur: 'Professeur',
    etudiant: 'Étudiant',
    rup_specialite: 'RUP Spécialité',
    rup_projet: 'RUP Projet',
  }
  return map[auth.user?.role ?? ''] ?? ''
})

const roleBadgeClass = computed(() => {
  const map: Record<string, string> = {
    professeur: 'badge-primary',
    etudiant: 'badge-success',
    rup_specialite: 'badge-warning',
    rup_projet: 'badge-danger',
  }
  return map[auth.user?.role ?? ''] ?? 'badge-info'
})

function isActive(to: string) {
  if (to === '/') return route.path === '/'
  return route.path === to || route.path.startsWith(`${to}/`)
}
</script>

<template>
  <div class="app-layout" :class="{ 'sidebar-mini': isSidebarMini, 'mobile-open': isMobileOpen, 'has-ticker': announcements.length > 0 }">
    <div v-if="isMobileOpen" class="mobile-overlay" @click="isMobileOpen = false"></div>
    <Toast position="top-right" />
    
    <!-- ✅ TICKER ANNONCES -->
    <div v-if="announcements.length" class="announce-ticker">
      <span class="ticker-badge">📢 Communiqué</span>
      <div class="ticker-viewport">
        <div class="ticker-track">
          <template v-for="n in 2" :key="n">
            <span v-for="(a, i) in announcements" :key="`${n}-${i}`" class="ticker-item">
              <span class="ticker-title">{{ a.title }}</span>
              <span class="ticker-sep">—</span>
              <span class="ticker-body">{{ a.content }}</span>
              <span class="ticker-dot">·</span>
            </span>
          </template>
        </div>
      </div>
    </div>

    <!-- -- Sidebar ------------------------------- -->
    <aside class="sidebar">
      <!-- Brand -->
      <div class="sidebar-brand">
        <Transition name="fade-text">
          <span v-if="!isSidebarMini" class="brand-name">
            UP<span class="brand-accent">PRO</span>
          </span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="nav-group">
          <p v-if="!isSidebarMini" class="nav-group-label">Menu</p>
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
            :title="isSidebarMini ? item.label : ''"
          >
            <i :class="`pi ${item.icon}`" />
            <Transition name="fade-text">
              <span v-if="!isSidebarMini">{{ item.label }}</span>
            </Transition>
            <span v-if="isActive(item.to)" class="nav-item-indicator" />
          </RouterLink>
        </div>

        <div class="nav-group">
          <p v-if="!isSidebarMini" class="nav-group-label">Ressources</p>
          <RouterLink
            v-for="item in sharedNav"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ active: isActive(item.to) }"
          >
            <i :class="`pi ${item.icon}`" />
            <Transition name="fade-text">
              <span v-if="!isSidebarMini">{{ item.label }}</span>
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
            <div v-if="!isSidebarMini" class="user-info">
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

    <!-- -- Main content ------------------------ -->
    <div class="main-wrapper">
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <button
            class="btn btn-ghost btn-icon desktop-toggle"
            @click="isSidebarMini = !isSidebarMini"
          >
            <i :class="`pi ${isSidebarMini ? 'pi-arrow-right' : 'pi-arrow-left'}`" />
          </button>
          <button
            class="btn btn-ghost btn-icon mobile-toggle"
            @click="isMobileOpen = !isMobileOpen"
          >
            <i class="pi pi-bars" />
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

    <!-- ✅ Chat IA flottant -->
    <AIChatFloating />
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-base);
}

/* -- Sidebar -------------------------------------- */
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

.app-layout.sidebar-mini .sidebar {
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

/* -- Main Wrapper ------------------------------- */
.main-wrapper {
  flex: 1;
  margin-left: var(--sidebar-width);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left var(--transition-base);
}

.app-layout.sidebar-mini .main-wrapper {
  margin-left: var(--sidebar-collapsed);
}

/* -- Topbar ------------------------------------- */
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



/* -- Page Content ------------------------------- */
.page-content {
  flex: 1;
  padding: 2rem;
  animation: fadeIn 0.3s ease;
}

/* -- Transitions -------------------------------- */
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

.desktop-toggle { display: flex; }
.mobile-toggle { display: none; }

.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
}

@media (max-width: 768px) {
  .desktop-toggle { display: none !important; }
  .mobile-toggle { display: flex !important; }

  .sidebar {
    transform: translateX(-100%);
    width: var(--sidebar-width) !important;
    box-shadow: none;
  }
  .app-layout.mobile-open .sidebar {
    transform: translateX(0);
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
  }
  .main-wrapper {
    margin-left: 0 !important;
  }
  .topbar-search {
    display: none;
  }
}

/* ══════════════════════════════════════════
   TICKER ANNONCES
══════════════════════════════════════════ */
.announce-ticker {
  position: fixed;
  top: 0;
  left: var(--sidebar-width);
  right: 0;
  z-index: 300;
  height: 42px;
  background: linear-gradient(90deg, #0F2544 0%, #1A3561 100%);
  border-bottom: 2px solid var(--color-warning);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 16px;
  overflow: hidden;
  transition: left var(--transition-base);
}

.app-layout.sidebar-mini .announce-ticker {
  left: var(--sidebar-collapsed);
}

.ticker-badge {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  background: var(--color-warning);
  color: #0F2544;
  padding: 3px 10px;
  border-radius: 3px;
  z-index: 1;
}

.ticker-viewport {
  flex: 1;
  overflow: hidden;
  height: 100%;
  display: flex;
  align-items: center;
  mask-image: linear-gradient(to right, transparent 0%, black 30px, black calc(100% - 30px), transparent 100%);
  -webkit-mask-image: linear-gradient(to right, transparent 0%, black 30px, black calc(100% - 30px), transparent 100%);
}

.ticker-track {
  display: flex;
  align-items: center;
  white-space: nowrap;
  animation: ticker-scroll 30s linear infinite;
}

.ticker-track:hover {
  animation-play-state: paused;
}

@keyframes ticker-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

.ticker-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 28px 0 0;
}

.ticker-title {
  font-size: 13px;
  font-weight: 600;
  color: #FFFFFF;
}

.ticker-sep {
  font-size: 11px;
  color: rgba(255,255,255,0.4);
}

.ticker-body {
  font-size: 13px;
  color: rgba(255,255,255,0.78);
}

.ticker-dot {
  font-size: 16px;
  color: var(--color-warning);
  line-height: 1;
  padding-left: 10px;
}

/* Décalage du contenu sous le ticker */
.app-layout.has-ticker .main-wrapper {
  padding-top: 42px;
}
.app-layout.has-ticker .topbar {
  top: 42px;
}

@media (max-width: 768px) {
  .announce-ticker {
    left: 0;
  }
}
</style>