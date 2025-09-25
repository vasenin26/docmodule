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
    return props.message.message.payload?.message || props.message.message.content || undefined;
}

function getServiceError(): string | undefined {
    return props.message.message.payload?.error || undefined;
}

function hasAnyContent(): boolean {
    return Boolean(getServiceMessage() || getServiceError());
}
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

            <!-- Ошибка -->
            <div v-if="getServiceError()" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Ошибка:</div>
                <div class="text-sm bg-red-50 text-red-800 p-2 rounded border border-red-200 overflow-x-auto whitespace-pre-wrap">
                    {{ getServiceError() }}
                </div>
            </div>

            <!-- Пустое содержимое -->
            <div v-if="!hasAnyContent()" class="text-gray-500 italic">
                Сообщение сервиса без содержимого
            </div>
        </div>
    </div>
</template>


