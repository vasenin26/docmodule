<template>
    <AppLayout :title="taskTitle || ((props.task as any).pageVersion?.page ? `Задача: ${(props.task as any).pageVersion.page.title}` : 'Задача')">
        <template #context-actions>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('tasks.edit', props.task.id)">Редактировать задачу</Link>
                    </Button>

                    <!-- Кнопка перезапуска генерации -->
                    <Button v-if="canRestartGeneration" @click="restartGeneration" :disabled="isRestartingGeneration" variant="outline" size="sm">
                        <span v-if="isRestartingGeneration">Перезапуск...</span>
                        <span v-else>Перезапустить генерацию</span>
                    </Button>

                    <!-- Кнопка чата -->
                    <ChatButton :show-condition="!!task.llm_chat" @click="openChatModal" variant="outline" size="sm" />

                    <TaskExportButton />
                    <Button v-if="task.pageVersion?.page" as-child variant="outline" size="sm">
                        <Link :href="route('pages.show', task.pageVersion.page.id)"> К странице</Link>
                    </Button>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Метаинформация -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ taskTitle || 'Задача без заголовка' }}</CardTitle>
                        <CardDescription>Сведения о задаче</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label class="text-sm font-medium">ID задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.id }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Создатель задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.creator?.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Дата создания задачи</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(task.created_at) }}</p>
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
            </div>
            <div class="space-y-6 lg:col-span-1">
                <!-- Технический план -->
                <TechplanCard :techplane="task.techplane" :task-id="task.id" />

                <!-- Информация о странице -->
                <Card v-if="task.pageVersion && task.pageVersion.page">
                    <CardHeader>
                        <CardTitle>Информация о странице</CardTitle>
                        <CardDescription> Детали страницы, для которой создана задача</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label class="text-sm font-medium">Заголовок версии</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.title }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Автор страницы</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.page.creator?.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium">Дата создания версии</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(task.pageVersion.created_at) }}</p>
                            </div>
                            <div v-if="task.pageVersion.previousVersion">
                                <Label class="text-sm font-medium">Предыдущая версия</Label>
                                <p class="mt-1 text-sm text-muted-foreground">{{ task.pageVersion.previousVersion.id }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <!-- Привязанные страницы -->
                <Card v-if="task.attachedPageVersions && task.attachedPageVersions.length">
                    <CardHeader>
                        <CardTitle>Привязанные страницы</CardTitle>
                        <CardDescription>Список страниц, связанные с задачей</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2">
                            <li v-for="item in task.attachedPageVersions" :key="item.id" class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ item.title }}</div>
                                    <div class="text-xs text-muted-foreground">Версия: {{ item.version ?? '—' }}</div>
                                </div>
                                <Button as-child size="sm" variant="outline">
                                    <Link :href="route('pages.show', item.id)">К странице</Link>
                                </Button>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Сравнение версий -->
                <Card v-if="task.pageVersion && task.pageVersion.previousVersion">
                    <CardHeader>
                        <CardTitle>Сравнение версий</CardTitle>
                        <CardDescription> Изменения между предыдущей и текущей версией страницы</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Сравнение заголовков -->
                        <div v-if="task.pageVersion && task.pageVersion.title !== (task.pageVersion.previousVersion?.title || '')">
                            <Label class="text-sm font-medium">Изменение заголовка</Label>
                            <div class="mt-2 space-y-2">
                                <div class="rounded border border-red-200 bg-red-50 p-2">
                                    <span class="text-xs font-medium text-red-600">Было:</span>
                                    <p class="text-sm">{{ task.pageVersion.previousVersion.title || '' }}</p>
                                </div>
                                <div class="rounded border border-green-200 bg-green-50 p-2">
                                    <span class="text-xs font-medium text-green-600">Стало:</span>
                                    <p class="text-sm">{{ task.pageVersion.title }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Сравнение содержимого -->
                        <div v-if="task.pageVersion && task.pageVersion.page">
                            <Label class="text-sm font-medium">Изменение содержимого</Label>
                            <div class="mt-2">
                                <DiffViewer :old-content="task.pageVersion.previousVersion?.content || ''" :new-content="task.pageVersion.content" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Модальное окно чата -->
        <SidePanel v-model:open="isChatModalOpen">
            <AgentChat
                v-if="chat"
                :messages="chat.messages"
                :loading="isPolling"
                :status="generationStatus"
                :sending="isSending"
                :requestCount="requestCount"
                :contextFill="chat?.context_fill ?? 0"
                :totalTokens="chat?.total_tokens ?? 0"
                :context="chat?.context"
                @sendMessage="sendMessageToChat"
                @stop="sendStopGenerating"
            />
        </SidePanel>
    </AppLayout>
</template>

<script setup lang="ts">
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import ChatButton from '@/components/ChatButton.vue';
import DiffViewer from '@/components/DiffViewer.vue';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import TaskExportButton from '@/components/TaskExportButton.vue';
import TechplanCard from '@/components/TechplanCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { LLMChat } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SidePanel from '@/components/ui/sidepanel/SidePanel.vue';
import { useTaskChat } from '@/composables/useTaskChat';
import { createApi } from '@/service/api/Api';
import { TaskStatusRequest } from '@/service/api/request/Task/TaskStatusRequest';
import { TaskRestartGenerationRequest } from '@/service/api/request/Task/TaskRestartGenerationRequest';

interface TechplaneData {
    id: number;
    content: string | null;
    generation_status: string;
    created_at: string;
    creator: {
        id: number;
        name: string;
    };
}

interface TaskData {
    id: number;
    title: string | null;
    content: string;
    generation_status: string;
    created_at: string;
    updated_at: string;
    pageVersion: {
        id: number;
        title: string;
        content: string;
        created_at: string;
        page: {
            id: number;
            created_at: string;
            creator?: {
                id: number;
                name: string;
                email: string;
            };
        };
        previousVersion?: {
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
    techplane?: TechplaneData | null;
}

const props = defineProps<{
    task: TaskData & { attachedPageVersions?: { id: number; title: string; version: number | null }[] };
}>();

// Убираем computed, используем props.task напрямую

// Реактивные переменные для отслеживания статуса
const generationStatus = ref<string>(props.task.generation_status || 'unknown');
const taskTitle = ref<string | null>(props.task.title || null);
const taskContent = ref<string | null>(props.task.content || null);
const chat = ref<LLMChat | null>(props.task.llm_chat || null);
const isPolling = ref<boolean>(false);
const pollInterval = ref<number | null>(null);
const requestCount = ref<number>(0);

// Переменные для кнопки перезапуска
const isRestartingGeneration = ref<boolean>(false);

// Управление модальным окном чата
const isChatModalOpen = ref<boolean>(false);

// Composable для работы с чатом задачи
const { sendMessage, stopGenerating, updateChatMessages, isSending, error, hasError } = useTaskChat(props.task.id);

// Единый экземпляр API клиента
const api = createApi();

// Функции для управления модальным окном
const openChatModal = () => {
    isChatModalOpen.value = true;
};

const canRestartGeneration = computed(() => {
    return generationStatus.value !== 'generating';
});

// Кнопка редактирования доступна всегда

// Функция проверки статуса генерации
const checkGenerationStatus = async () => {
    try {
        const request = new TaskStatusRequest(props.task.id);
        const data = await request.call(api);
        requestCount.value++;
        generationStatus.value = data.status;
        taskTitle.value = (data as any).title || null;
        taskContent.value = data.content || null;

        // Обновляем сообщения чата, если пришли с сервера
        if (data.chat && data.chat.messages) {
            if (!chat.value) {
                // Инициализируем чат, если его ещё нет локально
                chat.value = {
                    id: data.chat.id,
                    messages: data.chat.messages,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                } as LLMChat;
            } else {
                chat.value.messages = data.chat.messages;
            }
            // Прокидываем context_fill, total_tokens и context из API
            (chat.value as any).context_fill = (data.chat as any).context_fill ?? (chat.value as any)?.context_fill ?? 0;
            (chat.value as any).total_tokens = (data.chat as any).total_tokens ?? (chat.value as any)?.total_tokens ?? 0;
            (chat.value as any).context = (data.chat as any).context ?? (chat.value as any)?.context ?? null;
        }

        // Останавливаем опрос если генерация завершена или завершилась с ошибкой
        if (generationStatus.value === 'completed' || generationStatus.value === 'failed') {
            stopPolling();
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
        const request = new TaskRestartGenerationRequest(props.task.id);
        const data = await request.call(api);
        if (data.success) {
            // Сбросить состояние и начать опрос заново
            generationStatus.value = 'pending';
            taskTitle.value = null;
            taskContent.value = null;
            startPolling();
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

const sendMessageToChat = async (message: string) => {
    startPolling();

    const result = await sendMessage(message);
    generationStatus.value = 'send-message';

    if (result?.success && result.chat) {
        // Обновляем локальное состояние чата
        updateChatMessages(props.task.llm_chat || null, result.chat.messages);
    } else if (hasError.value) {
        console.error('Ошибка при отправке:', error.value);
    }
};

const sendStopGenerating = async () => {
    await stopGenerating();
    generationStatus.value = 'completed';
}
</script>
