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

// Анализ задач из последнего результата get-task-list
const taskInfo = computed(() => {
    if (!props.messages || props.messages.length === 0) {
        return '0';
    }

    // Ищем последнее сообщение с результатом get-task-list
    const taskListMessages = props.messages.filter(msg => {
        if (msg.type !== 'tool') return false;
        const toolName = msg.message?.name || msg.message?.tool_name;
        return toolName === 'get-task-list';
    });

    if (taskListMessages.length === 0) {
        return '0';
    }

    // Берем последнее сообщение с результатом get-task-list
    const lastTaskListMessage = taskListMessages[taskListMessages.length - 1];
    const messageData = lastTaskListMessage.message;

    // Проверяем успешность выполнения
    const isSuccess = messageData?.success || messageData?.tool_success;
    if (!isSuccess) {
        return '0';
    }

    // Парсим результат
    const resultRaw = messageData?.result || messageData?.tool_result;
    if (!resultRaw) {
        return '0';
    }

    try {
        const tasks = JSON.parse(resultRaw);
        if (!Array.isArray(tasks)) {
            return '0';
        }

        const totalTasks = tasks.length;
        const completedTasks = tasks.filter((task: any) => task.done === true).length;
        const remainingCount = totalTasks - completedTasks;

        return `${remainingCount} из ${totalTasks}`;
    } catch (error) {
        return '0';
    }
});

// Показывать ли блок с информацией о задачах
const shouldShow = computed(() => {
    if (!props.messages || props.messages.length === 0) {
        return false;
    }

    // Ищем последнее сообщение с результатом get-task-list
    const taskListMessages = props.messages.filter(msg => {
        if (msg.type !== 'tool') return false;
        const toolName = msg.message?.name || msg.message?.tool_name;
        return toolName === 'get-task-list';
    });

    if (taskListMessages.length === 0) {
        return false;
    }

    // Берем последнее сообщение с результатом get-task-list
    const lastTaskListMessage = taskListMessages[taskListMessages.length - 1];
    const messageData = lastTaskListMessage.message;

    // Проверяем успешность выполнения
    const isSuccess = messageData?.success || messageData?.tool_success;
    if (!isSuccess) {
        return false;
    }

    // Парсим результат
    const resultRaw = messageData?.result || messageData?.tool_result;
    if (!resultRaw) {
        return false;
    }

    try {
        const tasks = JSON.parse(resultRaw);
        if (!Array.isArray(tasks)) {
            return false;
        }

        const totalTasks = tasks.length;
        const completedTasks = tasks.filter((task: any) => task.done === true).length;
        const remainingCount = totalTasks - completedTasks;

        // Показываем блок только если есть невыполненные задачи
        return remainingCount > 0;
    } catch (error) {
        return false;
    }
});
</script>
