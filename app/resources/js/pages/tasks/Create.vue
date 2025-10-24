<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading title="Создание задачи" />
                    <p class="mt-1 text-sm text-muted-foreground">Проект #{{ project.id }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('projects.show', project.id)">К проекту</Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="space-y-6 lg:col-span-1">
                <form @submit.prevent="submit" class="space-y-4">
                    <TaskEditor
                        v-model:content="form.description"
                        v-model:title="form.title"
                        :errors="errors || {}"
                        :submitting="processing"
                        :cancel-href="route('projects.tasks.index', project.id)"
                        submit-text="Создать задачу"
                        submitting-text="Создание..."
                        card-title="Создание задачи"
                        description="Введите описание задачи."
                        :show-title="true"
                    />
                </form>
            </div>
            <div class="space-y-6 lg:col-span-1"></div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TaskEditor from '@/components/Task/TaskEditor.vue';

interface Project { id: number; title: string }

const props = defineProps<{
    project: Project;
    errors?: Record<string, string>;
}>();

const form = useForm({
    title: '' as string,
    description: '' as string,
});

const processing = ref(false);

const submit = () => {
    processing.value = true;
    form.post(route('projects.tasks.store', props.project.id), {
        onFinish: () => { processing.value = false; },
    });
};

const cancel = () => {
    router.visit(route('projects.tasks.index', props.project.id));
};
</script>


