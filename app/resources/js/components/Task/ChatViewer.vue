<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[80vh] flex flex-col">
            <!-- Заголовок -->
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold">Содержимое чата</h3>
                <Button variant="ghost" size="sm" @click="$emit('close')">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Контент -->
            <div class="flex-1 overflow-hidden">
                <!-- Индикатор загрузки -->
                <div v-if="loading" class="flex items-center justify-center p-8">
                    <div class="flex items-center space-x-2">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                        <span>Загрузка содержимого чата...</span>
                    </div>
                </div>

                <!-- Ошибка -->
                <div v-else-if="error" class="p-8 text-center">
                    <div class="text-red-600 mb-4">
                        <AlertCircle class="h-8 w-8 mx-auto mb-2" />
                        <p class="font-medium">Ошибка загрузки</p>
                    </div>
                    <p class="text-muted-foreground mb-4">{{ error }}</p>
                    <Button @click="loadChatContent" variant="outline">
                        Попробовать снова
                    </Button>
                </div>

                <!-- Содержимое чата -->
                <div v-else-if="chatContent" class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-muted-foreground">
                            Chat ID: {{ chatId }}
                        </div>
                        <div class="flex gap-2">
                            <Button @click="toggleViewMode" variant="outline" size="sm">
                                <Eye class="h-4 w-4 mr-2" />
                                {{ viewMode === 'json' ? 'Текст' : 'JSON' }}
                            </Button>
                            <Button @click="copyToClipboard" variant="outline" size="sm">
                                <Copy class="h-4 w-4 mr-2" />
                                Копировать
                            </Button>
                        </div>
                    </div>

                    <div class="bg-muted rounded-lg p-4 max-h-96 overflow-auto">
                        <!-- JSON Viewer -->
                        <JsonViewer
                            v-if="viewMode === 'json' && parsedContent"
                            :value="parsedContent"
                            copyable
                            boxed
                            sort
                            theme="light"
                            @onKeyClick="handleKeyClick"
                        />

                        <!-- Текстовый вид -->
                        <pre v-else class="text-sm whitespace-pre-wrap">{{ formattedContent }}</pre>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else class="p-8 text-center text-muted-foreground">
                    <MessageSquare class="h-8 w-8 mx-auto mb-2" />
                    <p>Содержимое чата не найдено</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { X, AlertCircle, Copy, MessageSquare, Eye } from 'lucide-vue-next';
import { JsonViewer } from 'vue3-json-viewer';
import 'vue3-json-viewer/dist/vue3-json-viewer.css';
import { createApi } from '@/services/api/Api';
import { AgentTaskChatContentRequest } from '@/services/api/request/Task/AgentTaskChatContentRequest';

interface Props {
    taskId: number | null;
    chatId: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const loading = ref(false);
const error = ref<string | null>(null);
const chatContent = ref<string | null>(null);
const viewMode = ref<'text' | 'json'>('text');

const formattedContent = computed(() => {
    if (!chatContent.value) return '';

    try {
        const parsed = JSON.parse(chatContent.value);
        return JSON.stringify(parsed, null, 2);
    } catch {
        return chatContent.value;
    }
});

const parsedContent = computed(() => {
    if (!chatContent.value) return null;

    try {
        return JSON.parse(chatContent.value);
    } catch {
        return null;
    }
});

const loadChatContent = async () => {
    if (!props.taskId) return;

    loading.value = true;
    error.value = null;

    try {
        const api = createApi();
        const request = new AgentTaskChatContentRequest(props.taskId);
        const response = await request.call(api);
        chatContent.value = response.content;
    } catch (err: any) {
        console.error('Failed to load chat content:', err);
        error.value = err.payload?.error || 'Не удалось загрузить содержимое чата';
    } finally {
        loading.value = false;
    }
};

const copyToClipboard = async () => {
    if (!chatContent.value) return;

    try {
        await navigator.clipboard.writeText(chatContent.value);
        // Можно добавить уведомление об успешном копировании
    } catch (err) {
        console.error('Failed to copy to clipboard:', err);
    }
};

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'text' ? 'json' : 'text';
};

const handleKeyClick = (keyName: string) => {
    console.log(`Клик по ключу: ${keyName}`);
};

onMounted(() => {
    loadChatContent();
});
</script>
