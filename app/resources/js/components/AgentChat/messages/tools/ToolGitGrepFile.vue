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
        <ToolHeaderStatus :title="'поиск по файлу'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.file_path }}</div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">строк: {{ parseResult()?.total_lines }}</div>
                    <div class="bg-white p-2 rounded border">совпадений: {{ parseResult()?.matches_count }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Шаблон:</div>
                    <div class="text-xs bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.pattern }}</div>
                    <div class="text-xs text-gray-600">опции: {{ JSON.stringify(parseResult()?.search_options) }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Совпадения:</div>
                    <ul v-if="Array.isArray(parseResult()?.matches) && parseResult()?.matches.length" class="space-y-1">
                        <li v-for="m in parseResult()?.matches" :key="m.file + ':' + m.line" class="bg-white p-2 rounded border text-xs">
                            <div class="font-mono text-gray-600">строка {{ m.line_number ?? m.line }}</div>
                            <div class="font-mono whitespace-pre-wrap">{{ m.line_content ?? m.content }}</div>
                        </li>
                    </ul>
                    <div v-else class="text-gray-500 italic text-xs">Нет совпадений</div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
