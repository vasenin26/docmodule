<template>
    <div v-if="shouldShow" class="flex flex-col gap-2 border-t p-4 text-xs">
        Осталось задачь: {{ taskInfo }}
    </div>
</template>

<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { computed } from 'vue';

interface Props {
    messages?: LLMMessage[];
}

const props = withDefaults(defineProps<Props>(), {
    messages: () => [],
});

// Единая выборка последних актуальных stats из сообщений
function extractLatestStats(): { total: number; completed: number; remaining: number } | null {
    if (!props.messages || props.messages.length === 0) return null;

    const allowedTools = new Set(['get-task-list', 'tasks-add', 'tasks-complete']);

    // Идем с конца к началу, чтобы взять самое свежее успешное сообщение нужного инструмента
    for (let i = props.messages.length - 1; i >= 0; i--) {
        const msg = props.messages[i];
        if (msg.type !== 'tool') continue;
        const toolName = (msg.message?.name || msg.message?.tool_name) as string | undefined;
        if (!toolName || !allowedTools.has(toolName)) continue;

        const isSuccess = (msg.message as any)?.success || (msg.message as any)?.tool_success;
        if (!isSuccess) continue;

        const resultRaw = (msg.message as any)?.result || (msg.message as any)?.tool_result;
        if (!resultRaw) continue;

        try {
            const parsed = JSON.parse(resultRaw);
            // Новый формат: stats из объекта результата
            if (parsed && parsed.stats && typeof parsed.stats.total === 'number') {
                const { total, completed, remaining } = parsed.stats;
                return { total, completed, remaining };
            }
            // Старый формат: массив задач
            if (Array.isArray(parsed)) {
                const totalTasks = parsed.length;
                const completedTasks = parsed.filter((t: any) => t && t.done === true).length;
                const remainingCount = totalTasks - completedTasks;
                return { total: totalTasks, completed: completedTasks, remaining: remainingCount };
            }
        } catch {
            // ignore parse errors, continue
        }
    }
    return null;
}

// Текстовая сводка по задачам на основании последних stats
const taskInfo = computed(() => {
    const stats = extractLatestStats();
    if (!stats) return '0';
    return `${stats.remaining} из ${stats.total}`;
});

// Показывать ли блок: только если есть stats и есть невыполненные задачи
const shouldShow = computed(() => {
    const stats = extractLatestStats();
    if (!stats) return false;
    return stats.remaining > 0;
});
</script>
