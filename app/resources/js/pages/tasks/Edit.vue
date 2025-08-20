<template>
    <AppLayout :title="`Редактирование задачи: ${task.pageVersion.page.title}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="`Редактирование задачи для страницы: ${task.pageVersion.page.title}`" />
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
                    <!-- Форма редактирования -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Редактирование описания задачи</CardTitle>
                            <CardDescription>Измените описание задачи. При сохранении техплан будет очищен.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="content" class="text-sm font-medium">Описание задачи</Label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    placeholder="Введите описание задачи..."
                                    class="mt-2 min-h-[200px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{ 'border-red-500': errors.content }"
                                />
                                <p v-if="errors.content" class="mt-1 text-sm text-red-600">{{ errors.content }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Кнопки действий -->
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center justify-between">
                                <Button type="button" variant="outline" as-child>
                                    <Link :href="route('tasks.show', task.id)">Отмена</Link>
                                </Button>
                                <Button type="submit" :disabled="isSubmitting">
                                    <span v-if="isSubmitting">Сохранение...</span>
                                    <span v-else>Сохранить изменения</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6 lg:col-span-1">
                    <!-- Информация о странице -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Информация о странице</CardTitle>
                            <CardDescription>Детали страницы, для которой создана задача</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label class="text-sm font-medium">Заголовок</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.page.title }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium">Автор</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.page.creator.name }}</p>
                                </div>
                                <div>
                                    <Label class="text-sm font-medium">Дата создания</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(task.pageVersion.page.created_at) }}</p>
                                </div>
                                <div v-if="task.pageVersion.previousVersion">
                                    <Label class="text-sm font-medium">Предыдущая версия</Label>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.previousVersion.title }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Сравнение версий -->
                    <Card v-if="task.pageVersion.previousVersion">
                        <CardHeader>
                            <CardTitle>Сравнение версий</CardTitle>
                            <CardDescription>Изменения между предыдущей и текущей версией страницы</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Сравнение заголовков -->
                            <div v-if="task.pageVersion.page.title !== (task.pageVersion.previousVersion.title || '')">
                                <Label class="text-sm font-medium">Изменение заголовка</Label>
                                <div class="mt-2 space-y-2">
                                    <div class="rounded border border-red-200 bg-red-50 p-2">
                                        <span class="text-xs font-medium text-red-600">Было:</span>
                                        <p class="text-sm">{{ task.pageVersion.previousVersion.title || '' }}</p>
                                    </div>
                                    <div class="rounded border border-green-200 bg-green-50 p-2">
                                        <span class="text-xs font-medium text-green-600">Стало:</span>
                                        <p class="text-sm">{{ task.pageVersion.page.title }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Сравнение содержимого -->
                            <div v-if="task.pageVersion.page.content !== (task.pageVersion.previousVersion.content || '')">
                                <Label class="text-sm font-medium">Изменение содержимого</Label>
                                <div class="mt-2">
                                    <DiffViewer :old-content="task.pageVersion.previousVersion.content" :new-content="task.pageVersion.page.content" />
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
