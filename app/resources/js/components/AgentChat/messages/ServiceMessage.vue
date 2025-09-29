<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useMessageExpansion } from '@/composables/useMessageExpansion';
import { useTextExpansion } from '@/composables/useTextExpansion';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

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

function getServicePayload(): any {
    return (props.message.message as any)?.payload || undefined;
}

function hasPayload(): boolean {
    const payload = getServicePayload();
    return Boolean(payload && Object.keys(payload).length > 0);
}

function mapStatus(): 'success' | 'error' | 'processing' | 'wait' {
    const s = getServiceStatus();
    if (s === 'error') return 'error';
    if (s === 'processing' || s === 'wait') return 'processing';
    return 'success';
}

// payload выводить не требуется
</script>

<template>
    <div class="rounded-lg p-3 bg-indigo-50 border border-indigo-200">
        <!-- Заголовок сообщения -->
        <ToolHeaderStatus :title="'Сервис: ' + getServiceKey()" :status="mapStatus()" theme="indigo" badgeLetter="S" />

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

            <!-- Payload с счётчиками -->
            <div v-if="hasPayload()" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Использованные инструменты:</div>
                <div class="bg-white p-2 rounded border flex flex-wrap gap-1.5">
                    <span v-for="(count, key) in getServicePayload()" :key="key" class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full font-medium">
                        {{ key }} ({{ count }})
                    </span>
                </div>
            </div>

            <!-- Пустое содержимое -->
            <div v-if="!hasAnyContent() && !hasPayload()" class="text-gray-500 italic">Сообщение сервиса без содержимого</div>
        </div>
    </div>
</template>


