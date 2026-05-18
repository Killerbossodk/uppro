<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'

const editor = useEditor({
  content: `
    <h1>Rapport de Projet - S2</h1>
    <p>Ce document est partagé avec tout le groupe. Vous pouvez co-rédiger votre rapport ici avant de le soumettre.</p>
    <h2>1. Introduction</h2>
    <p>Commencez à taper votre introduction ici...</p>
  `,
  extensions: [
    StarterKit,
    Placeholder.configure({
      placeholder: 'Commencez à écrire votre rapport...',
    }),
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose-base focus:outline-none max-w-full',
    },
  },
})
</script>

<template>
  <div class="document-editor card">
    <div v-if="editor" class="editor-toolbar">
      <div class="toolbar-group">
        <button 
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" 
          :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
          class="toolbar-btn"
          title="Titre 1"
        >
          <span style="font-weight: 800">H1</span>
        </button>
        <button 
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" 
          :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
          class="toolbar-btn"
          title="Titre 2"
        >
          <span style="font-weight: 700">H2</span>
        </button>
      </div>

      <div class="toolbar-divider" />

      <div class="toolbar-group">
        <button 
          @click="editor.chain().focus().toggleBold().run()" 
          :disabled="!editor.can().chain().focus().toggleBold().run()" 
          :class="{ 'is-active': editor.isActive('bold') }"
          class="toolbar-btn"
          title="Gras"
        >
          <i class="pi pi-bold" />
        </button>
        <button 
          @click="editor.chain().focus().toggleItalic().run()" 
          :disabled="!editor.can().chain().focus().toggleItalic().run()" 
          :class="{ 'is-active': editor.isActive('italic') }"
          class="toolbar-btn"
          title="Italique"
        >
          <i class="pi pi-italic" />
        </button>
        <button 
          @click="editor.chain().focus().toggleStrike().run()" 
          :disabled="!editor.can().chain().focus().toggleStrike().run()" 
          :class="{ 'is-active': editor.isActive('strike') }"
          class="toolbar-btn"
          title="Barré"
        >
          <i class="pi pi-minus" />
        </button>
      </div>

      <div class="toolbar-divider" />

      <div class="toolbar-group">
        <button 
          @click="editor.chain().focus().toggleBulletList().run()" 
          :class="{ 'is-active': editor.isActive('bulletList') }"
          class="toolbar-btn"
          title="Liste à puces"
        >
          <i class="pi pi-list" />
        </button>
        <button 
          @click="editor.chain().focus().toggleOrderedList().run()" 
          :class="{ 'is-active': editor.isActive('orderedList') }"
          class="toolbar-btn"
          title="Liste numérotée"
        >
          <i class="pi pi-sort-numeric-down" />
        </button>
        <button 
          @click="editor.chain().focus().toggleBlockquote().run()" 
          :class="{ 'is-active': editor.isActive('blockquote') }"
          class="toolbar-btn"
          title="Citation"
        >
          <i class="pi pi-comment" />
        </button>
      </div>

      <div class="toolbar-spacer" />

      <div class="toolbar-group">
        <button class="btn btn-primary btn-sm">
          <i class="pi pi-save" /> Sauvegarder
        </button>
        <button class="btn btn-secondary btn-sm">
          <i class="pi pi-cloud-upload" /> Soumettre au RUP
        </button>
      </div>
    </div>

    <div class="editor-content-wrapper">
      <editor-content :editor="editor" class="tiptap-editor" />
    </div>
  </div>
</template>

<style scoped>
.document-editor {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 600px;
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--border-default);
}

.editor-toolbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-default);
  flex-wrap: wrap;
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.toolbar-divider {
  width: 1px;
  height: 24px;
  background: var(--border-default);
  margin: 0 0.25rem;
}

.toolbar-spacer {
  flex: 1;
}

.toolbar-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-family: var(--font-sans);
  font-size: 0.9rem;
}

.toolbar-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.toolbar-btn.is-active {
  background: var(--color-primary-subtle);
  color: var(--color-primary-light);
  font-weight: bold;
}

.toolbar-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.editor-content-wrapper {
  flex: 1;
  overflow-y: auto;
  background: var(--bg-base);
  display: flex;
  justify-content: center;
  padding: 2rem;
}

/* Tiptap content styling */
:deep(.tiptap-editor) {
  width: 100%;
  max-width: 800px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 3rem 4rem;
  min-height: 800px;
  box-shadow: var(--shadow-sm);
  font-family: var(--font-sans);
  color: var(--text-primary);
  line-height: 1.6;
}

:deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: var(--text-muted);
  pointer-events: none;
  height: 0;
}

:deep(.tiptap h1) {
  font-family: var(--font-display);
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
  border-bottom: 2px solid var(--border-default);
  padding-bottom: 0.5rem;
}

:deep(.tiptap h2) {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 700;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: var(--text-primary);
}

:deep(.tiptap p) {
  margin-bottom: 1rem;
}

:deep(.tiptap ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 1rem;
}

:deep(.tiptap ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 1rem;
}

:deep(.tiptap blockquote) {
  border-left: 4px solid var(--color-primary-light);
  padding-left: 1rem;
  margin-left: 0;
  margin-bottom: 1rem;
  color: var(--text-secondary);
  font-style: italic;
  background: var(--color-primary-subtle);
  padding: 1rem;
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
}

@media (max-width: 768px) {
  :deep(.tiptap-editor) {
    padding: 1.5rem;
  }
}
</style>
