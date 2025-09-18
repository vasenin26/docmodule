import { ref, computed } from 'vue';
import type { LLMChat } from '@/types';
import { createApi } from '@/service/api/Api';
import { SendActualizationMessageRequest } from '@/service/api/request/Actualization/SendActualizationMessageRequest';

export interface SendMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export function useActualizationChat(actualizationId: number) {
    const isSending = ref(false);
    const error = ref<string | null>(null);

    const sendMessage = async (message: string): Promise<SendMessageResponse | null> => {
        if (!message.trim()) {
            error.value = 'Сообщение не может быть пустым';
            return null;
        }

        isSending.value = true;
        error.value = null;

        try {
            const api = createApi();
            const req = new SendActualizationMessageRequest(actualizationId, { message });
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

    const updateChatMessages = (chat: LLMChat | null, newMessages: any[]) => {
        if (chat) {
            chat.messages = newMessages;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        isSending: computed(() => isSending.value),
        error: computed(() => error.value),
        hasError: computed(() => error.value !== null),
        sendMessage,
        updateChatMessages,
        clearError,
    };
}


