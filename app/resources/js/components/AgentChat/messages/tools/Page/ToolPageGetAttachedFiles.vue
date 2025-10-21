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

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Б';
    const k = 1024;
    const sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'прикрепленные файлы страницы'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Страница:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.page_title || parseResult()?.title || 'Не указана' }}</div>
                </div>

                <div v-if="parseResult()?.files && parseResult()?.files.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Найдено файлов: {{ parseResult()?.files?.length || 0 }}</div>
                    <div class="space-y-1">
                        <div v-for="(file, index) in parseResult()?.files" :key="index" class="bg-gray-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-medium text-sm">{{ file.name || file.filename }}</div>
                                <div class="text-xs text-gray-500">{{ formatFileSize(file.size || 0) }}</div>
                            </div>
                            <div v-if="file.type" class="text-xs text-gray-600 mt-1">Тип: {{ file.type }}</div>
                            <div v-if="file.url" class="text-xs font-mono text-blue-600 mt-1 break-all">{{ file.url }}</div>
                            <div v-if="file.description" class="text-xs text-gray-600 mt-1">{{ file.description }}</div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-gray-500 italic text-sm">Нет прикрепленных файлов</div>

                <div v-if="parseResult()?.total_size" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Общий размер:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ formatFileSize(parseResult()?.total_size) }}</div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
