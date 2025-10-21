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

function parseArgs(): { url?: string; path?: string; content?: string } | undefined {
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

function parseResult(): { success?: boolean; message?: string; data?: any } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        return parsed as any;
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
        <ToolHeaderStatus :title="'изменение файла'" :subtitle="parseArgs()?.path" :isError="!getSuccess()" />

        <div class="text-sm space-y-3">
            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                    {{ (parseResult()?.message) || (getSuccess() ? 'Файл обновлён успешно' : 'Не удалось обновить файл') }}
                </div>
            </div>

            <div v-if="parseArgs()?.content !== undefined" class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Содержимое файла:</div>
                    <button
                        @click="toggleTextExpansion('editor-file-content-' + props.index)"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        {{ isTextExpanded('editor-file-content-' + props.index) ? 'Скрыть' : 'Показать' }}
                    </button>
                </div>
                <div v-if="isTextExpanded('editor-file-content-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                    {{ parseArgs()?.content }}
                </div>
            </div>
        </div>
    </div>
</template>
