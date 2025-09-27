<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useMessageExpansion } from '@/composables/useMessageExpansion';
import { useTextExpansion } from '@/composables/useTextExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { expandedMessages, isLongMessage, getTruncatedContent, toggleMessageExpansion } = useMessageExpansion();
const { isLongText, getTruncatedText, toggleTextExpansion, isTextExpanded } = useTextExpansion();

function getServiceKey(): string {
    return props.message.message.key || 'service';
}

function getServiceMessage(): string | undefined {
    return (props.message.message as any)?.message || undefined;
}

function hasAnyContent(): boolean {
    return Boolean(getServiceMessage());
}

function getServiceStatus(): string | undefined {
    return (props.message.message as any)?.status || undefined;
}

// payload выводить не требуется
</script>

<template>
    <div class="rounded-lg p-3 bg-indigo-50 border border-indigo-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-500">
                    <span class="text-xs font-medium text-white">
                        S
                    </span>
                </div>
                <span class="text-xs font-medium text-indigo-700">
                    Сервис: {{ getServiceKey() }}
                </span>
                <!-- Иконки статуса -->
                <span v-if="getServiceStatus() === 'error'" class="inline-flex items-center" title="Ошибка">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5a1 1 0 112 0 1 1 0 01-2 0zm1-8a1 1 0 00-1 1v5a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span v-else-if="getServiceStatus() === 'success'" class="inline-flex items-center" title="Успех">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span v-else-if="getServiceStatus() === 'processing' || getServiceStatus() === 'wait'" class="inline-flex items-center" title="Выполняется">
                    <svg class="h-4 w-4 text-indigo-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm space-y-3">
            <!-- Сообщение -->
            <div v-if="getServiceMessage()" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                <div v-if="isLongText(getServiceMessage(), 200) && !isTextExpanded('service-message-' + props.index)" class="space-y-1">
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                        {{ getTruncatedText(getServiceMessage(), 200) }}
                    </div>
                    <button @click="toggleTextExpansion('service-message-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        Показать полностью
                    </button>
                </div>
                <div v-else class="space-y-1">
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                        {{ getServiceMessage() }}
                    </div>
                    <button
                        v-if="isLongText(getServiceMessage(), 200)"
                        @click="toggleTextExpansion('service-message-' + props.index)"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    >
                        Свернуть
                    </button>
                </div>
            </div>

            

            

            <!-- Пустое содержимое -->
            <div v-if="!hasAnyContent()" class="text-gray-500 italic">Сообщение сервиса без содержимого</div>
        </div>
    </div>
</template>


