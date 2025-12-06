<script setup lang="ts">
import { onMounted } from 'vue';
import TerminalTabs from '@/components/Terminal/TerminalTabs.vue';
import TerminalHeaderMenu from '@/components/Terminal/TerminalHeaderMenu.vue';
import TerminalView from '@/components/Terminal/TerminalView.vue';
import { useTerminals } from '@/composables/useTerminals';

const { terminals, activeId, load, create, setActive } = useTerminals();

onMounted(() => {
  load();
});
</script>

<template>
  <div class="h-full flex flex-col">
    <div class="flex items-center justify-between px-4 py-2 border-b">
      <TerminalTabs :terminals="terminals" :activeId="activeId" @select="setActive" />
      <TerminalHeaderMenu @create="create" />
    </div>

    <div class="flex-1 min-h-0">
      <TerminalView :terminalId="activeId" />
    </div>
  </div>
</template>
