<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref } from 'vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const expandedMessages = ref(new Set<number>());

// Константы
const MAX_MESSAGE_LENGTH = 200;

// Функции для работы с контентом
function isLongMessage(content: string | null | undefined): boolean {
    return content !== undefined && content !== null && content.length > MAX_MESSAGE_LENGTH;
}

function getTruncatedContent(content: string | null | undefined): string {
    if(content === undefined) return '';
    if (!content) return '';
    return content.substring(0, MAX_MESSAGE_LENGTH) + '...';
}

function toggleMessageExpansion(index: number): void {
    if (expandedMessages.value.has(index)) {
        expandedMessages.value.delete(index);
    } else {
        expandedMessages.value.add(index);
    }
}
</script>

<template>
    <div class="rounded-lg p-3 bg-gray-50 border border-gray-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-500">
                    <span class="text-xs font-medium text-white">
                        S
                    </span>
                </div>
                <span class="text-xs font-medium text-gray-700">
                    Система
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
