import { ref, computed } from 'vue';
import type { LLMChat } from '@/types';

export interface SendMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

import { createApi } from '@/service/api/Api';
import { TaskSendMessageRequest } from '@/service/api/request/Task/TaskSendMessageRequest';

export function useTaskChat(taskId: number) {
    const isSending = ref(false);
    const error = ref<string | null>(null);

    /**
     * Отправить сообщение в чат задачи
     */
    const sendMessage = async (message: string): Promise<SendMessageResponse | null> => {
        if (!message.trim()) {
            error.value = 'Сообщение не может быть пустым';
            return null;
        }

        isSending.value = true;
        error.value = null;

        try {
            const api = createApi();
            const req = new TaskSendMessageRequest(route('tasks.send-message', taskId), { message });
            const data = await req.call(api);

            if (data.success) {
                return data;
            } else {
                error.value = data.message || 'Ошибка при отправке сообщения';
                return null;
            }
        } catch (err) {
            console.error('Ошибка при отправке сообщения:', err);
            error.value = 'Ошибка сети при отправке сообщения';
            return null;
        } finally {
            isSending.value = false;
        }
    };

    /**
     * Обновить сообщения чата в локальном состоянии
     */
    const updateChatMessages = (chat: LLMChat | null, newMessages: any[]) => {
        if (chat) {
            chat.messages = newMessages;
        }
    };

    /**
     * Очистить ошибку
     */
    const clearError = () => {
        error.value = null;
    };

    return {
        // Состояние
        isSending: computed(() => isSending.value),
        error: computed(() => error.value),
        hasError: computed(() => error.value !== null),

        // Методы
        sendMessage,
        updateChatMessages,
        clearError,
    };
}
