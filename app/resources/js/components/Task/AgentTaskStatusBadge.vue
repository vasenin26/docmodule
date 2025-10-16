<template>
    <span :class="badgeClass">{{ label }}</span>
    
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    status: 'wait' | 'processing' | 'success' | 'failed' | string;
}

const props = defineProps<Props>();

const label = computed(() => {
    switch (props.status) {
        case 'wait':
            return 'Ожидание';
        case 'processing':
            return 'В работе';
        case 'success':
            return 'Успех';
        case 'failed':
            return 'Ошибка';
        default:
            return props.status;
    }
});

const badgeClass = computed(() => {
    switch (props.status) {
        case 'wait':
            return 'inline-flex items-center rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground';
        case 'processing':
            return 'inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800';
        case 'success':
            return 'inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-800';
        case 'failed':
            return 'inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-800';
        default:
            return 'inline-flex items-center rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground';
    }
});
</script>


