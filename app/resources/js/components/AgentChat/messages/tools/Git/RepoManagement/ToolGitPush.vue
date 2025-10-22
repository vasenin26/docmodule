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
        if (parsed && parsed.payload) {
            // Объединяем message из корня с payload для полной информации
            return {
                ...parsed.payload,
                message: parsed.message || parsed.payload.message
            };
        }
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
        <ToolHeaderStatus :title="'отправка изменений (git push)'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div v-if="parseResult()?.message" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.message }}</div>
                </div>

                <div v-if="parseResult()?.code" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Код ошибки:</div>
                    <div class="text-sm bg-red-100 p-2 rounded border text-red-800 font-mono">{{ parseResult()?.code }}</div>
                </div>

                <div v-if="parseResult()?.exception" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Исключение:</div>
                    <div class="text-sm bg-red-100 p-2 rounded border text-red-800 font-mono">{{ parseResult()?.exception }}</div>
                </div>

                <div v-if="parseResult()?.url" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">URL репозитория:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono break-all">
                        {{ parseResult()?.url }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
