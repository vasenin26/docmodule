<script setup lang="ts">
import { onMounted, onDeactivated } from 'vue';
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import { useChatAgent } from '@/composables/useChatAgent';

const props = defineProps<{
    chatId: number | null;
}>();

const {
    status,
    sending,
    messages,
    requestCount,
    totalTokens,
    contextFill,
    polingState,
    stopGeneration,
    sendMessage,
    stopPoling,
    startPoling
} = useChatAgent(props.chatId);

onMounted(startPoling);
onDeactivated(stopPoling);

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
