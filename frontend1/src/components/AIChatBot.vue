<template>
    <div class="fixed bottom-6 right-6 z-50">
      <button 
        v-if="!isOpen"
        @click="openChat"
        class="w-16 h-16 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full shadow-lg flex items-center justify-center text-white text-2xl hover:scale-110 transition-all duration-300"
      >
        <span>🤖</span>
      </button>
  
      <div 
        v-else
        class="w-96 h-[600px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200"
      >
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-3 flex justify-between items-center">
          <div class="flex items-center space-x-2">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-white font-medium">Assistant UP-PRO</h3>
              <p class="text-purple-200 text-xs">{{ getRoleLabel }}</p>
            </div>
          </div>
          <button @click="isOpen = false" class="text-white hover:text-gray-200 text-xl">✕</button>
        </div>
  
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50" ref="messagesContainer">
          <div v-if="messages.length === 0" class="flex justify-start">
            <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-sm max-w-[80%]">
              <p class="text-sm text-gray-800">{{ welcomeMessage }}</p>
            </div>
          </div>
  
          <div v-for="(msg, index) in messages" :key="index" class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
            <div 
              class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm"
              :class="msg.role === 'user' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none'"
            >
              <div class="whitespace-pre-wrap break-words text-sm leading-relaxed">
                {{ msg.content }}
              </div>
            </div>
          </div>
  
          <div v-if="typing" class="flex justify-start">
            <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
              <div class="flex space-x-1">
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
              </div>
            </div>
          </div>
        </div>
  
        <div class="px-4 py-2 bg-gray-50 border-t border-gray-200">
          <div class="flex flex-wrap gap-2">
            <button 
              v-for="suggestion in quickSuggestions" 
              :key="suggestion"
              @click="sendQuickMessage(suggestion)"
              class="text-xs px-3 py-1.5 bg-white border border-gray-300 rounded-full hover:bg-gray-100 text-gray-600 transition"
            >
              {{ suggestion }}
            </button>
          </div>
        </div>
  
        <div class="p-4 bg-white border-t border-gray-200">
          <div class="flex space-x-2">
            <input 
              v-model="inputMessage"
              @keyup.enter="sendMessage"
              type="text"
              placeholder="Écrivez votre message..."
              class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              :disabled="typing"
            />
            <button 
              @click="sendMessage"
              :disabled="!inputMessage.trim() || typing"
              class="bg-purple-600 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              <span v-if="!typing">➤</span>
              <span v-else class="animate-spin">⏳</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, nextTick } from 'vue';
  import { useAuthStore } from '@/magasins/auth';
  import api from '@/services/api';
  
  const props = defineProps({
    reportId: { type: Number, default: null }
  });
  
  const auth = useAuthStore();
  const isOpen = ref(false);
  const messages = ref([]);
  const inputMessage = ref('');
  const typing = ref(false);
  const messagesContainer = ref(null);
  
  const userRole = computed(() => auth.user?.role);
  
  const getRoleLabel = computed(() => {
    const labels = {
      'etudiant': 'Assistant Étudiant',
      'professeur': 'Assistant Professeur',
      'rup_projet': 'Assistant RUP Projet',
      'rup_specialite': 'Assistant RUP Spécialité'
    };
    return labels[userRole.value] || 'Assistant UP-PRO';
  });
  
  const welcomeMessage = computed(() => {
    const messages = {
      'etudiant': 'Bonjour ! Je suis votre assistant étudiant. Je peux vous aider à corriger, reformuler, résumer ou analyser vos textes. Que voulez-vous faire ?',
      'professeur': 'Bonjour Professeur ! Je peux vous aider à générer des idées de projets, analyser des rapports, suggérer des notes ou rédiger des commentaires.',
      'rup_projet': 'Bonjour ! Je peux vous fournir des statistiques, générer des rapports de suivi ou identifier des projets similaires.',
      'rup_specialite': 'Bonjour ! Je peux vous aider à constituer des jurys, analyser les notes ou générer des synthèses par spécialité.'
    };
    return messages[userRole.value] || 'Bonjour ! Comment puis-je vous aider ?';
  });
  
  const quickSuggestions = computed(() => {
    const suggestions = {
      'etudiant': ['📝 Corriger mon texte', '📊 Analyser mon rapport', '📄 Résumer ce document', '✍️ Reformuler en académique'],
      'professeur': ['💡 Générer des idées de projets', '📊 Analyser un rapport', '📝 Rédiger un commentaire', '🎓 Questions de soutenance'],
      'rup_projet': ['📊 Statistiques globales', '📈 Projets similaires', '📋 Rapport de suivi'],
      'rup_specialite': ['👨‍⚖️ Suggérer un jury', '📊 Moyennes par spécialité', '📝 Synthèse des notes']
    };
    return suggestions[userRole.value] || ['💬 Parler à l\'assistant'];
  });
  
  function openChat() {
    isOpen.value = true;
    scrollToBottom();
  }
  
  async function sendMessage() {
    if (!inputMessage.value.trim() || typing.value) return;
    
    const userMsg = inputMessage.value;
    messages.value.push({ role: 'user', content: userMsg });
    inputMessage.value = '';
    typing.value = true;
    
    await scrollToBottom();
    
    try {
      const res = await api.post('/ai/chat', {
        message: userMsg,
        history: messages.value.slice(-10),
        report_id: props.reportId
      });
      
      typing.value = false;
      messages.value.push({
        role: 'assistant',
        content: res.data.message
      });
      
      await scrollToBottom();
    } catch (error) {
      typing.value = false;
      console.error('Erreur chat:', error);
      messages.value.push({
        role: 'assistant',
        content: '❌ Désolé, une erreur est survenue. Veuillez réessayer.'
      });
    }
  }
  
  function sendQuickMessage(message) {
    inputMessage.value = message;
    sendMessage();
  }
  
  async function scrollToBottom() {
    await nextTick();
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  }
  
  onMounted(() => {
    scrollToBottom();
  });
  </script>
  
  <style scoped>
  .whitespace-pre-wrap {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  
  .max-w-\[85\%\] {
    max-width: 85%;
  }
  
  .overflow-y-auto::-webkit-scrollbar {
    width: 5px;
  }
  
  .overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
  
  .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c084fc;
    border-radius: 10px;
  }
  </style>