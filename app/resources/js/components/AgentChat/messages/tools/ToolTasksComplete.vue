<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { computed } from 'vue';
import { useTextExpansion } from '@/composables/useTextExpansion';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();

const stats = computed(() => parseStats());

function getSuccessFlag(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function getResultRaw(): string | undefined {
    const m: any = props.message.message;
    return m?.result || m?.tool_result;
}

function parseResult(): any | undefined {
    const raw = getResultRaw();
    if (!raw) return undefined;
    try { return JSON.parse(raw as string); } catch { return undefined; }
}

function parseTitle(): string | undefined {
    const parsed = parseResult();
    if (parsed && parsed.task && typeof parsed.task.title === 'string') {
        return parsed.task.title as string;
    }
    // Fallback для старого формата
    if (parsed && typeof parsed.title === 'string') return parsed.title as string;
    return undefined;
}

function parseStats(): { total: number; completed: number; remaining: number } | undefined {
    const parsed = parseResult();
    if (parsed && parsed.stats) {
        return parsed.stats;
    }
    return undefined;
}

function getErrorMessage(): string | undefined {
    const parsed = parseResult();
    if (parsed && typeof parsed.error === 'string') return parsed.error as string;
    const raw = getResultRaw();
    if (typeof raw === 'string' && /^error[:]?/i.test(raw.trim())) return raw;
    return undefined;
}

function isError(): boolean {
    if (!getSuccessFlag()) return true;
    return Boolean(getErrorMessage());
}

function containerClass(): string {
    return isError() ? 'bg-red-50 border border-red-200' : 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'задача выполнена'" :isError="isError()" />

        <div class="text-sm space-y-3">
            <template v-if="!isError() && parseTitle()">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Выполненная задача:</div>
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
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение об ошибке:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getErrorMessage() || 'Не удалось обработать результат' }}</div>
                </div>
            </template>
        </div>
    </div>
</template>
