<template>
    <AppLayout title="Редактировать страницу">
        <template #header>
            <div class="flex items-center justify-between">
                <Heading title="Редактировать страницу" />
                <div class="flex items-center gap-2">
                    <!-- Кнопка актуализации только для черновиков -->
                    <Button
                        type="button"
                        @click="showActualizeDialog"
                        variant="outline"
                        :disabled="actualization"
                    >
                        Актуализировать
                    </Button>

                    <Button as-child variant="outline">
                        <Link :href="route('pages.show', pageVersion?.page_id)"> Просмотр</Link>
                    </Button>
                    <PageListButton :page="page" />
                    <Button
                        v-if="actualization"
                        @click="openChatModal"
                        variant="default"
                        size="sm"
                    >
                        Чат
                    </Button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <ActualizationStatus
                    v-if="actualization"
                    actualization="actualization"
                />
                <Card v-else>
                    <CardHeader>
                        <CardTitle>{{ pageVersion?.title || 'Без названия' }}</CardTitle>
                        <CardDescription>
                            {{
                                is_current_version
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
                                <Button v-if="is_current_version" type="submit" :disabled="processing" @click="createDraft">
                                    {{ processing ? 'Создание...' : 'Создать черновик' }}
                                </Button>

                                <!-- Кнопка "Сохранить" для черновика -->
                                <Button v-else type="submit" :disabled="processing">
                                    {{ processing ? 'Сохранение...' : 'Сохранить' }}
                                </Button>

                                <Button v-if="!is_current_version" type="button" @click="approveDraft" variant="default"> Утвердить черновик </Button>
                                <Button type="button" variant="outline" @click="cancel"> Отмена</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6 lg:col-span-1">
                <!-- Предварительный просмотр -->
                <MarkdownPreview :content="form.content" />
            </div>
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

<!-- Chat modal placed after main template to avoid slot constraints -->
<SidePanel v-model:open="isChatModalOpen">
    <AgentChat 
        v-if="chat" 
        :messages="chat.messages"
        :loading="isPolling"
        :status="actualizationStatus"
        :sending="isSending"
        @sendMessage="sendMessageToChat" 
    />
    
</SidePanel>

<script setup lang="ts">
import FileLinksList from '@/components/FileLinksList.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import PageListButton from '@/components/PageInfo/PageListButton.vue';
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
import { Actualization, Page } from '@/types/index.ts';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ActualizationStatus from '@/components/PageInfo/ActualizationStatus.vue';
import ActualizationButton from '@/components/PageInfo/ActualizationButton.vue';
import SidePanel from '@/components/ui/sidepanel/SidePanel.vue';
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import type { LLMChat } from '@/types';
import { useActualizationChat } from '@/composables/useActualizationChat';
import { createApi } from '@/service/api/Api';
import { ActualizationStatusRequest } from '@/service/api/request/Actualization/ActualizationStatusRequest';

type PageVersion = {
    id: number;
    title: string;
    page_id: number;
    content: string;
    files?: string[];
    is_draft?: boolean;
};

const props = withDefaults(
    defineProps<{
        page: Page,
        pageVersion: PageVersion,
        is_current_version: false,
        actualization?: Actualization,
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

// Управление модальным окном чата и состояние чата
const isChatModalOpen = ref<boolean>(false);
const chat = ref<LLMChat | null>(props.actualization?.llm_chat || null);
const actualizationStatus = ref<string>(props.actualization?.status || 'unknown');
const isPolling = ref<boolean>(false);
const pollInterval = ref<number | null>(null);

// API и composable для чата
const api = createApi();
const { sendMessage, updateChatMessages, isSending, error, hasError } = useActualizationChat(props.actualization?.id || 0);

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
        ? route('pages.create-draft', { page: props.pageVersion.page_id })
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

    router.post(
        route('drafts.actualize', props.pageVersion.id),
        {},
        {
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
            },
        },
    );
};

// Отменить актуализацию
const cancelActualization = () => {
    showActualizeConfirm.value = false;
};

// Открыть модальное окно чата
const openChatModal = () => {
    isChatModalOpen.value = true;
    if (props.actualization) {
        startPolling();
    }
};

// Отправить сообщение в чат актуализации
const sendMessageToChat = async (message: string) => {
    if (!props.actualization) return;
    const response = await sendMessage(message);
    if (response && response.chat) {
        updateChatMessages(chat.value, response.chat.messages);
    }
};

// Получить статус актуализации с чатом
const fetchActualizationStatus = async () => {
    if (!props.actualization) return;
    try {
        const req = new ActualizationStatusRequest(props.actualization.id);
        const data = await req.call(api);
        if (data.success) {
            actualizationStatus.value = data.data.status;
            if (data.data.chat) {
                chat.value = {
                    id: data.data.chat.id,
                    messages: data.data.chat.messages,
                    created_at: '',
                    updated_at: ''
                } as LLMChat;
            }
        }
    } catch (err) {
        console.error('Ошибка при получении статуса актуализации:', err);
    }
};

// Запустить polling для обновления статуса
const startPolling = () => {
    if (pollInterval.value) return;
    isPolling.value = true;
    fetchActualizationStatus();
    pollInterval.value = window.setInterval(() => {
        fetchActualizationStatus();
    }, 3000);
};

// Остановить polling
const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
    isPolling.value = false;
};

// Проверить, можно ли перезапустить актуализацию
const canRestartActualization = computed(() => {
    return actualizationStatus.value && !['pending', 'processing'].includes(actualizationStatus.value);
});

// Обновить состояние кнопки актуализации
const actualizationButtonDisabled = computed(() => {
    return !!props.actualization && ['pending', 'processing'].includes(actualizationStatus.value);
});

// Lifecycle hooks
onMounted(() => {
    if (props.actualization) {
        fetchActualizationStatus();
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>
