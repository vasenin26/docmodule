<template>
    <AppLayout :title="pageTitle">
        <template #context-actions>
            <Button v-if="project" as-child variant="outline">
                <Link :href="route('projects.show', project.id)"> К проекту </Link>
            </Button>
        </template>

        <div class="space-y-2">
            <Heading :title="pageTitle" />
            <p v-if="project" class="mt-1 text-sm text-muted-foreground">Проект #{{ project.id }}</p>
        </div>

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
        data: Array<{
            id: number;
            type: string;
            creator?: { id: number; name: string } | null;
            chat_id?: number | null;
            context_id?: string | null;
            agent_model?: string | null;
            agent_assigned: boolean;
            reserved_at?: string | null;
            reserved_until?: string | null;
            reserved_seconds?: number | null;
            status: string;
            prompt_tokens?: number | null;
            completion_tokens?: number | null;
            total_tokens?: number | null;
            updated_at: string;
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


