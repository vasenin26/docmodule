<template>
    <Button @click="handleExport" variant="outline" :disabled="isExporting">
        <Icon name="download" class="mr-2 h-4 w-4" v-if="!isExporting" />
        <Icon name="loader-2" class="mr-2 h-4 w-4 animate-spin" v-if="isExporting" />
        {{ isExporting ? 'Экспорт...' : 'Экспортировать' }}
    </Button>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';

const isExporting = ref(false);

/**
 * Обработчик экспорта задачи
 * На данном этапе это заглушка для будущей реализации
 */
const handleExport = () => {
    isExporting.value = true;

    // Имитация процесса экспорта
    setTimeout(() => {
        isExporting.value = false;

        // TODO: Реализовать фактический экспорт задачи
        // Это может быть экспорт в различные форматы:
        // - PDF документ
        // - Word документ
        // - Markdown файл
        // - Интеграция с внешними системами трекинга задач

        alert('Функция экспорта будет реализована в будущих версиях');
    }, 1000);
};
</script>
