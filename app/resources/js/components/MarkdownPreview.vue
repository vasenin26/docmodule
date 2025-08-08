<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <Button
        type="button"
        variant="outline"
        size="sm"
        @click="showPreview = !showPreview"
      >
        {{ showPreview ? 'Скрыть предварительный просмотр' : 'Показать предварительный просмотр' }}
      </Button>
    </div>
    
    <div v-if="showPreview" class="border rounded-lg p-4 bg-gray-50">
      <h3 class="text-sm font-medium mb-2 text-gray-700">Предварительный просмотр:</h3>
      <div v-if="content" class="markdown-preview">
        <MarkdownRenderer :content="content" />
      </div>
      <div v-else class="text-gray-500 text-sm">
        Введите содержимое для предварительного просмотра
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from '@/components/ui/button/Button.vue'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'

interface Props {
  content: string
}

const props = defineProps<Props>()

const showPreview = ref(false)
</script>

<style scoped>
.markdown-preview {
  max-height: 20rem;
  overflow-y: auto;
}
</style>
