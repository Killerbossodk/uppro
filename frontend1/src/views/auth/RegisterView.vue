<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

const router = useRouter()
const toast = useToast()

const form = reactive({
  name: '',
  prenom: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'etudiant' as string,
  specialite_id: null as number | null,
  annee_universitaire: '',
})

const loading = ref(false)
const errorMessage = ref<string | null>(null)

async function handleRegister() {
  if (form.password !== form.password_confirmation) {
    toast.add({ severity: 'warn', summary: 'Erreur', detail: 'Les mots de passe ne correspondent pas.', life: 4000 })
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    // Note: L'endpoint de création d'utilisateur est réservé au RUP
    // Pour l'instant, on simule ou on utilise un endpoint dédié
    const res = await api.post('/auth/register', {
      name: form.name,
      prenom: form.prenom,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      role: form.role,
      specialite_id: form.specialite_id,
      annee_universitaire: form.annee_universitaire,
    })
    
    toast.add({ severity: 'success', summary: 'Compte créé !', detail: 'Vous pouvez maintenant vous connecter.', life: 3000 })
    router.push('/login')
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message || 'Erreur lors de la création du compte.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page-container">
    <!-- Left Side: Institution Image & Branding -->
    <div class="login-left">
      <div class="overlay"></div>
      <div class="left-content">
        <h1 class="institution-name">ÉCOLE SUPÉRIEURE D'INDUSTRIE</h1>
        <div class="divider-short"></div>
        <p class="institution-subtitle">Plateforme de gestion des projets académiques — INP-HB</p>
      </div>
    </div>

    <!-- Right Side: Register Form -->
    <div class="login-right">
      <div class="login-card animate-fade-in">
        <div class="form-wrapper">
          <div class="form-header">
            <h2 class="form-title">Créer un compte</h2>
            <p class="form-subtitle">Rejoignez la plateforme UP-PRO</p>
          </div>

          <Transition name="page">
            <div v-if="errorMessage" class="error-banner">
              <i class="pi pi-exclamation-triangle" />
              <span>{{ errorMessage }}</span>
            </div>
          </Transition>

          <form class="login-form-custom" @submit.prevent="handleRegister">
            <div class="form-row">
              <div class="form-group-custom">
                <label class="label-custom" for="reg-nom">Nom</label>
                <div class="input-icon-wrapper">
                  <i class="pi pi-id-card input-icon"></i>
                  <input id="reg-nom" v-model="form.name" type="text" class="input-custom with-icon" placeholder="Ex: Serme" required />
                </div>
              </div>
              <div class="form-group-custom">
                <label class="label-custom" for="reg-prenom">Prénom</label>
                <div class="input-icon-wrapper">
                  <i class="pi pi-user input-icon"></i>
                  <input id="reg-prenom" v-model="form.prenom" type="text" class="input-custom with-icon" placeholder="Ex: Issouf" required />
                </div>
              </div>
            </div>

            <div class="form-group-custom">
              <label class="label-custom" for="reg-email">Email universitaire</label>
              <div class="input-icon-wrapper">
                <i class="pi pi-envelope input-icon"></i>
                <input id="reg-email" v-model="form.email" type="email" class="input-custom with-icon" placeholder="Ex: issouf.serme24@inphb.ci" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group-custom">
                <label class="label-custom" for="reg-password">Mot de passe</label>
                <div class="input-icon-wrapper">
                  <i class="pi pi-lock input-icon"></i>
                  <input id="reg-password" v-model="form.password" type="password" class="input-custom with-icon" placeholder="••••••••" required />
                </div>
              </div>
              <div class="form-group-custom">
                <label class="label-custom" for="reg-confirm">Confirmer</label>
                <div class="input-icon-wrapper">
                  <i class="pi pi-lock input-icon"></i>
                  <input id="reg-confirm" v-model="form.password_confirmation" type="password" class="input-custom with-icon" placeholder="••••••••" required />
                </div>
              </div>
            </div>

            <button id="btn-register" type="submit" class="btn-login-custom" :disabled="loading">
              <span class="btn-text" v-if="!loading">Créer mon compte</span>
              <i v-if="!loading" class="pi pi-check btn-icon" />
              <i v-else class="pi pi-spin pi-spinner" />
            </button>
          </form>

          <div class="footer-links" style="justify-content: center;">
            <RouterLink to="/login" class="register-link">
              <i class="pi pi-arrow-left" style="font-size: 0.8rem; margin-right: 0.5rem"></i> Retour à la connexion
            </RouterLink>
          </div>
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
  background: #f8f9fa; /* Très léger gris */
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
  overflow-y: auto;
}

.login-card {
  width: 100%;
  max-width: 480px;
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
  gap: 0.75rem;
}

.form-group-custom {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.label-custom {
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-left: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* --- Grille de sélection des rôles --- */
.role-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.role-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem;
  border-radius: 12px;
  border: 2px solid transparent;
  background: #f3f4f6;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: center;
}

.role-icon-bg {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.role-card .pi {
  font-size: 1rem;
  color: #6b7280;
  transition: color 0.3s;
}

.role-card .role-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #4b5563;
  transition: color 0.3s;
}

.role-card:hover {
  background: #e5e7eb;
}

.role-card.active {
  background: white;
  border-color: var(--color-esi-maroon);
  box-shadow: 0 4px 12px rgba(125, 33, 64, 0.1);
}

.role-card.active .role-icon-bg {
  background: var(--color-esi-maroon);
  box-shadow: 0 4px 8px rgba(125, 33, 64, 0.3);
}

.role-card.active .pi {
  color: white;
}

.role-card.active .role-label {
  color: var(--color-esi-maroon);
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
  padding: 0.65rem 1rem;
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

.register-link {
  font-size: 0.9rem;
  color: #6b7280;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
  display: inline-flex;
  align-items: center;
}

.register-link:hover {
  color: var(--color-esi-maroon);
}

@media (max-width: 1024px) {
  .login-left {
    display: none;
  }
  .login-right {
    padding: 1.5rem;
    background: #f8f9fa;
  }
  .login-card {
    padding: 2rem 1.5rem;
  }
}
</style>
