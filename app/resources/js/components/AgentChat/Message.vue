<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref } from 'vue';
import { getStupidStore } from '@/lib/utils';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();
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

const functions: {[key: string]: string} = getStupidStore('functions');

function registrFunctionName(toolCall: {
    id: string,
    function: {
        name: string,
    }
}): string {
    functions[toolCall.id] = toolCall.function.name;

    return toolCall.function.name;
}

function getFunctionName(id: string): string {
    console.log(id, functions);
    return functions[id] || '';
}

</script>

<template>
    <div class="rounded-lg p-3" :class="getMessageClass(message.role)">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div :class="getRoleIconClass(message.role)" class="flex h-5 w-5 items-center justify-center rounded-full">
                    <span class="text-xs font-medium text-white">
                        {{ getRoleIcon(message.role) }} 
                    </span>
                </div>
                <span class="text-xs font-medium" :class="getRoleLabelClass(message.role)">
                    {{ getRoleLabel(message.role) }} {{ getFunctionName(message.tool_call_id) }}
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm">
            <div v-if="!message.content" class="text-gray-500 italic">Сообщение без текстового содержимого</div>
            <div v-if="message.tool_calls && message.tool_calls.length > 0" class="mt-2 flex flex-wrap gap-1">
                <div v-for="toolCall in message.tool_calls" :key="toolCall.id">
                    <div class="tag text-xs text-gray-500 italic">
                        {{ registrFunctionName(toolCall) }}
                    </div>
                </div>
            </div>
            <div v-else-if="isLongMessage(message.content) && !expandedMessages.has(props.index)" class="space-y-2">
                <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ getTruncatedContent(message.content) }}</div>
                <button @click="toggleMessageExpansion(props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                    Показать полностью
                </button>
            </div>
            <div v-else class="space-y-2">
                <div class="overflow-x-hidden break-words whitespace-pre-wrap">{{ message.content }}</div>
                <button
                    v-if="isLongMessage(message.content)"
                    @click="toggleMessageExpansion(props.index)"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800"
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
