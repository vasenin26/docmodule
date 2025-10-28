<script setup lang="ts">
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import { onDeactivated, onMounted, ref } from 'vue';
import type { LLMMessage } from '@/types';
import { createApi } from '@/service/api/Api';
import { GetChatState } from '@/service/api/request/Chat/GetChatState';
import { StopChatGeneration } from '@/service/api/request/Chat/StopChatGeneration';
import { SendMessage } from '@/service/api/request/Chat/SendMessage';

const props = defineProps<{
    chatId: number | null;
}>();

const messages = ref<LLMMessage[]>([]);
const status = ref<string>('loading');
const sending = ref<boolean>(false);
const requestCount = ref<number>(0);
const totalTokens = ref<number>(0);
const contextFill = ref<number>(0);
const polingState = ref<boolean>(false);

const api = createApi();

onMounted(startPoling);
onDeactivated(stopPoling);

function startPoling() {
    polingState.value = true;
    poling();
}

function stopPoling() {
    polingState.value = false;
}

async function stopGeneration() {
    const result = await new StopChatGeneration(props.chatId).call(api);
    if (result.status === 'ok') stopPoling();
}

async function sendMessage(message: string) {
    sending.value = true;
    const result = await new SendMessage(props.chatId, message).call(api);
    sending.value = false;

    if (result.status === 'ok') {
        startPoling();
    }
}

async function poling() {
    const result = await new GetChatState(props.chatId).call(api);

    status.value = result.status;
    messages.value = result.messages;
    totalTokens.value = result.totalTokens;
    contextFill.value = result.contextFill;

    requestCount.value++;

    if (status.value === 'completed') stopPoling();
    if (!polingState.value) return;

    setTimeout(() => poling(), 500);
}
</script>

<template>
    <AgentChat
        :status
        :sending
        :messages
        :requestCount
        :totalTokens
        :contextFill
        :loading="polingState"
        @stop="stopGeneration"
        @sendMessage="sendMessage"
    />
</template>

<style scoped></style>
