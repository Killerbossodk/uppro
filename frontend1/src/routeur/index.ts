import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/magasins/auth'

const router = createRouter({
  history: createWebHistory((import.meta as any).env.BASE_URL),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    // -- Auth ------------------------------------------
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { layout: 'auth', guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { layout: 'auth', guest: true },
    },

    // -- Professeur ------------------------------------
    {
      path: '/professeur',
      meta: { requiresAuth: true, role: 'professeur' },
      children: [
        {
          path: '',
          redirect: '/professeur/dashboard',
        },
        {
          path: 'dashboard',
          name: 'prof.dashboard',
          component: () => import('@/views/professeur/DashboardView.vue'),
          meta: { title: 'Tableau de bord' },
        },
        {
          path: 'projets',
          name: 'prof.projects',
          component: () => import('@/views/professeur/ProjectsView.vue'),
          meta: { title: 'Mes Projets' },
        },
        {
          path: 'projets/:id',
          name: 'prof.project.detail',
          component: () => import('@/views/shared/ProjectDetailView.vue'),
          meta: { title: 'D�tail Projet' },
        },
        {
          path: 'groupes',
          name: 'prof.groups',
          component: () => import('@/views/professeur/GroupsView.vue'),
          meta: { title: 'Groupes' },
        },
        {
          path: 'groupes/:id',
          name: 'prof.group.detail',
          component: () => import('@/views/shared/GroupDetailView.vue'),
          meta: { title: 'D�tail Groupe', requiresAuth: true, role: 'professeur' },
        },
        {
          path: 'rapports',
          name: 'prof.reports',
          component: () => import('@/views/professeur/ReportsView.vue'),
          meta: { title: 'Rapports' },
        },
        { path: 'agenda', name: 'prof.agenda', component: () => import('@/views/professeur/AgendaView.vue'), meta: { title: 'Agenda & RDV' } },
        { path: 'soutenances', name: 'prof.defenses', component: () => import('@/views/professeur/SoutenancesView.vue'), meta: { title: 'Mes Soutenances' } },
        { path: 'soutenance/:id', name: 'prof.soutenance', redirect: to => `/soutenance-live/${to.params.id}` },
        {
          path: 'projets/:id/edit',
          name: 'prof.project.edit',
          component: () => import('@/views/professeur/ProjectEditView.vue'),
          meta: { title: 'Modifier le projet' }
        }
      ],
    },

    // -- �tudiant -------------------------------------
    {
      path: '/etudiant',
      meta: { requiresAuth: true, role: 'etudiant' },
      children: [
        {
          path: '',
          redirect: '/etudiant/dashboard',
        },
        {
          path: 'dashboard',
          name: 'student.dashboard',
          component: () => import('@/views/etudiant/DashboardView.vue'),
          meta: { title: 'Mon Espace' },
        },
        {
          path: 'choix-projet',
          name: 'student.project.selection',
          component: () => import('@/views/etudiant/ProjectSelectionView.vue'),
          meta: { title: 'Choix de Projet' },
        },
        {
          path: 'projet',
          name: 'student.project',
          component: () => import('@/views/shared/ProjectDetailView.vue'),
          meta: { title: 'Mon Projet' },
        },
        {
          path: 'groupe',
          name: 'student.group',
          component: () => import('@/views/shared/GroupDetailView.vue'),
          meta: { title: 'Mon Groupe' },
        },
        {
          path: 'rapports',
          name: 'student.reports',
          component: () => import('@/views/etudiant/ReportsView.vue'),
          meta: { title: 'Mes Rapports' },
        },
        {
          path: 'agenda',
          name: 'student.agenda',
          component: () => import('@/views/etudiant/AgendaView.vue'),
          meta: { title: 'Mon Agenda' },
        },
        {
          path: 'soutenance-resultat/:id',
          name: 'student.soutenance.result',
          component: () => import('@/views/etudiant/DefenseResultView.vue'),
          meta: { title: 'Résultat Soutenance' },
        },
        {
          path: 'fiche-soutenance/:id',
          name: 'student.fiche.soutenance',
          component: () => import('@/views/etudiant/FicheSoutenance.vue'),
          meta: { title: 'Fiche de Soutenance' },
        },
        {
          path: 'critiques-encadreurs',
          name: 'student.feedbacks',
          component: () => import('@/views/etudiant/EncadreurFeedbackView.vue'),
          meta: { title: 'Évaluer mon Encadreur' },
        },
      ],
    },
    // -- RUP Projet ------------------------------------
{
  path: '/rup-projet',
  meta: { requiresAuth: true, role: 'rup_projet' },
  children: [
    { path: '', redirect: '/rup-projet/dashboard' },
    { path: 'dashboard', name: 'rupProjet.dashboard', component: () => import('@/views/rup-projet/DashboardView.vue'), meta: { title: 'Tableau de bord RUP' } },
    { path: 'utilisateurs', name: 'rupProjet.users', component: () => import('@/views/rup-projet/UsersView.vue'), meta: { title: 'Utilisateurs' } },
    { path: 'annonces', name: 'rupProjet.announcements', component: () => import('@/views/rup-projet/AnnouncementsView.vue'), meta: { title: 'Communiqués' } },
    { path: 'analytics', name: 'rupProjet.analytics', component: () => import('@/views/rup-projet/AnalyticsView.vue'), meta: { title: 'Analytique' } },
    { path: 'rapports', name: 'rupProjet.reports', component: () => import('@/views/rup-projet/ReportsView.vue'), meta: { title: 'Rapports' } },
    { path: 'soutenances', name: 'rupProjet.defenses', component: () => import('@/views/rup-projet/DefensesView.vue'), meta: { title: 'Soutenances' } },
    { path: 'parametres', name: 'rupProjet.settings', component: () => import('@/views/rup-projet/SettingsView.vue'), meta: { title: 'Paramètres' } },
  ],
},

    // -- RUP ------------------------------------------
    // -- RUP (Spécialité) ----------------------------------
// -- RUP (Spécialité) ----------------------------------
{
  path: '/rup',
  meta: { requiresAuth: true, role: 'rup_specialite' },
  children: [
    { path: '', redirect: '/rup/dashboard' },
    { path: 'dashboard', name: 'rup.dashboard', component: () => import('@/views/rup/DashboardView.vue'), meta: { title: 'Tableau de bord RUP' } },
    { path: 'jurys', name: 'rup.jurys', component: () => import('@/views/rup/JurysView.vue'), meta: { title: 'Jurys' } },
    { path: 'notes', name: 'rup.notes', component: () => import('@/views/rup/GradesView.vue'), meta: { title: 'Gestion des Notes' } },
    { path: 'annonces', name: 'rup.announcements', component: () => import('@/views/rup/AnnouncementsView.vue'), meta: { title: 'Annonces' } },
    { path: 'agenda', name: 'rup.agenda', component: () => import('@/views/rup/AgendaView.vue'), meta: { title: 'Agenda' } },
    { path: 'archives', name: 'rup.archives', component: () => import('@/views/rup/ArchivesView.vue'), meta: { title: 'Archives' } },
  ],
},
    // -- Shared ----------------------------------------
    {
      path: '/bibliotheque',
      name: 'library',
      component: () => import('@/views/shared/LibraryView.vue'),
      meta: { requiresAuth: true, title: 'Bibliothque' },
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: () => import('@/views/shared/AnalyticsView.vue'),
      meta: { requiresAuth: true, role: 'rup_projet', title: 'Analytics' },
    },
    {
      path: '/profil',
      name: 'profil',
      component: () => import('@/views/shared/ProfileView.vue'),
      meta: { requiresAuth: true, title: 'Mon Profil' },
    },
    {
      path: '/messagerie',
      name: 'messaging',
      component: () => import('@/views/shared/MessagesView.vue'),
      meta: { requiresAuth: true, title: 'Messagerie' },
    },
    {
      path: '/rapports/:id',
      name: 'report.view',
      component: () => import('@/views/shared/ReportViewer.vue'),
      meta: { requiresAuth: true, title: 'Détail Rapport' },
    },
    {
      path: '/soutenance-live/:id',
      name: 'soutenance.live',
      component: () => import('@/views/shared/SoutenanceLiveView.vue'),
      meta: { requiresAuth: true, title: 'Soutenance en Direct' },
    },

    // -- Redirects -------------------------------------
    {
      path: '/',
      redirect: () => {
        const token = localStorage.getItem('token')
        if (!token) return '/login'
        
        // Optionnel : rediriger vers le dashboard du rôle
        const role = localStorage.getItem('userRole') // si tu stockes le rôle
        if (role === 'professeur') return '/professeur/dashboard'
        if (role === 'etudiant') return '/etudiant/dashboard'
        if (role === 'rup_projet') return '/rup-projet/dashboard'
        if (role === 'rup_specialite') return '/rup-specialite/dashboard'
        return '/login'
      },

    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

// -- Navigation Guards ------------------------------
router.beforeEach(async (to, _from) => {
  const auth = useAuthStore()
  const token = localStorage.getItem('token')
  
  // 🔥 Route login : toujours accessible
  if (to.path === '/login') {
    return true
  }
  
  // 🔥 Pas de token → login
  if (!token) {
    return '/login'
  }
  
  // 🔥 Token présent mais pas d'utilisateur → on essaie de charger
  if (!auth.user) {
    const ok = await auth.fetchMe()
    if (!ok) {
      // Token invalide → déconnexion + login
      await auth.logout()
      return '/login'
    }
  }
  
  // 🔥 Vérification du rôle si nécessaire
  const requiredRole = to.meta.role
  if (requiredRole && auth.user?.role !== requiredRole) {
    const role = auth.user?.role
    if (role === 'professeur') return '/professeur/dashboard'
    if (role === 'etudiant') return '/etudiant/dashboard'
    if (role === 'rup_projet') return '/rup-projet/dashboard'
    if (role === 'rup_specialite') return '/rup-specialite/dashboard'
    return '/login'
  }
  
  return true
})
export default router