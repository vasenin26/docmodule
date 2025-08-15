<template>
    <AppLayout title="Создать страницу">
        <template #header>
            <div class="flex items-center justify-between">
                <Heading title="Создать страницу" />
                <Button as-child variant="outline">
                    <Link :href="project ? route('projects.show', project.id) : route('pages.index')">
                        {{ project ? 'Назад к проекту' : 'Назад к списку' }}
                    </Link>
                </Button>
            </div>
        </template>

        <div class="max-w-4xl">
            <Card>
                <CardHeader>
                    <CardTitle>Новая страница</CardTitle>
                    <CardDescription> Создайте новую страницу документации </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Проект -->
                        <div v-if="project" class="rounded-lg bg-muted/50 p-4">
                            <p class="mb-2 text-sm text-muted-foreground">Проект:</p>
                            <p class="font-medium">{{ project.title }}</p>
                        </div>

                        <!-- Выбор проекта (если не указан в URL) -->
                        <div v-else-if="projects && projects.length > 0" class="space-y-2">
                            <Label for="project_id">Проект</Label>
                            <select
                                id="project_id"
                                v-model="form.project_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option :value="null">Без проекта</option>
                                <option v-for="proj in projects" :key="proj.id" :value="proj.id">
                                    {{ proj.title }}
                                </option>
                            </select>
                            <InputError v-if="errors.project_id" :message="errors.project_id" />
                        </div>

                        <!-- Родительская страница -->
                        <div v-if="parentPage" class="rounded-lg bg-muted/50 p-4">
                            <p class="mb-2 text-sm text-muted-foreground">Родительская страница:</p>
                            <p class="font-medium">{{ parentPage.title }}</p>
                        </div>

                        <!-- Название -->
                        <div class="space-y-2">
                            <Label for="title">Название страницы *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Введите название страницы"
                                :class="{ 'border-destructive': errors.title }"
                            />
                            <InputError v-if="errors.title" :message="errors.title" />
                        </div>

                        <!-- Содержимое -->
                        <div class="space-y-2">
                            <Label for="content">Содержимое</Label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                rows="15"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Введите содержимое страницы в формате Markdown..."
                                :class="{ 'border-destructive': errors.content }"
                            />
                            <InputError v-if="errors.content" :message="errors.content" />
                            <p class="text-xs text-muted-foreground">Поддерживается формат Markdown</p>

                            <!-- Предварительный просмотр -->
                            <MarkdownPreview :content="form.content" />
                        </div>

                        <!-- Прикрепленные файлы -->
                        <div class="space-y-2">
                            <FileLinksList v-model="form.files" />
                            <InputError v-if="errors?.files" :message="errors.files" />
                        </div>

                        <!-- Скрытое поле для parent_id -->
                        <input v-if="parentPage" type="hidden" name="parent_id" :value="parentPage.id" />

                        <!-- Кнопки -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Создание...' : 'Создать страницу' }}
                            </Button>
                            <Button type="button" variant="outline" @click="cancel"> Отмена </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import FileLinksList from '@/components/FileLinksList.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ParentPage {
    id: number;
    title: string;
}

interface Project {
    id: number;
    title: string;
}

const props = withDefaults(
    defineProps<{
        parentPage?: ParentPage;
        project?: Project;
        projects?: Project[];
        errors?: Record<string, string>;
    }>(),
    {
        errors: () => ({}),
    },
);

const form = useForm({
    title: '',
    content: '',
    files: [] as string[],
    parent_id: props.parentPage?.id || null,
    project_id: props.project?.id || null,
});

const processing = ref(false);

const submit = () => {
    processing.value = true;

    // Определяем URL для отправки формы
    const submitUrl = props.project ? route('projects.pages.store', props.project.id) : route('pages.store');

    form.post(submitUrl, {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const cancel = () => {
    if (props.project) {
        router.visit(route('projects.show', props.project.id));
    } else {
        router.visit(route('pages.index'));
    }
};
</script>
