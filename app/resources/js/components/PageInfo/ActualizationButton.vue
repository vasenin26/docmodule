<template>
    <Button @click="makeActualisation" :disabled="isActualizing" variant="outline">
        <span v-if="has" class="flex items-center gap-2">
            Актуализация
            <ArrowBigRightDash v-if="has" />
        </span>
        <span v-else class="flex items-center">
            <RefreshCw :class="{ 'animate-spin': isActualizing }" class="mr-2 h-4 w-4" />
            Актуализировать
        </span>
    </Button>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { usePageActualization } from '@/composables/usePageActualization';
import { RefreshCw, ArrowBigRightDash } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface Props {
    versionId: number | null;
    has?: boolean;
}

const props = defineProps<Props>();

async function makeActualisation(): Promise<void> {
    const actualizationId: number | null = await startActualization(props.versionId);

    if (actualizationId === null) {
        return;
    }

    router.visit(route('actualizations.show', actualizationId));
}

// Используем composable для управления состоянием актуализации
const { isActualizing, startActualization } = usePageActualization(props.pageId);
</script>
