<template>
    <AppLayout title="Редактировать страницу">
        <template #header>
            <div class="flex items-center justify-between">
                <Heading title="Редактировать страницу" />
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('pages.show', page?.id)"> Просмотр </Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="route('pages.index')"> Назад к списку </Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl">
            <!-- Предупреждение о существующем черновике -->
            <!-- <div v-if="hasActiveDraft" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-6">
        <p class="text-sm text-yellow-800">
          <strong>Внимание:</strong> У этой страницы есть активный черновик. 
          Вы можете продолжить редактирование черновика или создать новый.
        </p>
        <div class="mt-2 flex gap-2">
          <Button @click="continueDraft" variant="outline" size="sm">
            Продолжить черновик
          </Button>
          <Button @click="createNewDraft" variant="outline" size="sm">
            Создать новый черновик
          </Button>
        </div>
      </div> -->

            <!-- Информация о версии -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-800">
                    <strong>Редактирование версии:</strong>
                    {{ version.is_current ? 'Текущая версия' : `Версия #${version.id}` }}
                    (создана {{ formatDate(version.created_at) }})
                </p>
                <p v-if="currentDraft" class="text-sm text-blue-800 mt-2">
                    <strong>Активный черновик:</strong>
                    Создан {{ formatDate(currentDraft.created_at) }}
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ version?.title || 'Без названия' }}</CardTitle>
                    <CardDescription v-if="!currentDraft">
                        {{ version.is_current 
                            ? 'Редактирование текущей версии. При сохранении будет создан черновик.' 
                            : 'Редактирование версии. При сохранении будет создана новая версия.' 
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Название -->
                        <div class="space-y-2">
                            <Label for="title">Название страницы *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Введите название страницы"
                                :class="{ 'border-destructive': errors?.title }"
                            />
                            <InputError v-if="errors?.title" :message="errors.title" />
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
                                :class="{ 'border-destructive': errors?.content }"
                            />
                            <InputError v-if="errors?.content" :message="errors.content" />
                            <p class="text-xs text-muted-foreground">Поддерживается формат Markdown</p>

                            <!-- Предварительный просмотр -->
                            <MarkdownPreview :content="form.content" />
                        </div>

                        <!-- Прикрепленные файлы -->
                        <div class="space-y-2">
                            <FileLinksList v-model="form.files" />
                            <InputError v-if="errors?.files" :message="errors.files" />
                        </div>

                        <!-- Checkbox для создания задачи -->
                        <div v-if="currentDraft" class="flex items-center space-x-2">
                            <Checkbox id="createTask" v-model="form.createTask" />
                            <Label for="createTask">Создать задачу при утверждении</Label>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Сохранение...' : 
                                   currentDraft ? 'Обновить черновик' : 
                                   version.is_current ? 'Создать черновик' : 'Создать новую версию' 
                                }}
                            </Button>
                            <Button v-if="currentDraft" type="button" @click="approveDraft" variant="default"> Утвердить черновик </Button>
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
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Page {
    id: number;
    title: string;
    content: string;
    files?: string[];
    current: boolean;
}

interface Draft {
    id: number;
    title: string;
    content: string;
    files?: string[];
    created_at: string;
    updated_at: string;
}

interface Version {
    id: number;
    title: string;
    content: string;
    files?: string[];
    created_at: string;
    is_current: boolean;
}

const props = withDefaults(
    defineProps<{
        page: Page;
        version: Version;
        currentDraft?: Draft;
        hasActiveDraft?: boolean;
        errors?: Record<string, string>;
    }>(),
    {
        errors: () => ({}),
        hasActiveDraft: false,
    },
);

const form = useForm({
    title: props.currentDraft?.title || props.version?.title || 'Без названия',
    content: props.currentDraft?.content || props.version?.content || '',
    files: props.currentDraft?.files || props.version?.files || [],
    createTask: false,
});

const processing = ref(false);

const submit = () => {
    processing.value = true;
    
    // Используем URL для версии, если это не текущая версия
    const url = props.version.is_current 
        ? route('pages.update', props.page?.id)
        : route('pages.versions.update', [props.page?.id, props.version?.id]);
    
    form.put(url, {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const continueDraft = () => {
    if (props.currentDraft) {
        form.title = props.currentDraft.title;
        form.content = props.currentDraft.content;
        form.files = props.currentDraft.files || [];
    }
};

const createNewDraft = () => {
    form.title = props.version.title;
    form.content = props.version.content;
    form.files = props.version.files || [];
};

const approveDraft = () => {
    if (props.currentDraft) {
        router.post(route('drafts.approve', props.currentDraft.id), {
            create_task: form.createTask,
        });
    }
};

const cancel = () => {
    router.visit(route('pages.show', props.page?.id));
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('ru-RU');
};
</script>
