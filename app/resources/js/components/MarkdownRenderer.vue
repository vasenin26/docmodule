<template>
    <div class="ck-content" v-html="renderedContent"></div>
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
</style>
