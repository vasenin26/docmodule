<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref } from 'vue';
import { useMessageExpansion } from '@/composables/useMessageExpansion';
import { useTextExpansion } from '@/composables/useTextExpansion';
import { useToolCallCache } from '@/composables/useToolCallCache';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { expandedMessages, isLongMessage, getTruncatedContent, toggleMessageExpansion } = useMessageExpansion();
const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();
const { registerFunctionName } = useToolCallCache();

const expandedOtherFields = ref(false);

// Получить прочие поля (не основные)
function getOtherFields(): Record<string, any> {
    const { content, timestamp, tool_call_id, tool_calls, tool_name, tool_args, tool_result, tool_success, url, versionId } = props.message.message;
    const otherFields: Record<string, any> = {};
    
    // Добавляем только те поля, которые не являются основными
    if (props.message.message.tool_name !== undefined) otherFields.tool_name = tool_name;
    if (props.message.message.tool_args !== undefined) otherFields.tool_args = tool_args;
    if (props.message.message.tool_result !== undefined) otherFields.tool_result = tool_result;
    if (props.message.message.tool_success !== undefined) otherFields.tool_success = tool_success;
    if (props.message.message.url !== undefined) otherFields.url = url;
    if (props.message.message.versionId !== undefined) otherFields.versionId = versionId;
    
    return otherFields;
}

const otherFields = getOtherFields();
const hasOtherFields = Object.keys(otherFields).length > 0;
</script>

<template>
    <div class="rounded-lg p-3 bg-gray-50 border border-gray-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-400">
                    <span class="text-xs font-medium text-white">
                        ?
                    </span>
                </div>
                <span class="text-xs font-medium text-gray-600">
                    Неизвестный тип: {{ message.type }}
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm space-y-3">
            <!-- Текстовый контент -->
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

            <!-- Tool calls (если есть) -->
            <div v-if="message.message.tool_calls && message.message.tool_calls.length > 0" class="space-y-2">
                <div class="text-xs font-medium text-gray-600">Вызовы инструментов:</div>
                <div class="flex flex-wrap gap-1 mb-2">
                    <div v-for="toolCall in message.message.tool_calls" :key="toolCall.id" class="tag text-xs text-gray-500 italic">
                        {{ registerFunctionName(toolCall) }}
                    </div>
                </div>
                
                <!-- Аргументы каждого вызова -->
                <div v-for="toolCall in message.message.tool_calls" :key="'args-' + toolCall.id" class="mb-2">
                    <div class="text-xs text-gray-600 font-medium mb-1">
                        Аргументы функции {{ toolCall.function.name }}:
                    </div>
                    <div v-if="isLongText(toolCall.function.arguments, 100) && !isTextExpanded(toolCall.id)" class="space-y-1">
                        <div class="text-xs font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                            {{ getTruncatedText(toolCall.function.arguments, 100) }}
                        </div>
                        <button @click="toggleTextExpansion(toolCall.id)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            Показать полностью
                        </button>
                    </div>
                    <div v-else class="space-y-1">
                        <div class="text-xs font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                            {{ toolCall.function.arguments }}
                        </div>
                        <button
                            v-if="isLongText(toolCall.function.arguments, 100)"
                            @click="toggleTextExpansion(toolCall.id)"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800"
                        >
                            Свернуть
                        </button>
                    </div>
                </div>
            </div>

            <!-- Прочие поля -->
            <div v-if="hasOtherFields" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Прочие поля:</div>
                <div v-if="!expandedOtherFields" class="space-y-1">
                    <div class="text-xs font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                        {{ JSON.stringify(otherFields, null, 2).substring(0, 100) }}...
                    </div>
                    <button @click="expandedOtherFields = true" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Показать полностью
                    </button>
                </div>
                <div v-else class="space-y-1">
                    <div class="text-xs font-mono bg-gray-100 p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                        {{ JSON.stringify(otherFields, null, 2) }}
                    </div>
                    <button @click="expandedOtherFields = false" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Свернуть
                    </button>
                </div>
            </div>

            <!-- Сообщение без содержимого -->
            <div v-if="!message.message.content && !message.message.tool_calls && !hasOtherFields" class="text-gray-500 italic">
                Сообщение без содержимого
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
