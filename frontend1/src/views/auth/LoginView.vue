<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/magasins/auth'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const toast = useToast()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const error = ref<string | null>(null)

async function handleLogin() {
  if (!form.email || !form.password) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir tous les champs.', life: 3000 })
    return
  }

  loading.value = true
  error.value = null

  const ok = await auth.login(form.email, form.password)

  loading.value = false

  if (ok) {
    toast.add({ severity: 'success', summary: 'Connexion réussie', detail: `Bienvenue, ${auth.user?.name} !`, life: 3000 })
    const redirect = route.query.redirect as string
    if (redirect && redirect !== '/login') {
      router.push(redirect)
    } else {
      const role = auth.user?.role
      if (role === 'professeur') router.push('/professeur/dashboard')
      else if (role === 'etudiant') router.push('/etudiant/dashboard')
      else if (role === 'rup_specialite' || role === 'rup') router.push('/rup/dashboard')
      else if (role === 'rup_projet') router.push('/rup-projet/dashboard')
    }
  } else {
    error.value = auth.error || 'Identifiants incorrects'
  }
}
</script>

<template>
  <div class="login-page-container">
    <div class="login-left">
      <div class="overlay"></div>
      <div class="left-content">
        <h1 class="institution-name">ECOLE SUPERIEURE D'INDUSTRIE</h1>
        <div class="divider-short"></div>
        <p class="institution-subtitle">Plateforme de gestion des projets academiques - INP-HB</p>
      </div>
    </div>
    <div class="login-right">
      <div class="login-card">
        <div class="form-wrapper">
          <div class="logo-container">
            <img src="/assets/img/esi.png" alt="Logo ESI" class="esi-logo-img" />
          </div>
          <div class="form-header">
            <h2 class="form-title">Bienvenue sur UP-PRO</h2>
            <p class="form-subtitle">Connectez-vous a votre espace</p>
          </div>
          <div v-if="error" class="error-banner">
            <i class="pi pi-exclamation-triangle" /> {{ error }}
          </div>
          <form @submit.prevent="handleLogin">
            <div class="form-group-custom">
              <label for="email">Email</label>
              <div class="input-icon-wrapper">
                <i class="pi pi-envelope input-icon"></i>
                <input id="email" v-model="form.email" type="email" class="input-custom with-icon" placeholder="nom@inphb.ci" autocomplete="email" required />
              </div>
            </div>
            <div class="form-group-custom">
              <label for="password">Mot de passe</label>
              <div class="input-icon-wrapper">
                <i class="pi pi-lock input-icon"></i>
                <input id="password" v-model="form.password" type="password" class="input-custom with-icon" placeholder="........" autocomplete="current-password" required />
              </div>
            </div>
            <button type="submit" class="btn-login-custom" :disabled="loading">
              <span v-if="!loading">Se connecter</span>
              <i v-else class="pi pi-spin pi-spinner" />
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<!-- Le style reste exactement le même -->
<style scoped>
/* --- Structure Globale --- */
.login-page-container {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  background: #f8f9fa;
}

/* --- Partie Gauche (Image) --- */
.login-left {
  flex: 1.2;
  position: relative;
  background: url('/assets/img/inphb1.jpeg') center/cover no-repeat;
  display: flex;
  align-items: center;
  padding: 0 5rem;
}

.overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(145deg, rgba(125, 33, 64, 0.88) 0%, rgba(30, 8, 15, 0.7) 100%);
  backdrop-filter: blur(4px);
}

.left-content {
  position: relative;
  z-index: 2;
  color: white;
  max-width: 600px;
}

.institution-name {
  font-family: var(--font-display);
  font-size: 3.8rem;
  font-weight: 900;
  line-height: 1.05;
  margin-bottom: 1.5rem;
  text-transform: uppercase;
  letter-spacing: -0.03em;
  text-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.divider-short {
  width: 60px;
  height: 5px;
  background: white;
  margin-bottom: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.institution-subtitle {
  font-size: 1.3rem;
  opacity: 0.95;
  font-weight: 500;
  line-height: 1.4;
}

/* --- Partie Droite (Formulaire) --- */
.login-right {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #fdfdfd;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: white;
  border-radius: 20px;
  padding: 1.5rem 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0,0,0,0.02);
  border: 1px solid rgba(0,0,0,0.02);
}

.form-wrapper {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

/* --- Logo --- */
.logo-container {
  width: 70px;
  height: 70px;
  margin: 0 auto 1rem auto;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 18px;
  box-shadow: 0 8px 20px rgba(125, 33, 64, 0.12);
  padding: 8px;
  transition: transform 0.3s ease;
}

.logo-container:hover {
  transform: translateY(-3px) scale(1.02);
}

.esi-logo-img {
  width: 100%;
  height: auto;
  object-fit: contain;
}

/* --- Typographie --- */
.form-header {
  text-align: center;
  margin-bottom: 1rem;
}

.form-title {
  font-family: var(--font-display);
  font-size: 1.6rem;
  font-weight: 800;
  color: #111;
  margin-bottom: 0.2rem;
  letter-spacing: -0.01em;
}

.form-subtitle {
  color: #6b7280;
  font-size: 0.9rem;
}

/* --- Alertes --- */
.error-banner {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  border-radius: 8px;
  color: #991b1b;
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

/* --- Formulaire --- */
.login-form-custom {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.form-group-custom {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.label-custom {
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-left: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* --- Inputs Modernes --- */
.input-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1.2rem;
  color: #9ca3af;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.input-custom {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #f3f4f6;
  border: 2px solid transparent;
  border-radius: 12px;
  font-size: 0.95rem;
  color: #1f2937;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
}

.input-custom.with-icon {
  padding-left: 3rem;
}

.input-custom:hover {
  background: #e5e7eb;
}

.input-custom:focus {
  background: white;
  border-color: var(--color-esi-maroon);
  box-shadow: 0 4px 12px rgba(125, 33, 64, 0.1);
}

.input-icon-wrapper:focus-within .input-icon {
  color: var(--color-esi-maroon);
  transform: scale(1.1);
}

.input-custom::placeholder {
  color: #9ca3af;
  font-weight: 400;
}

/* --- Bouton d'action --- */
.btn-login-custom {
  width: 100%;
  padding: 0.85rem;
  background: linear-gradient(135deg, #8A2548 0%, var(--color-esi-maroon) 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
  box-shadow: 0 6px 15px rgba(125, 33, 64, 0.25);
  position: relative;
  overflow: hidden;
}

.btn-login-custom::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(rgba(255,255,255,0.1), rgba(255,255,255,0));
  border-radius: 12px;
}

.btn-login-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 25px rgba(125, 33, 64, 0.35);
}

.btn-login-custom:active {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(125, 33, 64, 0.2);
}

.btn-icon {
  font-size: 1rem;
  transition: transform 0.3s ease;
}

.btn-login-custom:hover .btn-icon {
  transform: translateX(4px);
}

.btn-login-custom:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* --- Liens bas de page --- */
.footer-links {
  width: 100%;
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f3f4f6;
}

.register-link, .forgot-link {
  font-size: 0.9rem;
  color: #6b7280;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.register-link:hover, .forgot-link:hover {
  color: var(--color-esi-maroon);
}

/* --- Responsive --- */
@media (max-width: 1024px) {
  .login-left {
    display: none;
  }
  .login-right {
    padding: 1.5rem;
    background: #f8f9fa;
  }
  .login-card {
    padding: 2.5rem 2rem;
  }
}
</style>