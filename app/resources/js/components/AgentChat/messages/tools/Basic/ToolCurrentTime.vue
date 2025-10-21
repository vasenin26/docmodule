<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseResult(): any | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        if (parsed && parsed.payload) return parsed.payload;
        return parsed;
    } catch {
        return undefined;
    }
}

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}

function formatDateTime(dateTime: string): string {
    try {
        const date = new Date(dateTime);
        return date.toLocaleString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZoneName: 'short'
        });
    } catch {
        return dateTime;
    }
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'текущее время'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <div class="text-xs font-medium text-gray-600">Время сервера:</div>
                        <div class="text-sm bg-white p-2 rounded border font-mono">
                            {{ formatDateTime(parseResult()?.server_time || parseResult()?.current_time) }}
                        </div>
                    </div>
                    
                    <div v-if="parseResult()?.timezone" class="space-y-1">
                        <div class="text-xs font-medium text-gray-600">Часовой пояс:</div>
                        <div class="text-sm bg-white p-2 rounded border">
                            {{ parseResult()?.timezone }}
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.timestamp" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Unix timestamp:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border font-mono">
                        {{ parseResult()?.timestamp }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
