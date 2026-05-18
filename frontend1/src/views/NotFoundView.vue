<script setup lang="ts">
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="not-found">
    <div class="nf-content animate-fade-in">
      <div class="nf-code">404</div>
      <h1 class="nf-title">Page introuvable</h1>
      <p class="nf-desc">La page que vous cherchez n'existe pas ou a été déplacée.</p>
      <button class="btn btn-primary btn-lg" @click="router.go(-1)">
        <i class="pi pi-arrow-left" />
        Retour
      </button>
    </div>
  </div>
</template>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-base);
}

.nf-content {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.nf-code {
  font-family: var(--font-display);
  font-size: 8rem;
  font-weight: 900;
  line-height: 1;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  opacity: 0.3;
}

.nf-title {
  font-family: var(--font-display);
  font-size: var(--text-3xl);
  font-weight: 800;
  color: var(--text-primary);
}

.nf-desc { color: var(--text-secondary); font-size: var(--text-base); }
</style>
