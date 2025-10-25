<template>
    <div class="flex h-full flex-col flex-nowrap overflow-hidden rounded-lg border bg-white">
        <!-- Заголовок чата -->
        <div class="border-b bg-gray-100 px-4 py-3">
            <h3 class="text-sm font-medium text-gray-800">История LLM генерации</h3>
        </div>

        <!-- Содержимое чата -->
        <div ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto p-4" @scroll="handleScroll">
            <!-- Сообщения отсутствуют -->
            <div v-if="!visibleMessages || visibleMessages.length === 0" class="flex items-center justify-center py-8">
                <div class="text-center text-gray-500">
                    <div class="text-sm">История LLM пока пуста</div>
                </div>
            </div>

            <!-- Список сообщений -->
            <div v-else class="space-y-4">
                <Message v-for="(message, index) in visibleMessages" :key="index" :message="message" :index="index" />

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3" v-if="status != 'completed'">
                    <div class="flex items-center">
                        <span class="text-xs font-medium flex-grow">Ответ генерируется{{ loadingDots }}</span>
                        <Button size="sm" variant="outline" @click="stopGenerating">Стоп</Button>
                    </div>
                </div>
            </div>
        </div>
        <TaskProgressInfo :tasks="context?.tasks || []" />

        <div class="flex flex-col gap-2 border-t p-4">
            <textarea
                v-model="input"
                :disabled="frozenInput"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                @keydown.enter="handleEnter"
            ></textarea>
            <Button @click="sendMessage" :disabled="frozenInput || !input.trim()">
                <span v-if="sending">Отправка...</span>
                <span v-else>Отправить</span>
            </Button>
            <div v-if="typeof safeContextFill === 'number'" class="flex items-center gap-2">
                <span class="text-xs text-gray-500 whitespace-nowrap">
                    Расход: {{ formattedTotalTokens }}
                </span>
                <LinearProgress :value="safeContextFill" :heightPx="5" class="flex-1" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Message from '@/components/AgentChat/Message.vue';
import TaskProgressInfo from '@/components/AgentChat/TaskProgressInfo.vue';
import type { LLMMessage } from '@/types';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Button from '../ui/button/Button.vue';
import LinearProgress from '@/components/ui/progress/LinearProgress.vue';

interface Props {
    messages?: LLMMessage[];
    loading?: boolean;
    sending?: boolean;
    status?: string;
    requestCount?: number;
    contextFill?: number;
    totalTokens?: number;
    context?: any;
}

const props = withDefaults(defineProps<Props>(), {
    messages: () => [],
    loading: false,
    sending: false,
    contextFill: 0,
    totalTokens: 0,
    context: () => ({}),
});

const emit = defineEmits<{
    (e: 'sendMessage', message: string): void;
    (e: 'stop'): void;
}>();

const input = ref('');

// Отображение точек на основе количества запросов
const loadingDots = computed(() => {
    if (!props.requestCount || props.requestCount === 0) {
        return '';
    }
    
    // Циклический счетчик: 1-4 точки, затем сброс к 1
    const dotCount = ((props.requestCount - 1) % 4) + 1;
    return '.'.repeat(dotCount);
});

function sendMessage() {
    emit('sendMessage', input.value);
    input.value = '';
}

const messagesContainer = ref<HTMLElement>();

// Сообщения, отображаемые в списке (скрываем пустые ответы ассистента)
const visibleMessages = computed(() => {
    const source = props.messages ?? [];
    return source.filter(m => {
        if (m.type !== 'assistant') return true;
        const content = m.message?.content ?? '';
        return String(content).trim().length > 0;
    });
});

// Управление автопрокруткой
const isAutoScrollEnabled = ref(true);
const SCROLL_BOTTOM_THRESHOLD_PX = 16;

// Автоматическая прокрутка к последнему сообщению
async function scrollToBottom(): Promise<void> {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTo({
            top: messagesContainer.value.scrollHeight,
            behavior: 'smooth'
        });
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

function stopGenerating() {
    emit('stop')
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

const safeContextFill = computed(() => {
    const v = Number(props.contextFill);
    if (!isFinite(v)) return 0;
    if (v < 0) return 0;
    if (v > 1) return 1;
    return v;
});


// Человекочитаемый формат числа токенов с пробелами как разделителями тысяч
const formattedTotalTokens = computed(() => {
    const n = Number(props.totalTokens || 0);
    if (!isFinite(n)) return '0';
    return n.toLocaleString('ru-RU');
});

// Обработчик нажатия Enter: отправляет сообщение по одному нажатию Enter,
// а при зажатом Ctrl или Meta (Cmd) позволяет вставлять перенос строки.
function handleEnter(e: KeyboardEvent) {
    // Если поле ввода заблокировано — не отправляем
    if (props.status !== 'completed') {
        return;
    }

    // Если зажат Ctrl или Meta (Cmd) — позволяем вставку новой строки
    if (e.ctrlKey || e.metaKey) {
        return;
    }

    // Для обычного Enter — предотвращаем вставку новой строки и отправляем сообщение
    e.preventDefault();
    sendMessage();
}


// Пр прокрутка при монтировании
onMounted(() => {
    if (props.messages && props.messages.length > 0) {
        scrollToBottom();
    }
    // Инициализируем состояние автопрокрутки в зависимости от позиции
    isAutoScrollEnabled.value = isAtBottom();
});
</script>
