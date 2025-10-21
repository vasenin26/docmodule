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
        <ToolHeaderStatus :title="'иерархическое дерево страниц'" :isError="!getSuccess()" />

        <template v-if="parseResult()">
            <div class="text-sm space-y-3">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Корневая страница:</div>
                    <div class="text-sm bg-white p-2 rounded border">{{ parseResult()?.root_page || parseResult()?.root_title || 'Не указана' }}</div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-gray-600">Структура дерева:</div>
                        <button @click="toggleTextExpansion('hierarchy-tree-' + props.index)" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ isTextExpanded('hierarchy-tree-' + props.index) ? 'Скрыть' : 'Показать' }}
                        </button>
                    </div>
                    <div v-if="isTextExpanded('hierarchy-tree-' + props.index)" class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-auto whitespace-pre">
                        {{ parseResult()?.tree_structure || parseResult()?.hierarchy }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Всего страниц:</div>
                        <div class="text-sm">{{ parseResult()?.total_pages || 0 }}</div>
                    </div>
                    <div class="bg-white p-2 rounded border">
                        <div class="font-medium text-gray-600">Уровней вложенности:</div>
                        <div class="text-sm">{{ parseResult()?.max_depth || 0 }}</div>
                    </div>
                </div>

                <div v-if="parseResult()?.pages && parseResult()?.pages.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Список страниц:</div>
                    <div class="space-y-1">
                        <div v-for="(page, index) in parseResult()?.pages" :key="index" class="bg-gray-100 p-2 rounded border">
                            <div class="font-medium text-sm">{{ page.title || page.name }}</div>
                            <div v-if="page.level" class="text-xs text-gray-600 mt-1">Уровень: {{ page.level }}</div>
                            <div v-if="page.url" class="text-xs font-mono text-blue-600 mt-1">{{ page.url }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
