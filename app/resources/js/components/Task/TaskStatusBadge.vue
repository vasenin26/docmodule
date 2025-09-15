<template>
    <span :class="statusClass">
        {{ statusText }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    status: string;
}

const props = defineProps<Props>();

const statusConfig = {
    pending: { text: 'Ожидает', class: 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800' },
    generating: { text: 'Генерируется', class: 'px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800' },
    completed: { text: 'Завершена', class: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800' },
    failed: { text: 'Ошибка', class: 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800' },
};

const statusText = computed(() => statusConfig[props.status]?.text || 'Неизвестно');
const statusClass = computed(() => statusConfig[props.status]?.class || 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800');
</script>
