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

function getStatusColor(status: string): string {
    switch (status) {
        case 'modified': return 'text-yellow-600';
        case 'added': return 'text-green-600';
        case 'deleted': return 'text-red-600';
        case 'renamed': return 'text-blue-600';
        case 'untracked': return 'text-gray-600';
        default: return 'text-gray-600';
    }
}

function hasAnyChanges(): boolean {
    const result = parseResult();
    if (!result) return false;
    
    return (result.modified && result.modified.length > 0) ||
           (result.staged && result.staged.length > 0) ||
           (result.untracked && result.untracked.length > 0) ||
           (result.deleted && result.deleted.length > 0);
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'статус git репозитория'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div v-if="parseResult()?.modified && parseResult()?.modified.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Измененные файлы:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.modified" :key="file" class="bg-yellow-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-sm">{{ file }}</div>
                                <div class="text-xs">
                                    <span class="text-yellow-600 font-medium">modified</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.staged && parseResult()?.staged.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файлы в индексе:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.staged" :key="file" class="bg-green-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-sm">{{ file }}</div>
                                <div class="text-xs">
                                    <span class="text-green-600 font-medium">staged</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.untracked && parseResult()?.untracked.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Неотслеживаемые файлы:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.untracked" :key="file" class="bg-gray-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-sm">{{ file }}</div>
                                <div class="text-xs">
                                    <span class="text-gray-600 font-medium">untracked</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.deleted && parseResult()?.deleted.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Удаленные файлы:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.deleted" :key="file" class="bg-red-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-sm">{{ file }}</div>
                                <div class="text-xs">
                                    <span class="text-red-600 font-medium">deleted</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!hasAnyChanges()" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Статус:</div>
                    <div class="text-sm bg-green-100 p-2 rounded border text-green-800">
                        Рабочая директория чистая
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
