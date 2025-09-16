<template>
    <div class="space-y-6">
        <!-- Поиск и фильтры -->
        <Card>
            <CardContent class="p-4">
                <div class="flex justify-between mb-2" v-if="props.project">
                    <div></div>
                    <Button type="button" @click="createTask">Создать</Button>
                </div>
                <form @submit.prevent="search" class="flex gap-4">
                    <div class="flex-1">
                        <Input v-model="searchQuery" placeholder="Поиск по названию страницы..." @keyup.enter="search" />
                    </div>
                    <select v-model="statusFilter" class="w-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Все статусы</option>
                        <option value="pending">Ожидает</option>
                        <option value="generating">Генерируется</option>
                        <option value="completed">Завершена</option>
                        <option value="failed">Ошибка</option>
                    </select>
                    <Button type="submit" variant="outline">Найти</Button>
                    <Button type="button" variant="outline" @click="clearSearch">Очистить</Button>
                </form>
            </CardContent>
        </Card>

        <!-- Таблица задач -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="p-4 text-left font-medium">ID</th>
                                <th class="p-4 text-left font-medium">Заголовок страницы</th>
                                <th class="p-4 text-left font-medium">Статус</th>
                                <th class="p-4 text-left font-medium">Дата создания</th>
                                <th class="p-4 text-left font-medium">Создатель</th>
                                <th class="p-4 text-left font-medium">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in tasks.data" :key="task.id" class="border-b">
                                <td class="p-4">
                                    <span class="font-medium">#{{ task.id }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ task.pageVersion?.page?.title ?? 'Без страницы' }}</span>
                                        <span class="text-sm text-muted-foreground">
                                            {{ truncateContent(task.pageVersion?.page?.content) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <TaskStatusBadge :status="task.generation_status" />
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{ formatDate(task.created_at) }}
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{ task.creator?.name }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="route('tasks.show', task.id)">Открыть</Link>
                                        </Button>
                                        <Button 
                                            size="sm" 
                                            variant="destructive" 
                                            @click="deleteTask(task.id)"
                                            :disabled="!canDelete(task)"
                                        >
                                            Удалить
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tasks.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-muted-foreground">
                                    <div v-if="searchQuery || statusFilter">Задачи не найдены по заданным критериям</div>
                                    <div v-else>Задачи не найдены</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Пагинация -->
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
import TaskStatusBadge from '@/components/Task/TaskStatusBadge.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface TaskListItem {
    id: number;
    generation_status: string;
    created_at: string;
    pageVersion: {
        page: {
            title: string;
            content: string;
        };
    };
    creator: {
        name: string;
    };
}

interface Props {
    tasks: {
        data: TaskListItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    project?: {
        id: number;
        title: string;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const truncateContent = (content: string) => {
    if (!content) return '';
    return content.length > 100 ? content.substring(0, 100) + '...' : content;
};

const search = () => {
    const searchRoute = props.project ? route('projects.tasks.index', props.project.id) : route('tasks.index');

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
    const searchRoute = props.project ? route('projects.tasks.index', props.project.id) : route('tasks.index');

    router.get(
        searchRoute,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const deleteTask = (taskId: number) => {
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        const deleteRoute = props.project ? route('projects.tasks.destroy', [props.project.id, taskId]) : route('tasks.destroy', taskId);
        router.delete(deleteRoute);
    }
};

const canDelete = (task: TaskListItem) => {
    // Проверка прав доступа - только создатель может удалить задачу
    // Это должно быть реализовано через проверку текущего пользователя
    return true; // Заглушка, нужно реализовать проверку
};

const createTask = () => {
    if (!props.project) return;
    router.post(route('projects.tasks.store', props.project.id));
};
</script>
