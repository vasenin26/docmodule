<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useTextExpansion } from '@/composables/useTextExpansion';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isTextExpanded, toggleTextExpansion } = useTextExpansion();

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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'обновление статьи'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Статья:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.article_title || parseResult()?.title || 'Не указана' }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Версия:</div>
                        <div class="text-sm">{{ parseResult()?.version || 'Не указана' }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Статус:</div>
                        <div class="text-sm">
                            <span :class="parseResult()?.status === 'updated' ? 'text-green-600' : 'text-orange-600'">
                                {{ parseResult()?.status || 'Не указан' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Изменения:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.changes_summary || parseResult()?.summary || 'Не указаны' }}</div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Содержимое:</div>
                        <button @click="toggleTextExpansion('update-article-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('update-article-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('update-article-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.content || parseResult()?.new_content }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Обновлено:</div>
                        <div class="text-sm">{{ formatDate(parseResult()?.updated_at || parseResult()?.timestamp) }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Автор:</div>
                        <div class="text-sm">{{ parseResult()?.author || 'Не указан' }}</div>
                    </div>
                </div>

                <div v-if="parseResult()?.file_path" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.file_path }}</div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
