<script setup lang="ts">
import TerminalOutput from './TerminalOutput.vue';
import TerminalInput from './TerminalInput.vue';
import { ref, watch } from 'vue';

const props = defineProps({ terminalId: [String, Number] });
const outputLines = ref<string[]>([]);

function onSubmitCommand(cmd: string) {
  outputLines.value.push(`$ ${cmd}`);
  outputLines.value.push(`(вывод команды не реализован)`);
}

watch(() => props.terminalId, (newId) => {
  // Очистка вывода при переключении терминала (можно заменить на загрузку истории)
  outputLines.value = [];
});
</script>

<template>
  <div class="h-full flex flex-col">
    <TerminalOutput :lines="outputLines" />
    <TerminalInput @submit="onSubmitCommand" />
  </div>
</template>
