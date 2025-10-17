<template>
    <AppLayout title="Проекты">
        <div class="flex flex-col space-y-6">
            <div class="flex items-center justify-between">
                <Heading>Проекты</Heading>
                <Button as-child>
                    <Link :href="route('projects.create')">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Создать проект
                    </Link>
                </Button>
            </div>

            <div v-if="projects.length === 0" class="py-12 text-center">
                <div class="mx-auto mb-4 h-24 w-24 text-muted-foreground">
                    <Icon name="folder" class="h-full w-full" />
                </div>
                <h3 class="mb-2 text-xl font-semibold">Нет проектов</h3>
                <p class="mb-4 text-muted-foreground">Создайте свой первый проект для организации документации</p>
                <Button as-child>
                    <Link :href="route('projects.create')">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Создать проект
                    </Link>
                </Button>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="project in projects"
                    :key="project.id"
                    class="cursor-pointer transition-shadow hover:shadow-md"
                    @click="$inertia.visit(route('projects.show', project.id))"
                >
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg">{{ project.title }}</CardTitle>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click.stop>
                                        <Icon name="MoreHorizontal" class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="route('projects.show', project.id)" class="flex items-center">
                                            <Icon name="eye" class="mr-2 h-4 w-4" />
                                            Просмотр
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <Link :href="route('projects.edit', project.id)" class="flex items-center">
                                            <Icon name="edit" class="mr-2 h-4 w-4" />
                                            Редактировать
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="flex items-center text-destructive" @click="deleteProject(project)">
                                        <Icon name="trash-2" class="mr-2 h-4 w-4" />
                                        Удалить
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="text-sm text-muted-foreground">ID: {{ project.id }}</div>
                            <div class="text-sm text-muted-foreground">Создан: {{ formatDate(project.created_at) }}</div>
                            <div class="text-sm text-muted-foreground">Владелец: {{ project.owner.name }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Project {
    id: number;
    title: string;
    owner_id: number;
    owner: User;
    created_at: string;
    updated_at: string;
}

defineProps<{
    projects: Project[];
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const deleteProject = (project: Project) => {
    if (confirm(`Вы уверены, что хотите удалить проект "${project.title}"?`)) {
        router.delete(route('projects.destroy', project.id));
    }
};
</script>
