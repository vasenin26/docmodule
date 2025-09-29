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

function parseArgs(): { url?: string } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.args || m?.tool_args;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

function parseResult(): { success?: boolean; message?: string; data?: any } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        return parsed as any;
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
        <ToolHeaderStatus :title="'анализ структуры репозитория'" :subtitle="parseArgs()?.url" :isError="!getSuccess()" />

        <div class="text-sm space-y-3">
            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">
                    {{ parseResult()?.message || (getSuccess() ? 'Анализ успешно выполнен' : 'Анализ завершился с ошибкой') }}
                </div>
            </div>

            <template v-if="parseResult()?.data">
                <div v-if="parseResult()?.data?.project_type" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Тип проекта:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto">{{ parseResult()?.data?.project_type }}</div>
                </div>

                <div v-if="Array.isArray(parseResult()?.data?.main_directories)" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Основные директории ({{ parseResult()?.data?.main_directories.length }}):</div>
                    <ul class="list-disc pl-5 space-y-1">
                        <li v-for="dir in parseResult()?.data?.main_directories" :key="dir" class="bg-white p-2 rounded border">{{ dir }}</li>
                    </ul>
                </div>

                <div v-if="Array.isArray(parseResult()?.data?.entry_points)" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Точки входа ({{ parseResult()?.data?.entry_points.length }}):</div>
                    <ul v-if="parseResult()?.data?.entry_points.length" class="list-disc pl-5 space-y-1">
                        <li v-for="ep in parseResult()?.data?.entry_points" :key="ep" class="bg-white p-2 rounded border">{{ ep }}</li>
                    </ul>
                    <div v-else class="text-gray-500 italic">не найдены</div>
                </div>

                <div v-if="Array.isArray(parseResult()?.data?.config_files)" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Конфигурационные файлы ({{ parseResult()?.data?.config_files.length }}):</div>
                    <ul v-if="parseResult()?.data?.config_files.length" class="list-disc pl-5 space-y-1">
                        <li v-for="cf in parseResult()?.data?.config_files" :key="cf" class="bg-white p-2 rounded border">{{ cf }}</li>
                    </ul>
                    <div v-else class="text-gray-500 italic">не найдены</div>
                </div>
            </template>
            <template v-else>
                <div class="text-gray-500 italic">Нет данных для отображения</div>
            </template>
        </div>
    </div>
</template>
