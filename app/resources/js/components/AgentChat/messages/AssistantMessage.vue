<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useMessageExpansion } from '@/composables/useMessageExpansion';
import { useTextExpansion } from '@/composables/useTextExpansion';
import { useToolCallCache } from '@/composables/useToolCallCache';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { expandedMessages, isLongMessage, getTruncatedContent, toggleMessageExpansion } = useMessageExpansion();
const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();
const { registerFunctionName, getFunctionName } = useToolCallCache();

function getHeaderFunctionName(): string {
    const calls = (props.message.message.tool_calls || []) as Array<{ id: string; name?: string }>;
    if (calls && calls.length > 0) {
        return calls.map(c => registerFunctionName(c)).filter(Boolean).join(', ');
    }
    return getFunctionName(props.message.message.tool_call_id || '');
}

function getToolCallName(toolCall: { name?: string }): string {
    return toolCall.name || '';
}

function getToolCallArgs(toolCall: { arguments?: string }): string | undefined {
    return toolCall.arguments;
}
</script>

<template>
    <div class="rounded-lg p-3 bg-green-50 border border-green-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-green-500">
                    <span class="text-xs font-medium text-white">
                        A
                    </span>
                </div>
                <span class="text-xs font-medium text-green-700">
                    Ассистент {{ getHeaderFunctionName() }}
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm">
            <div v-if="!message.message.content" class="text-gray-500 italic">Сообщение без текстового содержимого</div>

            <!-- Tool calls -->
            <div v-if="message.message.tool_calls && message.message.tool_calls.length > 0" class="mt-2 mb-2">
                <div class="flex flex-wrap gap-1 mb-2">
                    <div v-for="toolCall in message.message.tool_calls" :key="toolCall.id" class="tag text-xs text-gray-500 italic">
                        {{ registerFunctionName(toolCall) }}
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div v-if="message.message.content">
                <div v-if="isLongMessage(message.message.content) && !expandedMessages.has(props.index)" class="space-y-2">
                    <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ getTruncatedContent(message.message.content) }}</div>
                    <button @click="toggleMessageExpansion(props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Показать полностью
                    </button>
                </div>
                <div v-else class="space-y-2">
                    <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ message.message.content }}</div>
                    <button
                        v-if="isLongMessage(message.message.content)"
                        @click="toggleMessageExpansion(props.index)"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        Свернуть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tag {
    padding: 4px 6px;
    border-radius: 4px;
    background-color: #f0f0f0;
    color: #333;
    display: inline-block;
    font-size: 12px;
    font-weight: 500;
}
</style>
