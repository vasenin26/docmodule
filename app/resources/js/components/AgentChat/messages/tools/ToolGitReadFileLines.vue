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
        return JSON.parse(raw);
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
        <ToolHeaderStatus :title="'чтение строк файла'" :isError="!getSuccess()" />

        <template v-if="parseResult()?.data">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.data?.file_path }}</div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">диапазон: {{ parseResult()?.data?.start_line }}-{{ parseResult()?.data?.end_line }}</div>
                    <div class="bg-white p-2 rounded border">всего строк: {{ parseResult()?.data?.total_lines }}</div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Выбранные строки ({{ parseResult()?.data?.lines_count }}):</div>
                        <button @click="toggleTextExpansion('git-read-file-lines-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('git-read-file-lines-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('git-read-file-lines-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.data?.content }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
