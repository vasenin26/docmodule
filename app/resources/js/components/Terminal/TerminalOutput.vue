<script setup lang="ts">
import { ref, nextTick, watch, computed } from 'vue';
import type { LLMMessage } from '@/types';

const props = defineProps<{
    messages: LLMMessage[];
    processing?: boolean;
}>();

const container = ref<HTMLElement | null>(null);

const lastUserMessageIndex = computed(() => {
  for (let i = props.messages.length - 1; i >= 0; i--) {
    if (props.messages[i].type === 'user') {
      return i;
    }
  }
  return -1;
});

function scrollToBottom() {
  nextTick(() => {
    if (container.value) {
      container.value.scrollTop = container.value.scrollHeight;
    }
  });
}

watch(() => props.messages.length, () => {
  scrollToBottom();
});
</script>

<template>
  <div ref="container" class="flex-1 overflow-y-auto p-4 bg-black text-green-400 font-mono text-sm min-h-0">
    <div v-if="!props.messages.length" class="text-gray-500 italic">Нет вывода</div>
    <div v-else>
      <div v-for="(message, index) in props.messages" :key="index" class="mb-2">
        <div v-if="message.type === 'user'" class="text-blue-400 flex items-center gap-2">
          <span>$ {{ message.message?.content || '' }}</span>
          <div 
            v-if="processing && index === lastUserMessageIndex" 
            class="inline-flex items-center"
          >
            <svg class="animate-spin h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>
        <div v-else-if="message.type === 'assistant'" class="text-green-400 whitespace-pre-wrap">
          {{ message.message?.content || '' }}
        </div>
        <div v-else-if="message.type === 'result'" class="text-yellow-400 whitespace-pre-wrap">
          {{ message.message?.content || '' }}
        </div>
      </div>
    </div>
  </div>
</template>
