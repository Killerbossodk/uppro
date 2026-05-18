<template>
    <div class="report-view">
  
      <!-- CHARGEMENT -->
      <div v-if="loading" class="empty-state">
        <div class="spinner" />
        <p class="text-muted text-sm mt-3">Chargement du rapport...</p>
      </div>
  
      <div v-else-if="report" class="report-layout">
  
        <!-- ===== EN-TÊTE ===== -->
        <div class="report-header">
          <div class="report-header__left">
            <div class="report-header__badges">
              <span class="type-badge">{{ getTypeLabel(report.type) }}</span>
              <span class="version-badge">v{{ report.version }}</span>
              <span :class="`status-pill status-pill--${report.statut}`">
                {{ report.statut === 'soumis' ? 'En attente' : report.statut === 'valide' ? 'Validé' : 'Rejeté' }}
              </span>
            </div>
            <h1 class="report-title">{{ report.titre }}</h1>
            <p class="report-meta">
              <span><i class="pi pi-users" /> {{ report.group?.nom }}</span>
              <span class="meta-sep">·</span>
              <span><i class="pi pi-calendar" /> {{ formatDate(report.created_at) }}</span>
              <span class="meta-sep">·</span>
              <span><i class="pi pi-user" /> {{ report.deposant?.prenom }} {{ report.deposant?.name }}</span>
            </p>
          </div>
          <div class="report-header__actions">
            <button @click="exportPdf" :disabled="exporting" class="btn btn-secondary btn-sm">
              <i :class="`pi ${exporting ? 'pi-spin pi-spinner' : 'pi-file-pdf'}`" />
              Exporter en PDF
            </button>
            <a :href="absoluteFileUrl" download class="btn btn-primary btn-sm">
              <i class="pi pi-download" /> Télécharger
            </a>
            <button @click="$router.back()" class="btn btn-ghost btn-sm">
              <i class="pi pi-arrow-left" /> Retour
            </button>
          </div>
        </div>
  
        <!-- ===== GRILLE PRINCIPALE ===== -->
        <div class="report-grid">
  
          <!-- COLONNE GAUCHE : document -->
          <div class="report-grid__main">
  
            <!-- PDF -->
            <PDFViewer
              v-if="isPdf"
              :fileUrl="absoluteFileUrl"
              :reportId="report.id"
            />
  
            <!-- DOCX -->
            <div v-else class="doc-preview card">
              <div class="doc-preview__toolbar">
                <div class="doc-preview__toolbar-left">
                  <i class="pi pi-file-word doc-icon" />
                  <span class="font-medium">Aperçu du document</span>
                  <span class="text-muted text-xs">.docx</span>
                </div>
                <div class="doc-preview__toolbar-right">
                  <button @click="toggleFullText" class="btn btn-ghost btn-xs">
                    {{ showFullText ? 'Réduire' : 'Voir tout' }}
                  </button>
                  <a :href="absoluteFileUrl" download class="btn btn-ghost btn-xs">
                    <i class="pi pi-download" />
                  </a>
                </div>
              </div>
              <div class="doc-preview__body" :class="{ 'doc-preview__body--full': showFullText }">
                <div v-if="loadingText" class="doc-preview__loading">
                  <div class="spinner spinner--sm" />
                  <span class="text-muted text-sm">Chargement du texte...</span>
                </div>
                <div v-else-if="documentText" class="doc-preview__text">
                  {{ documentText }}
                </div>
                <div v-else class="doc-preview__empty">
                  <i class="pi pi-exclamation-circle" />
                  <p>Impossible d'extraire le texte.</p>
                  <a :href="absoluteFileUrl" download class="btn btn-ghost btn-xs mt-2">
                    Télécharger pour visualiser
                  </a>
                </div>
              </div>
            </div>
          </div>
  
          <!-- COLONNE DROITE : panneau latéral -->
          <div class="report-grid__sidebar">
  
            <!-- ======= FEEDBACK IA ======= -->
            <div class="panel card">
              <div class="panel__header">
                <div class="panel__header-left">
                  <div class="panel-icon panel-icon--ai">
                    <i class="pi pi-sparkles" />
                  </div>
                  <div>
                    <h3 class="panel__title">Analyse IA</h3>
                    <p class="panel__subtitle text-muted text-xs">Commentaires automatiques</p>
                  </div>
                </div>
                <button
                  @click="refreshAnalysis"
                  :disabled="loadingIA"
                  class="btn btn-primary btn-sm"
                >
                  <i :class="`pi ${loadingIA ? 'pi-spin pi-spinner' : 'pi-refresh'}`" />
                  {{ loadingIA ? 'Analyse...' : 'Lancer' }}
                </button>
              </div>
  
              <div class="panel__divider" />
  
              <div class="panel__body">
                <div v-if="loadingIA" class="panel-loading">
                  <div class="spinner" />
                  <p class="text-muted text-sm mt-2">Analyse en cours...</p>
                </div>
  
                <div v-else-if="report.feedback_ia" class="ia-feedback">
                  <div
                    class="ia-feedback__text markdown-content"
                    :class="{ 'ia-feedback__text--collapsed': !showFullFeedback }"
                    v-html="renderMarkdown(report.feedback_ia)"
                  >
                  </div>
                  <button
                    v-if="report.feedback_ia.length > 500"
                    @click="showFullFeedback = !showFullFeedback"
                    class="ia-feedback__toggle"
                  >
                    <i :class="`pi ${showFullFeedback ? 'pi-chevron-up' : 'pi-chevron-down'}`" />
                    {{ showFullFeedback ? 'Voir moins' : 'Voir plus' }}
                  </button>
                </div>
  
                <div v-else class="panel-empty">
                  <i class="pi pi-robot panel-empty__icon" />
                  <p class="text-muted text-sm">Aucune analyse disponible.</p>
                  <p class="text-muted text-xs mt-1">Cliquez sur « Lancer » pour générer un feedback.</p>
                </div>
              </div>
            </div>


            <!-- ======= SUGGESTION DE NOTE ======= -->
            <div v-if="isProfessor && report.feedback_ia" class="panel card">
              <div class="panel__header">
                <div class="panel__header-left">
                  <div class="panel-icon panel-icon--grade">
                    <i class="pi pi-chart-bar" />
                  </div>
                  <div>
                    <h3 class="panel__title">Suggestion de note</h3>
                    <p class="panel__subtitle text-muted text-xs">Estimation IA</p>
                  </div>
                </div>
                <button
                  @click="suggestGrade"
                  :disabled="suggestingGrade"
                  class="btn btn-sm btn-warning"
                >
                  <i :class="`pi ${suggestingGrade ? 'pi-spin pi-spinner' : 'pi-star'}`" />
                  {{ suggestingGrade ? '...' : 'Suggérer' }}
                </button>
              </div>
              <div class="panel__divider" />
              <div v-if="gradeSuggestion" class="panel__body">
                <div v-if="gradeSuggestion.note" class="grade-display">
                  <span class="grade-display__value">{{ gradeSuggestion.note }}</span>
                  <span class="grade-display__total">/20</span>
                </div>
                <div v-else class="grade-display__error">
                  <i class="pi pi-exclamation-triangle" />
                  {{ gradeSuggestion.justification }}
                </div>
                <p v-if="gradeSuggestion.justification && gradeSuggestion.note" class="grade-display__justification markdown-content text-sm text-muted mt-2" v-html="renderMarkdown(gradeSuggestion.justification)">
                </p>
              </div>
              <div v-else class="panel__body">
                <div class="panel-empty">
                  <p class="text-muted text-sm">Cliquez sur « Suggérer » pour obtenir une estimation.</p>
                </div>
              </div>
            </div>
  
            <!-- ======= COMMENTAIRES ======= -->
            <div class="panel card">
              <div class="panel__header">
                <div class="panel__header-left">
                  <div class="panel-icon panel-icon--comments">
                    <i class="pi pi-comments" />
                  </div>
                  <div>
                    <h3 class="panel__title">Commentaires</h3>
                    <p class="panel__subtitle text-muted text-xs">{{ comments.length }} message{{ comments.length !== 1 ? 's' : '' }}</p>
                  </div>
                </div>
              </div>
  
              <div class="panel__divider" />
  
              <div class="comments-list">
                <div v-if="comments.length === 0" class="panel-empty">
                  <i class="pi pi-comment panel-empty__icon" />
                  <p class="text-muted text-sm">Aucun commentaire pour le moment.</p>
                </div>
  
                <div v-for="c in comments" :key="c.id" class="comment-item">
                  <div class="comment-item__avatar" :class="`avatar--${c.user?.role === 'professeur' ? 'prof' : 'rup'}`">
                    {{ (c.user?.prenom?.[0] || c.user?.name?.[0] || '?').toUpperCase() }}
                  </div>
                  <div class="comment-item__body">
                    <div class="comment-item__header">
                      <span class="comment-item__name">{{ c.user?.prenom }} {{ c.user?.name }}</span>
                      <span v-if="c.user?.role === 'professeur'" class="role-tag role-tag--prof">Enseignant</span>
                      <span v-else-if="c.user?.role?.startsWith('rup')" class="role-tag role-tag--rup">RUP</span>
                      <span class="comment-item__date text-xs text-muted">{{ formatDate(c.created_at) }}</span>
                    </div>
                    <p class="comment-item__text markdown-content" v-html="renderMarkdown(c.content)"></p>
                  </div>
                </div>
              </div>
  
              <!-- Formulaire commentaire -->
              <div v-if="canComment" class="comment-form">
                <div class="comment-form__avatar">
                  {{ (auth.user?.prenom?.[0] || auth.user?.name?.[0] || '?').toUpperCase() }}
                </div>
                <div class="comment-form__input-wrap">
                  <textarea
                    v-model="newComment"
                    rows="2"
                    class="comment-form__textarea"
                    placeholder="Ajouter un commentaire..."
                  />
                  <div class="comment-form__footer">
                    <button
                      @click="addComment"
                      :disabled="!newComment.trim()"
                      class="btn btn-primary btn-sm"
                    >
                      <i class="pi pi-send" /> Publier
                    </button>
                  </div>
                </div>
              </div>
            </div>
  
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import { useAuthStore } from '@/magasins/auth';
  import api from '@/services/api';
  import PDFViewer from '@/components/PDFViewer.vue';
  import { marked } from 'marked';
  
  // Configuration marked pour la sécurité
  marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: false,
    mangle: false
  });
  
  function renderMarkdown(text) {
    if (!text) return '';
    return marked.parse(text);
  }
  
  const route = useRoute();
  const auth = useAuthStore();
  const reportId = Number(route.params.id);
  const showFullFeedback = ref(false);
  const report = ref(null);
  const comments = ref([]);
  const newComment = ref('');
  const loading = ref(true);
  const loadingIA = ref(false);
  const gradeSuggestion = ref(null);
  const documentText = ref('');
  const loadingText = ref(false);
  const showFullText = ref(false);
  const suggestingGrade = ref(false);
  const exporting = ref(false);

  async function exportPdf() {
    exporting.value = true;
    try {
      const res = await api.get(`/reports/${reportId}/export-pdf`, {
        responseType: 'blob'
      });
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `rapport-${report.value.titre.replace(/\s+/g, '-').toLowerCase()}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      alert("Erreur lors de l'export PDF");
    } finally {
      exporting.value = false;
    }
  }
  
  const isPdf = computed(() => {
    if (!report.value?.fichier_url) return false;
    return report.value.fichier_url.toLowerCase().endsWith('.pdf');
  });
  
  const absoluteFileUrl = computed(() => report.value?.fichier_url || '');
  const isProfessor = computed(() => auth.user?.role === 'professeur');
  const canComment = computed(() => {
    const role = auth.user?.role;
    return role === 'professeur' || role === 'rup_specialite' || role === 'rup_projet';
  });
  
  async function fetchReport() {
    loading.value = true;
    try {
      const res = await api.get(`/reports/${reportId}`);
      report.value = res.data;
      
      // On charge le texte d'abord si c'est un docx (rapide)
      if (!isPdf.value) await fetchDocumentText();
      
      // Le rapport est prêt, on affiche TOUT DE SUITE
      loading.value = false;

    } catch (error) {
      console.error('Erreur chargement rapport:', error);
      loading.value = false;
    }
  }
  
  async function fetchDocumentText() {
    loadingText.value = true;
    try {
      const res = await api.get(`/reports/${reportId}/text`);
      if (res.data.text?.length > 0) documentText.value = res.data.text;
    } catch (error) {
      console.error('Erreur chargement texte:', error);
    } finally {
      loadingText.value = false;
    }
  }
  
  async function fetchComments() {
    try {
      const res = await api.get(`/reports/${reportId}/comments`);
      comments.value = res.data;
    } catch {
      comments.value = [];
    }
  }
  
  async function addComment() {
    if (!newComment.value.trim()) return;
    try {
      await api.post(`/reports/${reportId}/comments`, { content: newComment.value });
      newComment.value = '';
      await fetchComments();
    } catch {
      alert("Erreur lors de l'ajout du commentaire");
    }
  }
  
  async function refreshAnalysis() {
    loadingIA.value = true;
    try {
      await api.post(`/ai/analyze-report/${reportId}`);
      await fetchReport();
    } catch (error) {
      alert('Erreur lors de l\'analyse: ' + (error.response?.data?.error || error.message));
    } finally {
      loadingIA.value = false;
    }
  }
  
  async function suggestGrade() {
    suggestingGrade.value = true;
    try {
      const res = await api.post(`/ai/suggest-grade/${reportId}`);
      gradeSuggestion.value = res.data;
    } catch {
      gradeSuggestion.value = { note: null, justification: 'Erreur lors de la suggestion' };
    } finally {
      suggestingGrade.value = false;
    }
  }
  
  function toggleFullText() { showFullText.value = !showFullText.value; }
  function getTypeLabel(type) {
    return { intermediaire: 'Intermédiaire', final: 'Final', corrige: 'Corrigé' }[type] || type;
  }
  function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleString('fr-FR', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  }
  
  onMounted(() => {
    fetchReport();
    fetchComments();
  });
  </script>
  
  <style scoped>
  /* ===== PAGE ===== */
  .report-view {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .report-layout {
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
  }
  
  /* ===== EN-TÊTE ===== */
  .report-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-default);
  }
  
  .report-header__left { flex: 1; min-width: 0; }
  
  .report-header__badges {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
    flex-wrap: wrap;
  }
  
  .type-badge {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.25rem 0.65rem;
    background: var(--color-primary-subtle);
    color: var(--color-primary);
    border-radius: 99px;
  }
  
  .version-badge {
    font-size: 11px;
    font-weight: 500;
    padding: 0.25rem 0.6rem;
    background: var(--bg-elevated);
    color: var(--text-muted);
    border-radius: 99px;
    border: 1px solid var(--border-default);
  }
  
  .status-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 99px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .status-pill--soumis  { background: rgba(245,158,11,0.12); color: var(--color-warning); }
  .status-pill--valide  { background: rgba(16,185,129,0.12); color: var(--color-success); }
  .status-pill--rejete  { background: rgba(239,68,68,0.1);   color: var(--color-danger);  }
  
  .report-title {
    font-family: var(--font-display);
    font-size: var(--text-2xl);
    font-weight: 800;
    margin: 0 0 0.5rem;
    line-height: 1.2;
  }
  
  .report-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-size: var(--text-sm);
    color: var(--text-muted);
  }
  .report-meta .pi { font-size: 0.75rem; }
  .meta-sep { opacity: 0.4; }
  
  .report-header__actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
    flex-wrap: wrap;
  }
  
  /* ===== GRILLE PRINCIPALE ===== */
  .report-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1.5rem;
    align-items: start;
  }
  
  .report-grid__main { min-width: 0; }
  
  .report-grid__sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    position: sticky;
    top: 1.5rem;
  }
  
  /* ===== APERÇU DOCUMENT ===== */
  .doc-preview { overflow: hidden; padding: 0; }
  
  .doc-preview__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--border-default);
  }
  .doc-preview__toolbar-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .doc-preview__toolbar-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .doc-icon {
    color: var(--color-info);
    font-size: 1.1rem;
  }
  
  .doc-preview__body {
    padding: 1.5rem;
    max-height: 480px;
    overflow-y: auto;
  }
  .doc-preview__body--full { max-height: none; }
  
  .doc-preview__text {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: var(--text-sm);
    line-height: 1.75;
    color: var(--text-secondary);
  }
  
  .doc-preview__loading,
  .doc-preview__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: var(--text-muted);
    gap: 0.5rem;
  }
  .doc-preview__empty .pi { font-size: 2rem; opacity: 0.3; }
  
  /* ===== PANELS LATÉRAUX ===== */
  .panel { padding: 0; overflow: hidden; }
  
  .panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
  }
  
  .panel__header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  
  .panel-icon {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
  }
  .panel-icon--ai       { background: rgba(139,92,246,0.12); color: #8b5cf6; }
  .panel-icon--resources { background: rgba(16,185,129,0.12); color: var(--color-success); }
  .panel-icon--grade    { background: rgba(245,158,11,0.12); color: var(--color-warning); }
  .panel-icon--comments { background: var(--color-primary-subtle); color: var(--color-primary); }

  /* ===== RESOURCES ===== */
  .resources-list { display: flex; flex-direction: column; gap: 0.75rem; }
  .resource-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    text-decoration: none;
    transition: all 0.2s;
  }
  .resource-card:hover { border-color: var(--color-primary); transform: translateX(4px); background: var(--bg-elevated); }
  .resource-card__icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
  }
  .resource-card__icon.video { background: rgba(239,68,68,0.1); color: #ef4444; }
  .resource-card__icon.doc   { background: rgba(59,130,246,0.1); color: #3b82f6; }
  .resource-card__icon.article { background: rgba(139,92,246,0.1); color: #8b5cf6; }
  .resource-card__content { flex: 1; min-width: 0; }
  .resource-card__title { font-size: 11px; font-weight: 700; color: var(--text-primary); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .resource-card__desc { font-size: 10px; color: var(--text-muted); line-height: 1.3; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  
  .panel__title {
    font-size: var(--text-sm);
    font-weight: 700;
    margin: 0;
  }
  .panel__subtitle { margin: 0.1rem 0 0; }
  
  .panel__divider {
    height: 1px;
    background: var(--border-default);
    opacity: 0.6;
  }
  
  .panel__body {
    padding: 1.1rem 1.25rem;
  }
  
  /* ===== IA FEEDBACK ===== */
  .ia-feedback {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .ia-feedback__text {
    font-size: var(--text-sm);
    line-height: 1.7;
    color: var(--text-secondary);
    white-space: pre-wrap;
    word-break: break-word;
    background: var(--bg-elevated);
    border-radius: var(--radius-md);
    padding: 1rem;
    border-left: 3px solid #8b5cf6;
  }
  .ia-feedback__text--collapsed {
    max-height: 200px;
    overflow-y: auto;
  }
  
  .ia-feedback__toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: var(--text-xs);
    color: #8b5cf6;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-weight: 500;
    transition: opacity 0.15s;
  }
  .ia-feedback__toggle:hover { opacity: 0.7; }
  
  /* ===== NOTE ===== */
  .grade-display {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }
  .grade-display__value {
    font-family: var(--font-display);
    font-size: 3rem;
    font-weight: 800;
    color: var(--color-warning);
    line-height: 1;
  }
  .grade-display__total {
    font-size: var(--text-lg);
    color: var(--text-muted);
    font-weight: 500;
  }
  .grade-display__error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: var(--text-sm);
    color: var(--color-danger);
  }
  .grade-display__justification { line-height: 1.6; }
  
  /* ===== COMMENTAIRES ===== */
  .comments-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    max-height: 320px;
    overflow-y: auto;
    padding: 0.75rem 1.25rem;
  }
  
  .comment-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-default);
  }
  .comment-item:last-child { border-bottom: none; }
  
  .comment-item__avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
    color: #fff;
  }
  .avatar--prof { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .avatar--rup  { background: linear-gradient(135deg, var(--color-primary), #6366f1); }
  
  .comment-item__body { flex: 1; min-width: 0; }
  
  .comment-item__header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-bottom: 0.3rem;
  }
  
  .comment-item__name {
    font-size: var(--text-sm);
    font-weight: 600;
  }
  
  .comment-item__date {
    margin-left: auto;
    white-space: nowrap;
  }
  
  .role-tag {
    font-size: 10px;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 99px;
  }
  .role-tag--prof { background: rgba(245,158,11,0.12); color: var(--color-warning); }
  .role-tag--rup  { background: var(--color-primary-subtle); color: var(--color-primary); }
  
  .comment-item__text {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    line-height: 1.55;
    margin: 0;
    word-break: break-word;
  }
  
  /* Formulaire commentaire */
  .comment-form {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-default);
    background: var(--bg-elevated);
  }
  
  .comment-form__avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--color-primary-subtle);
    color: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
  }
  
  .comment-form__input-wrap { flex: 1; min-width: 0; }
  
  .comment-form__textarea {
    width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 0.6rem 0.875rem;
    font-size: var(--text-sm);
    color: var(--text-primary);
    resize: none;
    transition: border-color 0.15s;
    font-family: inherit;
    box-sizing: border-box;
  }
  .comment-form__textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-subtle);
  }
  .comment-form__textarea::placeholder { color: var(--text-muted); }
  
  .comment-form__footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5rem;
  }
  
  /* ===== ÉTATS VIDES / LOADING ===== */
  .panel-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1rem;
    text-align: center;
  }
  
  .panel-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem 1rem;
    gap: 0.4rem;
  }
  .panel-empty__icon {
    font-size: 1.75rem;
    opacity: 0.25;
    margin-bottom: 0.25rem;
  }
  
  /* ===== SPINNER ===== */
  .spinner {
    width: 36px;
    height: 36px;
    border: 2px solid var(--border-default);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    margin: 0 auto;
  }
  .spinner--sm { width: 22px; height: 22px; }
  @keyframes spin { to { transform: rotate(360deg); } }
  
  /* ===== UTILITAIRES ===== */
  .text-muted   { color: var(--text-muted); }
  .text-sm      { font-size: var(--text-sm); }
  .text-xs      { font-size: var(--text-xs); }
  .text-lg      { font-size: var(--text-lg); }
  .font-medium  { font-weight: 500; }
  .mt-1         { margin-top: 0.25rem; }
  .mt-2         { margin-top: 0.5rem; }
  
  /* ===== BTN WARNING ===== */
  .btn-warning {
    background: rgba(245,158,11,0.12);
    color: var(--color-warning);
    border: 1px solid rgba(245,158,11,0.25);
  }
  .btn-warning:hover {
    background: rgba(245,158,11,0.2);
  }
  .btn-xs {
    font-size: 11px;
    padding: 0.25rem 0.6rem;
  }
  
  /* ===== RESPONSIVE ===== */
  @media (max-width: 1024px) {
    .report-grid {
      grid-template-columns: 1fr;
    }
    .report-grid__sidebar {
      position: static;
    }
  }
  
  @media (max-width: 640px) {
    .report-header { flex-direction: column; }
    .report-view { padding: 1rem; }
  }

  /* Markdown Styling */
  :deep(.markdown-content) {
    line-height: 1.6;
  }
  :deep(.markdown-content p) {
    margin-bottom: 0.75rem;
  }
  :deep(.markdown-content p:last-child) {
    margin-bottom: 0;
  }
  :deep(.markdown-content strong) {
    font-weight: 700;
    color: var(--text-primary);
  }
  :deep(.markdown-content ul), :deep(.markdown-content ol) {
    margin: 0.5rem 0 0.75rem 1.25rem;
  }
  :deep(.markdown-content li) {
    margin-bottom: 0.25rem;
  }
  :deep(.markdown-content code) {
    background: var(--bg-surface);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9em;
  }
  :deep(.markdown-content hr) {
    border: 0;
    border-top: 1px solid var(--border-default);
    margin: 1rem 0;
  }
  :deep(.markdown-content blockquote) {
    border-left: 3px solid var(--color-primary);
    padding-left: 1rem;
    margin-left: 0;
    color: var(--text-muted);
    font-style: italic;
  }
  </style>