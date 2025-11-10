<template>
    <PagesLayout>
        <template #context-actions>
            <Button as-child variant="outline">
                <Link :href="project ? route('projects.show', project.id) : route('pages.index')">
                    {{ project ? 'Назад к проекту' : 'Назад к списку' }}
                </Link>
            </Button>
        </template>

        <div class="space-y-2">
            <Heading title="Создать страницу" />
        </div>

        <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <Card>
                    <CardHeader>
                        <CardTitle>Новая страница</CardTitle>
                        <CardDescription> Создайте новую страницу документации</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Проект -->
                            <div v-if="project" class="rounded-lg bg-muted/50 p-4">
                                <p class="mb-2 text-sm text-muted-foreground">Проект:</p>
                                <p class="font-medium">{{ project.title }}</p>
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
                                <div class="flex items-center gap-2">
                                    <ImportantStar v-model="form.isImportant" />
                                </div>

                                <InputError v-if="errors.title" :message="errors.title" />
                            </div>

                            <!-- Содержимое -->
                            <div class="space-y-2">
                                <Label for="content">Содержимое</Label>
                                <AppContentWysiwyg v-model="form.content" />
                                <InputError v-if="errors.content" :message="errors.content" />
                                <p class="text-xs text-muted-foreground">Поддерживается формат Markdown</p>
                            </div>

                            <!-- Прикрепленные файлы -->
                            <div class="space-y-2">
                                <FileLinksList v-model="form.files" />
                                <InputError v-if="errors?.files" :message="errors.files" />
                                <InputError v-else-if="errors?.project_files" :message="errors.project_files" />
                            </div>

                            <!-- Скрытое поле для parent_id -->
                            <input v-if="parentPage" type="hidden" name="parent_id" :value="parentPage.id" />

                            <!-- Кнопки -->
                            <div class="flex items-center gap-4">
                                <Button type="submit" :disabled="processing">
                                    {{ processing ? 'Создание...' : 'Создать страницу' }}
                                </Button>
                                <Button type="button" variant="outline" @click="cancel"> Отмена</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PagesLayout>
</template>

<script setup lang="ts">
import AppContentWysiwyg from '@/components/AppContentWysiwyg.vue';
import FileLinksList from '@/components/FileLinksList.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import PagesLayout from '@/layouts/pages/PagesLayout.vue';
import ImportantStar from '@/components/ui/ImportantStar.vue';

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
    isImportant: false,

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

    const transformed = form.transform((data: any) => ({
        ...data,
        parent_id: props.parentPage?.id,
        project_files: Array.isArray(data.files) ? data.files.filter((u: string) => !!u).map((u: string) => ({ url: u })) : [],
        is_important: !!data.isImportant,

    }));

    transformed.post(submitUrl, {
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
