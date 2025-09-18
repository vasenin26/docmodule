<template>
    <AppLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Заголовок -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Технический план</h1>
                    <p class="mt-2 text-gray-600">Создан: {{ formatDate(techplane.created_at) }} • Автор: {{ techplane.creator?.name }}</p>
                </div>
                <div class="flex gap-3">
                    <!-- Кнопка перегенерации -->
                    <Button v-if="canRestartGeneration" @click="restartGeneration" :disabled="isRestartingGeneration" variant="outline" size="sm">
                        <span v-if="isRestartingGeneration">Перезапуск...</span>
                        <span v-else>Перезапустить генерацию</span>
                    </Button>
                    <!-- Кнопка экспорта (заглушка) -->
                    <Button variant="outline" disabled> Экспортировать </Button>
                    <!-- Кнопка выполнения техплана -->
                    <Button
                        v-if="canExecuteTechplane"
                        @click="executeTechplane"
                        :disabled="isExecutingTechplane"
                        variant="default" 
                    >
                        <span v-if="isExecutingTechplane">Создание реализации...</span>
                        <span v-else>Выполнить</span>
                    </Button>

                    <!-- Кнопка Готово -->
                    <Button
                        v-if="canMarkDone"
                        @click="openDoneModal"
                        :disabled="isMarkingDone"
                        variant="outline"
                        size="sm"
                    >
                        <span v-if="isMarkingDone">Сохранение...</span>
                        <span v-else>Готово</span>
                    </Button>
                    <!-- Кнопка чата (если есть) -->
                    <Button v-if="chat" @click="openChatModal" variant="default"> Чат </Button>
                    <!-- Кнопка возврата к задаче -->
                    <Button as-child variant="outline">
                        <Link :href="route('tasks.show', techplane.task.id)"> К задаче </Link>
                    </Button>
                </div>
            </div>

            <!-- Метаинформация -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Информация о техплане</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label class="text-sm font-medium text-gray-500">ID техплана</Label>
                            <p class="text-sm">{{ techplane.id }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Связанная задача</Label>
                            <p class="text-sm">
                                <Link :href="route('tasks.show', techplane.task.id)" class="text-blue-600 hover:underline">
                                    Задача #{{ techplane.task.id }}
                                </Link>
                            </p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Страница</Label>
                            <p class="text-sm">{{ techplane.task.pageVersion.page.title }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Статус</Label>
                            <p class="text-sm">{{ techplane.generation_status }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Содержимое техплана -->
            <Card>
                <CardHeader>
                    <CardTitle>Описание техплана</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="techplaneContent && techplaneContent.trim().length > 0" class="prose max-w-none">
                        <MarkdownRenderer :content="techplaneContent" />
                    </div>
                    <div v-else class="text-muted-foreground italic">
                        <div class="flex items-center gap-2">
                            <div v-if="isPolling" class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
                            <span>{{ getStatusMessage() }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Модальное окно чата -->
            <SidePanel v-model:open="showChatModal">
                <AgentChat
                    v-if="chat"
                    :messages="chat.messages"
                    :loading="isPolling"
                    :status="generationStatus"
                    :sending="chatSending"
                    @sendMessage="sendMessageToChat"
                />
            </SidePanel>

            <!-- Модалка Готово -->
            <TechplaneDoneModal
                v-model="showDoneModal"
                :techplane-id="props.techplane.id"
                @applied="onDoneApplied"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SidePanel from '@/components/ui/sidepanel/SidePanel.vue';
import { useTechplaneChat } from '@/composables/useTechplaneChat';
import { createApi } from '@/service/api/Api';
import { TechplaneStatusRequest } from '@/service/api/request/Techplane/TechplaneStatusRequest';
import { TechplaneExecuteRequest } from '@/service/api/request/Techplane/TechplaneExecuteRequest';
import TechplaneDoneModal from '@/components/techplane/TechplaneDoneModal.vue';
import { TechplaneMarkDoneRequest, type TechplaneMarkDoneResponse } from '@/service/api/request/Techplane/TechplaneMarkDoneRequest';
import type { LLMChat } from '@/types';

interface TechplaneData {
    id: number;
    content: string | null;
    generation_status: string;
    created_at: string;
    updated_at: string;
    creator: {
        id: number;
        name: string;
        email: string;
    };
    llm_chat?: LLMChat | null;
    task: {
        id: number;
        pageVersion: {
            id: number;
            title: string;
            page: {
                id: number;
                title: string;
            };
        };
    };
}

const props = defineProps<{
    techplane: TechplaneData;
}>();

const showChatModal = ref(false);

// Реактивные данные для отслеживания состояния генерации
const generationStatus = ref(props.techplane.generation_status);
const techplaneContent = ref(props.techplane.content);
const isPolling = ref(false);
const pollInterval = ref<number | null>(null);
const isRestartingGeneration = ref(false);
const isExecutingTechplane = ref(false);
const isMarkingDone = ref(false);
const showDoneModal = ref(false);

// Реактивные переменные для чата
const chat = ref<LLMChat | null>(props.techplane.llm_chat || null);
const isSending = ref<boolean>(false);

// Единый экземпляр API клиента
const api = createApi();

// Composable для работы с чатом техплана
const { sendMessage, updateChatMessages, isSending: chatSending, error, hasError } = useTechplaneChat(props.techplane.id);

// Вычисляемые свойства
const canRestartGeneration = computed(() => {
    return generationStatus.value !== 'generating';
});

// Вычисляемое свойство для доступности кнопки выполнения
const canExecuteTechplane = computed(() => {
    return generationStatus.value === 'completed' && techplaneStatus.value !== 'executed';
});

const canMarkDone = computed(() => {
    return generationStatus.value === 'completed' && techplaneStatus.value !== 'executed';
});

// Функция для проверки статуса генерации
const checkGenerationStatus = async () => {
    try {
        const request = new TechplaneStatusRequest(props.techplane.id);
        const data = await request.call(api);
        generationStatus.value = data.status;
        techplaneContent.value = data.content;

        // Обновляем сообщения чата, если пришли с сервера
        if (data.chat && data.chat.messages) {
            if (!chat.value) {
                chat.value = {
                    id: data.chat.id,
                    messages: data.chat.messages,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                } as LLMChat;
            } else {
                chat.value.messages = data.chat.messages;
            }
        }

        // Остановить опрос если генерация завершена
        if (data.status === 'completed' || data.status === 'failed') {
            stopPolling();
        }
    } catch (error) {
        console.error('Ошибка при запросе статуса:', error);
    }
};
const techplaneStatus = ref<string>((props.techplane as any).status || 'planned');

function openDoneModal() {
    showDoneModal.value = true;
}

async function onDoneApplied(payload: TechplaneMarkDoneResponse) {
    techplaneStatus.value = payload.status;
}

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
        const response = await fetch(route('techplanes.restart-generation', props.techplane.id), {
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
                techplaneContent.value = null;
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

// Функция выполнения техплана
const executeTechplane = async () => {
    if (!canExecuteTechplane.value || isExecutingTechplane.value) {
        return;
    }

    isExecutingTechplane.value = true;

    try {
        const api = createApi();
        const request = new TechplaneExecuteRequest(props.techplane.id);

        console.log('Отправляем запрос на выполнение техплана:', props.techplane.id);
        const data = await request.call(api);
        console.log('Получен ответ от сервера:', data);

        if (data && data.success) {
            console.log('Реализация создана успешно, перенаправляем на:', data.redirect_url);
            // Перенаправление на страницу реализации произойдет автоматически
            window.location.href = data.redirect_url;
        } else {
            const errorMessage = data?.message || 'Неизвестная ошибка при создании реализации';
            console.error('Ошибка при создании реализации:', errorMessage, 'Данные:', data);
            alert(errorMessage);
        }
    } catch (error) {
        console.error('Ошибка при создании реализации:', error);
        const errorMessage = error instanceof Error ? error.message : 'Ошибка сети при создании реализации';
        alert(errorMessage);
    } finally {
        isExecutingTechplane.value = false;
    }
};

// Lifecycle hooks
onMounted(() => {
    // Начинаем опрос если содержимое пустое или статус не завершен
    if (!techplaneContent.value || (generationStatus.value !== 'completed' && generationStatus.value !== 'failed')) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const openChatModal = () => {
    showChatModal.value = true;
};

// Функция отправки сообщения в чат
const sendMessageToChat = async (message: string) => {
    startPolling();

    const result = await sendMessage(message);
    generationStatus.value = 'send-message';

    if (result?.success && result.chat) {
        // Обновляем локальное состояние чата
        updateChatMessages(props.techplane.llm_chat || null, result.chat.messages);
    } else if (hasError.value) {
        console.error('Ошибка при отправке:', error.value);
    }
};

// Функция для получения сообщения о статусе
const getStatusMessage = () => {
    switch (generationStatus.value) {
        case 'pending':
            return 'Техплан ожидает генерации...';
        case 'generating':
            return 'Техплан генерируется...';
        case 'failed':
            return 'Ошибка при генерации техплана';
        default:
            return 'Техплан еще не сгенерирован';
    }
};
</script>
