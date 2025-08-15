<template>
    <AppLayout :title="`Задача: ${task.page.title}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="`Задача для страницы: ${task.page.title}`" />
                    <p class="mt-1 text-sm text-muted-foreground">Создана {{ formatDate(task.created_at) }} пользователем {{ task.creator?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Кнопка перезапуска генерации -->
                    <Button v-if="canRestartGeneration" @click="restartGeneration" :disabled="isRestartingGeneration" variant="outline" size="sm">
                        <span v-if="isRestartingGeneration">Перезапуск...</span>
                        <span v-else>Перезапустить генерацию</span>
                    </Button>

                    <!-- Кнопка чата -->
                    <Button v-if="task.llm_chat" @click="openChatModal" variant="outline" size="sm"> Чат</Button>

                    <TaskExportButton />
                    <Button as-child variant="outline">
                        <Link :href="route('pages.show', task.page.id)"> К странице</Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Информация о странице -->
                <Card>
                    <CardHeader>
                        <CardTitle>Информация о странице</CardTitle>
                        <CardDescription> Детали страницы, для которой создана задача</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label class="text-sm font-medium">Заголовок</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.page.title }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Автор</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.page.creator.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Дата создания</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(task.page.created_at) }}</p>
                            </div>
                            <div v-if="task.page.previous_version">
                                <Label class="text-sm font-medium">Предыдущая версия</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.page.previous_version.title }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Описание задачи -->
                <Card>
                    <CardHeader>
                        <CardTitle>Описание задачи</CardTitle>
                        <CardDescription> Автоматически сгенерированное описание задачи на основе изменений в документации </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm max-w-none">
                            <MarkdownRenderer v-if="taskContent && taskContent.trim().length > 0" :content="taskContent" />
                            <div v-else class="text-muted-foreground italic">
                                <div class="flex items-center gap-2">
                                    <div
                                        v-if="isPolling"
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"
                                    ></div>
                                    <span>{{ getStatusMessage() }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Технический план -->
                <Card>
                    <CardHeader>
                        <CardTitle>Технический план</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="task.techplane">
                            <!-- Содержимое техплана -->
                            <div v-if="task.techplane.content" class="mb-4">
                                <div class="prose prose-sm max-w-none">
                                    <MarkdownRenderer :content="task.techplane.content" />
                                </div>
                            </div>
                            <div v-else class="text-muted-foreground italic mb-4">
                                Техплан создан, но содержимое еще не сгенерировано
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="flex gap-3">
                                <Button variant="outline" disabled>
                                    Сгенерировать
                                </Button>
                                <Button
                                    as-child
                                    variant="default"
                                >
                                    <Link :href="route('techplanes.show', task.techplane.id)">
                                        Редактировать
                                    </Link>
                                </Button>
                            </div>
                        </div>
                        <div v-else>
                            <!-- Кнопка создания техплана -->
                            <Button
                                as-child
                                variant="default"
                            >
                                <Link 
                                    :href="route('tasks.create-techplane', task.id)" 
                                    method="post"
                                    as="button"
                                >
                                    Создать технический план
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <div class="space-y-6 lg:col-span-1">
                <!-- Метаинформация -->
                <Card>
                    <CardHeader>
                        <CardTitle>Метаинформация</CardTitle>
                        <CardDescription>Сведения о задаче</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label class="text-sm font-medium">ID задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.id }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">ID страницы</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.page.id }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Создатель задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.creator.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Дата создания задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(task.created_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Сравнение версий -->
                <Card v-if="task.page.previous_version">
                    <CardHeader>
                        <CardTitle>Сравнение версий</CardTitle>
                        <CardDescription> Изменения между предыдущей и текущей версией страницы</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Сравнение заголовков -->
                        <div v-if="task.page.title !== task.page.previous_version.title">
                            <Label class="text-sm font-medium">Изменение заголовка</Label>
                            <div class="mt-2 space-y-2">
                                <div class="rounded border border-red-200 bg-red-50 p-2">
                                    <span class="text-xs font-medium text-red-600">Было:</span>
                                    <p class="text-sm">{{ task.page.previous_version.title }}</p>
                                </div>
                                <div class="rounded border border-green-200 bg-green-50 p-2">
                                    <span class="text-xs font-medium text-green-600">Стало:</span>
                                    <p class="text-sm">{{ task.page.title }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Сравнение содержимого -->
                        <div v-if="task.page.content !== task.page.previous_version.content">
                            <Label class="text-sm font-medium">Изменение содержимого</Label>
                            <div class="mt-2">
                                <DiffViewer :old-content="task.page.previous_version.content" :new-content="task.page.content" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Модальное окно чата -->
        <Dialog v-model:open="isChatModalOpen">
            <DialogContent class="max-w-5xl">
                <DialogHeader>
                    <DialogTitle>Чат с LLM</DialogTitle>
                </DialogHeader>
                <AgentChat v-if="task.llm_chat" :messages="task.llm_chat.messages" :loading="isPolling && generationStatus === 'generating'" />
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup lang="ts">
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import DiffViewer from '@/components/DiffViewer.vue';
import Heading from '@/components/Heading.vue';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import TaskExportButton from '@/components/TaskExportButton.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { LLMChat } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface TaskData {
    id: number;
    content: string;
    generation_status: string;
    created_at: string;
    updated_at: string;
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
            content: string;
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

// Реактивные переменные для отслеживания статуса
const generationStatus = ref<string>(props.task.generation_status || 'unknown');
const taskContent = ref<string | null>(props.task.content || null);
const isPolling = ref<boolean>(false);
const pollInterval = ref<number | null>(null);

// Переменные для кнопки перезапуска
const isRestartingGeneration = ref<boolean>(false);

// Управление модальным окном чата
const isChatModalOpen = ref<boolean>(false);

// Функции для управления модальным окном
const openChatModal = () => {
    isChatModalOpen.value = true;
};

const canRestartGeneration = computed(() => {
    return generationStatus.value !== 'generating';
});

// Функция проверки статуса генерации
const checkGenerationStatus = async () => {
    try {
        const response = await fetch(route('tasks.status', props.task.id), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            generationStatus.value = data.status;
            taskContent.value = data.content || null;

            // Останавливаем опрос если генерация завершена или завершилась с ошибкой
            if (generationStatus.value === 'completed' || generationStatus.value === 'failed') {
                stopPolling();
            }
        } else {
            console.error('Ошибка HTTP:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Ошибка при запросе статуса:', error);
    }
};

// Функция для запуска автоматического опроса
const startPolling = () => {
    if (!isPolling.value) {
        isPolling.value = true;
        pollInterval.value = setInterval(checkGenerationStatus, 3000); // каждые 3 секунды
    }
};

// Функция для остановки опроса
const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
        isPolling.value = false;
    }
};

// Функция перезапуска генерации
const restartGeneration = async () => {
    if (!canRestartGeneration.value || isRestartingGeneration.value) {
        return;
    }

    isRestartingGeneration.value = true;

    try {
        const response = await fetch(route('tasks.restart-generation', props.task.id), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                // Сбросить состояние и начать опрос заново
                generationStatus.value = 'pending';
                taskContent.value = null;
                startPolling();
            }
        } else {
            console.error('Ошибка при перезапуске генерации:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Ошибка при перезапуске генерации:', error);
    } finally {
        isRestartingGeneration.value = false;
    }
};

// Lifecycle hooks
onMounted(() => {
    // Начинаем опрос если содержимое пустое или статус не завершен
    if (!taskContent.value || (generationStatus.value !== 'completed' && generationStatus.value !== 'failed')) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Функция для получения сообщения о статусе
const getStatusMessage = () => {
    switch (generationStatus.value) {
        case 'pending':
            return 'Описание задачи ожидает генерации...';
        case 'generating':
            return 'Описание задачи генерируется...';
        case 'failed':
            return 'Ошибка при генерации описания задачи';
        default:
            return 'Описание задачи еще не сгенерировано';
    }
};
</script>
