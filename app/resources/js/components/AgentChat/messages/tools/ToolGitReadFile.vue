<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useTextExpansion } from '@/composables/useTextExpansion';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isTextExpanded, toggleTextExpansion } = useTextExpansion();

function getSuccessFlag(): boolean {
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

function getResultRaw(): any {
    const m: any = props.message.message;
    return m?.result || m?.tool_result;
}

function getFileContent(): string | undefined {
    const raw: any = getResultRaw();
    if (typeof raw === 'string' && !isErrorText(raw)) return raw;
    return undefined;
}

function isErrorText(raw: string): boolean {
    const t = raw.trim();
    return /^file\s+not\s+found[:]?/i.test(t) || /^error[:]?/i.test(t);
}

function isError(): boolean {
    const raw = getResultRaw();
    if (typeof raw === 'string' && isErrorText(raw)) return true;
    return !getSuccessFlag();
}

function containerClass(): string {
    return isError() ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'чтение файла'" :isError="isError()" />

        <div class="text-sm space-y-3">
            <div v-if="parseArgs()?.path" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Файл:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto">{{ parseArgs()?.path }}</div>
            </div>

            <template v-if="!isError()">
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
            </template>
            <template v-else>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение об ошибке:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getResultRaw() }}</div>
                </div>
            </template>
        </div>
    </div>
</template>
