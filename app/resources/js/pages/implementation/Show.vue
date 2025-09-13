<template>
    <AppLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Заголовок -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Реализация техплана</h1>
                    <p class="mt-2 text-gray-600">Создана: {{ formatDate(implementation.created_at) }} • Автор: {{ implementation.creator?.name }}</p>
                </div>
                <div class="flex gap-3">
                    <!-- Кнопка чата (если есть) -->
                    <Button v-if="chat" @click="openChatModal" variant="default"> Чат </Button>
                    <!-- Кнопка возврата к техплану -->
                    <Button as-child variant="outline">
                        <Link v-if="implementation.techplane" :href="route('techplanes.show', implementation.techplane.id)"> К техплану </Link>
                    </Button>
                </div>
            </div>

            <!-- Метаинформация -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Информация о реализации</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label class="text-sm font-medium text-gray-500">ID реализации</Label>
                            <p class="text-sm">{{ implementation.id }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Связанный техплан</Label>
                            <p class="text-sm">
                                <Link v-if="implementation.techplane" :href="route('techplanes.show', implementation.techplane.id)" class="text-blue-600 hover:underline">
                                    Техплан #{{ implementation.techplane.id }}
                                </Link>
                                <span v-else class="text-gray-500">Техплан не найден</span>
                            </p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Страница</Label>
                            <p class="text-sm">{{ implementation.techplane?.task?.pageVersion?.page?.title || 'Неизвестно' }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-gray-500">Статус</Label>
                            <p class="text-sm">{{ implementation.status }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Содержимое реализации -->
            <Card>
                <CardHeader>
                    <CardTitle>Результат реализации</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="implementationContent && implementationContent.trim().length > 0" class="prose max-w-none">
                        <MarkdownRenderer :content="implementationContent" />
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
                    :status="implementationStatus"
                    :sending="chatSending"
                    @sendMessage="sendMessageToChat" 
                />
            </SidePanel>
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
import { useImplementationChat } from '@/composables/useImplementationChat';
import type { LLMChat } from '@/types';
import { createApi } from '@/service/api/Api';
import { ImplementationStatusRequest } from '@/service/api/request/Implementation/ImplementationStatusRequest';

interface ImplementationData {
    id: number;
    content: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    creator: {
        id: number;
        name: string;
        email: string;
    };
    llm_chat?: LLMChat | null;
    techplane: {
        id: number;
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
    };
}

const props = defineProps<{
    implementation: ImplementationData;
}>();

// Единый экземпляр API клиента
const api = createApi();

const showChatModal = ref(false);

// Реактивные данные для отслеживания состояния
const implementationStatus = ref(props.implementation.status);
const implementationContent = ref(props.implementation.content);
const isPolling = ref(false);
const pollInterval = ref<number | null>(null);

// Реактивные переменные для чата
const chat = ref<LLMChat | null>(props.implementation.llm_chat || null);

// Composable для работы с чатом реализации
const { sendMessage, updateChatMessages, isSending: chatSending, error, hasError } = useImplementationChat(props.implementation.id);

// Функция для проверки статуса
const checkImplementationStatus = async () => {
    try {
        const request = new ImplementationStatusRequest(props.implementation.id);
        const data = await request.call(api);
        
        implementationStatus.value = data.status;
        implementationContent.value = data.content;
        
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
        }
        
        // Останавливаем опрос если реализация завершена
        if (data.status === 'completed' || data.status === 'failed') {
            stopPolling();
        }
    } catch (error) {
        console.error('Ошибка при проверке статуса:', error);
    }
};

// Функция для получения сообщения статуса
const getStatusMessage = () => {
    switch (implementationStatus.value) {
        case 'pending':
            return 'Ожидание обработки...';
        case 'processing':
            return 'Агент выполняет реализацию...';
        case 'completed':
            return 'Реализация завершена';
        case 'failed':
            return 'Ошибка при выполнении реализации';
        default:
            return 'Неизвестный статус';
    }
};

// Функции управления опросом
const startPolling = () => {
    if (pollInterval.value) return;
    
    isPolling.value = true;
    pollInterval.value = setInterval(checkImplementationStatus, 3000);
};

const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
    isPolling.value = false;
};

// Функция отправки сообщения в чат
const sendMessageToChat = async (message: string) => {
    const result = await sendMessage(message);
    
    if (result?.success && result.chat) {
        // Обновляем локальное состояние чата
        updateChatMessages(chat.value, result.chat.messages);
    } else if (hasError.value) {
        console.error('Ошибка при отправке:', error.value);
    }
    
    // Обновляем чат после отправки сообщения
    setTimeout(checkImplementationStatus, 1000);
};

// Lifecycle hooks
onMounted(() => {
    // Отладочная информация
    console.log('Implementation data:', props.implementation);
    console.log('Techplane data:', props.implementation.techplane);
    console.log('Task data:', props.implementation.techplane?.task);
    console.log('PageVersion data:', props.implementation.techplane?.task?.pageVersion);
    console.log('Page data:', props.implementation.techplane?.task?.pageVersion?.page);
    
    // Начинаем опрос если содержимое пустое или статус не завершен
    if (!implementationContent.value || (implementationStatus.value !== 'completed' && implementationStatus.value !== 'failed')) {
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
</script>
