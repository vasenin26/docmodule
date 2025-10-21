<template>
    <div v-if="shouldShow" class="flex flex-col gap-2 border-t p-4 text-xs">
        <div class="flex items-center justify-between">
            <div class="font-medium text-gray-700">
                Задачи: {{ taskInfo }}
            </div>
            <button 
                @click="toggleListVisibility"
                class="flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors"
                :title="isListVisible ? 'Скрыть список' : 'Показать список'"
            >
                <span class="text-xs">{{ isListVisible ? 'Скрыть' : 'Показать' }}</span>
                <svg 
                    :class="[
                        'w-3 h-3 transition-transform duration-200',
                        !isListVisible ? 'rotate-180' : ''
                    ]"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
        
        <div v-if="isListVisible" class="space-y-1 max-h-32 overflow-y-auto">
            <div 
                v-for="task in tasks" 
                :key="task.id" 
                class="flex items-center gap-2 text-xs"
            >
                <div class="flex-shrink-0 w-4 h-4 flex items-center justify-center">
                    <div 
                        :class="[
                            'w-3 h-3 rounded-full border-2',
                            task.done 
                                ? 'bg-green-500 border-green-500' 
                                : 'border-gray-300 bg-white'
                        ]"
                    >
                        <svg 
                            v-if="task.done" 
                            class="w-2 h-2 text-white" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div 
                    :class="[
                        'flex-1 truncate',
                        task.done ? 'text-gray-500 line-through' : 'text-gray-700'
                    ]"
                    :title="task.title"
                >
                    {{ task.title }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    tasks?: Array<{id: number; title: string; done: boolean}>;
}

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
});

// Состояние видимости списка задач
const isListVisible = ref(false);

// Получение статистики задач
function extractStatsFromTasks(): { total: number; completed: number; remaining: number } | null {
    if (!props.tasks || !Array.isArray(props.tasks)) {
        return null;
    }

    const tasks = props.tasks;
    const totalTasks = tasks.length;
    const completedTasks = tasks.filter((task) => task && task.done === true).length;
    const remainingCount = totalTasks - completedTasks;

    return { total: totalTasks, completed: completedTasks, remaining: remainingCount };
}

// Текстовая сводка по задачам
const taskInfo = computed(() => {
    const stats = extractStatsFromTasks();
    if (!stats) return '0';
    return `${stats.remaining} из ${stats.total}`;
});

// Функция переключения видимости списка
function toggleListVisibility() {
    isListVisible.value = !isListVisible.value;
}

// Показывать ли блок: только если есть задачи
const shouldShow = computed(() => {
    const stats = extractStatsFromTasks();
    if (!stats) return false;
    return stats.total > 0;
});
</script>
