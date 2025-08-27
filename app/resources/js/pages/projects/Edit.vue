<template>
    <AppLayout :title="`Редактировать ${project.title}`">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6">
                <Heading title="Параметры проекта"></Heading>
                <p class="mt-2 text-muted-foreground">Измените информацию о проекте</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Информация о проекте</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updateProject" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="title">Название проекта</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Введите название проекта"
                                :class="{ 'border-destructive': form.errors.title }"
                                required
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <Button type="button" variant="outline" @click="$inertia.visit(route('projects.show', project.id))"> Отмена </Button>
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="destructive" @click="deleteProject"> Удалить </Button>
                                <Button type="submit" :disabled="form.processing">
                                    <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Управление репозиториями -->
            <Card class="mt-6">
                <CardHeader>
                    <CardTitle>Репозитории</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Список существующих репозиториев -->
                    <div v-if="project.repositories && project.repositories.length > 0" class="space-y-2">
                        <div
                            v-for="repository in project.repositories"
                            :key="repository.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div class="flex items-center space-x-3">
                                <Icon name="git-branch" class="h-4 w-4 text-muted-foreground" />
                                <a :href="repository.url" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">
                                    {{ repository.url }}
                                </a>
                                <Icon name="external-link" class="h-3 w-3 text-muted-foreground" />
                            </div>
                            <Button variant="destructive" size="sm" @click="removeRepository(repository.id)" :disabled="repositoryProcessing.value">
                                <Icon v-if="repositoryProcessing.value" name="loader-2" class="mr-2 h-3 w-3 animate-spin" />
                                Удалить
                            </Button>
                        </div>
                    </div>

                    <!-- Сообщение, если репозиториев нет -->
                    <div v-else class="py-8 text-center text-muted-foreground">
                        <Icon name="git-branch" class="mx-auto mb-2 h-8 w-8 text-muted-foreground/50" />
                        <p>Репозитории не добавлены</p>
                    </div>

                    <!-- Форма добавления нового репозитория -->
                    <div class="border-t pt-4">
                        <form @submit.prevent="addRepository" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="repository_url">Добавить репозиторий</Label>
                                <div class="flex space-x-2">
                                    <Input
                                        id="repository_url"
                                        v-model="repositoryForm.url"
                                        type="text"
                                        placeholder="https://github.com/username/repo или git@github.com:username/repo.git"
                                        :class="{ 'border-destructive': repositoryForm.errors.url }"
                                        class="flex-1"
                                        required
                                    />
                                    <Button type="submit" :disabled="repositoryForm.processing || !repositoryForm.url">
                                        <Icon v-if="repositoryForm.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                        Добавить
                                    </Button>
                                </div>
                                <InputError :message="repositoryForm.errors.url" />
                            </div>
                        </form>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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

interface Project {
    id: number;
    title: string;
    owner_id: number;
    owner: User;
    repositories?: Repository[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    project: Project;
}>();

const form = useForm({
    title: props.project.title,
});

const repositoryForm = useForm({
    url: '',
});

const repositoryProcessing = ref(false);

// Клиентская валидация URL
const isValidUrl = (url: string) => {
    // Проверка HTTPS формата
    if (url.startsWith('https://')) {
        try {
            new URL(url);
            return url.includes('github.com');
        } catch {
            return false;
        }
    }

    // Проверка SSH формата
    if (url.startsWith('git@')) {
        const parts = url.split(':');
        if (parts.length !== 2) return false;

        const domainPart = parts[0];
        const pathPart = parts[1];

        // Проверяем формат git@domain
        if (!domainPart.includes('@') || domainPart.split('@').length !== 2) return false;

        // Проверяем, что это GitHub
        if (!domainPart.includes('github.com')) return false;

        // Проверяем, что путь не пустой
        return pathPart.length > 0;
    }

    return false;
};

// Проверка, не дублируется ли репозиторий
const isDuplicateRepository = (url: string) => {
    return props.project.repositories?.some((repo) => repo.url === url) || false;
};

const updateProject = () => {
    form.put(route('projects.update', props.project.id));
};

const deleteProject = () => {
    if (confirm(`Вы уверены, что хотите удалить проект "${props.project.title}"? Все страницы проекта также будут удалены.`)) {
        router.delete(route('projects.destroy', props.project.id));
    }
};

const addRepository = () => {
    // Клиентская валидация перед отправкой
    if (!repositoryForm.url.trim()) {
        repositoryForm.setError('url', 'URL репозитория обязателен для заполнения');
        return;
    }

    if (!isValidUrl(repositoryForm.url)) {
        repositoryForm.setError('url', 'URL должен быть в формате HTTPS или SSH GitHub репозитория');
        return;
    }

    if (isDuplicateRepository(repositoryForm.url)) {
        repositoryForm.setError('url', 'Этот репозиторий уже добавлен к проекту');
        return;
    }

    repositoryForm.clearErrors();
    repositoryForm.post(route('projects.repositories.store', props.project.id), {
        onSuccess: () => {
            repositoryForm.reset();
        },
        onError: () => {
            // Ошибки будут отображены через InputError
        },
    });
};

const removeRepository = (repositoryId: number) => {
    if (confirm('Вы уверены, что хотите удалить этот репозиторий из проекта?')) {
        repositoryProcessing.value = true;
        router.delete(
            route('projects.repositories.destroy', {
                project: props.project.id,
                repository: repositoryId,
            }),
            {
                onFinish: () => {
                    repositoryProcessing.value = false;
                },
            },
        );
    }
};
</script>
