<template>
  <div v-if="visible" class="fixed bottom-4 left-0 right-0 flex justify-center pointer-events-none">
    <div class="bg-white shadow rounded px-3 py-2 flex gap-2 pointer-events-auto">
      <div v-for="item in items" :key="item.chat_id" class="relative">
        <button @click="onClick(item)" class="w-12 h-12 rounded-full flex items-center justify-center border">
          <span class="sr-only">Open task</span>
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <component :is="typeIcon(item.type)"/>
          </svg>
          <span :class="['absolute bottom-0 right-0 w-3 h-3 rounded-full', statusColor(item.raw_status)]"></span>
        </button>
        <button @click.stop="hide(item)" class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-xs">×</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Bot, FileText} from 'lucide-vue-next';
import { useAgentTasksPanel } from '@/composables/useAgentTasksPanel';

const {items} = useAgentTasksPanel();

const visible = computed(() => items.value.length > 0);

function onClick() {

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

function typeIcon(stype: string) {
    switch (stype) {
        case 'task':
            return FileText
        default:
            return Bot
    }
}
</script>

<style scoped>
/* small styling adjustments */
</style>
