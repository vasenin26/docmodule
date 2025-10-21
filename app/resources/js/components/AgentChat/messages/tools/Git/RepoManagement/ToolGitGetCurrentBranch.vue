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
        <ToolHeaderStatus :title="'текущая ветка git'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div v-if="parseResult()?.message" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.message }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Текущая ветка:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono">{{ parseResult()?.branch || parseResult()?.branch_name || parseResult()?.current_branch }}</div>
                </div>

                <div v-if="parseResult()?.is_detached" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Статус:</div>
                    <div class="text-sm bg-yellow-100 p-2 rounded border text-yellow-800">
                        Отсоединенная HEAD (detached HEAD)
                    </div>
                </div>

                <div v-if="parseResult()?.commit_hash" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Последний коммит:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border font-mono">{{ parseResult()?.commit_hash }}</div>
                </div>

                <div v-if="parseResult()?.remote_branch" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Удаленная ветка:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono">{{ parseResult()?.remote_branch }}</div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
