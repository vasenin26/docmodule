<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref, computed } from 'vue';
import { useTextExpansion } from '@/composables/useTextExpansion';
import TaskListItem from '@/components/AgentChat/messages/tools/Tasks/TaskListItem.vue';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();

type TaskItem = { id: number; title: string; done: boolean };

const showCompleted = ref(false);

function getSuccessFlag(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function getResultRaw(): string | undefined {
    const m: any = props.message.message;
    return m?.result || m?.tool_result;
}

function parseResultAny(): any {
    const raw = getResultRaw();
    if (!raw) return undefined;
    try { return JSON.parse(raw); } catch { return undefined; }
}

function getToolResultMessage(): string | undefined {
    const parsed = parseResultAny();
    if (parsed && typeof parsed.message === 'string') return parsed.message as string;
    if (parsed && typeof parsed.error === 'string') return parsed.error as string;
    return undefined;
}

function parseTasks(): TaskItem[] | undefined {
    const parsed = parseResultAny();
    // Новый формат ToolResult: { message, payload }
    if (parsed && parsed.payload && Array.isArray(parsed.payload.tasks)) return parsed.payload.tasks as TaskItem[];
    // Переходный формат: { tasks: [...] }
    if (parsed && Array.isArray(parsed.tasks)) return parsed.tasks as TaskItem[];
    // Старый формат: массив задач напрямую
    if (Array.isArray(parsed)) return parsed as TaskItem[];
    return undefined;
}

function parseStats(): { total: number; completed: number; remaining: number } | undefined {
    const parsed = parseResultAny();
    // Новый формат ToolResult
    if (parsed && parsed.payload && parsed.payload.stats) return parsed.payload.stats as { total: number; completed: number; remaining: number };
    // Переходный формат
    if (parsed && parsed.stats) return parsed.stats as { total: number; completed: number; remaining: number };
    return undefined;
}

function getErrorMessage(): string | undefined {
    const parsed = parseResultAny();
    if (parsed && typeof parsed.message === 'string' && !getSuccessFlag()) return parsed.message as string;
    if (parsed && typeof parsed.error === 'string') return parsed.error as string;
    return undefined;
}

// Ошибка только если success=false или есть поле error
const isError = computed(() => !getSuccessFlag() || Boolean(getErrorMessage()));

const displayedTasks = computed<TaskItem[] | undefined>(() => {
    const all = parseTasks();
    if (!all) return undefined;
    if (showCompleted.value) return all;
    return all.filter(t => !t.done);
});

function containerClass(): string {
    return isError.value ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'список задач'" :isError="isError" />

        <div class="text-sm space-y-3">
            <template v-if="!isError">
                <div class="flex justify-center">
                    <button @click="showCompleted = !showCompleted" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        {{ showCompleted ? 'скрыть выполненные' : 'показать выполненные' }}
                    </button>
                </div>

                <div v-if="displayedTasks && displayedTasks.length === 0" class="text-gray-500 italic">Список пуст</div>
                <ul v-else-if="displayedTasks && displayedTasks.length > 0" class="space-y-2">
                    <TaskListItem v-for="task in displayedTasks" :key="task.id" :id="task.id" :title="task.title" :done="task.done" />
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
