<template>
    <div
        v-if="actualizationStatus"
        class="rounded-lg border p-4"
        :class="{
            'border-blue-200 bg-blue-50': statusColor === 'blue',
            'border-yellow-200 bg-yellow-50': statusColor === 'yellow',
            'border-green-200 bg-green-50': statusColor === 'green',
            'border-red-200 bg-red-50': statusColor === 'red',
        }"
    >
        <div class="flex items-start gap-3">
            <RefreshCw :class="{ 'animate-spin': hasActiveActualization }" class="mt-0.5 h-4 w-4 flex-shrink-0" />
            <div class="flex w-full items-center justify-between">
                <div>
                    <strong>Статус актуализации:</strong> {{ statusText }}
                    <br />
                    <span class="text-sm text-muted-foreground">
                        Обновлено: {{ formatDate(actualizationStatus.updated_at) }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canCancelActualization" @click="cancelActualization" variant="outline" size="sm">
                        Отменить
                    </Button>
                    <Button
                        v-if="actualizationStatus.has_chat && actualizationStatus.status === 'completed'"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link :href="route('actualizations.show', actualizationStatus.id)"> Подробности </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';

interface ActualizationStatus {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    created_at: string;
    updated_at: string;
    created_by: string;
    has_chat: boolean;
}

interface Props {
    actualizationStatus: ActualizationStatus | null;
    hasActiveActualization: boolean;
    statusText: string | null;
    statusColor: string;
    canCancelActualization: boolean;
    onCancelActualization: () => void;
}

const props = defineProps<Props>();

const cancelActualization = () => {
    props.onCancelActualization();
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
