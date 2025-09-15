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
const { getFunctionName } = useToolCallCache();

// Функции для работы с данными инструмента (поддержка двух форматов)
function getToolName(): string {
    return props.message.message.name || props.message.message.tool_name || getFunctionName(props.message.message.tool_call_id || '') || 'Неизвестный инструмент';
}

function getToolArgs(): string | undefined {
    return props.message.message.args || props.message.message.tool_args;
}

function getToolResult(): string | undefined {
    return props.message.message.result || props.message.message.tool_result;
}

function getToolSuccess(): boolean | undefined {
    return props.message.message.success !== undefined ? props.message.message.success : props.message.message.tool_success;
}

function getToolCallId(): string | undefined {
    return props.message.message.id || props.message.message.tool_call_id;
}

// Функции для стилей
function getToolIconClass(success: boolean | undefined): string {
    return success === false ? 'bg-red-500' : 'bg-orange-500';
}

function getToolLabelClass(success: boolean | undefined): string {
    return success === false ? 'text-red-700' : 'text-orange-700';
}

function getToolMessageClass(success: boolean | undefined): string {
    return success === false 
        ? 'bg-red-50 border border-red-200' 
        : 'bg-orange-50 border border-orange-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="getToolMessageClass(getToolSuccess())">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div :class="getToolIconClass(getToolSuccess())" class="flex h-5 w-5 items-center justify-center rounded-full">
                    <span class="text-xs font-medium text-white">
                        T
                    </span>
                </div>
                <span class="text-xs font-medium" :class="getToolLabelClass(getToolSuccess())">
                    Инструмент {{ getToolName() }} 
                </span>
                <span 
                    class="px-2 py-1 rounded text-xs font-medium"
                    :class="getToolSuccess() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                >
                    {{ getToolSuccess() ? 'Успешно' : 'Ошибка' }}
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm space-y-3">

            <!-- Аргументы инструмента -->
            <div v-if="getToolArgs()" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Аргументы:</div>
                <div v-if="isLongText(getToolArgs(), 100) && !isTextExpanded('tool-args')" class="space-y-1">
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                        {{ getTruncatedText(getToolArgs(), 100) }}
                    </div>
                    <button @click="toggleTextExpansion('tool-args')" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Показать полностью
                    </button>
                </div>
                <div v-else class="space-y-1">
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                        {{ getToolArgs() }}
                    </div>
                    <button
                        v-if="isLongText(getToolArgs(), 100)"
                        @click="toggleTextExpansion('tool-args')"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        Свернуть
                    </button>
                </div>
            </div>

            <!-- Результат выполнения -->
            <div v-if="getToolResult()" class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Результат выполнения:</div>
                    <button 
                        @click="toggleTextExpansion('tool-result')" 
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        {{ isTextExpanded('tool-result') ? 'Скрыть' : 'Показать' }}
                    </button>
                </div>
                <div v-if="isTextExpanded('tool-result')" class="space-y-1">
                    <div v-if="isLongText(getToolResult(), 300) && !isTextExpanded('tool-result-full')" class="space-y-1">
                        <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                            {{ getTruncatedText(getToolResult(), 300) }}
                        </div>
                        <button @click="toggleTextExpansion('tool-result-full')" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            Показать полностью
                        </button>
                    </div>
                    <div v-else class="space-y-1">
                        <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                            {{ getToolResult() }}
                        </div>
                        <button
                            v-if="isLongText(getToolResult(), 300)"
                            @click="toggleTextExpansion('tool-result-full')"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800"
                        >
                            Свернуть
                        </button>
                    </div>
                </div>
            </div>

            <!-- Основной контент (если есть) -->
            <div v-if="props.message.message.content">
                <div class="text-xs font-medium text-gray-600 mb-1">Дополнительная информация:</div>
                <div v-if="isLongMessage(props.message.message.content) && !expandedMessages.has(props.index)" class="space-y-2">
                    <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ getTruncatedContent(props.message.message.content) }}</div>
                    <button @click="toggleMessageExpansion(props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Показать полностью
                    </button>
                </div>
                <div v-else class="space-y-2">
                    <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ props.message.message.content }}</div>
                    <button
                        v-if="isLongMessage(props.message.message.content)"
                        @click="toggleMessageExpansion(props.index)"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        Свернуть
                    </button>
                </div>
            </div>

            <!-- Сообщение без содержимого -->
            <div v-if="!props.message.message.content && !getToolName() && !getToolArgs() && !getToolResult()" class="text-gray-500 italic">
                Сообщение без содержимого
            </div>
        </div>
    </div>
</template>
