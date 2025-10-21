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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'поиск связанных страниц'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Исходная страница:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.source_page || parseResult()?.page_title || 'Не указана' }}</div>
                </div>

                <div v-if="parseResult()?.related_pages && parseResult()?.related_pages.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Найдено связанных страниц: {{ parseResult()?.related_pages?.length || 0 }}</div>
                    <div class="space-y-1">
                        <div v-for="(page, index) in parseResult()?.related_pages" :key="index" class="bg-gray-100 p-2 rounded border">
                            <div class="font-medium text-sm">{{ page.title || page.name }}</div>
                            <div v-if="page.description" class="text-xs text-gray-600 mt-1">{{ page.description }}</div>
                            <div v-if="page.url" class="text-xs font-mono text-blue-600 mt-1">{{ page.url }}</div>
                            <div v-if="page.relevance_score" class="text-xs text-gray-500 mt-1">
                                Релевантность: {{ page.relevance_score }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.search_criteria" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Критерии поиска:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">
                        {{ parseResult()?.search_criteria }}
                    </div>
                </div>

                <div v-if="parseResult()?.total_found" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Всего найдено:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        {{ parseResult()?.total_found }} страниц
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
