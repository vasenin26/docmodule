<template>
    <div class="space-y-6">
        <!-- Поиск и фильтры -->
        <Card>
            <CardContent class="p-4">
                <form @submit.prevent="search" class="flex gap-4">
                    <div class="flex-1">
                        <Input v-model="searchQuery" placeholder="Поиск по названию или содержимому..." @keyup.enter="search" />
                    </div>
                    <Button type="submit" variant="outline"> Найти </Button>
                    <Button type="button" variant="outline" @click="clearSearch"> Очистить </Button>
                </form>
            </CardContent>
        </Card>

        <!-- Таблица страниц -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="p-4 text-left font-medium">Название</th>
                                <th v-if="!project" class="p-4 text-left font-medium">Проект</th>
                                <th class="p-4 text-left font-medium">Создатель</th>
                                <th class="p-4 text-left font-medium">Дата создания</th>
                                <th class="p-4 text-left font-medium">Дочерние страницы</th>
                                <th class="p-4 text-left font-medium">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="page in pagesData.data" :key="page.id" class="border-b">
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ page.current_version?.title }}</span>
                                        <span v-if="page.children && page.children.length > 0" class="text-xs text-muted-foreground">
                                            ({{ page.children.length }} дочерних)
                                        </span>
                                        <!-- Индикатор черновика -->
                                        <span v-if="page.hasActiveDraft" class="rounded bg-yellow-100 px-2 py-1 text-xs text-yellow-800">
                                            Черновик
                                        </span>
                                    </div>
                                </td>
                                <td v-if="!project" class="p-4 text-sm">
                                    <div v-if="page.project">
                                        <Link :href="route('projects.show', page.project.id)" class="text-blue-600 hover:underline">
                                            {{ page.project.title }}
                                        </Link>
                                    </div>
                                    <div v-else class="text-muted-foreground">Без проекта</div>
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{ page.creator?.name }}
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{ formatDate(page.created_at) }}
                                </td>
                                <td class="p-4">
                                    <div v-if="page.children && page.children.length > 0" class="space-y-1">
                                        <div v-for="child in page.children.slice(0, 3)" :key="child.id" class="text-sm">
                                            {{ child.title }}
                                        </div>
                                        <div v-if="page.children.length > 3" class="text-xs text-muted-foreground">
                                            и еще {{ page.children.length - 3 }}...
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">Нет</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="props.project ? route('pages.show', [page.id]) : route('pages.show', page.id)">
                                                Просмотр
                                            </Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="props.project ? route('pages.edit', [page.id]) : route('pages.edit', [page.id])">
                                                Редактировать
                                            </Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="route('pages.versions', page.id)"> Версии </Link>
                                        </Button>

                                        <!-- Удаление: показываем кнопку только если есть право -->
                                        <Button v-if="page.canDelete" size="sm" variant="destructive" @click.prevent="confirmDelete(page)"> Удалить </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pagesData.data.length === 0">
                                <td :colspan="project ? 5 : 6" class="p-8 text-center text-muted-foreground">
                                    <div v-if="searchQuery">Страницы не найдены по запросу "{{ searchQuery }}"</div>
                                    <div v-else>Страницы не найдены</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Пагинация -->
        <div v-if="pagesData.links && pagesData.links.length > 3" class="flex justify-center">
            <nav class="flex items-center gap-1">
                <Link
                    v-for="link in pagesData.links"
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

        <!-- Модальное подтверждение удаления -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h3 class="text-lg font-medium">Подтвердите удаление</h3>
                <p class="mt-2 text-sm text-muted-foreground">Вы действительно хотите удалить страницу "{{ deletingPage?.current_version?.title ?? deletingPage?.title }}"? Это действие можно отменить, восстановив страницу.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" @click="showDeleteModal = false">Отмена</Button>
                    <Button variant="destructive" :loading="isDeleting" @click="performDelete">Удалить</Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Input from '@/components/ui/input/Input.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

interface Project {
    id: number;
    title: string;
    owner_id: number;
    owner: User;
    created_at: string;
    updated_at: string;
    pages?: Page[];
    repositories?: Repository[];
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Repository {
    id: number;
    url: string;
    options: any;
    created_at: string;
    updated_at: string;
}

interface Page {
    id: number;
    title: string;
    content: string;
    created_at: string;
    created_by: number;
    creator: User;
    project?: Project;
    children: Page[];
    hasActiveDraft?: boolean;
    currentDraft?: {
        id: number;
        version: number;
    };
    current_version: Version
}

type Version = {
    id: number
    title: string
    content: string
}

interface PagesData {
    data: Page[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    pages: PagesData;
    project?: Project;
    filters: {
        search?: string;
        parent_id?: number;
    };
    showCreateButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showCreateButton: false,
});

// Локальная копия pages для управления удалением без изменения prop напрямую
const pagesData = ref(JSON.parse(JSON.stringify(props.pages)) as PagesData);

const searchQuery = ref(props.filters.search || '');
const showDeleteModal = ref(false);
const deletingPage = ref(null as null | any);
const isDeleting = ref(false);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const search = () => {
    const searchRoute = props.project ? route('projects.pages.index', props.project.id) : route('pages.index');

    router.get(
        searchRoute,
        {
            search: searchQuery.value,
            parent_id: props.filters.parent_id,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    const searchRoute = props.project ? route('projects.pages.index', props.project.id) : route('pages.index');

    router.get(
        searchRoute,
        {
            parent_id: props.filters.parent_id,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

function confirmDelete(page: Page) {
    deletingPage.value = page;
    showDeleteModal.value = true;
}

async function performDelete() {
    if (!deletingPage.value) return;
    isDeleting.value = true;

    try {
        const projectId = props.project?.id;
        const url = projectId
            ? route('projects.pages.destroy', [projectId, deletingPage.value.id])
            : route('pages.destroy', deletingPage.value.id);

        await axios.delete(url);

        // Удаляем из локального списка
        const idx = pagesData.value.data.findIndex(p => p.id === deletingPage.value.id);
        if (idx !== -1) {
            pagesData.value.data.splice(idx, 1);
        }

        // Можно показать уведомление при необходимости
    } catch (err: any) {
        if (err.response && err.response.status === 403) {
            alert('У вас нет прав на удаление этой страницы');
        } else if (err.response && err.response.status === 404) {
            alert('Страница не найдена');
        } else {
            alert('Произошла ошибка при удалении страницы');
        }
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
        deletingPage.value = null;
    }
}

</script>