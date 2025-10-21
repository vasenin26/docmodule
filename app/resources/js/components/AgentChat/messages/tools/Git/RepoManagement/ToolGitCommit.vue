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
        <ToolHeaderStatus :title="'коммит изменений'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение коммита:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.message || parseResult()?.commit_message }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Хеш коммита:</div>
                        <div class="text-sm font-mono">{{ parseResult()?.commit_hash || 'Не указан' }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Автор:</div>
                        <div class="text-sm">{{ parseResult()?.author || 'Не указан' }}</div>
                    </div>
                </div>

                <div v-if="parseResult()?.files_changed" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Измененные файлы:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">
                        <div v-for="file in parseResult()?.files_changed" :key="file" class="font-mono text-xs">
                            {{ file }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Добавлено строк:</div>
                        <div class="text-sm">{{ parseResult()?.insertions || 0 }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Удалено строк:</div>
                        <div class="text-sm">{{ parseResult()?.deletions || 0 }}</div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
