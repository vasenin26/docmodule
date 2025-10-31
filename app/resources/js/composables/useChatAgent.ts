import { createApi } from '@/services/api/Api';
import { GetChatState, SendMessage, StopChatGeneration } from '@/services/api/request/Chat/requests';
import { ref } from 'vue';

const chatId = ref<int|null>(null);
const messages = ref<LLMMessage[]>([]);
const status = ref<string>('loading');
const sending = ref<boolean>(false);
const requestCount = ref<number>(0);
const totalTokens = ref<number>(0);
const contextFill = ref<number>(0);
const polingState = ref<boolean>(false);

export function useChatAgent(targetChatId: number | null) {
    chatId.value = targetChatId;

    const api = createApi();

    function setChatId(target: number) {
        chatId.value = target;
    }

    function startPoling() {
        polingState.value = true;
        poling();
    }

    function stopPoling() {
        polingState.value = false;
    }

    async function stopGeneration() {
        if(chatId.value === null) return;
        const result = await new StopChatGeneration(chatId.value).call(api);
        if (result.status === 'ok') stopPoling();
    }

    async function sendMessage(message: string) {
        if(chatId.value === null) return;
        sending.value = true;
        const result = await new SendMessage(chatId.value, message).call(api);
        sending.value = false;

        if (result.status === 'ok') {
            startPoling();
        }
    }

    function reset() {
        status.value = 'loading';
        sending.value = false;
        messages.value = [];
        totalTokens.value = 0;
    }

    async function poling() {
        if(chatId.value === null) return;
        const result = await new GetChatState(chatId.value).call(api);

        status.value = result.status;
        messages.value = result.messages;
        totalTokens.value = result.totalTokens;
        contextFill.value = result.contextFill;

        requestCount.value++;

        if (status.value === 'completed') stopPoling();
        if (!polingState.value) return;

        setTimeout(() => poling(), 700);
    }

    return {
        messages,
        status,
        sending,
        requestCount,
        totalTokens,
        contextFill,
        polingState,
        setChatId,
        startPoling,
        stopPoling,
        stopGeneration,
        sendMessage,
        reset,
    };
}
