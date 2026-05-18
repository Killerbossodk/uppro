<template>
    <div class="pdf-viewer-container">
      <div class="toolbar">
        <button @click="prevPage" :disabled="currentPage <= 1" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300">
          ◀ Précédent
        </button>
        <span>Page {{ currentPage }} / {{ totalPages }}</span>
        <button @click="nextPage" :disabled="currentPage >= totalPages" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50 hover:bg-gray-300">
          Suivant ▶
        </button>
        
        <div class="ml-4 flex items-center gap-2">
          <span class="text-sm">Zoom:</span>
          <button @click="zoomOut" class="px-2 py-1 bg-gray-200 rounded text-sm">-</button>
          <span class="text-sm w-12 text-center">{{ Math.round(scale * 100) }}%</span>
          <button @click="zoomIn" class="px-2 py-1 bg-gray-200 rounded text-sm">+</button>
          <button @click="fitToWidth" class="px-2 py-1 bg-blue-100 rounded text-sm ml-2">📐 Largeur</button>
          <button @click="fitToPage" class="px-2 py-1 bg-blue-100 rounded text-sm">📄 Page</button>
        </div>
        
        <button @click="toggleAnnotations" class="px-3 py-1 bg-purple-100 rounded ml-4">
          {{ showAnnotations ? 'Masquer' : 'Afficher' }} annotations
        </button>
      </div>
  
      <div class="canvas-wrapper" :style="wrapperStyle">
        <canvas ref="pdfCanvas" class="pdf-canvas"></canvas>
        
        <div 
          v-for="ann in annotations" 
          :key="ann.id" 
          v-show="showAnnotations"
          class="annotation-marker absolute cursor-pointer"
          :style="{ left: (ann.x * scaleRatio) + 'px', top: (ann.y * scaleRatio) + 'px' }"
          @click="editAnnotation(ann)"
          :title="ann.content"
        >
          💬
        </div>
      </div>
  
      <div v-if="showAnnotationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Ajouter une annotation</h3>
          <p class="text-sm text-gray-600 mb-2">Texte sélectionné :</p>
          <div class="bg-gray-100 p-2 rounded mb-3 text-sm italic max-h-32 overflow-y-auto">
            "{{ selectedText }}"
          </div>
          <textarea 
            v-model="annotationContent" 
            rows="4" 
            class="w-full border rounded-md p-2"
            placeholder="Ajoutez votre commentaire..."
          ></textarea>
          <div class="flex justify-end mt-4 space-x-2">
            <button @click="showAnnotationModal = false" class="px-4 py-2 border rounded-md hover:bg-gray-50">
              Annuler
            </button>
            <button @click="saveAnnotation" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
              Sauvegarder
            </button>
          </div>
        </div>
      </div>
  
      <div v-if="notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50">
        {{ notification }}
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, onUnmounted, computed } from 'vue';
  import * as pdfjsLib from 'pdfjs-dist';
  import api from '../services/api';
  
  // ✅ AJOUTER LES PROPS ICI
  const props = defineProps({
    fileUrl: { type: String, required: true },
    reportId: { type: Number, required: true }
  });
  
  // Configuration du worker PDF.js
  pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('/pdf.worker.min.js', window.location.origin).href;
  
  const pdfCanvas = ref(null);
  let pdfDoc = null;
  let originalViewport = null;
  let renderTask = null;
  const currentPage = ref(1);
  const totalPages = ref(0);
  const scale = ref(1.5);
  const annotations = ref([]);
  const showAnnotations = ref(true);
  const showAnnotationModal = ref(false);
  const annotationContent = ref('');
  const selectedText = ref('');
  const selectedPosition = ref({ x: 0, y: 0, page: 1, originalX: 0, originalY: 0 });
  const notification = ref('');
  const fitMode = ref('width');
  
  const scaleRatio = computed(() => scale.value / 1.5);
  
  const wrapperStyle = computed(() => ({
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '600px',
    backgroundColor: '#e5e5e5',
    borderRadius: '8px',
    padding: '20px'
  }));
  
  // URL du PDF
  const pdfUrl = computed(() => props.fileUrl);
  
  async function loadPDF() {
    try {
      console.log('📄 Chargement PDF:', pdfUrl.value);
      const loadingTask = pdfjsLib.getDocument(pdfUrl.value);
      pdfDoc = await loadingTask.promise;
      totalPages.value = pdfDoc.numPages;
      console.log('✅ PDF chargé, pages:', totalPages.value);
      
      // Récupérer le viewport original pour l'ajustement
      const firstPage = await pdfDoc.getPage(1);
      originalViewport = firstPage.getViewport({ scale: 1 });
      
      await adjustScale();
      await renderPage(currentPage.value);
    } catch (error) {
      console.error('❌ Erreur:', error);
      notification.value = '❌ Erreur de chargement du PDF';
      setTimeout(() => notification.value = '', 3000);
    }
  }
  async function adjustScale() {
    if (!pdfCanvas.value || !originalViewport) return;
    
    const container = pdfCanvas.value.parentElement;
    if (!container) return;
    
    const containerWidth = container.clientWidth - 40;
    
    if (fitMode.value === 'width') {
      scale.value = containerWidth / originalViewport.width;
    } else if (fitMode.value === 'page') {
      const containerHeight = window.innerHeight - 200;
      const scaleByWidth = containerWidth / originalViewport.width;
      const scaleByHeight = containerHeight / originalViewport.height;
      scale.value = Math.min(scaleByWidth, scaleByHeight);
    }
    
    scale.value = Math.min(Math.max(scale.value, 0.5), 3);
  }
  
  async function renderPage(pageNumber) {
    if (!pdfDoc || !pdfCanvas.value) return;
    
    try {
      if (renderTask) {
        renderTask.cancel();
        renderTask = null;
      }
      
      const page = await pdfDoc.getPage(pageNumber);
      const viewport = page.getViewport({ scale: scale.value });
      const canvas = pdfCanvas.value;
      const context = canvas.getContext('2d');
      
      if (!canvas || !context) return;
      
      canvas.height = viewport.height;
      canvas.width = viewport.width;
      canvas.style.display = 'block';
      canvas.style.margin = '0 auto';
      
      renderTask = page.render({
        canvasContext: context,
        viewport: viewport
      });
      
      await renderTask.promise;
      renderTask = null;
    } catch (error) {
      console.error('Erreur rendu page:', error);
    }
  }
  
  function nextPage() {
    if (currentPage.value >= totalPages.value) return;
    currentPage.value++;
    renderPage(currentPage.value);
  }
  
  function prevPage() {
    if (currentPage.value <= 1) return;
    currentPage.value--;
    renderPage(currentPage.value);
  }
  
  function zoomIn() {
    scale.value = Math.min(scale.value + 0.25, 3);
    fitMode.value = 'auto';
    renderPage(currentPage.value);
  }
  
  function zoomOut() {
    scale.value = Math.max(scale.value - 0.25, 0.5);
    fitMode.value = 'auto';
    renderPage(currentPage.value);
  }
  
  function fitToWidth() {
    fitMode.value = 'width';
    adjustScale();
    renderPage(currentPage.value);
  }
  
  function fitToPage() {
    fitMode.value = 'page';
    adjustScale();
    renderPage(currentPage.value);
  }
  
  function handleTextSelection() {
    const selection = window.getSelection();
    const text = selection.toString().trim();
    
    if (text.length > 0 && text.length < 500 && pdfCanvas.value) {
      const rect = selection.getRangeAt(0).getBoundingClientRect();
      const canvasRect = pdfCanvas.value.getBoundingClientRect();
      
      if (rect.left >= canvasRect.left && rect.right <= canvasRect.right) {
        const relativeX = ((rect.left + rect.right) / 2 - canvasRect.left) / scale.value * 1.5;
        const relativeY = (rect.top - canvasRect.top) / scale.value * 1.5;
        
        selectedText.value = text;
        selectedPosition.value = {
          x: (rect.left + rect.right) / 2 - canvasRect.left,
          y: rect.top - canvasRect.top,
          page: currentPage.value,
          originalX: relativeX,
          originalY: relativeY
        };
        annotationContent.value = '';
        showAnnotationModal.value = true;
        selection.removeAllRanges();
      }
    }
  }
  
  async function saveAnnotation() {
    if (!annotationContent.value.trim()) {
      alert('Veuillez saisir un commentaire');
      return;
    }
    try {
      await api.post(`/reports/${props.reportId}/annotations`, {
        page: selectedPosition.value.page,
        x: selectedPosition.value.originalX || selectedPosition.value.x,
        y: selectedPosition.value.originalY || selectedPosition.value.y,
        content: `"${selectedText.value}"\n\n${annotationContent.value}`
      });
      await fetchAnnotations();
      showAnnotationModal.value = false;
      notification.value = '✅ Annotation ajoutée';
      setTimeout(() => notification.value = '', 2000);
    } catch (error) {
      console.error('Erreur:', error);
      alert('Erreur lors de la sauvegarde');
    }
  }
  
  async function fetchAnnotations() {
    try {
      const res = await api.get(`/reports/${props.reportId}/annotations`);
      annotations.value = res.data;
    } catch (error) {
      console.error('Erreur chargement annotations:', error);
      annotations.value = [];
    }
  }
  
  function toggleAnnotations() {
    showAnnotations.value = !showAnnotations.value;
  }
  
  function editAnnotation(ann) {
    alert('Annotation :\n\n' + ann.content);
  }
  
  function handleResize() {
    if (fitMode.value !== 'auto' && pdfDoc) {
      adjustScale();
      renderPage(currentPage.value);
    }
  }
  
  onMounted(() => {
    console.log('📄 PDFViewer monté, fileUrl:', props.fileUrl);
    console.log('📄 PDFViewer monté, reportId:', props.reportId);
    loadPDF();
    fetchAnnotations();
    
    setTimeout(() => {
      if (pdfCanvas.value) {
        pdfCanvas.value.addEventListener('mouseup', handleTextSelection);
      }
    }, 1000);
    
    window.addEventListener('resize', handleResize);
  });
  
  onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    if (pdfCanvas.value) {
      pdfCanvas.value.removeEventListener('mouseup', handleTextSelection);
    }
    if (renderTask) {
      renderTask.cancel();
    }
  });
  </script>
  
  <style scoped>
  .pdf-viewer-container {
    background: #f5f5f5;
    border-radius: 12px;
    padding: 16px;
  }
  
  .toolbar {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
    padding: 8px 16px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    flex-wrap: wrap;
  }
  
  .canvas-wrapper {
    position: relative;
    overflow: auto;
    max-width: 100%;
    min-height: 600px;
    background: #525252;
    border-radius: 8px;
  }
  
  .pdf-canvas {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background: white;
    display: block;
  }
  
  .annotation-marker {
    background: rgba(255, 235, 59, 0.85);
    border-radius: 50%;
    min-width: 28px;
    min-height: 28px;
    padding: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    font-size: 14px;
    transform: translate(-50%, -50%);
    white-space: nowrap;
  }
  
  .annotation-marker:hover {
    transform: translate(-50%, -50%) scale(1.2);
    z-index: 10;
  }
  
  button {
    cursor: pointer;
    transition: all 0.2s;
  }
  
  button:hover:not(:disabled) {
    transform: scale(1.02);
  }
  
  button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  </style>