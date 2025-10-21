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
        <ToolHeaderStatus :title="'отправка результата'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Получатель:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        {{ parseResult()?.recipient || parseResult()?.target || 'Не указан' }}
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Тип результата:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        {{ parseResult()?.type || parseResult()?.result_type || 'Не указан' }}
                    </div>
                </div>
                
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Содержимое:</div>
                        <button @click="toggleTextExpansion('send-result-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('send-result-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('send-result-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.content || parseResult()?.data || parseResult()?.message }}
                    </div>
                </div>

                <div v-if="parseResult()?.status" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Статус отправки:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        <span :class="parseResult()?.status === 'success' ? 'text-green-600' : 'text-red-600'">
                            {{ parseResult()?.status }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
