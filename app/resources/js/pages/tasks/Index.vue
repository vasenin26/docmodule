<template>
    <AppLayout :title="pageTitle">
        <template #context-actions>
            <Button v-if="project" as-child variant="outline">
                <Link :href="route('projects.show', project.id)"> К проекту</Link>
            </Button>
            <Button v-if="project" as-child>
                <Link :href="route('projects.tasks.create', project.id)">Создать</Link>
            </Button>
        </template>

        <TaskList
            :tasks="tasks"
            :project="project"
            :filters="filters"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TaskList from '@/components/Task/TaskList.vue';
import { computed } from 'vue';
import Button from '../../components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    // Локальное описание элемента списка задач для типизации
    // Минимально необходимое для этой страницы
    // Полное описание находится внутри компонента TaskList
    // и не экспортируется, поэтому дублируем здесь кратко
    // чтобы избежать ошибки типизации
     
    tasks: {
        data: Array<{
            id: number;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    project?: {
        id: number;
        title: string;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const pageTitle = computed(() =>
    props.project ? `Задачи проекта: ${props.project.title}` : 'Все задачи'
);

// Переход на форму создания через Link выше
</script>
