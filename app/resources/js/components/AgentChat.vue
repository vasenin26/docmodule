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
        <div
          v-for="(message, index) in messages"
          :key="index"
          :class="getMessageClass(message.role)"
          class="p-3 rounded-lg"
        >
          <!-- Заголовок сообщения -->
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <div :class="getRoleIconClass(message.role)" class="w-5 h-5 rounded-full flex items-center justify-center">
                <span class="text-xs font-medium text-white">
                  {{ getRoleIcon(message.role) }}
                </span>
              </div>
              <span class="text-xs font-medium" :class="getRoleLabelClass(message.role)">
                {{ getRoleLabel(message.role) }}
              </span>
            </div>
            <span class="text-xs text-gray-500">
              {{ formatTimestamp(message.timestamp) }}
            </span>
          </div>

          <!-- Содержимое сообщения -->
          <div class="text-sm">
            <div 
              v-if="isLongMessage(message.content) && !expandedMessages.has(index)"
              class="space-y-2"
            >
              <div class="whitespace-pre-wrap">{{ getTruncatedContent(message.content) }}</div>
              <button
                @click="toggleMessageExpansion(index)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
              >
                Показать полностью
              </button>
            </div>
            <div v-else class="space-y-2">
              <div class="whitespace-pre-wrap">{{ message.content }}</div>
              <button
                v-if="isLongMessage(message.content)"
                @click="toggleMessageExpansion(index)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
              >
                Свернуть
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, watch, onMounted } from 'vue';
import type { LLMMessage } from '@/types';

interface Props {
  messages?: LLMMessage[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  messages: () => [],
  loading: false,
});

const messagesContainer = ref<HTMLElement>();
const expandedMessages = ref(new Set<number>());

// Константы
const MAX_MESSAGE_LENGTH = 200;

// Функции для работы с ролями
function getRoleLabel(role: string): string {
  switch (role) {
    case 'user':
      return 'Пользователь';
    case 'assistant':
      return 'Ассистент';
    case 'system':
      return 'Система';
    default:
      return 'Неизвестно';
  }
}

function getRoleIcon(role: string): string {
  switch (role) {
    case 'user':
      return 'U';
    case 'assistant':
      return 'A';
    case 'system':
      return 'S';
    default:
      return '?';
  }
}

function getRoleIconClass(role: string): string {
  switch (role) {
    case 'user':
      return 'bg-blue-500';
    case 'assistant':
      return 'bg-green-500';
    case 'system':
      return 'bg-gray-500';
    default:
      return 'bg-gray-400';
  }
}

function getRoleLabelClass(role: string): string {
  switch (role) {
    case 'user':
      return 'text-blue-700';
    case 'assistant':
      return 'text-green-700';
    case 'system':
      return 'text-gray-700';
    default:
      return 'text-gray-600';
  }
}

function getMessageClass(role: string): string {
  switch (role) {
    case 'user':
      return 'bg-blue-50 border border-blue-200';
    case 'assistant':
      return 'bg-green-50 border border-green-200';
    case 'system':
      return 'bg-gray-50 border border-gray-200';
    default:
      return 'bg-gray-50 border border-gray-200';
  }
}

// Функции для работы с контентом
function isLongMessage(content: string): boolean {
  return content.length > MAX_MESSAGE_LENGTH;
}

function getTruncatedContent(content: string): string {
  return content.substring(0, MAX_MESSAGE_LENGTH) + '...';
}

function toggleMessageExpansion(index: number): void {
  if (expandedMessages.value.has(index)) {
    expandedMessages.value.delete(index);
  } else {
    expandedMessages.value.add(index);
  }
}

// Форматирование времени
function formatTimestamp(timestamp: string): string {
  try {
    const date = new Date(timestamp);
    return new Intl.DateTimeFormat('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  } catch {
    return 'Неизвестно';
  }
}

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
