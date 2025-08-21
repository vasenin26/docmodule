<template>
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <Button type="button" variant="outline" size="sm" @click="showPreview = !showPreview">
                {{ showPreview ? 'Скрыть предварительный просмотр' : 'Показать предварительный просмотр' }}
            </Button>
        </div>

        <div v-if="showPreview" class="rounded-lg border bg-gray-50 p-4">
            <h3 class="mb-2 text-sm font-medium text-gray-700">Предварительный просмотр:</h3>
            <div v-if="content" class="markdown-preview">
                <MarkdownRenderer :content="content" />
            </div>
            <div v-else class="text-sm text-gray-500">Введите содержимое для предварительного просмотра</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import Button from '@/components/ui/button/Button.vue';
import { ref } from 'vue';

interface Props {
    content: string;
}

defineProps<Props>();

const showPreview = ref(true);
</script>

<style scoped>
.markdown-preview {
    max-height: 20rem;
    overflow-y: auto;
}
</style>
