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
          <span :class="['absolute top-0 right-0 w-3 h-3 rounded-full', statusColor(item.raw_status)]"></span>
        </button>
        <button @click.stop="hide(item)" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-xs">×</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import agentTasksPoller from '@/services/agentTasksPoller';

// Set component name for devtools
// @ts-ignore - defineOptions may not be typed in all setups
if (typeof defineOptions === 'function') defineOptions({ name: 'AgentTasksPanel' });

const items = ref([]);
const visible = computed(() => items.value.length > 0);

function refresh() {
  const list = agentTasksPoller.getPanelItems() || [];
  const filtered = (list || []).filter((it) => !it.hidden);
  items.value = filtered;
}

function onUpdated(e) {
  const detail = e?.detail ?? e;
  if (detail && Array.isArray(detail.items)) {
    items.value = detail.items.filter((it) => !it.hidden);
  } else if (Array.isArray(detail)) {
    items.value = detail.filter((it) => !it.hidden);
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
  try {
    agentTasksPoller.hideChat(item.chat_id ?? item.chatId);
  } catch (e) {
    console.error('hide failed', e);
  }
  refresh();
}

function statusColor(status) {
  switch (status) {
    case 'processing':
      return 'bg-yellow-400';
    case 'wait':
      return 'bg-blue-400';
    case 'completed':
      return 'bg-green-500';
    default:
      return 'bg-gray-400';
  }
}

onMounted(() => {
  agentTasksPoller.startPoller();
  refresh();
  window.addEventListener('agent_tasks.updated', onUpdated);
});

onBeforeUnmount(() => {
  window.removeEventListener('agent_tasks.updated', onUpdated);
  agentTasksPoller.stopPoller();
});
</script>

<style scoped>
/* small styling adjustments */
</style>
