<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { useTextExpansion } from '@/composables/useTextExpansion';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

const { isTextExpanded, toggleTextExpansion } = useTextExpansion();

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
        <ToolHeaderStatus :title="'вставка или замена в файле'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div v-if="!getSuccess()" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                    <div class="text-sm bg-gray-100 p-2 rounded border">{{ parseResult()?.message }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.file_path }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Режим:</div>
                        <div class="text-sm">{{ parseResult()?.mode || 'Не указан' }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Изменения внесены:</div>
                        <div class="text-sm">
                            <span :class="parseResult()?.changes_made ? 'text-green-600' : 'text-red-600'">
                                {{ parseResult()?.changes_made ? 'Да' : 'Нет' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Файл создан:</div>
                        <div class="text-sm">
                            <span :class="parseResult()?.file_created ? 'text-green-600' : 'text-gray-600'">
                                {{ parseResult()?.file_created ? 'Да' : 'Нет' }}
                            </span>
                        </div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Байт записано:</div>
                        <div class="text-sm">{{ parseResult()?.bytes_written || 0 }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Исходная длина:</div>
                        <div class="text-sm">{{ parseResult()?.original_length || 0 }} байт</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Новая длина:</div>
                        <div class="text-sm">{{ parseResult()?.new_length || 0 }} байт</div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
