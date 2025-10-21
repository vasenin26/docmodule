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
        <ToolHeaderStatus :title="'информация о странице'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Название:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.title || parseResult()?.name || 'Не указано' }}</div>
                </div>

                <div v-if="parseResult()?.description" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Описание:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.description }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">ID:</div>
                        <div class="text-sm font-mono">{{ parseResult()?.id || 'Не указан' }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Статус:</div>
                        <div class="text-sm">
                            <span :class="parseResult()?.status === 'active' ? 'text-green-600' : 'text-gray-600'">
                                {{ parseResult()?.status || 'Не указан' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Создана:</div>
                        <div class="text-sm">{{ formatDate(parseResult()?.created_at || parseResult()?.created) }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Обновлена:</div>
                        <div class="text-sm">{{ formatDate(parseResult()?.updated_at || parseResult()?.updated) }}</div>
                    </div>
                </div>

                <div v-if="parseResult()?.author" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Автор:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.author }}</div>
                </div>

                <div v-if="parseResult()?.url" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">URL:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono break-all">{{ parseResult()?.url }}</div>
                </div>

                <div v-if="parseResult()?.tags && parseResult()?.tags.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Теги:</div>
                    <div class="flex flex-wrap gap-1">
                        <span v-for="tag in parseResult()?.tags" :key="tag" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                            {{ tag }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
