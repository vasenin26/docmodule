<template>
    <div class="space-y-6">
        <!-- Поиск и фильтры -->
        <Card>
            <CardContent class="p-4">
                <form @submit.prevent="search" class="flex gap-4">
                    <div class="flex-1">
                        <Input v-model="searchQuery" placeholder="Поиск по handler/agent/id/проекту/создателю..." @keyup.enter="search" />
                    </div>
                    <select v-model="statusFilter" class="w-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Все статусы</option>
                        <option value="wait">Ожидание</option>
                        <option value="processing">В работе</option>
                        <option value="success">Успех</option>
                        <option value="failed">Ошибка</option>
                    </select>
                    <Button type="submit" variant="outline">Найти</Button>
                    <Button type="button" variant="outline" @click="clearSearch">Очистить</Button>
                </form>
            </CardContent>
        </Card>

        <!-- Таблица задач агентов -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="p-4 text-left font-medium">ID</th>
                                <th class="p-4 text-left font-medium">Тип</th>
                                <th class="p-4 text-left font-medium">Создатель</th>
                                <th class="p-4 text-left font-medium">Чат</th>
                                <th class="p-4 text-left font-medium">Контекст</th>
                                <th class="p-4 text-left font-medium">Модель</th>
                                <th class="p-4 text-left font-medium">Агент назначен</th>
                                <th class="p-4 text-left font-medium">Резервирование</th>
                                <th class="p-4 text-left font-medium">Расход</th>
                                <th class="p-4 text-left font-medium">Статус</th>
                                <th class="p-4 text-left font-medium">Обновлено</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in tasks.data" :key="task.id" class="border-b">
                                <td class="p-4">#{{ task.id }}</td>
                                <td class="p-4">
                                    {{ task.type }}
                                    <Button
                                        v-if="task.has_handler"
                                        variant="ghost"
                                        size="sm"
                                        @click="navigateToTargetResource(task.id)"
                                        class="h-8 w-8 p-0"
                                        :disabled="isLoadingTargetResource"
                                    >
                                        <ArrowRight class="h-4 w-4" />
                                    </Button>
                                </td>
                                <td class="p-4">{{ task.creator?.name ?? '—' }}</td>
                                <td class="p-4">
                                    <div v-if="task.chat_id" class="flex items-center gap-2">
                                        <span>{{ task.chat_id }}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openChatViewer(task.id, task.chat_id)"
                                            class="h-6 w-6 p-0"
                                        >
                                            <MessageSquare class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="p-4">{{ task.context_id ?? '—' }}</td>
                                <td class="p-4">{{ task.agent_model ?? '—' }}</td>
                                <td class="p-4">{{ task.agent_assigned ? 'Да' : 'Нет' }}</td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    <div>at: {{ formatDateTime(task.reserved_at) || '—' }}</div>
                                    <div>until: {{ formatDateTime(task.reserved_until) || '—' }}</div>
                                    <div>sec: {{ task.reserved_seconds ?? '—' }}</div>
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    <div>tx: {{ task.prompt_tokens ?? '—' }}</div>
                                    <div>rx: {{ task.completion_tokens ?? '—' }}</div>
                                </td>
                                <td class="p-4">
                                    <AgentTaskStatusBadge :status="task.status" />
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">{{ formatDateTime(task.updated_at) }}</td>
                            </tr>
                            <tr v-if="tasks.data.length === 0">
                                <td colspan="12" class="p-8 text-center text-muted-foreground">
                                    <div v-if="searchQuery || statusFilter">Задачи не найдены по заданным критериям</div>
                                    <div v-else>Задачи не найдены</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Пагинация (совместима с форматом links) -->
        <div v-if="tasks.links && tasks.links.length > 3" class="flex justify-center">
            <nav class="flex items-center gap-1">
                <Link
                    v-for="link in tasks.links"
                    :key="link.label"
                    :href="link.url ?? ''"
                    :class="[
                        'rounded-md px-3 py-2 text-sm',
                        link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>

        <!-- Модальное окно для просмотра чата -->
        <ChatViewer
            v-if="showChatViewer"
            :task-id="selectedTaskId"
            :chat-id="selectedChatId"
            @close="closeChatViewer"
        />
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Input from '@/components/ui/input/Input.vue';
import AgentTaskStatusBadge from './AgentTaskStatusBadge.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ChatViewer from './ChatViewer.vue';
import { MessageSquare, ArrowRight } from 'lucide-vue-next';

interface AgentTaskListItem {
    id: number;
    type: string;
    creator?: { id: number; name: string } | null;
    chat_id?: number | null;
    context_id?: string | null;
    agent_model?: string | null;
    agent_assigned: boolean;
    has_handler: boolean;
    reserved_at?: string | null;
    reserved_until?: string | null;
    reserved_seconds?: number | null;
    status: string;
    prompt_tokens?: number | null;
    completion_tokens?: number | null;
    total_tokens?: number | null;
    updated_at: string;
}

interface Props {
    tasks: {
        data: AgentTaskListItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    project?: { id: number; title: string } | null;
    filters: { search?: string | null; status?: string | null };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Состояние для модального окна чата
const selectedTaskId = ref<number | null>(null);
const selectedChatId = ref<number | null>(null);
const showChatViewer = ref(false);

// Состояние для загрузки целевого ресурса
const isLoadingTargetResource = ref(false);

const formatDateTime = (date: string | null | undefined) => {
    if (!date) return '';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('ru-RU');
};

// shortJson no longer used in the table but kept if needed elsewhere
const shortJson = (value: unknown) => {
    try {
        const json = JSON.stringify(value ?? {}, null, 0);
        return json.length > 60 ? json.slice(0, 60) + '…' : json;
    } catch (e) {
        return '';
    }
};

const search = () => {
    const searchRoute = props.project
        ? route('projects.agent-tasks.index', props.project.id)
        : null; // только project-scoped

    if (!searchRoute) return;

    router.get(
        searchRoute,
        {
            search: searchQuery.value,
            status: statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    const searchRoute = props.project
        ? route('projects.agent-tasks.index', props.project.id)
        : null;

    if (!searchRoute) return;

    router.get(
        searchRoute,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const openChatViewer = (taskId: number, chatId: number) => {
    selectedTaskId.value = taskId;
    selectedChatId.value = chatId;
    showChatViewer.value = true;
};

const closeChatViewer = () => {
    showChatViewer.value = false;
    selectedTaskId.value = null;
    selectedChatId.value = null;
};

const navigateToTargetResource = async (taskId: number) => {
    if (isLoadingTargetResource.value) return;

    isLoadingTargetResource.value = true;

    try {
        const response = await fetch(`/agent-tasks/${taskId}/target-resource`);
        const data = await response.json();

        if (response.ok && data.url) {
            // Перенаправляем на целевую страницу
            window.location.href = data.url;
        } else {
            // Показываем ошибку
            alert(data.error || 'Ресурс не найден');
        }
    } catch (error) {
        console.error('Error fetching target resource:', error);
        alert('Ошибка при определении целевого ресурса');
    } finally {
        isLoadingTargetResource.value = false;
    }
};
</script>


