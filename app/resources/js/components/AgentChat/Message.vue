<script setup lang="ts">
import type { LLMMessage } from '@/types';
import UserMessage from './messages/UserMessage.vue';
import AssistantMessage from './messages/AssistantMessage.vue';
import SystemMessage from './messages/SystemMessage.vue';
import ToolMessage from './messages/ToolMessage.vue';
import GitFileMessage from './messages/GitFileMessage.vue';
import PageVersionMessage from './messages/PageVersionMessage.vue';
import InfoMessage from './messages/InfoMessage.vue';
import ServiceMessage from './messages/ServiceMessage.vue';
import UnknownMessage from './messages/UnknownMessage.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

</script>

<template>
    <UserMessage 
        v-if="message.type === 'user'" 
        :message="message" 
        :index="index" 
    />
    <AssistantMessage 
        v-else-if="message.type === 'assistant'" 
        :message="message" 
        :index="index" 
    />
    <SystemMessage 
        v-else-if="message.type === 'system'" 
        :message="message" 
        :index="index" 
    />
    <ToolMessage 
        v-else-if="message.type === 'tool'" 
        :message="message" 
        :index="index" 
    />
    <GitFileMessage 
        v-else-if="message.type === 'git-file'" 
        :message="message" 
        :index="index" 
    />
    <PageVersionMessage 
        v-else-if="message.type === 'page-version'" 
        :message="message" 
        :index="index" 
    />
    <InfoMessage 
        v-else-if="message.type === 'info'" 
        :message="message" 
        :index="index" 
    />
    <ServiceMessage 
        v-else-if="message.type === 'service'" 
        :message="message" 
        :index="index" 
    />
    <UnknownMessage 
        v-else 
        :message="message" 
        :index="index" 
    />
</template>

