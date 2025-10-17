<script setup lang="ts">
import { useProjectStore } from '@/stores/project';
import { computed } from 'vue';
import { LayoutGrid, Folder } from 'lucide-vue-next';

const projectStore = useProjectStore();

// Динамическое название и иконка
const displayInfo = computed(() => {
    if (projectStore.hasSelectedProject && projectStore.selectedProject) {
        return {
            title: projectStore.selectedProject.title,
            subtitle: `Проект #${projectStore.selectedProject.id}`,
            icon: Folder,
            isProject: true
        };
    } else {
        return {
            title: 'DocModule',
            subtitle: 'Система управления документацией',
            icon: LayoutGrid,
            isProject: false
        };
    }
});
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <component
            :is="displayInfo.icon"
            class="size-5 fill-current text-white dark:text-black"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ displayInfo.title }}</span>
        <span class="truncate text-xs text-sidebar-primary-foreground/70">{{ displayInfo.subtitle }}</span>
    </div>
</template>
