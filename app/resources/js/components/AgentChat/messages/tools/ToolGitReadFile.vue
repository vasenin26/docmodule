<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useTextExpansion } from '@/composables/useTextExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isTextExpanded, toggleTextExpansion } = useTextExpansion();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseArgs(): { url?: string; path?: string } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.args || m?.tool_args;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        return parsed as any;
    } catch {
        return undefined;
    }
}

function getFileContent(): string | undefined {
    const m: any = props.message.message;
    // For readFile, result is the file content string
    const raw: any = m?.result || m?.tool_result;
    if (typeof raw === 'string') return raw;
    return undefined;
}

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
                <span class="text-xs font-medium" :class="getSuccess() ? 'text-orange-700' : 'text-red-700'">чтение файла</span>
                <span class="px-2 py-1 rounded text-xs font-medium" :class="getSuccess() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ getSuccess() ? 'Успешно' : 'Ошибка' }}
                </span>
            </div>
        </div>

        <div class="text-sm space-y-3">
            <div v-if="parseArgs()?.path" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Файл:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto">{{ parseArgs()?.path }}</div>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Содержимое файла:</div>
                    <button 
                        @click="toggleTextExpansion('git-read-file-content-' + props.index)" 
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        {{ isTextExpanded('git-read-file-content-' + props.index) ? 'Скрыть' : 'Показать' }}
                    </button>
                </div>
                <div v-if="isTextExpanded('git-read-file-content-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                    {{ getFileContent() }}
                </div>
            </div>
        </div>
    </div>
</template>
