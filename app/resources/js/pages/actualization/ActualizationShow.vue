<template>
    <FullScreenLayout>
        <template #context-actions>
            <div class="flex items-center gap-2">
                <ChatButton :showCondition="!isWaiting" @click="showChat = true" />
                <Button @click="restartGeneration" variant="outline">
                    <RefreshCw :class="{ 'animate-spin': isRestarting }" class="mr-2 h-4 w-4" />
                    Перезапустить
                </Button>
                <Button>
                    <Link :href="route('pages.versions.edit', [version.page_id, version.id])"> Редактировать</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="route('pages.show', version.page_id)"> Назад к странице</Link>
                </Button>
            </div>
        </template>

        <div class="mx-auto flex max-w-4xl flex-col gap-2">
            <!-- Статус актуализации -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <RefreshCw :class="{ 'animate-spin': isWaiting }" class="h-5 w-5" />
                        Статус актуализации
                    </CardTitle>
                    <CardDescription> Информация о процессе актуализации документации</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium">Статус</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-gray-100 text-gray-800': actualisationStatus === 'init',
                                        'bg-blue-100 text-blue-800': actualisationStatus === 'pending',
                                        'bg-yellow-100 text-yellow-800': actualisationStatus === 'processing',
                                        'bg-green-100 text-green-800': actualisationStatus === 'completed',
                                        'bg-red-100 text-red-800': actualisationStatus === 'failed',
                                    }"
                                >
                                    {{ getStatusText(actualisationStatus) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Запущена</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ formatDate(actualization.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Обновлена</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ formatDate(actualization.updated_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Инициатор</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ actualization.created_by.name }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Обновленное содержимое -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex gap-2 items-center">Обновленное содержимое <span v-if="loading">(загрузка...)</span></CardTitle>
                    <CardDescription> Результат актуализации документации на основе прикрепленных файлов </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="ck-content" v-html="content"></div>
                </CardContent>
            </Card>
        </div>

        <!-- Модальное окно чата -->
        <SidePanel v-model:open="showChat">
            <SmartAgentChat
                :chatId="chat_id"
                @updated="checkUpdates"
            />
        </SidePanel>
    </FullScreenLayout>
</template>

<script setup lang="ts">
import SmartAgentChat from '@/components/AgentChat/SmartAgentChat.vue';
import ChatButton from '@/components/ChatButton.vue';
import SidePanel from '@/components/ui/sidepanel/SidePanel.vue';
import { useChatAgent } from '@/composables/useChatAgent';
import FullScreenLayout from '@/layouts/fullscreen/FullScreenLayout.vue';
import { createApi } from '@/service/api/Api';
import { RestartGeneration } from '@/service/api/request/Actualization/RestartGenerationRequest';
import { PageVersion } from '@/types';
import { Link } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import Button from '../../components/ui/button/Button.vue';
import Card from '../../components/ui/card/Card.vue';
import CardContent from '../../components/ui/card/CardContent.vue';
import CardDescription from '../../components/ui/card/CardDescription.vue';
import CardHeader from '../../components/ui/card/CardHeader.vue';
import CardTitle from '../../components/ui/card/CardTitle.vue';
import {sleep} from  '@/utils/utils'
import { ActualizationStatusRequest } from '@/service/api/request/Actualization/ActualizationStatusRequest';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Actualization {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    created_at: string;
    updated_at: string;
    created_by: User;
}

const props = defineProps<{
    project_id: number;
    actualization: Actualization;
    version: PageVersion;
    chat_id: number | null;
}>();

const showChat = ref<bool>(false);
const loading = ref<bool>(false);
const isRestarting = ref<bool>(false);
const isWaiting = ref<bool>(false);

const content = ref<string>('');
const actualisationStatus = ref<string>('');

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusText = (status: string) => {
    const statusMap = {
        pending: 'Ожидает обработки',
        processing: 'Обрабатывается',
        completed: 'Завершена',
        failed: 'Ошибка',
    };
    return statusMap[status] || status;
};

const { setChatId, reset, startPoling, stopPoling, status } = useChatAgent(props.chat_id);
const api = createApi();

onMounted(() => {

    content.value = props.version.content
    status.value = props.actualization.status

    reset()

    if (props.chat_id) {
        setChatId(props.chat_id);
    } else {
        (async () => {
            const chatId = await waitChat();
            setChatId(chatId)
            startPoling()
        })()
    }
})

watch(status, () => {
    if (status.value === 'completed') checkUpdates();
});

async function restartGeneration() {
    stopPoling();

    isRestarting.value = true;
    const response = await new RestartGeneration(props.actualization.id).call(api);

    if (!response.success) {
        return;
    }

    do {
        const info = await loadInfo(props.actualization.id);

        if (info.data.status === 'restarting') {
            await async function () {
                return new Promise((r) => setTimeout(r, 400));
            };

            continue;
        }

        setChatId(info.data.chat_id);

        break;
    } while (true);

    isRestarting.value = false;

    reset();
    startPoling();
}

async function loadInfo() {
    return new ActualizationStatusRequest(props.actualization.id).call(api);
}

async function waitChat(): int {
    let chatId = null;
    isWaiting.value = true;

    while ((chatId = (await loadInfo()).data.chat_id) === null) {
        await sleep(500);
    }

    isWaiting.value = false;

    return chatId;
}

async function checkUpdates() {
    loading.value = true;

    const info = (await loadInfo())

    content.value = info.data.content
    actualisationStatus.value = info.data.status

    loading.value = false;
}
</script>
