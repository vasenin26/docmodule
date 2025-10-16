<template>
    <a :href="downloadUrl" class="flex items-center" target="_blank" rel="noopener noreferrer">
        <Icon name="download" class="mr-2 h-4 w-4" />
        Скачать план в формате Markdown
    </a>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

const props = defineProps<{ techplaneId: number }>();

const downloadUrl = computed(() => `/techplanes/${props.techplaneId}/download-markdown`);
</script>


