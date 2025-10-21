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
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'статус git репозитория'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Текущая ветка:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono">{{ parseResult()?.branch || 'Не указана' }}</div>
                </div>

                <div v-if="parseResult()?.files && parseResult()?.files.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Измененные файлы:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.files" :key="file.path" class="bg-gray-100 p-2 rounded border">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-sm">{{ file.path }}</div>
                                <div class="text-xs">
                                    <span :class="getStatusColor(file.status)" class="font-medium">
                                        {{ file.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.staged_files && parseResult()?.staged_files.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файлы в индексе:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.staged_files" :key="file" class="bg-green-100 p-2 rounded border font-mono text-sm">
                            {{ file }}
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.untracked_files && parseResult()?.untracked_files.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Неотслеживаемые файлы:</div>
                    <div class="space-y-1">
                        <div v-for="file in parseResult()?.untracked_files" :key="file" class="bg-gray-100 p-2 rounded border font-mono text-sm">
                            {{ file }}
                        </div>
                    </div>
                </div>

                <div v-if="parseResult()?.is_clean" class="space-y-1">
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
