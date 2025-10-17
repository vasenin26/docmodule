<template>
    <AppLayout :title="project.title">
        <template #context-actions>
            <Button variant="outline" size="sm" @click="$inertia.visit(route('projects.pages.create', project.id))">
                <Icon name="plus" class="mr-2 h-4 w-4" />
                Новая страница
            </Button>
            <ProjectDropdownMenu :project-id="project.id" :project-title="project.title" />
        </template>

        <div class="space-y-6">
            <div>
                <p class="mt-1 text-muted-foreground">Проект #{{ project.id }} • Создан {{ formatDate(project.created_at) }}</p>
            </div>  

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Всего страниц</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ project.pages ? project.pages.length : 0 }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Репозитории</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ project.repositories ? project.repositories.length : 0 }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Владелец</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm">
                            {{ project.owner.name }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Дата создания</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm">
                            {{ formatDate(project.created_at) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Страницы</h3>
                    <Button size="sm" @click="$inertia.visit(route('projects.pages.create', project.id))">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Добавить страницу
                    </Button>
                </div>

                <div v-if="!pages || pages.length === 0">
                    <Card>
                        <CardContent class="py-12 text-center">
                            <div class="mx-auto mb-4 h-12 w-12 text-muted-foreground">
                                <Icon name="FileText" class="h-full w-full" />
                            </div>
                            <h4 class="mb-2 text-lg font-semibold">Нет страниц</h4>
                            <p class="mb-4 text-muted-foreground">Создайте первую страницу для этого проекта</p>
                            <Button @click="$inertia.visit(route('projects.pages.create', project.id))">
                                <Icon name="plus" class="mr-2 h-4 w-4" />
                                Создать страницу
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <PageList v-else :pages="pages" :project="project" :filters="{ search: '', parent_id: undefined }" :show-create-button="false" />
            </div>

            <!-- Репозитории -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Репозитории</h3>
                    <Button size="sm" @click="$inertia.visit(route('projects.edit', project.id))">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Управление репозиториями
                    </Button>
                </div>

                <div v-if="!project.repositories || project.repositories.length === 0">
                    <Card>
                        <CardContent class="py-12 text-center">
                            <div class="mx-auto mb-4 h-12 w-12 text-muted-foreground">
                                <Icon name="GitBranch" class="h-full w-full" />
                            </div>
                            <h4 class="mb-2 text-lg font-semibold">Нет репозиториев</h4>
                            <p class="mb-4 text-muted-foreground">Добавьте репозитории для этого проекта</p>
                            <Button @click="$inertia.visit(route('projects.edit', project.id))">
                                <Icon name="plus" class="mr-2 h-4 w-4" />
                                Добавить репозиторий
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="repository in project.repositories" :key="repository.id" class="transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle class="flex items-center text-base">
                                <Icon name="GitBranch" class="mr-2 h-4 w-4 text-muted-foreground" />
                                Репозиторий
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <a
                                    :href="repository.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center text-sm break-all text-primary hover:underline"
                                >
                                    {{ repository.url }}
                                    <Icon name="ExternalLink" class="ml-1 h-3 w-3 flex-shrink-0" />
                                </a>
                                <div class="text-xs text-muted-foreground">Добавлен {{ formatDate(repository.created_at) }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import PageList from '@/components/PageList.vue';
import ProjectDropdownMenu from '@/components/ProjectDropdownMenu.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagesData, Project } from '@/types';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    project: Project;
    pages: PagesData
}>();


const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

</script>
