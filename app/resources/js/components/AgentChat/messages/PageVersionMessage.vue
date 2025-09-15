<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useMessageExpansion } from '@/composables/useMessageExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { expandedMessages, isLongMessage, getTruncatedContent, toggleMessageExpansion } = useMessageExpansion();
</script>

<template>
    <div class="rounded-lg p-3 bg-indigo-50 border border-indigo-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-500">
                    <span class="text-xs font-medium text-white">
                        V
                    </span>
                </div>
                <span class="text-xs font-medium text-indigo-700">
                    Версия страницы
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm space-y-3">
            <!-- ID версии -->
            <div v-if="message.message.versionId" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">ID версии страницы:</div>
                <div class="text-sm font-mono bg-gray-100 p-2 rounded border">
                    {{ message.message.versionId }}
                </div>
            </div>

            <!-- Информация о прикреплении -->
            <div class="flex items-center space-x-2 p-2 bg-indigo-100 rounded">
                <div class="text-indigo-600">
                    📄
                </div>
                <div class="text-sm text-indigo-700">
                    Версия страницы прикреплена к чату для анализа
                </div>
            </div>

            <!-- Основной контент (если есть) -->
            <div v-if="message.message.content">
                <div class="text-xs font-medium text-gray-600 mb-1">Дополнительная информация:</div>
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

            <!-- Сообщение без содержимого -->
            <div v-if="!message.message.content && !message.message.versionId" class="text-gray-500 italic">
                Сообщение без содержимого
            </div>
        </div>
    </div>
</template>
