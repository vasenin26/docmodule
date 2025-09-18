<template>
    <AppLayout :title="`Актуализация: ${page.title}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="`Актуализация: ${page.title}`" />
                    <p class="mt-1 text-sm text-muted-foreground">
                        Запущена {{ formatDate(actualization.created_at) }} пользователем {{ actualization.created_by.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('pages.show', page.id)"> Назад к странице </Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl space-y-6">
            <!-- Статус актуализации -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <RefreshCw :class="{ 'animate-spin': actualization.status === 'processing' }" class="h-5 w-5" />
                        Статус актуализации
                    </CardTitle>
                    <CardDescription> Информация о процессе актуализации документации </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium">Статус</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-blue-100 text-blue-800': actualization.status === 'pending',
                                        'bg-yellow-100 text-yellow-800': actualization.status === 'processing',
                                        'bg-green-100 text-green-800': actualization.status === 'completed',
                                        'bg-red-100 text-red-800': actualization.status === 'failed',
                                    }"
                                >
                                    {{ getStatusText(actualization.status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Запущена</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ formatDate(actualization.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Обновлена</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ formatDate(actualization.updated_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Инициатор</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ actualization.created_by.name }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Обновленное содержимое -->
            <Card>
                <CardHeader>
                    <CardTitle>Обновленное содержимое</CardTitle>
                    <CardDescription> Результат актуализации документации на основе прикрепленных файлов </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="prose max-w-none">
                        <MarkdownRenderer :content="page.content" />
                    </div>
                </CardContent>
            </Card>

            <!-- Прикрепленные файлы -->
            <Card v-if="page.project_files && page.project_files.length > 0">
                <CardHeader>
                    <CardTitle>Проанализированные файлы</CardTitle>
                    <CardDescription> Файлы, которые были использованы для актуализации документации </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-for="a in page.project_files" :key="a.id" class="flex items-center gap-3 rounded-lg border p-3">
                            <FileIcon class="h-4 w-4 flex-shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="block truncate text-sm font-medium hover:underline">
                                    {{ getFileName(a.url) }}
                                </a>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ a.url }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- LLM чат (если есть) -->
            <Card v-if="chat">
                <CardHeader>
                    <CardTitle>Детали обработки</CardTitle>
                    <CardDescription> Информация о работе ИИ при актуализации </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="mb-4 grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ chat.prompt_tokens || 0 }}</p>
                            <p class="text-sm text-muted-foreground">Промпт токены</p>
                        </div>
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ chat.completion_tokens || 0 }}</p>
                            <p class="text-sm text-muted-foreground">Ответ токены</p>
                        </div>
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ chat.total_tokens || 0 }}</p>
                            <p class="text-sm text-muted-foreground">Всего токенов</p>
                        </div>
                    </div>

                    <!-- Показываем сообщения, если они есть -->
                    <div v-if="chat.messages && chat.messages.length > 0" class="space-y-4">
                        <h4 class="text-lg font-semibold">Диалог с ИИ</h4>
                        <div
                            v-for="(message, index) in chat.messages"
                            :key="index"
                            class="rounded-lg p-4"
                            :class="{
                                'border-l-4 border-blue-500 bg-blue-50': message.role === 'user',
                                'border-l-4 border-green-500 bg-green-50': message.role === 'assistant',
                                'border-l-4 border-gray-500 bg-gray-50': message.role === 'system',
                            }"
                        >
                            <div class="mb-2 flex items-center gap-2">
                                <span
                                    class="text-xs font-medium tracking-wide uppercase"
                                    :class="{
                                        'text-blue-600': message.role === 'user',
                                        'text-green-600': message.role === 'assistant',
                                        'text-gray-600': message.role === 'system',
                                    }"
                                >
                                    {{ getRoleText(message.role) }}
                                </span>
                            </div>
                            <div class="prose prose-sm max-w-none">
                                <pre class="text-sm whitespace-pre-wrap">{{ message.content }}</pre>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { FileIcon, RefreshCw } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Page {
    id: number;
    title: string;
    content: string;
    project_files?: { id: number; url: string; description?: string | null }[];
}

interface ChatMessage {
    role: 'user' | 'assistant' | 'system';
    content: string;
}

interface Chat {
    id: number;
    messages: ChatMessage[];
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
}

interface Actualization {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    created_at: string;
    updated_at: string;
    created_by: User;
}

const props = defineProps<{
    actualization: Actualization;
    page: Page;
    chat?: Chat;
}>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusText = (status: string) => {
    const statusMap = {
        pending: 'Ожидает обработки',
        processing: 'Обрабатывается',
        completed: 'Завершена',
        failed: 'Ошибка',
    };
    return statusMap[status] || status;
};

const getRoleText = (role: string) => {
    const roleMap = {
        user: 'Пользователь',
        assistant: 'ИИ-Ассистент',
        system: 'Система',
    };
    return roleMap[role] || role;
};

const getFileName = (url: string): string => {
    try {
        const urlObj = new URL(url);
        const pathParts = urlObj.pathname.split('/');
        return pathParts[pathParts.length - 1] || 'Файл';
    } catch {
        return 'Файл';
    }
};
</script>
