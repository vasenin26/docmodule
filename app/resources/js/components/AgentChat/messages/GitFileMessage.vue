<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useMessageExpansion } from '@/composables/useMessageExpansion';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { expandedMessages, isLongMessage, getTruncatedContent, toggleMessageExpansion } = useMessageExpansion();

function getFileName(url: string | undefined): string {
    if (!url) return 'Неизвестный файл';
    return url.split('/').pop() || url;
}
</script>

<template>
    <div class="rounded-lg p-3 bg-purple-50 border border-purple-200">
        <!-- Заголовок сообщения -->
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-purple-500">
                    <span class="text-xs font-medium text-white">
                        F
                    </span>
                </div>
                <span class="text-xs font-medium text-purple-700">
                    Файл из Git
                </span>
            </div>
        </div>

        <!-- Содержимое сообщения -->
        <div class="text-sm space-y-3">
            <!-- URL файла -->
            <div v-if="message.message.url" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Файл из репозитория:</div>
                <div class="flex items-center space-x-2">
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded border flex-1 overflow-x-auto">
                        {{ message.message.url }}
                    </div>
                    <a 
                        :href="message.message.url" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition-colors"
                    >
                        Открыть
                    </a>
                </div>
            </div>

            <!-- Описание файла -->
            <div v-if="message.message.description" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Описание:</div>
                <div class="text-sm text-gray-700 bg-gray-50 p-2 rounded border">
                    {{ message.message.description }}
                </div>
            </div>
        </div>
    </div>
</template>
