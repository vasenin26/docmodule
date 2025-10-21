<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { computed } from 'vue';
import TaskListItem from '@/components/AgentChat/messages/tools/Tasks/TaskListItem.vue';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

type TaskItem = { id: number; title: string; done: boolean };

function getSuccessFlag(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function getResultRaw(): string | undefined {
    const m: any = props.message.message;
    return m?.result || m?.tool_result;
}

function parseAny(): any | undefined {
    const raw = getResultRaw();
    if (!raw) return undefined;
    try { return JSON.parse(raw); } catch { return undefined; }
}

function parseTasks(): TaskItem[] | undefined {
    const parsed = parseAny();
    if (parsed && parsed.payload && Array.isArray(parsed.payload.tasks)) {
        return parsed.payload.tasks as TaskItem[];
    }
    if (parsed && parsed.tasks && Array.isArray(parsed.tasks)) {
        return parsed.tasks as TaskItem[];
    }
    // Fallback для старого формата
    if (Array.isArray(parsed)) return parsed as TaskItem[];
    return undefined;
}

function parseStats(): { total: number; completed: number; remaining: number } | undefined {
    const parsed = parseAny();
    if (parsed && parsed.payload && parsed.payload.stats) {
        return parsed.payload.stats;
    }
    if (parsed && parsed.stats) {
        return parsed.stats;
    }
    return undefined;
}

function getErrorMessage(): string | undefined {
    const parsed = parseAny();
    if (parsed && typeof parsed.message === 'string' && !getSuccessFlag()) return parsed.message as string;
    if (parsed && typeof parsed.error === 'string') return parsed.error as string;
    return undefined;
}

// Ошибка только если success=false или есть явное сообщение об ошибке
const isError = computed(() => !getSuccessFlag() || Boolean(getErrorMessage()));

const tasks = computed<TaskItem[] | undefined>(() => parseTasks());
const stats = computed(() => parseStats());

function containerClass(): string {
    return isError.value ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'добавленные задачи'" :isError="isError" />

        <div class="text-sm space-y-3">
            <template v-if="!isError">
                <div v-if="tasks && tasks!.length === 0" class="text-gray-500 italic">Список пуст</div>
                <ul v-else-if="tasks && tasks!.length > 0" class="space-y-2">
                    <TaskListItem v-for="task in tasks" :key="task.id" :id="task.id" :title="task.title" :done="task.done" expansionKeyPrefix="tasks-add-title-" />
                </ul>
                <div v-else class="text-gray-500 italic">Нет данных</div>
            </template>
            <template v-else>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение об ошибке:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getErrorMessage() || 'Некорректные данные результата' }}</div>
                </div>
            </template>
        </div>
    </div>
</template>
