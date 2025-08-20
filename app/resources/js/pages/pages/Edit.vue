<template>
    <AppLayout title="Редактировать страницу">
        <template #header>
            <div class="flex items-center justify-between">
                <Heading title="Редактировать страницу" />
                <div class="flex items-center gap-2">

                    <!-- Кнопка актуализации только для черновиков -->
                    <Button
                        v-if="!is_current_version && pageVersion.is_draft"
                        type="button"
                        @click="showActualizeDialog"
                        variant="outline"
                        :disabled="processing"
                    >
                        Актуализировать
                    </Button>

                    <Button as-child variant="outline">
                        <Link :href="route('pages.show', pageVersion?.page_id)"> Просмотр </Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="route('pages.index')"> Назад к списку </Link>
                    </Button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl">
            <Card>
                <CardHeader>
                    <CardTitle>{{ pageVersion?.title || 'Без названия' }}</CardTitle>
                    <CardDescription>
                        {{ is_current_version
                            ? 'Редактирование текущей версии. При сохранении будет создан черновик.'
                            : 'Редактирование версии. При сохранении содержимое версии будет обновлено.'
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
                        <div v-if="!is_current_version" class="flex items-center space-x-2">
                            <Checkbox id="createTask" v-model="form.createTask" />
                            <Label for="createTask">Создать задачу при утверждении</Label>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex items-center gap-4">
                            <!-- Кнопка "Создать черновик" для текущей версии -->
                            <Button
                                v-if="is_current_version"
                                type="submit"
                                :disabled="processing"
                                @click="createDraft"
                            >
                                {{ processing ? 'Создание...' : 'Создать черновик' }}
                            </Button>

                            <!-- Кнопка "Сохранить" для черновика -->
                            <Button
                                v-else
                                type="submit"
                                :disabled="processing"
                            >
                                {{ processing ? 'Сохранение...' : 'Сохранить'}}
                            </Button>

                            <Button v-if="!is_current_version" type="button" @click="approveDraft" variant="default"> Утвердить черновик </Button>
                            <Button type="button" variant="outline" @click="cancel"> Отмена </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <!-- Диалог подтверждения актуализации -->
        <ConfirmDialog
            v-model:open="showActualizeConfirm"
            title="Подтверждение актуализации"
            description="При актуализации черновика его содержимое будет обновлено на основе прикрепленных файлов."
            :warning="form.isDirty ? 'Все несохраненные изменения будут утеряны.' : undefined"
            confirm-text="Продолжить актуализацию"
            cancel-text="Отмена"
            :loading="actualizationLoading"
            @confirm="confirmActualizeDraft"
            @cancel="cancelActualization"
        />
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
import { ConfirmDialog } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type PageVersion = {
    id: number;
    title: string;
    page_id: number;
    content: string;
    files?: string[];
    is_draft?: boolean;
}

const props = withDefaults(
    defineProps<{
        pageVersion: PageVersion,
        is_current_version: false
        errors: any
    }>(),
    {
        errors: () => ({}),
        hasActiveDraft: false,
        isCurrentVersion: false,
    },
);

const form = useForm({
    title: props.pageVersion.title || 'Без названия',
    content: props.pageVersion.content,
    files: props.pageVersion?.files || [],
    createTask: false,
    is_current_version: false as boolean,
});

const processing = ref(false);

// Состояние диалога подтверждения
const showActualizeConfirm = ref(false);
const actualizationLoading = ref(false);

// Метод для создания черновика
const createDraft = () => {
    processing.value = true;

    form.post(route('pages.create-draft', props.pageVersion.page_id), {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const submit = () => {
    processing.value = true;

    const url = props.is_current_version
        ? route('pages.create-draft', {page: props.pageVersion.page_id})
        : route('pages.versions.update', [props.pageVersion.page_id, props.pageVersion.id]);

    form.put(url, {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const approveDraft = () => {
    if (!props.is_current_version) {
        form.put(route('drafts.approve', props.pageVersion.id), {
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
            },
        });
    }
};

const cancel = () => {
    router.visit(route('pages.show', props.pageVersion.page_id));
};

// Показать диалог подтверждения актуализации
const showActualizeDialog = () => {
    showActualizeConfirm.value = true;
};

// Подтвердить актуализацию черновика
const confirmActualizeDraft = () => {
    actualizationLoading.value = true;

    router.post(route('drafts.actualize', props.pageVersion.id), {}, {
        onSuccess: () => {
            showActualizeConfirm.value = false;
            actualizationLoading.value = false;
            // Обновить страницу или показать уведомление об успешной актуализации
            location.reload(); // или router.reload()
        },
        onError: (errors) => {
            actualizationLoading.value = false;
            // Показать ошибку актуализации
            console.error('Ошибка актуализации:', errors);
        }
    });
};

// Отменить актуализацию
const cancelActualization = () => {
    showActualizeConfirm.value = false;
};
</script>
