<template>
    <div class="overflow-hidden rounded-lg border font-mono text-sm">
        <div class="border-b bg-gray-100 px-4 py-2">
            <span class="text-gray-600">Изменения в содержимом</span>
        </div>
        <div class="max-h-100 overflow-y-auto">
            <div v-for="(line, index) in diffLines" :key="index" :class="getLineClass(line)" class="px-4 py-1 whitespace-pre-wrap">
                {{ line }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { generateGitStyleDiff, getDiffLineType } from '@/lib/diffUtils';
import { computed } from 'vue';

interface Props {
    oldContent: string | null;
    newContent: string | null;
}

const props = defineProps<Props>();

const diffLines = computed(() => {
    const oldContent = props.oldContent || '';
    const newContent = props.newContent || '';
    const diff = generateGitStyleDiff(oldContent, newContent);
    return diff.split('\n');
});

function getLineClass(line: string): string {
    const type = getDiffLineType(line);

    switch (type) {
        case 'header':
            return 'bg-blue-50 text-blue-800 font-semibold';
        case 'added':
            return 'bg-green-50 text-green-800';
        case 'removed':
            return 'bg-red-50 text-red-800';
        case 'context':
        default:
            return 'bg-gray-50 text-gray-700';
    }
}
</script>
