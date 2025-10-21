<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseResult(): any | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        if (parsed && parsed.payload) return parsed.payload;
        return parsed;
    } catch {
        return undefined;
    }
}

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}

function formatDate(dateString: string): string {
    try {
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return dateString;
    }
}

function getStatusColor(status: string): string {
    switch (status) {
        case 'completed': return 'text-green-600';
        case 'in_progress': return 'text-blue-600';
        case 'pending': return 'text-yellow-600';
        case 'cancelled': return 'text-red-600';
        default: return 'text-gray-600';
    }
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'история задач страницы'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Страница:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.page_title || parseResult()?.title || 'Не указана' }}</div>
                </div>

                <div v-if="parseResult()?.tasks && parseResult()?.tasks.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">История задач: {{ parseResult()?.tasks?.length || 0 }}</div>
                    <div class="space-y-1">
                        <div v-for="(task, index) in parseResult()?.tasks" :key="index" class="bg-gray-100 p-2 rounded border">
                            <div class="font-medium text-sm">{{ task.title || task.name }}</div>
                            <div v-if="task.description" class="text-xs text-gray-600 mt-1">{{ task.description }}</div>
                            <div class="flex items-center justify-between mt-1">
                                <div class="text-xs">
                                    <span :class="getStatusColor(task.status)">
                                        {{ task.status }}
                                    </span>
                                </div>
                                <div v-if="task.created_at" class="text-xs text-gray-500">
                                    {{ formatDate(task.created_at) }}
                                </div>
                            </div>
                            <div v-if="task.assignee" class="text-xs text-gray-600 mt-1">Исполнитель: {{ task.assignee }}</div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-gray-500 italic text-sm">Задачи не найдены</div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Всего задач:</div>
                        <div class="text-sm">{{ parseResult()?.total_tasks || 0 }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Завершено:</div>
                        <div class="text-sm">{{ parseResult()?.completed_tasks || 0 }}</div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
