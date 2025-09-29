<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useTextExpansion } from '@/composables/useTextExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseTitle(): string | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed.title === 'string') {
            return parsed.title as string;
        }
        return undefined;
    } catch {
        return undefined;
    }
}

function containerClass(): string {
    return 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-500">
                    <span class="text-xs font-medium text-white">T</span>
                </div>
                <span class="text-xs font-medium text-orange-700">задача выполнена</span>
                <span v-if="getSuccess()" class="inline-flex items-center" title="Успех">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="text-sm space-y-3">
            <template v-if="getSuccess() && parseTitle()">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Результат:</div>
                    <div v-if="isLongText(parseTitle(), 250) && !isTextExpanded('tasks-complete-title-' + props.index)" class="space-y-1">
                        <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getTruncatedText(parseTitle(), 250) }}</div>
                        <button @click="toggleTextExpansion('tasks-complete-title-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">Показать полностью</button>
                    </div>
                    <div v-else class="space-y-1">
                        <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ parseTitle() }}</div>
                        <button v-if="isLongText(parseTitle(), 250)" @click="toggleTextExpansion('tasks-complete-title-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">Свернуть</button>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="text-gray-500 italic">Нет данных для отображения</div>
            </template>
        </div>
    </div>
</template>
