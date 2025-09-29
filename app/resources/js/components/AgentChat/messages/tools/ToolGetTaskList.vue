<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref, computed } from 'vue';
import { useTextExpansion } from '@/composables/useTextExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();

type TaskItem = { id: number; title: string; done: boolean };

const showCompleted = ref(false);

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

const displayedTasks = computed<TaskItem[] | undefined>(() => {
    const all = parseTasks();
    if (!all) return undefined;
    if (showCompleted.value) return all;
    return all.filter(t => !t.done);
});

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
                <span class="text-xs font-medium" :class="getSuccess() ? 'text-orange-700' : 'text-red-700'">список задач</span>
                <span class="px-2 py-1 rounded text-xs font-medium" :class="getSuccess() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ getSuccess() ? 'Успешно' : 'Ошибка' }}
                </span>
            </div>
        </div>

        <div class="text-sm space-y-3">
            <template v-if="displayedTasks !== undefined">
                <div class="flex justify-center">
                    <button @click="showCompleted = !showCompleted" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        {{ showCompleted ? 'скрыть выполненные' : 'показать выполненные' }}
                    </button>
                </div>

                <div v-if="displayedTasks!.length === 0" class="text-gray-500 italic">Список пуст</div>
                <ul v-else class="space-y-2">
                    <li v-for="task in displayedTasks" :key="task.id" class="flex items-start space-x-2">
                        <span style="padding-top: 8px;">
                        <span class="mt-1 inline-flex items-center" :title="task.done ? 'Выполнено' : 'Не выполнено'">
                            <svg v-if="task.done" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </span>
                    </span>
                        <div class="flex-1 min-w-0">
                            <div v-if="isLongText(task.title, 200) && !isTextExpanded('task-title-' + task.id)" class="space-y-1">
                                <div class="relative min-w-0" :title="task.title">
                                    <div class="text-sm bg-white p-2 rounded border truncate pr-8">{{ task.title }}</div>
                                    <button @click="toggleTextExpansion('task-title-' + task.id)" class="absolute bottom-1 right-1 p-1 text-gray-500 hover:text-gray-700" :title="'Показать полностью'" aria-label="Показать полностью">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <circle cx="4" cy="10" r="1.5" />
                                            <circle cx="10" cy="10" r="1.5" />
                                            <circle cx="16" cy="10" r="1.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="space-y-1">
                                <div class="relative min-w-0">
                                    <div class="text-sm bg-white p-2 rounded border" :class="isTextExpanded('task-title-' + task.id) ? 'whitespace-pre-wrap' : 'truncate pr-8'" :title="isTextExpanded('task-title-' + task.id) ? undefined : task.title">{{ task.title }}</div>
                                    <button v-if="isLongText(task.title, 200)" @click="toggleTextExpansion('task-title-' + task.id)" class="absolute bottom-1 right-1 p-1 text-gray-500 hover:text-gray-700" :title="isTextExpanded('task-title-' + task.id) ? 'Свернуть' : 'Показать полностью'" aria-label="Переключить разворачивание">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <circle cx="4" cy="10" r="1.5" />
                                            <circle cx="10" cy="10" r="1.5" />
                                            <circle cx="16" cy="10" r="1.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </template>
            <template v-else>
                <div class="text-gray-500 italic">Нет данных для отображения</div>
            </template>
        </div>
    </div>
</template>
