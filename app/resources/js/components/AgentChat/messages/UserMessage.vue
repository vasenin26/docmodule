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
    <div class="rounded-lg p-3 bg-blue-50 border border-blue-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500">
                    <span class="text-xs font-medium text-white">
                        U
                    </span>
                </div>
                <span class="text-xs font-medium text-blue-700">
                    Пользователь
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm">
            <div v-if="!message.message.content" class="text-gray-500 italic">Сообщение без текстового содержимого</div>
            <div v-else-if="isLongMessage(message.message.content) && !expandedMessages.has(props.index)" class="space-y-2">
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
</template>
