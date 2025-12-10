<script setup lang="ts">
import { onMounted } from 'vue';
import TerminalTabs from '@/components/Terminal/TerminalTabs.vue';
import TerminalHeaderMenu from '@/components/Terminal/TerminalHeaderMenu.vue';
import TerminalView from '@/components/Terminal/TerminalView.vue';
import { useTerminals } from '@/composables/useTerminals';
import FullScreenLayout from '@/layouts/fullscreen/FullScreenLayout.vue';

const { terminals, activeId, load, create, setActive } = useTerminals();

onMounted(() => {
  load();
});
</script>

<template>
  <FullScreenLayout>
    <template #context-actions>
      <TerminalHeaderMenu @create="create" />
    </template>

    <div class="terminals-page">
      <div class="flex items-center justify-between px-4 py-2 border-b">
        <TerminalTabs :terminals="terminals" :activeId="activeId ?? undefined" @select="setActive" />
      </div>

      <div class="flex-1 min-h-0">
        <TerminalView v-if="activeId" :terminalId="activeId" />
        <div v-else class="flex items-center justify-center h-full text-muted-foreground">
          Выберите или создайте терминал
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<style scoped>
.terminals-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 46px); /* header height */
  margin: -15px; /* компенсация padding из .body */
  padding: 0;
  overflow: hidden;
}
</style>

