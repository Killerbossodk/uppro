<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'

const route = useRoute()
const isFullScreen = computed(() => ['login', 'register'].includes(route.name as string))
</script>

<template>
  <div class="auth-layout" :class="{ 'auth-layout-full': isFullScreen }">
    <!-- Only show the animated background and container for non-login pages -->
    <template v-if="!isFullScreen">
      <div class="auth-background">
        <div class="blob blob-1" />
        <div class="blob blob-2" />
        <div class="blob blob-3" />
      </div>

      <div class="auth-container">
        <!-- Brand header -->
        <div class="auth-brand">
          <div class="brand-icon">
            <i class="pi pi-graduation-cap" />
          </div>
          <span class="brand-name">UP<span class="brand-accent">PRO</span></span>
        </div>

        <!-- Page content -->
        <RouterView v-slot="{ Component }">
          <Transition name="page" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>

        <!-- Footer -->
        <p class="auth-footer">
          © {{ new Date().getFullYear() }} UP PRO — Plateforme de projets académiques
        </p>
      </div>
    </template>

    <!-- For Login page, render directly to allow full screen split -->
    <template v-else>
      <RouterView />
    </template>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  background: var(--bg-base);
}

.auth-layout-full {
  display: block;
  padding: 0;
  background: white;
}

.auth-background {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
}

.blob-1 {
  width: 600px;
  height: 600px;
  background: var(--color-primary);
  top: -200px;
  left: -200px;
  animation: blobFloat 8s ease-in-out infinite;
}

.blob-2 {
  width: 400px;
  height: 400px;
  background: var(--color-secondary);
  bottom: -100px;
  right: -100px;
  animation: blobFloat 10s ease-in-out infinite reverse;
}

.blob-3 {
  width: 300px;
  height: 300px;
  background: var(--color-accent);
  top: 50%;
  left: 60%;
  animation: blobFloat 12s ease-in-out infinite;
}

@keyframes blobFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(20px, -20px) scale(1.05); }
  66% { transform: translate(-15px, 15px) scale(0.95); }
}

.auth-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 480px;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.auth-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  box-shadow: var(--shadow-glow);
}

.brand-name {
  font-family: var(--font-display);
  font-size: var(--text-3xl);
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.02em;
}

.brand-accent {
  color: var(--color-primary-light);
}

.auth-footer {
  font-size: var(--text-xs);
  color: var(--text-muted);
  text-align: center;
}
</style>
