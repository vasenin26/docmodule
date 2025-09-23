<template>
    <div class="flex h-full flex-col flex-nowrap overflow-hidden rounded-lg border bg-white">
        <!-- Заголовок чата -->
        <div class="border-b bg-gray-100 px-4 py-3">
            <h3 class="text-sm font-medium text-gray-800">История LLM генерации</h3>
        </div>

        <!-- Содержимое чата -->
        <div ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto p-4" @scroll="handleScroll">
            <!-- Сообщения отсутствуют -->
            <div v-if="!messages || messages.length === 0" class="flex items-center justify-center py-8">
                <div class="text-center text-gray-500">
                    <div class="text-sm">История LLM пока пуста</div>
                </div>
            </div>

            <!-- Список сообщений -->
            <div v-else class="space-y-4">
                <Message v-for="(message, index) in messages" :key="index" :message="message" :index="index" />

                <div class="rounded-lg p-3 bg-gray-50 border border-gray-200" v-if="status != 'completed'">
                    <span class="text-xs font-medium">
                        Ответ генерируется....
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 border-t p-4">
            <textarea
                v-model="input"
                :disabled="frozenInput"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                @keydown.enter="sendMessage"
            ></textarea>
            <Button @click="sendMessage" :disabled="frozenInput || !input.trim()">
                <span v-if="sending">Отправка...</span>
                <span v-else>Отправить</span>
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import Message from '@/components/AgentChat/Message.vue';
import type { LLMMessage } from '@/types';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Button from '../ui/button/Button.vue';

interface Props {
    messages?: LLMMessage[];
    loading?: boolean;
    sending?: boolean;
    status?: string;
}

const props = withDefaults(defineProps<Props>(), {
    messages: () => [],
    loading: false,
    sending: false,
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

// Управление автопрокруткой
const isAutoScrollEnabled = ref(true);
const SCROLL_BOTTOM_THRESHOLD_PX = 16;

// Автоматическая прокрутка к последнему сообщению
async function scrollToBottom(): Promise<void> {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

function isAtBottom(): boolean {
    if (!messagesContainer.value) return true;
    const el = messagesContainer.value;
    const distanceToBottom = el.scrollHeight - (el.scrollTop + el.clientHeight);
    return distanceToBottom <= SCROLL_BOTTOM_THRESHOLD_PX;
}

function handleScroll() {
    // Включаем автопрокрутку только если пользователь у низа чата
    isAutoScrollEnabled.value = isAtBottom();
}

// Следим за изменениями сообщений для автопрокрутки
watch(
    () => props.messages,
    () => {
        if (props.messages && props.messages.length > 0 && isAutoScrollEnabled.value) {
            scrollToBottom();
        }
    },
    { deep: true },
);

const frozenInput = computed(() => props.status !== 'completed');

// Прокрутка при монтировании
onMounted(() => {
    if (props.messages && props.messages.length > 0) {
        scrollToBottom();
    }
    // Инициализируем состояние автопрокрутки в зависимости от позиции
    isAutoScrollEnabled.value = isAtBottom();
});
</script>
