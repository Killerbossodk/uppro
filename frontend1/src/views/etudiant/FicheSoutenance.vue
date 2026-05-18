<script setup lang="ts">
import { onMounted, ref } from 'vue'
import html2pdf from 'html2pdf.js'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const meetingId = route.params.id
const loading = ref(true)
const meeting = ref<any>(null)
const jury = ref<any>(null)

onMounted(async () => {
  try {
    const [mRes, jRes] = await Promise.all([
      api.get(`/meetings/${meetingId}`),
      api.get(`/meetings/${meetingId}/jury`).catch(() => ({ data: null }))
    ])
    meeting.value = mRes.data
    jury.value = jRes.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})

function printPage() {
  const element = document.querySelector('.fiche-soutenance')
  
  if (!element) return
  
  const opt = {
    margin:       [0.5, 0, 0.5, 0],
    filename:     `Convocation_UPPRO_${meetingId}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true },
    jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
  }

  html2pdf().set(opt).from(element).save()
}

function formatDate(dateString: string) {
  const options: Intl.DateTimeFormatOptions = { 
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  }
  return new Date(dateString).toLocaleDateString('fr-FR', options)
}
function getAcademicYear() {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth() + 1
  return month >= 9 ? `${year}/${year + 1}` : `${year - 1}/${year}`
}
</script>

<template>
  <div class="fiche-wrapper" v-if="!loading && meeting">
    
    <div class="fiche-soutenance">
      <!-- Watermark Background -->
      <div class="watermark"></div>

      <!-- Top Official Header -->
      <div class="official-header">
        <div class="university-brand">
          <img src="/assets/img/esi.png" alt="Logo ESI" class="esi-logo-img" style="height: 60px; object-fit: contain;" />
          <div class="uni-text">
            <h4>Institut National Polytechnique Félix Houphouët-Boigny (INPHB)</h4>
            <p>École Supérieure d'Industrie (ESI)</p>
          </div>
        </div>
        <div class="doc-meta">
          <span class="ref">RÉF: UPPRO-{{ meetingId.toString().padStart(5, '0') }}-26</span>
          <span class="year">Année Universitaire {{ meeting.project?.annee_universitaire || getAcademicYear() }}</span>
        </div>
      </div>

      <div class="title-banner">
        <h1>CONVOCATION OFFICIELLE</h1>
        <h2>Soutenance de Projet UP-PRO</h2>
      </div>

      <!-- Details Grid -->
      <div class="details-section">
        <div class="detail-group">
          <label><i class="pi pi-users"></i> Candidat / Groupe</label>
          <p class="value highlight">{{ meeting.groupes?.[0]?.nom || 'Non assigné' }}</p>
        </div>
        <div class="detail-group">
          <label><i class="pi pi-folder"></i> Intitulé du Projet</label>
          <p class="value">{{ meeting.project?.titre || 'Titre non disponible' }}</p>
        </div>
        <div class="detail-row">
          <div class="detail-group half">
            <label><i class="pi pi-calendar-clock"></i> Date & Heure</label>
            <p class="value date-val">{{ formatDate(meeting.date_heure) }}</p>
          </div>
          <div class="detail-group half">
            <label><i class="pi pi-map-marker"></i> Lieu / Salle</label>
            <p class="value">{{ meeting.lieu || 'Vidéoconférence' }}</p>
          </div>
        </div>
      </div>

      <!-- Jury Section -->
      <div class="jury-section">
        <h3><i class="pi pi-star-fill"></i> Composition du Jury</h3>
        <div class="jury-list">
          <div class="jury-member president">
            <div class="member-info">
              <span class="role-badge">Président du Jury</span>
              <span class="name">{{ jury?.president?.prenom }} {{ jury?.president?.name || jury?.president?.nom }}</span>
            </div>
          </div>
          
          <div v-for="membre in jury?.membres?.filter((m: any) => m.id !== jury.president_id)" :key="membre.id" class="jury-member">
            <div class="member-info">
              <span class="role-badge examinateur">Examinateur</span>
              <span class="name">{{ membre.prenom }} {{ membre.name || membre.nom }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Attachment / Guidelines -->
      <div class="instructions-section">
        <h3>Consignes Importantes</h3>
        <ul class="guidelines-list">
          <li>Se présenter au moins <strong>15 minutes</strong> avant l'heure prévue.</li>
          <li>S'assurer de la bonne soumission du rapport final sur la plateforme.</li>
          <li>Se munir d'une pièce d'identité étudiante valide.</li>
        </ul>

        <div v-if="meeting.piece_jointe" class="attachment-box">
          <div class="att-info">
            <i class="pi pi-file-pdf pdf-icon"></i>
            <div>
              <h4>Fiche de Consignes (Livrables)</h4>
              <p>Document officiel joint par l'administration</p>
            </div>
          </div>
          <a :href="'http://localhost:8000' + meeting.piece_jointe" target="_blank" class="btn-download">
            <i class="pi pi-download"></i> Télécharger
          </a>
        </div>
      </div>

      <!-- Footer & Signatures -->
      <div class="footer-signatures" style="justify-content: flex-end; display: flex;">
        <div class="signature-block">
          <p class="auth-title">La Direction des Études</p>
          <div class="signature-line"></div>
          <p class="auth-date">Fait le {{ new Date().toLocaleDateString('fr-FR') }}</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="page-actions no-print">
      <button @click="printPage" class="action-btn print">
        <i class="pi pi-print"></i> Imprimer / PDF
      </button>
      <router-link :to="'/soutenance-live/' + meetingId" class="action-btn live">
        <i class="pi pi-video"></i> Rejoindre le Live
      </router-link>
    </div>
  </div>
  
  <div v-else-if="loading" class="loading-state">
    <i class="pi pi-spin pi-spinner"></i>
    <p>Génération du document officiel...</p>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

.fiche-wrapper {
  min-height: 100vh;
  padding: 3rem 1rem;
  background-color: #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
  font-family: 'Montserrat', sans-serif;
}

.fiche-soutenance {
  width: 100%;
  max-width: 850px;
  background: white;
  padding: 4rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  color: #1e293b;
}

/* Watermark */
.watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-30deg);
  font-size: 15rem;
  font-weight: 900;
  color: rgba(226, 232, 240, 0.3);
  content: 'UPPRO';
  z-index: 0;
  pointer-events: none;
  font-family: 'Montserrat', sans-serif;
}
.watermark::before {
  content: 'UPPRO';
}

/* Header */
.official-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 3rem;
  position: relative;
  z-index: 1;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 1.5rem;
}

.university-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-placeholder {
  width: 50px;
  height: 50px;
  background: #1e3a8a;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.uni-text h4 {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 1px;
}
.uni-text p {
  margin: 0;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.doc-meta {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.ref {
  font-family: monospace;
  font-weight: 700;
  color: #b91c1c;
  font-size: 0.9rem;
}

.year {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

/* Banner */
.title-banner {
  text-align: center;
  margin-bottom: 3rem;
  position: relative;
  z-index: 1;
}

.title-banner h1 {
  font-family: 'Montserrat', sans-serif;
  font-weight: 800;
  font-size: 2.5rem;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
  letter-spacing: 2px;
}

.title-banner h2 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #3b82f6;
  text-transform: uppercase;
  letter-spacing: 3px;
  margin: 0;
}

/* Details Section */
.details-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 3rem;
  position: relative;
  z-index: 1;
}

.detail-row {
  display: flex;
  gap: 2rem;
  margin-top: 1.5rem;
}

.detail-group {
  margin-bottom: 1.5rem;
}
.detail-group:last-child { margin-bottom: 0; }
.detail-group.half { flex: 1; margin: 0; }

.detail-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.5rem;
}
.detail-group label i { color: #3b82f6; }

.detail-group .value {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.4;
}

.detail-group .value.highlight {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1e40af;
}

.date-val {
  text-transform: capitalize;
}

/* Jury Section */
.jury-section {
  margin-bottom: 3rem;
  position: relative;
  z-index: 1;
}

.jury-section h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  color: #0f172a;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.75rem;
  margin-bottom: 1.5rem;
}
.jury-section h3 i { color: #eab308; }

.jury-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.jury-member {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.jury-member.president {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.member-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.role-badge {
  background: #166534;
  color: white;
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.25rem 0.75rem;
  border-radius: 99px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.role-badge.examinateur {
  background: #475569;
}

.name {
  font-weight: 700;
  font-size: 1.05rem;
}

/* Instructions */
.instructions-section {
  position: relative;
  z-index: 1;
}

.instructions-section h3 {
  font-size: 1rem;
  color: #0f172a;
  margin-bottom: 1rem;
}

.guidelines-list {
  margin: 0;
  padding-left: 1.5rem;
  color: #475569;
  line-height: 1.8;
  font-size: 0.95rem;
}

.attachment-box {
  margin-top: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem;
  background: #eff6ff;
  border: 1px dashed #93c5fd;
  border-radius: 8px;
}

.att-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pdf-icon {
  font-size: 2rem;
  color: #dc2626;
}

.att-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e3a8a;
}
.att-info p {
  margin: 0;
  font-size: 0.8rem;
  color: #3b82f6;
}

.btn-download {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #2563eb;
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.85rem;
  transition: background 0.2s;
}
.btn-download:hover {
  background: #1d4ed8;
}

/* Footer / Signatures */
.footer-signatures {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 5rem;
  position: relative;
  z-index: 1;
}

.stamp-container {
  padding-left: 2rem;
}

.stamp {
  width: 120px;
  height: 120px;
  border: 4px solid #dc2626;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transform: rotate(-15deg);
  opacity: 0.85;
}

.stamp-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #dc2626;
}

.stamp-inner span {
  font-size: 1.2rem;
  font-weight: 900;
  letter-spacing: 2px;
}
.stamp-inner i {
  font-size: 1.5rem;
  margin: 0.25rem 0;
}
.stamp-inner small {
  font-size: 0.75rem;
  font-weight: 700;
}

.signature-block {
  text-align: center;
  width: 250px;
}

.auth-title {
  font-weight: 700;
  font-size: 0.9rem;
  color: #0f172a;
  margin-bottom: 4rem;
}

.signature-line {
  border-bottom: 1px solid #cbd5e1;
  margin-bottom: 0.5rem;
}

.auth-date {
  font-size: 0.8rem;
  color: #64748b;
  font-style: italic;
}

/* Actions */
.page-actions {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  border: none;
  text-decoration: none;
  transition: transform 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
}

.action-btn.print {
  background: white;
  color: #0f172a;
  border: 1px solid #cbd5e1;
}

.action-btn.live {
  background: #2563eb;
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 1rem;
  color: #64748b;
  font-size: 1.2rem;
}
.loading-state i {
  font-size: 2.5rem;
  color: #3b82f6;
}

/* Print Styles */
@media print {
  @page { margin: 0; size: auto; }
  body { background: white; }
  .fiche-wrapper { padding: 0; background: white; }
  .fiche-soutenance {
    box-shadow: none;
    padding: 2cm;
    max-width: 100%;
  }
  .no-print { display: none !important; }
}
</style>
