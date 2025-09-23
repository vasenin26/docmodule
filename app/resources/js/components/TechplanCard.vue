<template>
    <Card>
        <CardHeader>
            <CardTitle>Технический план</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="techplane">
                <!-- Краткая информация без полного текста техплана -->
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <span class="text-sm font-medium">Статус</span>
                        <p class="mt-1 text-sm text-muted-foreground">{{ techplane.generation_status }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium">Дата создания</span>
                        <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(techplane.created_at) }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-sm font-medium">Автор</span>
                        <p class="mt-1 text-sm text-muted-foreground">{{ techplane.creator?.name }}</p>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="flex gap-3">
                    <Button v-if="techplane.content || techplane.generation_status === 'completed'" as-child variant="default">
                        <Link :href="route('techplanes.show', techplane.id)"> Открыть техплан </Link>
                    </Button>
                </div>
            </div>
            <div v-else>
                <!-- Кнопка создания техплана -->
                <Button as-child variant="default">
                    <Link :href="route('tasks.create-techplane', taskId)" method="post" as="button"> Создать технический план </Link>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface TechplaneData {
    id: number;
    content: string | null;
    generation_status: string;
    created_at: string;
    creator: {
        id: number;
        name: string;
    };
}

interface Props {
    techplane?: TechplaneData | null;
    taskId: number;
}

const props = defineProps<Props>();

const isRestartingGeneration = ref<boolean>(false);

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

</script>
