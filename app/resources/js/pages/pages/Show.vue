<template>
    <AppLayout :title="page.title || 'Без названия'">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="page.title || 'Без названия'" />
                    <p class="mt-1 text-sm text-muted-foreground">Создано {{ formatDate(page.created_at) }}
                        пользователем {{ page.creator?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="canCreateTask" @click="createTask" variant="default"> Создать задачу</Button>

                    <Button>
                        <Link :href="route('pages.edit', page.id)">
                            Редактировать
                        </Link>
                    </Button>

                    <ActualizationButton
                        :page-id="page.id"
                        :can-actualize="canActualize"
                    />

                    <Button as-child variant="outline">
                        <Link :href="route('pages.versions', page.id)"> Версии</Link>
                    </Button>
                    <PageListButton :page="page" />
                </div>
            </div>
        </template>


        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Информация о черновике -->
                <DraftInfo v-if="page.currentDraft" :draft="page.currentDraft" :page-id="page.id" />

                <!-- Статус актуализации -->
                <ActualizationStatus
                    v-if="actualizationStatus"
                    :actualization-status="actualizationStatus"
                    :has-active-actualization="hasActiveActualization"
                    :status-text="statusText"
                    :status-color="statusColor"
                    :can-cancel-actualization="canCancelActualization"
                    :on-cancel-actualization="cancelActualization"
                />

                <!-- Родительская страница -->
                <div v-if="page.parent" class="rounded-lg bg-muted/50 p-4">
                    <p class="mb-2 text-sm text-muted-foreground">Родительская страница:</p>
                    <Link :href="route('pages.show', page.parent.id)" class="font-medium hover:underline">
                        {{ page.parent.current_version.title }}
                    </Link>
                </div>

                <!-- Содержимое страницы -->
                <Card>
                    <CardContent class="p-6">
                        <div v-if="page.content">
                            <MarkdownRenderer :content="page.content" />
                        </div>
                        <div v-else class="py-8 text-center text-muted-foreground">Содержимое страницы отсутствует</div>
                    </CardContent>
                </Card>

                <!-- Описания задач -->
                <Card v-if="page.diffDescriptions && page.diffDescriptions.length > 0">
                    <CardHeader>
                        <CardTitle>Связанные задачи</CardTitle>
                        <CardDescription> Задачи, созданные на основе изменений в данной версии страницы
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="taskDescription in page.diffDescriptions" :key="taskDescription.id"
                                 class="rounded-lg border p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="mb-2 text-sm text-muted-foreground">
                                            Создано {{ formatDate(taskDescription.created_at) }}
                                            <span v-if="taskDescription.creator"> пользователем {{
                                                    taskDescription.creator.name
                                                }} </span>
                                        </p>
                                        <div class="prose prose-sm max-w-none">
                                            <MarkdownRenderer :content="taskDescription.content" />
                                        </div>
                                    </div>
                                    <Button as-child variant="outline" size="sm" class="ml-4">
                                        <Link :href="route('tasks.show', taskDescription.id)"> Перейти к задаче</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6 lg:col-span-1">
                <!-- Информация о странице -->
                <Card>
                    <CardHeader>
                        <CardTitle>Информация о странице</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">ID:</span>
                                <span class="ml-2 text-muted-foreground">{{ page.id }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Создатель:</span>
                                <span class="ml-2 text-muted-foreground">{{ page.creator?.name }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Дата создания версии:</span>
                                <span class="ml-2 text-muted-foreground">{{ formatDate(page.created_at) }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Дата утверждения:</span>
                                <span class="ml-2 text-muted-foreground">{{ formatDate(page.approved_at) }}</span>
                            </div>
                            <div v-if="page.children && page.children.length > 0">
                                <span class="font-medium">Дочерних страниц:</span>
                                <span class="ml-2 text-muted-foreground">{{ page.children.length }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Дочерние страницы -->
                <ChildPages :children="page.children" :parent-id="page.id" :project-id="page.project?.id || null" />

                <!-- Информация о версиях -->
                <Card>
                    <CardHeader>
                        <CardTitle>Информация о версиях</CardTitle>
                        <CardDescription> Детали версионирования страницы</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Текущая версия:</span>
                                <span class="ml-2 text-muted-foreground">{{ page.version_id }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Предыдущая версия:</span>
                                <span class="ml-2 text-muted-foreground">{{
                                        previousVersion?.id || 'Первая версия'
                                    }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Дата создания версии:</span>
                                <span class="ml-2 text-muted-foreground">{{ formatDate(page.created_at) }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Дата создания предыдущей версии:</span>
                                <span class="ml-2 text-muted-foreground">{{
                                        previousVersion ? formatDate(previousVersion.created_at) : 'Первая версия'
                                    }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Прикрепленные файлы -->
                <Card v-if="page.project_files && page.project_files.length > 0">
                    <CardHeader>
                        <CardTitle>Прикрепленные файлы</CardTitle>
                        <CardDescription> Файлы, связанные с данной страницей документации</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-for="a in page.project_files" :key="a.id"
                                 class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50">
                                <FileIcon class="h-5 w-5 text-muted-foreground" />
                                <div class="flex-1">
                                    <p class="font-mono text-sm break-all">{{ getFileName(a.url) }}</p>
                                    <p class="text-xs break-all text-muted-foreground">{{ a.url }}</p>
                                    <p v-if="a.description" class="text-xs text-muted-foreground">— {{ a.description }}</p>
                                </div>
                                <Button as-child variant="outline" size="sm" v-if="isValidRepositoryUrl(a.url)">
                                    <a :href="a.url" target="_blank" rel="noopener noreferrer"> Открыть файл </a>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import DraftInfo from '@/components/PageInfo/DraftInfo.vue';
import ActualizationStatus from '@/components/PageInfo/ActualizationStatus.vue';
import ActualizationButton from '@/components/PageInfo/ActualizationButton.vue';
import ChildPages, { ChildPage } from '@/components/PageInfo/ChildPages.vue';
import Heading from '@/components/Heading.vue';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

import { usePageActualization } from '@/composables/usePageActualization';
import { FileIcon } from 'lucide-vue-next';
import { Project } from '@/types';
import PageListButton from '@/components/PageInfo/PageListButton.vue';

interface Creator {
    name: string;
}

interface TaskDescription {
    id: number;
    content: string;
    created_at: string;
    creator?: Creator;
}

interface Draft {
    id: number;
    title: string;
    content: string;
    created_at: string;
    updated_at: string;
}

interface Page {
    id: number;
    title: string;
    content: string;
    project_files?: { id: number; url: string; description?: string | null }[];
    created_at: string;
    approved_at: string;
    creator: Creator;
    parent?: Page;
    project?: Project;
    children: ChildPage[];
    previous_version_id?: number;
    version_id?: number;
    current: boolean;
    currentDraft?: Draft;
    diffDescriptions?: TaskDescription[];
    hasActiveActualization?: boolean;
    isActualized?: boolean;
}

interface PreviousVersion {
    id: number;
    created_at: string;
}

const props = defineProps<{
    page: Page;
    previousVersion?: PreviousVersion;
}>();

const canCreateTask = computed(() => {
    return (
        props.previousVersion &&
        (!props.page.diff_descriptions || props.page.diff_descriptions.length === 0)
    );
});

// Логика актуализации (для статуса и кнопки)
const {
    actualizationStatus,
    statusText,
    statusColor,
    canStartActualization,
    canCancelActualization,
    cancelActualization,
    checkStatus
} = usePageActualization(props.page.id);

// Проверяем статус при загрузке компонента
onMounted(() => {
    if (props.page.hasActiveActualization || props.page.isActualized) {
        checkStatus();
    }
});

// Можно ли запустить актуализацию (есть файлы и нет активной актуализации)
const canActualize = computed<boolean>(() => {
    const hasFiles = Array.isArray(props.page.project_files) && props.page.project_files.length > 0;
    return !!hasFiles && !!canStartActualization.value;
});

const createTask = () => {
    router.post(route('pages.create-task', props.page.id));
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Методы для работы с файлами
const isValidRepositoryUrl = (url: string): boolean => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
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
