import { ref, computed } from 'vue';
import { createApi } from '@/service/api/Api';
import { SendImplementationMessageRequest } from '@/service/api/request/Implementation/SendImplementationMessageRequest';
import { ImplementationStopGeneratingRequest } from '@/service/api/request/Implementation/ImplementationStopGeneratingRequest';
import type { LLMChat } from '@/types';

export interface SendMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export function useImplementationChat(implementationId: number) {
    const isSending = ref(false);
    const error = ref<string | null>(null);
    const api = createApi();

    const sendMessage = async (message: string): Promise<SendMessageResponse | null> => {
        if (!message.trim()) {
            error.value = 'Сообщение не может быть пустым';
            return null;
        }

        isSending.value = true;
        error.value = null;

        try {
            const req = new SendImplementationMessageRequest(implementationId, { message });
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
        const req = new ImplementationStopGeneratingRequest(implementationId);
        await req.call(api);
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
        stopGenerating,
        updateChatMessages,
        clearError,
    };
}
