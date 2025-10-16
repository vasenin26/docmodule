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
                </div>
            </div>
        </template>

        <AgentTaskList
            :tasks="tasks"
            :project="project"
            :filters="filters"
        />
    </AppLayout>
    
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import AgentTaskList from '@/components/Task/AgentTaskList.vue';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    tasks: {
        data: Array<{ id: number }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    project?: {
        id: number;
        title: string;
    } | null;
    filters: {
        search?: string | null;
        status?: string | null;
    };
}

const props = defineProps<Props>();

const pageTitle = computed(() =>
    props.project ? `Задачи агентов: ${props.project.title}` : 'Задачи агентов'
);
</script>


