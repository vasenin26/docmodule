import { createApi } from '@/service/api/Api';
import { GetChatState, SendMessage, StopChatGeneration } from '@/service/api/request/Chat/requests';
import { ref } from 'vue';

export function useChatAgent(chatId: int) {
    const messages = ref<LLMMessage[]>([]);
    const status = ref<string>('loading');
    const sending = ref<boolean>(false);
    const requestCount = ref<number>(0);
    const totalTokens = ref<number>(0);
    const contextFill = ref<number>(0);
    const polingState = ref<boolean>(false);

    const api = createApi();

    function startPoling() {
        polingState.value = true;
        poling();
    }

    function stopPoling() {
        polingState.value = false;
    }

    async function stopGeneration() {
        const result = await new StopChatGeneration(chatId).call(api);
        if (result.status === 'ok') stopPoling();
    }

    async function sendMessage(message: string) {
        sending.value = true;
        const result = await new SendMessage(chatId, message).call(api);
        sending.value = false;

        if (result.status === 'ok') {
            startPoling();
        }
    }

    async function reset() {
        status.value = 'loading';
        sending.value = false;
        messages.value = [];
        totalTokens.value = 0;
    }

    async function poling() {
        const result = await new GetChatState(chatId).call(api);

        status.value = result.status;
        messages.value = result.messages;
        totalTokens.value = result.totalTokens;
        contextFill.value = result.contextFill;

        requestCount.value++;

        if (status.value === 'completed') stopPoling();
        if (!polingState.value) return;

        setTimeout(() => poling(), 500);
    }

    return {
        messages,
        status,
        sending,
        requestCount,
        totalTokens,
        contextFill,
        polingState,
        startPoling,
        stopPoling,
        stopGeneration,
        sendMessage,
        reset,
    };
}
