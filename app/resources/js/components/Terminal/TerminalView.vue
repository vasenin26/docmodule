<script setup lang="ts">
import TerminalOutput from './TerminalOutput.vue';
import TerminalInput from './TerminalInput.vue';
import { watch, onBeforeUnmount, computed } from 'vue';
import { useTerminalChat } from '@/composables/useTerminalChat';

const props = defineProps({ terminalId: [String, Number] });

const { messages, status, sending, sendCommand, startPolling, stopPolling } = 
    useTerminalChat(Number(props.terminalId));

const processing = computed(() => sending.value || status.value !== 'completed');

function onSubmitCommand(cmd: string) {
  sendCommand(cmd);
}

watch(() => props.terminalId, (newId) => {
  if (newId) {
    startPolling();
  } else {
    stopPolling();
  }
});

onBeforeUnmount(() => {
  stopPolling();
});
</script>

<template>
  <div class="h-full flex flex-col">
    <TerminalOutput :messages="messages" :processing="processing" />
    <TerminalInput @submit="onSubmitCommand" :disabled="processing" />
  </div>
</template>
