<template>
    <Button 
        v-if="canActualize" 
        @click="startActualization" 
        :disabled="isActualizing || hasActiveActualization" 
        variant="outline"
    >
        <RefreshCw :class="{ 'animate-spin': isActualizing || hasActiveActualization }" class="mr-2 h-4 w-4" />
        Актуализировать
    </Button>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { RefreshCw } from 'lucide-vue-next';
import { usePageActualization } from '@/composables/usePageActualization';

interface Props {
    pageId: number;
    canActualize: boolean;
}

const props = defineProps<Props>();

// Используем composable для управления состоянием актуализации
const {
    isActualizing,
    hasActiveActualization,
    startActualization,
} = usePageActualization(props.pageId);
</script>
