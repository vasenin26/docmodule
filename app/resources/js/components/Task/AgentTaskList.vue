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
                                <th class="p-4 text-left font-medium">Handler</th>
                                <th class="p-4 text-left font-medium">Опции</th>
                                <th class="p-4 text-left font-medium">Проект</th>
                                <th class="p-4 text-left font-medium">Создатель</th>
                                <th class="p-4 text-left font-medium">Чат</th>
                                <th class="p-4 text-left font-medium">Агент</th>
                                <th class="p-4 text-left font-medium">UUID</th>
                                <th class="p-4 text-left font-medium">Модель</th>
                                <th class="p-4 text-left font-medium">Требует результат</th>
                                <th class="p-4 text-left font-medium">Контекст</th>
                                <th class="p-4 text-left font-medium">Timeout</th>
                                <th class="p-4 text-left font-medium">Резервирование</th>
                                <th class="p-4 text-left font-medium">Статус</th>
                                <th class="p-4 text-left font-medium">Создано</th>
                                <th class="p-4 text-left font-medium">Обновлено</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in tasks.data" :key="task.id" class="border-b">
                                <td class="p-4">#{{ task.id }}</td>
                                <td class="p-4">{{ task.type }}</td>
                                <td class="p-4 text-xs break-all">{{ task.handler }}</td>
                                <td class="p-4 text-xs text-muted-foreground">{{ shortJson(task.handler_options) }}</td>
                                <td class="p-4">{{ task.project?.title ?? '—' }}</td>
                                <td class="p-4">{{ task.creator?.name ?? '—' }}</td>
                                <td class="p-4">{{ task.chat_id ?? '—' }}</td>
                                <td class="p-4">{{ task.agent_id ?? '—' }}</td>
                                <td class="p-4">{{ task.agent_uuid ?? '—' }}</td>
                                <td class="p-4">{{ task.agent_model ?? '—' }}</td>
                                <td class="p-4">{{ task.result_required ? 'Да' : 'Нет' }}</td>
                                <td class="p-4">{{ task.context_id ?? '—' }}</td>
                                <td class="p-4">{{ task.timeout ?? '—' }}</td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    <div>at: {{ formatDateTime(task.reserved_at) || '—' }}</div>
                                    <div>until: {{ formatDateTime(task.reserved_until) || '—' }}</div>
                                    <div>sec: {{ task.reserved_seconds ?? '—' }}</div>
                                </td>
                                <td class="p-4">
                                    <AgentTaskStatusBadge :status="task.status" />
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">{{ formatDateTime(task.created_at) }}</td>
                                <td class="p-4 text-sm text-muted-foreground">{{ formatDateTime(task.updated_at) }}</td>
                            </tr>
                            <tr v-if="tasks.data.length === 0">
                                <td colspan="17" class="p-8 text-center text-muted-foreground">
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
                    :href="link.url"
                    :class="[
                        'rounded-md px-3 py-2 text-sm',
                        link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Input from '@/components/ui/input/Input.vue';
import AgentTaskStatusBadge from '@/components/Task/AgentTaskStatusBadge.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface AgentTaskListItem {
    id: number;
    type: string;
    handler: string;
    handler_options: Record<string, unknown> | null;
    project?: { id: number; title: string } | null;
    creator?: { id: number; name: string } | null;
    chat_id?: number | null;
    llm_chat?: { id: number } | null;
    status: string;
    agent_id?: number | null;
    agent_uuid?: string | null;
    agent_model?: string | null;
    result_required: boolean;
    context_id?: string | null;
    timeout?: number | null;
    reserved_at?: string | null;
    reserved_until?: string | null;
    reserved_seconds?: number | null;
    created_at: string;
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

const formatDateTime = (date: string | null | undefined) => {
    if (!date) return '';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('ru-RU');
};

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
</script>


