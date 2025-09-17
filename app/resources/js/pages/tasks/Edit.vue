<template>
    <AppLayout :title="task.pageVersion?.page ? `Редактирование задачи: ${task.pageVersion.page.title}` : 'Редактирование задачи'">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="task.pageVersion?.page ? `Редактирование задачи для страницы: ${task.pageVersion.page.title}` : 'Редактирование задачи'" />
                    <p class="mt-1 text-sm text-muted-foreground">Создана {{ formatDate(task.created_at) }} пользователем {{ task.creator?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('tasks.show', task.id)">Назад к задаче</Link>
                    </Button>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Основное содержимое -->
                <div class="space-y-6 lg:col-span-1">
                    <TaskEditor
                        v-model:content="form.content"
                        :errors="errors"
                        :submitting="isSubmitting"
                        :cancel-href="route('tasks.show', task.id)"
                    />
                </div>

                <div class="space-y-6 lg:col-span-1">
                    <!-- Информация о странице -->
                    <Card v-if="task.pageVersion && task.pageVersion.page">
                        <CardHeader>
                            <CardTitle>Информация о странице</CardTitle>
                            <CardDescription>Детали страницы, для которой создана задача</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium">Заголовок</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion?.page?.title || '' }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium">Автор</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion?.page?.creator?.name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium">Дата создания</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion?.page?.created_at ? formatDate(task.pageVersion.page.created_at) : '' }}</p>
                                </div>
                                <div v-if="task.pageVersion && task.pageVersion.previousVersion">
                                    <Label class="text-sm font-medium">Предыдущая версия</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.previousVersion.title }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Сравнение версий -->
                    <Card v-if="task.pageVersion && task.pageVersion.previousVersion">
                        <CardHeader>
                            <CardTitle>Сравнение версий</CardTitle>
                            <CardDescription>Изменения между предыдущей и текущей версией страницы</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Сравнение заголовков -->
                            <div v-if="task.pageVersion && task.pageVersion.page && (task.pageVersion.page.title !== (task.pageVersion.previousVersion?.title || ''))">
                                <Label class="text-sm font-medium">Изменение заголовка</Label>
                                <div class="mt-2 space-y-2">
                                    <div class="rounded border border-red-200 bg-red-50 p-2">
                                        <span class="text-xs font-medium text-red-600">Было:</span>
                                        <p class="text-sm">{{ task.pageVersion.previousVersion?.title || '' }}</p>
                                    </div>
                                    <div class="rounded border border-green-200 bg-green-50 p-2">
                                        <span class="text-xs font-medium text-green-600">Стало:</span>
                                        <p class="text-sm">{{ task.pageVersion.page.title }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Сравнение содержимого -->
                            <div v-if="task.pageVersion && task.pageVersion.page && (task.pageVersion.page.content !== (task.pageVersion.previousVersion?.content || ''))">
                                <Label class="text-sm font-medium">Изменение содержимого</Label>
                                <div class="mt-2">
                                    <DiffViewer :old-content="task.pageVersion.previousVersion?.content || ''" :new-content="task.pageVersion.page.content" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </AppLayout>
</template>

<script setup lang="ts">
import DiffViewer from '@/components/DiffViewer.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import TaskEditor from '@/components/Task/TaskEditor.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import type { LLMChat } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface TaskData {
    id: number;
    content: string;
    generation_status: string;
    created_at: string;
    updated_at: string;
    edited_at?: string;
    page: {
        id: number;
        title: string;
        content: string;
        created_at: string;
        creator: {
            id: number;
            name: string;
            email: string;
        };
        previous_version?: {
            id: number;
            title: string;
            content: string | null;
        };
    };
    creator: {
        id: number;
        name: string;
        email: string;
    };
    llm_chat?: LLMChat | null;
}

const props = defineProps<{
    task: TaskData;
}>();

// Форма для редактирования
const form = useForm({
    content: props.task.content || '',
});

// Состояние отправки формы
const isSubmitting = ref(false);

// Ошибки валидации
const errors = ref<Record<string, string>>({});

// Функция отправки формы
const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        await form.put(route('tasks.update', props.task.id), {
            onSuccess: () => {
                // Успешное сохранение - редирект произойдет автоматически
            },
            onError: (validationErrors) => {
                errors.value = validationErrors;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        console.error('Ошибка при сохранении:', error);
        isSubmitting.value = false;
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
