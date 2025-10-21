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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'информация об актуализации страницы'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Страница:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.page_title || parseResult()?.title || 'Не указана' }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Последнее обновление:</div>
                        <div class="text-sm">{{ formatDate(parseResult()?.last_updated || parseResult()?.updated_at) }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Статус актуализации:</div>
                        <div class="text-sm">
                            <span :class="parseResult()?.is_actual ? 'text-green-600' : 'text-red-600'">
                                {{ parseResult()?.is_actual ? 'Актуальна' : 'Устарела' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.version" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Версия:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.version }}</div>
                </div>

                <div v-if="parseResult()?.next_review_date" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Следующий пересмотр:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ formatDate(parseResult()?.next_review_date) }}</div>
                </div>

                <div v-if="parseResult()?.responsible_person" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Ответственный:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.responsible_person }}</div>
                </div>

                <div v-if="parseResult()?.notes" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Примечания:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.notes }}</div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
