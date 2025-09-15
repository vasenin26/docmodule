<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <div class="flex items-center justify-between">
                <Heading :title="pageTitle" />
            </div>
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
import Heading from '@/components/Heading.vue';
import TaskList from '@/components/Task/TaskList.vue';
import { computed } from 'vue';

interface Props {
    tasks: {
        data: TaskListItem[];
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
</script>
