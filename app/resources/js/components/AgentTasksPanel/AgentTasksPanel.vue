<template>
  <div v-if="visible" class="fixed bottom-4 left-0 right-0 flex justify-center pointer-events-none">
    <div class="bg-white shadow rounded px-3 py-2 flex gap-2 pointer-events-auto">
      <div v-for="item in items" :key="item.id" class="relative">
        <button @click="onClick(item)" class="w-12 h-12 rounded-full flex items-center justify-center border">
          <span class="sr-only">Open task</span>
          <!-- Icon placeholder -->
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="8" stroke-width="1.5" />
          </svg>
          <span :class="['absolute top-0 right-0 w-3 h-3 rounded-full', statusColor(item.status)]"></span>
        </button>
        <button @click.stop="hide(item)" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-xs">×</button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import agentTasksPoller from '@/services/agentTasksPoller';

export default {
  name: 'AgentTasksPanel',
  setup() {
    const items = ref([]);
    const visible = ref(false);

    function refresh() {
      const list = agentTasksPoller.getPanelItems();
      // Only visible if at least one non-hidden item exists
      const filtered = (list || []).filter((it) => !it.hidden);
      items.value = filtered;
      visible.value = filtered.length > 0;
    }

    function onUpdated(e) {
      const detail = e?.detail ?? e;
      // If event provides merged list, use it, otherwise read from poller
      if (Array.isArray(detail)) {
        items.value = detail.filter((it) => !it.hidden);
        visible.value = items.value.length > 0;
      } else {
        refresh();
      }
    }

    function onClick(item) {
      if (item.url) {
        window.location.href = item.url;
      }
    }

    function hide(item) {
      agentTasksPoller.hideChat(item.chatId);
      refresh();
    }

    onMounted(() => {
      // lazy init poller
      agentTasksPoller.startPoller();
      refresh();
      window.addEventListener('agent_tasks_panel.updated', onUpdated);
    });

    onBeforeUnmount(() => {
      window.removeEventListener('agent_tasks_panel.updated', onUpdated);
      agentTasksPoller.stopPoller();
    });

    return {
      items,
      visible,
      onClick,
      hide,
      statusColor(status) {
        switch (status) {
          case 'processing':
            return 'bg-yellow-400';
          case 'await':
            return 'bg-blue-400';
          case 'completed':
            return 'bg-green-500';
          default:
            return 'bg-gray-400';
        }
      },
    };
  },
};
</script>

<style scoped>
/* small styling adjustments */
</style>
