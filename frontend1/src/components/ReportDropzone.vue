<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  accept?: string
  maxSizeMb?: number
  label?: string
}>()

const emit = defineEmits<{
  upload: [file: File]
}>()

const isDragging = ref(false)
const file = ref<File | null>(null)
const progress = ref(0)
const uploading = ref(false)
const uploaded = ref(false)
const error = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const maxSize = computed(() => (props.maxSizeMb ?? 20) * 1024 * 1024)
const acceptTypes = computed(() => props.accept ?? '.pdf,.doc,.docx,.zip')

function onDragEnter(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  const dropped = e.dataTransfer?.files[0]
  if (dropped) handleFile(dropped)
}

function onFileInput(e: Event) {
  const input = e.target as HTMLInputElement
  const selected = input.files?.[0]
  if (selected) handleFile(selected)
}

function handleFile(f: File) {
  error.value = null
  if (f.size > maxSize.value) {
    error.value = `Le fichier dépasse la taille maximale de ${props.maxSizeMb ?? 20} Mo.`
    return
  }
  file.value = f
  uploaded.value = false
  progress.value = 0
}

async function uploadFile() {
  if (!file.value) return
  uploading.value = true
  progress.value = 0
  error.value = null

  // Simulate upload progress
  const interval = setInterval(() => {
    progress.value = Math.min(progress.value + Math.random() * 15, 95)
  }, 200)

  try {
    // Real upload would go here via api.post('/reports', formData)
    await new Promise((res) => setTimeout(res, 2000))
    clearInterval(interval)
    progress.value = 100
    uploaded.value = true
    emit('upload', file.value)
  } catch {
    clearInterval(interval)
    error.value = 'Erreur lors de l\'envoi. Veuillez réessayer.'
  } finally {
    uploading.value = false
  }
}

function removeFile() {
  file.value = null
  progress.value = 0
  uploaded.value = false
  error.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
  return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}

function fileIcon(name: string) {
  if (name.endsWith('.pdf')) return 'pi-file-pdf'
  if (name.endsWith('.doc') || name.endsWith('.docx')) return 'pi-file-word'
  if (name.endsWith('.zip')) return 'pi-file-zip'
  return 'pi-file'
}

function fileIconColor(name: string) {
  if (name.endsWith('.pdf')) return '#ef4444'
  if (name.endsWith('.doc') || name.endsWith('.docx')) return '#3b82f6'
  return 'var(--text-muted)'
}
</script>

<template>
  <div class="dropzone-wrapper">
    <!-- Drop area -->
    <div
      v-if="!file"
      class="dropzone"
      :class="{ dragging: isDragging }"
      @dragenter="onDragEnter"
      @dragleave="onDragLeave"
      @dragover="onDragOver"
      @drop="onDrop"
      @click="fileInputRef?.click()"
      id="report-dropzone"
    >
      <div class="dz-icon" :class="{ bounce: isDragging }">
        <i class="pi pi-cloud-upload" />
      </div>
      <p class="dz-title">{{ label ?? 'Déposez votre rapport ici' }}</p>
      <p class="dz-subtitle">ou <span class="dz-link">cliquez pour parcourir</span></p>
      <p class="dz-hint">{{ acceptTypes }} · Max {{ maxSizeMb ?? 20 }} Mo</p>

      <input
        ref="fileInputRef"
        type="file"
        :accept="acceptTypes"
        style="display: none"
        @change="onFileInput"
        id="file-input-hidden"
      />
    </div>

    <!-- File preview -->
    <div v-else class="file-preview">
      <div class="fp-icon" :style="{ color: fileIconColor(file.name) }">
        <i :class="`pi ${fileIcon(file.name)}`" />
      </div>

      <div class="fp-info">
        <p class="fp-name">{{ file.name }}</p>
        <p class="fp-size text-muted text-sm">{{ formatSize(file.size) }}</p>

        <!-- Progress bar -->
        <div v-if="uploading || uploaded" class="fp-progress">
          <div class="fp-progress-bar">
            <div
              class="fp-progress-fill"
              :class="{ done: uploaded }"
              :style="{ width: `${progress}%` }"
            />
          </div>
          <span class="fp-progress-label text-xs">
            <template v-if="uploaded">
              <i class="pi pi-check-circle" style="color: var(--color-success);" /> Envoyé
            </template>
            <template v-else>{{ Math.round(progress) }}%</template>
          </span>
        </div>
      </div>

      <div class="fp-actions">
        <button
          v-if="!uploading && !uploaded"
          class="btn btn-primary btn-sm"
          @click="uploadFile"
          id="btn-upload-report"
        >
          <i class="pi pi-upload" /> Envoyer
        </button>
        <button
          v-if="uploaded"
          class="btn btn-success btn-sm"
          disabled
        >
          <i class="pi pi-check" /> Envoyé !
        </button>
        <button
          v-if="!uploading"
          class="btn btn-ghost btn-icon btn-sm"
          @click="removeFile"
          title="Supprimer"
        >
          <i class="pi pi-trash" />
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="dz-error">
      <i class="pi pi-exclamation-triangle" />
      {{ error }}
    </div>
  </div>
</template>

<style scoped>
.dropzone-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Drop area */
.dropzone {
  border: 2px dashed var(--border-default);
  border-radius: var(--radius-lg);
  padding: 3rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  background: var(--bg-surface);
  text-align: center;
}

.dropzone:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
}

.dropzone.dragging {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
  transform: scale(1.01);
}

.dz-icon {
  width: 64px;
  height: 64px;
  border-radius: var(--radius-full);
  background: var(--color-primary-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary-light);
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
  transition: transform var(--transition-fast);
}

.dz-icon.bounce {
  animation: bounce 0.4s ease infinite alternate;
}

@keyframes bounce {
  from { transform: translateY(0); }
  to { transform: translateY(-8px); }
}

.dz-title {
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--text-primary);
}

.dz-subtitle {
  font-size: var(--text-sm);
  color: var(--text-secondary);
}

.dz-link {
  color: var(--color-primary-light);
  text-decoration: underline;
  cursor: pointer;
}

.dz-hint {
  font-size: var(--text-xs);
  color: var(--text-muted);
  font-family: monospace;
}

/* File preview */
.file-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
}

.fp-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.fp-info {
  flex: 1;
  min-width: 0;
}

.fp-name {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.2rem;
}

.fp-progress {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.fp-progress-bar {
  flex: 1;
  height: 6px;
  background: var(--bg-active);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.fp-progress-fill {
  height: 100%;
  background: var(--gradient-primary);
  border-radius: var(--radius-full);
  transition: width 0.2s ease;
}

.fp-progress-fill.done {
  background: linear-gradient(135deg, var(--color-success), #34d399);
}

.fp-progress-label {
  color: var(--text-muted);
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.fp-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Success button */
.btn-success {
  background: rgba(16, 185, 129, 0.15);
  color: var(--color-success);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

/* Error */
.dz-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
  color: var(--color-danger);
  font-size: var(--text-sm);
}
</style>
