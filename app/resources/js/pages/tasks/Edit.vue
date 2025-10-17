<template>
    <AppLayout :title="task.pageVersion?.page ? `Редактирование задачи: ${task.pageVersion.page.title}` : 'Редактирование задачи'">
        <template #context-actions>
            <Button as-child variant="outline">
                <Link :href="route('tasks.show', task.id)">Назад к задаче</Link>
            </Button>
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

                    <!-- Привязанные страницы -->
                    <AttachedPages
                        v-model:items="attachedItems"
                        :task-id="task.id"
                        @request-attach="queueAttach"
                        @request-detach="queueDetach"
                    />

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
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import TaskEditor from '@/components/Task/TaskEditor.vue';
import AttachedPages from '@/components/Task/AttachedPages.vue';

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

const props = defineProps<{ task: TaskData & { attachedPageVersions?: { id:number; title:string; version:number|null }[] } }>();

// Форма для редактирования
const form = useForm({
    content: props.task.content || '',
    attachments_add: [] as number[],
    attachments_remove: [] as number[],
});

// Состояние отправки формы
const isSubmitting = ref(false);

// Ошибки валидации
const errors = ref<Record<string, string>>({});

const attachedItems = ref(props.task.attachedPageVersions || []);

const queueAttach = (item: { id:number; title:string; version:number|null }) => {
    // Если уже есть в remove — убираем оттуда
    form.attachments_remove = form.attachments_remove.filter(id => id !== item.id);
    // Если уже прикреплен визуально — не дублируем
    if (!attachedItems.value.find(x => x.id === item.id)) {
        attachedItems.value = [...attachedItems.value, item];
    }
    // Добавляем в pending add, если не было
    if (!form.attachments_add.includes(item.id)) {
        form.attachments_add.push(item.id);
    }
};

const queueDetach = (id: number) => {
    // Убираем из визуального списка
    attachedItems.value = attachedItems.value.filter(i => i.id !== id);
    // Если был запланирован на добавление — отменяем
    form.attachments_add = form.attachments_add.filter(x => x !== id);
    // Иначе планируем удаление
    if (!form.attachments_remove.includes(id)) {
        form.attachments_remove.push(id);
    }
};

// Функция отправки формы
const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        await form.put(route('tasks.update', props.task.id), {
            onSuccess: () => {
                // reset pending arrays after success
                form.attachments_add = [];
                form.attachments_remove = [];
            },
            onError: (validationErrors) => {
                errors.value = validationErrors as any;
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
