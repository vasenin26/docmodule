<template>
  <div class="markdown-content" v-html="renderedContent"></div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { marked } from 'marked'

interface Props {
  content: string | null
}

const props = defineProps<Props>()

const renderedContent = computed(() => {
  if (!props.content) return ''
  return marked(props.content)
})
</script>

<style scoped>
.markdown-content {
  max-width: none;
}

.markdown-content :deep(h1) {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.markdown-content :deep(h2) {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  margin-top: 1.5rem;
}

.markdown-content :deep(h3) {
  font-size: 1.125rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  margin-top: 1rem;
}

.markdown-content :deep(p) {
  margin-bottom: 0.75rem;
}

.markdown-content :deep(ul) {
  list-style-type: disc;
  list-style-position: inside;
  margin-bottom: 0.75rem;
}

.markdown-content :deep(ol) {
  list-style-type: decimal;
  list-style-position: inside;
  margin-bottom: 0.75rem;
}

.markdown-content :deep(li) {
  margin-bottom: 0.25rem;
}

.markdown-content :deep(blockquote) {
  border-left: 4px solid #d1d5db;
  padding-left: 1rem;
  font-style: italic;
  background-color: #f9fafb;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.markdown-content :deep(code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.markdown-content :deep(pre) {
  background-color: #f3f4f6;
  padding: 0.75rem;
  border-radius: 0.25rem;
  overflow-x: auto;
  margin-bottom: 0.75rem;
}

.markdown-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
}

.markdown-content :deep(a) {
  color: #2563eb;
  text-decoration: underline;
}

.markdown-content :deep(a:hover) {
  color: #1d4ed8;
}

.markdown-content :deep(strong) {
  font-weight: bold;
}

.markdown-content :deep(em) {
  font-style: italic;
}
</style>
