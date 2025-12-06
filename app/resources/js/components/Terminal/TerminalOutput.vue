<script setup lang="ts">
import { onUpdated, ref, nextTick, watch } from 'vue';
const props = defineProps({ lines: { type: Array, default: () => [] } });
const container = ref<HTMLElement | null>(null);

function scrollToBottom() {
  nextTick(() => {
    if (container.value) {
      container.value.scrollTop = container.value.scrollHeight;
    }
  });
}

watch(() => props.lines.length, () => {
  scrollToBottom();
});
</script>

<template>
  <div ref="container" class="flex-1 overflow-auto p-4 bg-white text-sm font-mono whitespace-pre-wrap min-h-0">
    <div v-if="!props.lines.length" class="text-gray-500 italic">Нет вывода</div>
    <div v-else>
      <div v-for="(l, i) in props.lines" :key="i">{{ l }}</div>
    </div>
  </div>
</template>
