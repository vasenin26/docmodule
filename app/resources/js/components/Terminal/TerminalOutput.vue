<script setup lang="ts">
import { ref, nextTick, watch } from 'vue';
import type { LLMMessage } from '@/types';

const props = defineProps<{
    messages: LLMMessage[];
}>();

const container = ref<HTMLElement | null>(null);

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
        <div v-if="message.type === 'user'" class="text-blue-400">
          $ {{ message.message?.content || '' }}
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
