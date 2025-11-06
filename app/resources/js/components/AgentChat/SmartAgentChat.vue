<script setup lang="ts">
import { onMounted, onDeactivated, watch } from 'vue';
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import { useChatAgent } from '@/composables/useChatAgent';

const props = defineProps<{
    chatId: number | null;
    frozen?: boolean
}>();

const { status, sending, messages, requestCount, totalTokens, contextFill, polingState, stopGeneration, sendMessage, stopPoling, startPoling } =
    useChatAgent(props.chatId);

onMounted(startPoling);
onDeactivated(stopPoling);

const emit = defineEmits<{
    (e: 'updated'): void;
}>();

let messageCounter = 0;

watch(messages, () => {
    if (messages.value.length !== messageCounter) {
        messageCounter = messages.value.length;

        emit('updated');
    }
});
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
