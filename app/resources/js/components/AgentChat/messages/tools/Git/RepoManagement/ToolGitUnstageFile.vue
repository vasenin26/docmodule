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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'отмена индексации файла'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.file_path }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Статус:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">
                        <span :class="parseResult()?.status === 'unstaged' ? 'text-orange-600' : 'text-gray-600'">
                            {{ parseResult()?.status || 'Не указан' }}
                        </span>
                    </div>
                </div>

                <div v-if="parseResult()?.message" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение git:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">
                        {{ parseResult()?.message }}
                    </div>
                </div>

                <div v-if="parseResult()?.previous_status" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Предыдущий статус:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        {{ parseResult()?.previous_status }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
