<template>
    <Button v-if="canActualize" @click="makeActualisation" :disabled="isActualizing" variant="outline">
        <RefreshCw :class="{ 'animate-spin': isActualizing }" class="mr-2 h-4 w-4" />
        Актуализировать
    </Button>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { usePageActualization } from '@/composables/usePageActualization';
import { RefreshCw } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface Props {
    versionId: number | null;
    canActualize: boolean;
}

const props = defineProps<Props>();

async function makeActualisation(): Promise<void> {
    const actualizationId: number | null = await startActualization(props.versionId);

    if (actualizationId === null) {
        return;
    }

    console.log(actualizationId)

    router.visit(route('actualizations.show', actualizationId));
}

// Используем composable для управления состоянием актуализации
const { isActualizing, startActualization } = usePageActualization(props.pageId);
</script>
