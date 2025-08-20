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
                            <tr v-for="page in pages.data" :key="page.id" class="border-b">
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
                                            <Link :href="route('pages.show', page.id)"> Просмотр </Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="route('pages.edit', [page.id])">
                                                Редактировать
                                            </Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="route('pages.versions', page.id)"> Версии </Link>
                                        </Button>
                                        <Button size="sm" variant="destructive" @click="deletePage(page.id)"> Удалить </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pages.data.length === 0">
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
        <div v-if="pages.links && pages.links.length > 3" class="flex justify-center">
            <nav class="flex items-center gap-1">
                <Link
                    v-for="link in pages.links"
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
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const searchQuery = ref(props.filters.search || '');

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

const deletePage = (pageId: number) => {
    if (confirm('Вы уверены, что хотите удалить эту страницу?')) {
        router.delete(route('pages.destroy', pageId));
    }
};
</script>
