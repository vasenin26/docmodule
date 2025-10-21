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
            day: '2-digit'
        });
    } catch {
        return dateString;
    }
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'страницы проекта'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Проект:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.project_name || parseResult()?.project || 'Не указан' }}</div>
                </div>

                <div v-if="parseResult()?.pages && parseResult()?.pages.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Найдено страниц: {{ parseResult()?.pages?.length || 0 }}</div>
                    <div class="space-y-1">
                        <div v-for="(page, index) in parseResult()?.pages" :key="index" class="bg-gray-100 p-2 rounded border">
                            <div class="font-medium text-sm">{{ page.title || page.name }}</div>
                            <div v-if="page.description" class="text-xs text-gray-600 mt-1">{{ page.description }}</div>
                            <div v-if="page.url" class="text-xs font-mono text-blue-600 mt-1">{{ page.url }}</div>
                            <div class="flex items-center justify-between mt-1">
                                <div v-if="page.status" class="text-xs">
                                    <span :class="page.status === 'active' ? 'text-green-600' : 'text-gray-600'">
                                        {{ page.status }}
                                    </span>
                                </div>
                                <div v-if="page.updated_at" class="text-xs text-gray-500">
                                    {{ formatDate(page.updated_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-gray-500 italic text-sm">Страницы не найдены</div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Всего страниц:</div>
                        <div class="text-sm">{{ parseResult()?.total_pages || 0 }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Активных:</div>
                        <div class="text-sm">{{ parseResult()?.active_pages || 0 }}</div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
