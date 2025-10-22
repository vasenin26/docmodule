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
        <ToolHeaderStatus :title="'Замена в файле'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Файл:</div>
                    <div class="text-sm bg-white p-2 rounded border font-mono overflow-x-auto">{{ parseResult()?.file_path }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Искомый текст:</div>
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto whitespace-pre">
                        {{ parseResult()?.search_text || parseResult()?.pattern }}
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Заменяющий текст:</div>
                        <button @click="toggleTextExpansion('replace-in-file-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('replace-in-file-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('replace-in-file-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.replacement_text || parseResult()?.replacement }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Найдено вхождений:</div>
                        <div class="text-sm">{{ parseResult()?.matches_found || 0 }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Заменено:</div>
                        <div class="text-sm">{{ parseResult()?.replacements_made || 0 }}</div>
                    </div>
                </div>

                <div v-if="parseResult()?.case_sensitive !== undefined" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Учитывать регистр:</div>
                    <div class="text-sm bg-white p-2 rounded border">
                        {{ parseResult()?.case_sensitive ? 'Да' : 'Нет' }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
