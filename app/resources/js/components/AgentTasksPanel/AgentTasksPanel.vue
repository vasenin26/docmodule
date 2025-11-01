<template>
    <div v-if="visible" class="pointer-events-none fixed right-0 bottom-4 left-0 flex justify-center">
        <div class="pointer-events-auto flex gap-2 rounded bg-white px-3 py-2 shadow">
            <div v-for="item in items" :key="item.chat_id" class="relative">
                <template v-if="!item.hidden">
                    <button @click="onClick(item)"
                            class="flex h-12 w-12 items-center justify-center rounded-full border">
                        <span class="sr-only">Open task</span>
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <component :is="typeIcon(item.type)" />
                        </svg>
                        <span
                            :class="['absolute right-0 bottom-0 h-3 w-3 rounded-full', statusColor(item.status)]"></span>
                    </button>
                    <button
                        @click.stop="hide(item)"
                        class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 text-xs"
                    >
                        ×
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {Bot, FileText, Hammer, Search, Pen, Brackets} from 'lucide-vue-next';
import { TaskItem, useAgentTasksPanel } from '@/composables/useAgentTasksPanel';
import {navigateToTargetResource} from "@/utils/utils";

const { items, hideItem, markItem } = useAgentTasksPanel();

const visible = computed(() => items.value.filter((m: TaskItem) => !m.hidden).length > 0);

function onClick(item: TaskItem) {
    markItem(item.chat_id)
    navigateToTargetResource(item.task_id)
}

function hide(item: TaskItem): void {
    hideItem(item.chat_id);
}

function statusColor(status) {
    switch (status) {
        case 'processing':
            return 'bg-yellow-400';
        case 'await':
            return 'bg-blue-400';
        case 'completed':
            return 'bg-green-200';
        default:
            return 'hidden';
    }
}

function typeIcon(stype: string) {
    switch (stype) {
        case 'actualization':
            return Search;
        case 'task':
            return Pen;
        case 'code':
            return Brackets;
        case 'tech':
            return Hammer;
        case 'text':
            return FileText;
        case 'plane-tasks':
        case 'search-relevant-files':
        default:
            return Bot;
    }
}
</script>

<style scoped>
/* small styling adjustments */
</style>
