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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'перехват контента'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Источник:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.source || 'Не указан' }}</div>
                </div>
                
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Перехваченный контент:</div>
                        <button @click="toggleTextExpansion('catch-content-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('catch-content-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('catch-content-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.content }}
                    </div>
                </div>

                <div v-if="parseResult()?.metadata" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Метаданные:</div>
                    <div class="text-xs font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                        {{ JSON.stringify(parseResult()?.metadata, null, 2) }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
