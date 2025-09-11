<template>
    <div class="flex h-full flex-col flex-nowrap overflow-hidden rounded-lg border bg-white">
        <!-- Заголовок чата -->
        <div class="border-b bg-gray-100 px-4 py-3">
            <h3 class="text-sm font-medium text-gray-800">История LLM генерации</h3>
        </div>

        <!-- Содержимое чата -->
        <div ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto p-4">
            <!-- Состояние загрузки -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2 text-gray-500">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-600"></div>
                    <span class="text-sm">Генерация...</span>
                </div>
            </div>

            <!-- Сообщения отсутствуют -->
            <div v-else-if="!messages || messages.length === 0" class="flex items-center justify-center py-8">
                <div class="text-center text-gray-500">
                    <div class="text-sm">История LLM пока пуста</div>
                    <div class="mt-1 text-xs">Сообщения появятся после генерации</div>
                </div>
            </div>

            <!-- Список сообщений -->
            <div v-else class="space-y-4">
                <Message v-for="(message, index) in messages" :key="index" :message="message" :index="index" />
            </div>
        </div>

        <div class="flex flex-col gap-2 p-4 border-t">
            <textarea
                v-model="input"
                :disabled="sending"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                @keydown.enter="sendMessage"
            ></textarea>
            <Button @click="sendMessage" :disabled="sending || !input.trim()">
                <span v-if="sending">Отправка...</span>
                <span v-else>Отправить</span>
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import Message from '@/components/AgentChat/Message.vue';
import type { LLMMessage } from '@/types';
import { nextTick, onMounted, ref, watch } from 'vue';
import Button from '../ui/button/Button.vue';

interface Props {
    messages?: LLMMessage[];
    loading?: boolean;
    sending?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    messages: () => [],
    loading: false,
    sending: false
});

const emit = defineEmits<{
    (e: 'sendMessage', message: string): void;
}>();

const input = ref('');

function sendMessage() {
    emit('sendMessage', input.value);
    input.value = '';
}

const messagesContainer = ref<HTMLElement>();

// Автоматическая прокрутка к последнему сообщению
async function scrollToBottom(): Promise<void> {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

// Следим за изменениями сообщений для автопрокрутки
watch(
    () => props.messages,
    () => {
        if (props.messages && props.messages.length > 0) {
            scrollToBottom();
        }
    },
    { deep: true }
);

// Прокрутка при монтировании
onMounted(() => {
    if (props.messages && props.messages.length > 0) {
        scrollToBottom();
    }
});
</script>
