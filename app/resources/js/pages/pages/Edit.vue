<template>
    <PagesLayout>
        <template #context-actions>
            <div class="flex items-center gap-2">
                <ActualizationButton
                    :versionId="page.current_version.id"
                />

                <Button as-child variant="outline">
                    <Link :href="route('pages.show', pageVersion?.page_id)"> Просмотр</Link>
                </Button>
                <PageListButton :page="page" />
            </div>
        </template>

        <div class="space-y-2">
            <Heading title="Редактировать страницу" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Основное содержимое -->
            <div class="space-y-6 lg:col-span-1">
                <ActualizationStatus
                    v-if="actualization"
                    :actualization="actualization"
                />
                <Card>
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
                                <AppContentWysiwyg
                                    v-model="form.content"
                                />
                                <InputError v-if="errors?.content" :message="errors.content" />
                                <p class="text-xs text-muted-foreground">Поддерживается формат Markdown</p>
                            </div>

                            <!-- Прикрепленные файлы -->
                            <div class="space-y-2">
                                <FileLinksList v-model="form.files" />
                                <InputError v-if="errors?.files" :message="errors.files" />
                                <InputError v-else-if="errors?.project_files" :message="errors.project_files" />
                            </div>

                            <!-- Checkbox для создания задачи -->
                            <div v-if="!is_current_version" class="flex items-center space-x-2">
                                <Checkbox id="createTask" v-model="form.createTask" />
                                <Label for="createTask">Создать задачу при утверждении</Label>
                            </div>

                            <!-- Кнопки -->
                            <div class="flex items-center gap-4">
                                <!-- Кнопка "Создать черновик" для текущей версии -->
                                <Button v-if="is_current_version" type="submit" :disabled="processing"
                                        @click="createDraft">
                                    {{ processing ? 'Создание...' : 'Создать черновик' }}
                                </Button>

                                <!-- Кнопка "Сохранить" для черновика -->
                                <Button v-else type="submit" :disabled="processing">
                                    {{ processing ? 'Сохранение...' : 'Сохранить' }}
                                </Button>

                                <Button v-if="!is_current_version" type="button" @click="approveDraft"
                                        variant="default"> Утвердить черновик
                                </Button>
                                <Button type="button" variant="outline" @click="cancel"> Отмена</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
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

        <!-- Chat modal placed after main template to avoid slot constraints -->
        <SidePanel v-model:open="isChatModalOpen">
            <AgentChat
                v-if="chat"
                :messages="chat.messages"
                :loading="isPolling"
                :status="actualizationProcessStatus"
                :sending="isSending"
                :requestCount="requestCount"
                :contextFill="chat?.context_fill ?? 0"
                :totalTokens="chat?.total_tokens ?? 0"
                :context="chat?.context"
                @sendMessage="sendMessageToChat"
                @stop="sendStopGenerating"
            />
        </SidePanel>
    </PagesLayout>
</template>


<script setup lang="ts">
import FileLinksList from '@/components/FileLinksList.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import PagesLayout from '@/layouts/pages/PagesLayout.vue';
import { Actualization, Page } from '@/types';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ActualizationStatus from '@/components/PageInfo/ActualizationStatus.vue';
import SidePanel from '@/components/ui/sidepanel/SidePanel.vue';
import AgentChat from '@/components/AgentChat/AgentChat.vue';
import type { LLMChat } from '@/types';
import { useActualizationChat } from '@/composables/useActualizationChat';
import { createApi } from '@/service/api/Api';
import { ActualizationStatusRequest } from '@/service/api/request/Actualization/ActualizationStatusRequest';
import { StartActualizationForDraftRequest } from '@/service/api/request/Actualization/StartActualizationForDraftRequest';
import AppContentWysiwyg from '@/components/AppContentWysiwyg.vue';
import ActualizationButton from '@/components/PageInfo/ActualizationButton.vue';

type PageVersion = {
    id: number;
    title: string;
    page_id: number;
    content: string;
    files?: string[];
    project_files?: { id: number; url: string; description?: string | null }[];
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
        isCurrentVersion: false
    }
);

const form = useForm({
    title: props.pageVersion.title || 'Без названия',
    content: props.pageVersion.content,
    files: (props.pageVersion?.project_files?.map((a: any) => a.url) as string[])
        || props.pageVersion?.files
        || [],
    createTask: false,
    is_current_version: false as boolean
});

const processing = ref(false);

// Состояние диалога подтверждения
const showActualizeConfirm = ref(false);
const actualizationLoading = ref(false);

// Управление модальным окном чата и состояние чата
const isChatModalOpen = ref<boolean>(false);
const chat = ref<LLMChat | null>(props.actualization?.llm_chat || null);
const actualizationProcessStatus = ref<string>(props.actualization?.status || 'unknown');
const isPolling = ref<boolean>(false);
const pollInterval = ref<number | null>(null);
const requestCount = ref<number>(0);
const hasActiveAgentTask = ref<boolean>(false);
const contentUpdated = ref<boolean>(false);

// API и composable для чата
const api = createApi();
const {
    sendMessage,
    stopGenerating,
    updateChatMessages,
    isSending,
} = useActualizationChat(props.actualization?.id || 0);

// Метод для создания черновика
const createDraft = () => {
    processing.value = true;
    const transformed = form.transform((data: any) => ({
        ...data,
        project_files: Array.isArray(data.files)
            ? data.files.filter((u: string) => !!u).map((u: string) => ({ url: u }))
            : []
    }));
    transformed.post(route('pages.create-draft', props.pageVersion.page_id), {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        }
    });
};

const submit = () => {
    processing.value = true;

    const createDraftUrl = route('pages.create-draft', { page: props.pageVersion.page_id });
    const updateUrl = route('pages.versions.update', [props.pageVersion.page_id, props.pageVersion.id]);

    const transformed = form.transform((data: any) => ({
        ...data,
        project_files: Array.isArray(data.files)
            ? data.files.filter((u: string) => !!u).map((u: string) => ({ url: u }))
            : []
    }));

    const options = {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        }
    } as any;

    if (props.is_current_version) {
        transformed.post(createDraftUrl, options);
    } else {
        transformed.put(updateUrl, options);
    }
};

const approveDraft = () => {
    if (!props.is_current_version) {
        const transformed = form.transform((data: any) => ({
            ...data,
            project_files: Array.isArray(data.files)
                ? data.files.filter((u: string) => !!u).map((u: string) => ({ url: u }))
                : []
        }));
        transformed.post(route('drafts.approve', props.pageVersion.id), {
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
            }
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

    (async () => {
        try {
            const req = new StartActualizationForDraftRequest(props.pageVersion.id);
            const result = await req.call(api);
            showActualizeConfirm.value = false;
            actualizationLoading.value = false;
            if (result?.success) {
                // Мгновенно переключаем UI в состояние ожидания и запускаем опрос
                actualizationProcessStatus.value = 'pending';
                contentUpdated.value = false; // Сбрасываем флаг обновления содержимого
                startPolling();
            } else {
                console.error('Ошибка актуализации:', result?.message);
            }
        } catch (err) {
            actualizationLoading.value = false;
            console.error('Ошибка актуализации:', err);
        }
    })();
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
    if (!props.actualization || props.actualization.generating) return;

    startPolling();
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
            requestCount.value++;
            actualizationProcessStatus.value = data.data.status;
            hasActiveAgentTask.value = !!data.data.has_active_agent_task;

            // Отладочная информация
            console.log('Статус актуализации:', data.data.status, 'Содержимое:', data.data.content ? 'есть' : 'нет');
            if (data.data.chat) {
                if (!chat.value) {
                    chat.value = {
                        id: data.data.chat.id,
                        messages: data.data.chat.messages,
                        created_at: '',
                        updated_at: ''
                    } as LLMChat;
                } else {
                    chat.value.messages = data.data.chat.messages;
                }
                // Прокидываем context_fill, total_tokens и context из API
                (chat.value as any).context_fill = (data.data.chat as any).context_fill ?? (chat.value as any)?.context_fill ?? 0;
                (chat.value as any).total_tokens = (data.data.chat as any).total_tokens ?? (chat.value as any)?.total_tokens ?? 0;
                (chat.value as any).context = (data.data.chat as any).context ?? (chat.value as any)?.context ?? null;
            }

            // Обновляем содержимое черновика при завершении актуализации
            if (data.data.status === 'success' && data.data.content && !contentUpdated.value) {
                form.content = data.data.content;
                contentUpdated.value = true;
                // Показываем уведомление пользователю
                console.log('Содержимое черновика обновлено после актуализации');
            }

            if (['success', 'failed'].includes(actualizationProcessStatus.value)) {
                stopPolling();
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

const actualizationButtonDisabled = computed(() => {
    return !!props.actualization && props.actualization.generating;
});

// Lifecycle hooks
onMounted(() => {
    if (props.actualization) {
        fetchActualizationStatus();
    }
});

const sendStopGenerating = async () => {
    await stopGenerating();
    actualizationProcessStatus.value = 'completed';
};

onUnmounted(() => {
    stopPolling();
});
</script>
