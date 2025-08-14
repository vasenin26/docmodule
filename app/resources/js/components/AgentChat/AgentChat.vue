<template>
  <div class="flex flex-col h-full border rounded-lg overflow-hidden bg-white">
    <!-- Заголовок чата -->
    <div class="bg-gray-100 px-4 py-3 border-b">
      <h3 class="text-sm font-medium text-gray-800">История LLM генерации</h3>
    </div>

    <!-- Содержимое чата -->
    <div
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-4 space-y-4 max-h-96"
    >
      <!-- Состояние загрузки -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="flex items-center space-x-2 text-gray-500">
          <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-gray-600"></div>
          <span class="text-sm">Генерация...</span>
        </div>
      </div>

      <!-- Сообщения отсутствуют -->
      <div v-else-if="!messages || messages.length === 0" class="flex items-center justify-center py-8">
        <div class="text-center text-gray-500">
          <div class="text-sm">История LLM пока пуста</div>
          <div class="text-xs mt-1">Сообщения появятся после генерации</div>
        </div>
      </div>

      <!-- Список сообщений -->
      <div v-else class="space-y-4">
          <Message
          v-for="(message, index) in messages"
          :key="index"
          :message="message"
          :index="index"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, watch, onMounted } from 'vue';
import type { LLMMessage } from '@/types';
import Message from "@/components/AgentChat/Message.vue";

interface Props {
  messages?: LLMMessage[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  messages: () => [],
  loading: false,
});

const messagesContainer = ref<HTMLElement>();

// Автоматическая прокрутка к последнему сообщению
async function scrollToBottom(): Promise<void> {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
}

// Следим за изменениями сообщений для автопрокрутки
watch(
  () => props.messages,
  () => {
    if (props.messages && props.messages.length > 0) {
      scrollToBottom();
    }
  },
  { deep: true }
);

// Прокрутка при монтировании
onMounted(() => {
  if (props.messages && props.messages.length > 0) {
    scrollToBottom();
  }
});
</script>
