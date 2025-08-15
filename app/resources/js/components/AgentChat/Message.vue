<script setup lang="ts">
import { ref } from 'vue';
import type { LLMMessage } from '@/types';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>()
const expandedMessages = ref(new Set<number>());

// Константы
const MAX_MESSAGE_LENGTH = 200;

// Функции для работы с ролями
function getRoleLabel(role: string): string {
    switch (role) {
        case 'user':
            return 'Пользователь';
        case 'assistant':
            return 'Ассистент';
        case 'system':
            return 'Система';
        case 'tool':
            return 'Инструмент';
        default:
            return 'Неизвестно';
    }
}

function getRoleIcon(role: string): string {
    switch (role) {
        case 'user':
            return 'U';
        case 'assistant':
            return 'A';
        case 'system':
            return 'S';
        default:
            return '?';
    }
}

function getRoleIconClass(role: string): string {
    switch (role) {
        case 'user':
            return 'bg-blue-500';
        case 'assistant':
            return 'bg-green-500';
        case 'system':
            return 'bg-gray-500';
        default:
            return 'bg-gray-400';
    }
}

function getRoleLabelClass(role: string): string {
    switch (role) {
        case 'user':
            return 'text-blue-700';
        case 'assistant':
            return 'text-green-700';
        case 'system':
            return 'text-gray-700';
        default:
            return 'text-gray-600';
    }
}

function getMessageClass(role: string): string {
    switch (role) {
        case 'user':
            return 'bg-blue-50 border border-blue-200';
        case 'assistant':
            return 'bg-green-50 border border-green-200';
        case 'system':
            return 'bg-gray-50 border border-gray-200';
        default:
            return 'bg-gray-50 border border-gray-200';
    }
}

// Функции для работы с контентом
function isLongMessage(content: string | null): boolean {
    return content !== null && content.length > MAX_MESSAGE_LENGTH;
}

function getTruncatedContent(content: string | null): string {
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
<div
    class="p-3 rounded-lg"
    :class="getMessageClass(message.role)"
    >
    <!-- Заголовок сообщения -->
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
            <div :class="getRoleIconClass(message.role)" class="w-5 h-5 rounded-full flex items-center justify-center">
                <span class="text-xs font-medium text-white">
                  {{ getRoleIcon(message.role) }}
                </span>
            </div>
            <span class="text-xs font-medium" :class="getRoleLabelClass(message.role)">
                {{ getRoleLabel(message.role) }}
              </span>
        </div>
    </div>

    <!-- Содержимое сообщения -->
    <div class="text-sm">
        <div v-if="!message.content" class="text-gray-500 italic">
            Сообщение без текстового содержимого
        </div>
        <div v-if="message.tool_calls && message.tool_calls.length > 0" class="mt-2 flex gap-1">
            <div v-for="toolCall in message.tool_calls" :key="toolCall.id">
                <div class="text-gray-500 italic text-xs tag">
                    {{ toolCall.function.name }}
                </div>
            </div>
        </div>
        <div
            v-else-if="isLongMessage(message.content) && !expandedMessages.has(props.index)"
            class="space-y-2"
        >
            <div class="whitespace-pre-wrap break-words overflow-x-hidden">{{ getTruncatedContent(message.content) }}</div>
            <button
                @click="toggleMessageExpansion(props.index)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
            >
                Показать полностью
            </button>
        </div>
        <div v-else class="space-y-2">
            <div class="whitespace-pre-wrap break-words overflow-x-hidden">{{ message.content }}</div>
            <button
                v-if="isLongMessage(message.content)"
                @click="toggleMessageExpansion(props.index)"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
            >
                Свернуть
            </button>
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
