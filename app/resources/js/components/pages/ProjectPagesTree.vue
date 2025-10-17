<script setup lang="ts">
import { computed } from 'vue';
import { useProjectStore } from '@/stores/project';
import type { Page, PagesData } from '@/types';
import ProjectPagesSubtree from '@/components/pages/ProjectPagesSubtree.vue';
import { usePage } from '@inertiajs/vue3';

defineOptions({ name: 'ProjectPagesTree' });

const projectStore = useProjectStore();
const inertiaPage = usePage();

const project = computed(() => projectStore.selectedProject);

const pagesFromProps = computed<Page[] | null>(() => {
    const props: any = inertiaPage.props;
    const pd: PagesData | undefined = props?.pages;
    if (pd && Array.isArray(pd.data)) return pd.data as Page[];
    return null;
});

const pages = computed<Page[]>(() => pagesFromProps.value || project.value?.pages || []);
const hasPages = computed(() => Array.isArray(pages.value) && pages.value.length > 0);
</script>

<template>
    <div class="project-pages-tree sidebar-card">
        <div v-if="!project">Проект не выбран</div>
        <div v-else-if="!hasPages">Страницы проекта не загружены</div>
        <ProjectPagesSubtree v-else :nodes="pages" />
    </div>
    
</template>



