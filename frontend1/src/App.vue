<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '@/mises_en_page/AppLayout.vue'
import AuthLayout from '@/mises_en_page/AuthLayout.vue'

const route = useRoute()
const layout = computed(() => (route.meta.layout === 'auth' ? 'auth' : 'app'))
</script>

<template>
  <AuthLayout v-if="layout === 'auth'" />
  <AppLayout v-else />
</template>
