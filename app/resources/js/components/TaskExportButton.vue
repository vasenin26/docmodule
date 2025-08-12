<template>
  <Button 
    @click="handleExport" 
    variant="outline"
    :disabled="isExporting"
  >
    <Icon name="download" class="w-4 h-4 mr-2" v-if="!isExporting" />
    <Icon name="loader-2" class="w-4 h-4 mr-2 animate-spin" v-if="isExporting" />
    {{ isExporting ? 'Экспорт...' : 'Экспортировать' }}
  </Button>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import Icon from '@/components/Icon.vue'

const isExporting = ref(false)

/**
 * Обработчик экспорта задачи
 * На данном этапе это заглушка для будущей реализации
 */
const handleExport = () => {
  isExporting.value = true
  
  // Имитация процесса экспорта
  setTimeout(() => {
    isExporting.value = false
    
    // TODO: Реализовать фактический экспорт задачи
    // Это может быть экспорт в различные форматы:
    // - PDF документ
    // - Word документ
    // - Markdown файл
    // - Интеграция с внешними системами трекинга задач
    
    alert('Функция экспорта будет реализована в будущих версиях')
  }, 1000)
}
</script>
