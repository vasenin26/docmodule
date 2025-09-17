<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="pageTitle" />
                    <p v-if="project" class="mt-1 text-sm text-muted-foreground">Проект #{{ project.id }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="project" as-child variant="outline">
                        <Link :href="route('projects.show', project.id)"> К проекту </Link>
                    </Button>
                    <Button type="button" @click="createTask">Создать</Button>
                </div>
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
import Button from '../../components/ui/button/Button.vue';
import { Link, router } from '@inertiajs/vue3';

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

const createTask = () => {
    if (!props.project) return;
    router.post(route('projects.tasks.store', props.project.id));
};
</script>
