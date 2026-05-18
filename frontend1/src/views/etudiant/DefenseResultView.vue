<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const meetingId = route.params.id
const loading = ref(true)
const meeting = ref<any>(null)
const grade = ref<any>(null)

const mention = computed(() => {
  const n = grade.value?.note || 0
  if (n >= 17) return 'Excellent'
  if (n >= 15) return 'Très Bien'
  if (n >= 13) return 'Bien'
  if (n >= 10) return 'Assez Bien'
  return 'Insuffisant'
})

onMounted(async () => {
  try {
    const [mRes, gRes] = await Promise.all([
      api.get(`/meetings/${meetingId}`),
      api.get(`/meetings/${meetingId}/grade`).catch(() => ({ data: null }))
    ])
    meeting.value = mRes.data
    grade.value = gRes.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})

function printPage() {
  window.print()
}
</script>

<template>
  <div class="result-wrapper" v-if="!loading && meeting">
    <!-- Animated background layers -->
    <div class="bg-blur"></div>
    <div class="bg-blur-2"></div>

    <div class="result-card">
      <div class="card-grid">
        <!-- Left Column: The Score Hero -->
        <div class="score-hero">
          <div class="hero-overlay"></div>
          
          <div class="hero-content">
            <!-- Top Branding & Status -->
            <div class="hero-top">
              <div class="brand-logo-container">
                <img src="/assets/img/esi.png" alt="Logo ESI" class="brand-logo" />
              </div>
              <div class="status-badge">SESSION CLÔTURÉE</div>
            </div>
            
            <!-- Center Score Ring -->
            <div class="hero-center">
              <div class="score-ring">
                <svg viewBox="0 0 100 100">
                  <circle cx="50" cy="50" r="46" class="track" />
                  <circle cx="50" cy="50" r="46" class="progress" 
                          :style="{ strokeDasharray: 289, strokeDashoffset: 289 - (289 * (grade?.note || 0) / 20) }" />
                </svg>
                <div class="score-display">
                  <span class="number">{{ grade?.note || '--' }}</span>
                  <span class="denom">/ 20</span>
                </div>
              </div>
            </div>
            
            <!-- Bottom Mention & Congrats -->
            <div class="hero-bottom">
              <div class="mention-tag">{{ mention }}</div>
              <p class="congrats">Félicitations pour votre parcours !</p>
            </div>
          </div>
        </div>

        <!-- Right Column: Details & Feedback -->
        <div class="content-body">
          <div class="scroll-area scrollbar">
            <!-- Header Info -->
            <div class="info-section">
              <div class="section-label">Informations Générales</div>
              <h1 class="project-title">{{ meeting.titre }}</h1>
              
              <div class="meta-grid">
                <div class="meta-card">
                  <i class="pi pi-calendar"></i>
                  <div class="m-txt">
                    <label>Date</label>
                    <span>{{ new Date(meeting.date_heure).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                  </div>
                </div>
                <div class="meta-card">
                  <i class="pi pi-map-marker"></i>
                  <div class="m-txt">
                    <label>Lieu</label>
                    <span>{{ meeting.lieu || 'Salle Virtuelle' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Jury -->
            <div class="info-section">
              <div class="section-label">Composition du Jury</div>
              <div class="jury-list">
                <!-- Président -->
                <div class="jury-member-card president" v-if="meeting.jury?.president">
                  <div class="member-avatar president">
                    {{ meeting.jury.president.prenom?.charAt(0) || 'P' }}
                  </div>
                  <div class="member-info">
                    <strong>{{ meeting.jury.president.prenom }} {{ meeting.jury.president.name }}</strong>
                    <span class="role-badge president">Président du Jury</span>
                  </div>
                </div>
                
                <!-- Membres / Examinateurs -->
                <template v-if="meeting.jury?.membres && meeting.jury.membres.length > 0">
                  <div v-for="m in meeting.jury.membres" :key="m.id" class="jury-member-card examiner">
                    <div class="member-avatar examiner">
                      {{ m.prenom?.charAt(0) || 'E' }}
                    </div>
                    <div class="member-info">
                      <strong>{{ m.prenom }} {{ m.name }}</strong>
                      <span class="role-badge examiner">Examinateur</span>
                    </div>
                  </div>
                </template>
                
                <div v-if="!meeting.jury" class="no-jury-card">
                  <i class="pi pi-exclamation-circle"></i> Aucun jury n'a encore été assigné à cette session.
                </div>
              </div>
            </div>

            <!-- Observations -->
            <div class="info-section">
              <div class="section-label">Observations et Feedback</div>
              <div class="feedback-container">
                <div class="feedback-icon"><i class="pi pi-comment"></i></div>
                <div class="feedback-text">
                  {{ meeting.critiques_generales || "Le jury n'a pas laissé de commentaires additionnels." }}
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div class="actions-bar">
            <router-link to="/etudiant/dashboard" class="btn-primary">
              Retour au Dashboard
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="preloader">
    <div class="loader-circle"></div>
    <span>Chargement des résultats officiels...</span>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

.result-wrapper {
  min-height: 100vh;
  background: #0f172a;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  font-family: 'Montserrat', sans-serif;
  position: relative;
  overflow: hidden;
}

/* Background effects */
.bg-blur {
  position: absolute;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(156, 46, 83, 0.12) 0%, transparent 70%);
  top: -100px;
  right: -100px;
  z-index: 0;
}
.bg-blur-2 {
  position: absolute;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(125, 33, 64, 0.08) 0%, transparent 70%);
  bottom: -100px;
  left: -100px;
  z-index: 0;
}

.result-card {
  width: 100%;
  max-width: 1100px;
  height: 700px;
  max-height: 700px;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 3rem;
  overflow: hidden;
  box-shadow: 0 40px 100px rgba(0,0,0,0.5);
  z-index: 10;
  animation: card-appear 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.card-grid {
  display: grid;
  grid-template-columns: 420px 1fr;
  height: 100%;
  max-height: 100%;
  min-height: 0;
}

/* Score Hero (Left) */
.score-hero {
  background-image: url('https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&q=80&w=1000');
  background-size: cover;
  background-position: center;
  position: relative;
  padding: 2.25rem 1.5rem;
  height: 100%;
  max-height: 100%;
  box-sizing: border-box;
  min-height: 0;
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 5;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  width: 100%;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(156, 46, 83, 0.95) 0%, rgba(88, 22, 45, 0.95) 100%);
  z-index: 1;
}

.hero-top {
  position: relative;
  z-index: 5;
  text-align: center;
  width: 100%;
}

.brand-logo-container {
  margin-bottom: 0.75rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.brand-logo {
  height: 38px;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2)) brightness(0) invert(1); /* Beautiful clean white logo image */
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 100px;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  color: white;
}

.hero-center {
  position: relative;
  z-index: 5;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.score-ring {
  width: 180px;
  height: 180px;
  position: relative;
}

.score-ring svg {
  transform: rotate(-90deg);
  width: 100%;
  height: 100%;
}

.score-ring .track {
  stroke: rgba(255, 255, 255, 0.1);
  stroke-width: 6;
  fill: none;
}

.score-ring .progress {
  stroke: white;
  stroke-width: 6;
  fill: none;
  stroke-linecap: round;
  transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.score-display {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.score-display .number {
  font-size: 3.5rem;
  font-weight: 900;
  line-height: 1;
  font-family: 'Montserrat', sans-serif;
  color: white;
}

.score-display .denom {
  font-size: 1.1rem;
  font-weight: 700;
  opacity: 0.6;
  color: white;
}

.hero-bottom {
  position: relative;
  z-index: 5;
  text-align: center;
  width: 100%;
}

.mention-tag {
  font-size: 1.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(to right, #fff, #fbcfe8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.congrats {
  font-size: 0.85rem;
  font-weight: 500;
  opacity: 0.8;
  color: white;
  margin: 0;
}

/* Content Body (Right) */
.content-body {
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

.scroll-area {
  flex: 1;
  padding: 4rem;
  overflow-y: auto;
}

.section-label {
  font-size: 0.65rem;
  font-weight: 800;
  color: #9c2e53;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  margin-bottom: 1rem;
}

.info-section {
  margin-bottom: 3.5rem;
}

.project-title {
  font-size: 2.5rem;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.1;
  letter-spacing: -0.03em;
  margin-bottom: 2rem;
}

.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.meta-card {
  padding: 1.5rem;
  background: white;
  border-radius: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.02);
  border: 1px solid #f1f5f9;
}

.meta-card i {
  font-size: 1.5rem;
  color: #9c2e53;
}

.m-txt label {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 600;
}

.m-txt span {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
}

/* Jury List Styles */
.jury-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.jury-member-card {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  background: white;
  padding: 1.25rem;
  border-radius: 1.5rem;
  border: 1px solid #f1f5f9;
  transition: all 0.2s;
}

.jury-member-card:hover {
  transform: translateY(-2px);
  border-color: rgba(156, 46, 83, 0.2);
  box-shadow: 0 8px 16px rgba(0,0,0,0.02);
}

.member-avatar {
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  font-size: 1.25rem;
  font-weight: 800;
}

.member-avatar.president {
  background: rgba(156, 46, 83, 0.1);
  color: #9c2e53;
}

.member-avatar.examiner {
  background: #f1f5f9;
  color: #475569;
}

.member-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.member-info strong {
  font-size: 1.05rem;
  color: #1e293b;
}

.role-badge {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  width: max-content;
}

.role-badge.president {
  background: rgba(156, 46, 83, 0.1);
  color: #9c2e53;
}

.role-badge.examiner {
  background: #f1f5f9;
  color: #64748b;
}

.no-jury-card {
  padding: 2rem;
  background: white;
  border-radius: 1.5rem;
  border: 1px dashed #cbd5e1;
  color: #64748b;
  text-align: center;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.feedback-container {
  display: flex;
  gap: 1.5rem;
  background: #0f172a;
  padding: 2.5rem;
  border-radius: 2rem;
  border: 1px solid rgba(156, 46, 83, 0.2);
  color: #e2e8f0;
}

.feedback-icon {
  font-size: 1.5rem;
  color: #9c2e53;
}

.feedback-text {
  font-size: 1rem;
  line-height: 1.7;
  font-weight: 500;
}

/* Actions Bar */
.actions-bar {
  padding: 2rem 4rem;
  background: white;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 1.5rem;
}

.btn-primary {
  background: #9c2e53;
  color: white;
  text-decoration: none;
  padding: 1rem 2rem;
  border-radius: 1.25rem;
  font-weight: 800;
  transition: all 0.2s;
  box-shadow: 0 10px 20px rgba(156, 46, 83, 0.2);
}

.btn-secondary {
  background: #f1f5f9;
  color: #1e293b;
  border: none;
  padding: 1rem 2rem;
  border-radius: 1.25rem;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.2s;
}

.btn-primary:hover { transform: translateY(-3px); background: #832444; }
.btn-secondary:hover { background: #e2e8f0; }

/* Animations */
@keyframes card-appear {
  0% { transform: translateY(30px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

.preloader {
  height: 100vh;
  background: #0f172a;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  color: #94a3b8;
}

.loader-circle {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255,255,255,0.05);
  border-top: 4px solid #9c2e53;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

@media (max-width: 1000px) {
  .result-card { height: auto; border-radius: 0; }
  .card-grid { grid-template-columns: 1fr; }
  .score-hero { padding: 4rem 2rem; }
  .scroll-area { padding: 2rem; }
  .project-title { font-size: 1.75rem; }
  .meta-grid { grid-template-columns: 1fr; }
}
</style>
