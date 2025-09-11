<template>
    <div class="markdown-content" v-html="renderedContent"></div>
</template>

<script setup lang="ts">
import MarkdownIt from 'markdown-it';
import { computed } from 'vue';

interface Props {
    content: string | null;
}

const props = defineProps<Props>();

const md = new MarkdownIt('commonmark');

const renderedContent = computed(() => {
    if (!props.content) return '';
    return md.render(props.content);
});
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
    list-style-position: outside;
    margin-bottom: 0.75rem;
    padding-left: 1.5rem;
}

.markdown-content :deep(ol) {
    list-style-type: decimal;
    list-style-position: outside;
    margin-bottom: 0.75rem;
    padding-left: 1.5rem;
}

.markdown-content :deep(li) {
    margin-bottom: 0.25rem;
    padding-left: 0.25rem;
}

/* Вложенные списки - общие стили */
.markdown-content :deep(ul ul),
.markdown-content :deep(ol ol),
.markdown-content :deep(ul ol),
.markdown-content :deep(ol ul) {
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
}

/* Маркеры для вложенных неупорядоченных списков (ul) */
.markdown-content :deep(ul) {
    list-style-type: disc;
}

.markdown-content :deep(ul ul) {
    list-style-type: circle;
}

.markdown-content :deep(ul ul ul) {
    list-style-type: square;
}

.markdown-content :deep(ul ul ul ul) {
    list-style-type: disc;
}

.markdown-content :deep(ul ul ul ul ul) {
    list-style-type: circle;
}

.markdown-content :deep(ul ul ul ul ul ul) {
    list-style-type: square;
}

.markdown-content :deep(ul ul ul ul ul ul ul) {
    list-style-type: disc;
}

.markdown-content :deep(ul ul ul ul ul ul ul ul) {
    list-style-type: circle;
}

.markdown-content :deep(ul ul ul ul ul ul ul ul ul) {
    list-style-type: square;
}

.markdown-content :deep(ul ul ul ul ul ul ul ul ul ul) {
    list-style-type: disc;
}

/* Маркеры для вложенных упорядоченных списков (ol) */
.markdown-content :deep(ol) {
    list-style-type: decimal;
}

.markdown-content :deep(ol ol) {
    list-style-type: lower-alpha;
}

.markdown-content :deep(ol ol ol) {
    list-style-type: lower-roman;
}

.markdown-content :deep(ol ol ol ol) {
    list-style-type: decimal;
}

.markdown-content :deep(ol ol ol ol ol) {
    list-style-type: lower-alpha;
}

.markdown-content :deep(ol ol ol ol ol ol) {
    list-style-type: lower-roman;
}

.markdown-content :deep(ol ol ol ol ol ol ol) {
    list-style-type: decimal;
}

.markdown-content :deep(ol ol ol ol ol ol ol ol) {
    list-style-type: lower-alpha;
}

.markdown-content :deep(ol ol ol ol ol ol ol ol ol) {
    list-style-type: lower-roman;
}

.markdown-content :deep(ol ol ol ol ol ol ol ol ol ol) {
    list-style-type: decimal;
}

/* Смешанные списки (ul внутри ol и наоборот) */
.markdown-content :deep(ol ul) {
    list-style-type: disc;
}

.markdown-content :deep(ol ul ul) {
    list-style-type: circle;
}

.markdown-content :deep(ol ul ul ul) {
    list-style-type: square;
}

.markdown-content :deep(ul ol) {
    list-style-type: decimal;
}

.markdown-content :deep(ul ol ol) {
    list-style-type: lower-alpha;
}

.markdown-content :deep(ul ol ol ol) {
    list-style-type: lower-roman;
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
