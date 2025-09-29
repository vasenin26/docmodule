<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { computed } from 'vue';
import TaskListItem from '@/components/AgentChat/messages/tools/TaskListItem.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

type TaskItem = { id: number; title: string; done: boolean };

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseTasks(): TaskItem[] | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            return parsed as TaskItem[];
        }
        return undefined;
    } catch {
        return undefined;
    }
}

const tasks = computed<TaskItem[] | undefined>(() => parseTasks());

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full" :class="getSuccess() ? 'bg-orange-500' : 'bg-red-500'">
                    <span class="text-xs font-medium text-white">T</span>
                </div>
                <span class="text-xs font-medium" :class="getSuccess() ? 'text-orange-700' : 'text-red-700'">добавленные задачи</span>
                <span class="px-2 py-1 rounded text-xs font-medium" :class="getSuccess() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ getSuccess() ? 'Успешно' : 'Ошибка' }}
                </span>
            </div>
        </div>

        <div class="text-sm space-y-3">
            <template v-if="tasks !== undefined">
                <div v-if="tasks!.length === 0" class="text-gray-500 italic">Список пуст</div>
                <ul v-else class="space-y-2">
                    <TaskListItem v-for="task in tasks" :key="task.id" :id="task.id" :title="task.title" :done="task.done" expansionKeyPrefix="tasks-add-title-" />
                </ul>
            </template>
            <template v-else>
                <div class="text-gray-500 italic">Нет данных для отображения</div>
            </template>
        </div>
    </div>
</template>
