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

import { createApi } from '@/services/api/Api';
import { TaskSendMessageRequest } from '@/services/api/request/Task/TaskSendMessageRequest';
import { TaskSendStopGenerating } from '@/services/api/request/Task/TaskSendStopGenerating';

export function useTaskChat(taskId: number) {
    const isSending = ref(false);
    const error = ref<string | null>(null);
    const api = createApi();

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
            const req = new TaskSendMessageRequest(taskId, { message });
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

    const stopGenerating = async () => {
        const req = new TaskSendStopGenerating(taskId);
        await req.call(api);
    }

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
        stopGenerating,
        updateChatMessages,
        clearError,
    };
}
