<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { useProjectStore } from '@/stores/project';
import type { Page, PagesData, FlatPage, TreeNode } from '@/types';
import ProjectPagesSubtree from '@/components/pages/ProjectPagesSubtree.vue';
import { usePage } from '@inertiajs/vue3';
import { normalizeToFlat, buildTree, sortTreeByTitle } from '@/utils/normalizeToFlat';
import { FlatPagesService } from '@/services/FlatPagesService';

defineOptions({ name: 'ProjectPagesTree' });

const projectStore = useProjectStore();
const inertiaPage = usePage();

const project = computed(() => projectStore.selectedProject);
const treeNodes = ref<TreeNode[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

const pagesFromProps = computed<Page[] | null>(() => {
    const props: any = inertiaPage.props;
    const pd: PagesData | undefined = props?.pages;
    if (pd && Array.isArray(pd.data)) return pd.data as Page[];
    return null;
});

const pages = computed<Page[]>(() => pagesFromProps.value || project.value?.pages || []);

/**
 * Загружает плоские страницы и строит дерево
 */
async function loadFlatPages() {
    if (!project.value?.id) {
        treeNodes.value = [];
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        // Приоритет источников данных: Inertia props > глобальное состояние проекта > серверная загрузка
        let flatPages: FlatPage[] = [];

        if (pagesFromProps.value && pagesFromProps.value.length > 0) {
            // Используем данные из Inertia props
            flatPages = normalizeToFlat(pagesFromProps.value);
        } else if (project.value?.pages && project.value.pages.length > 0) {
            // Используем данные из глобального состояния проекта
            flatPages = normalizeToFlat(project.value.pages);
        } else {
            // Загружаем с сервера через FlatPagesService
            flatPages = await FlatPagesService.loadFlatPagesForProject(project.value.id);
        }

        // Строим дерево и сортируем
        const tree = buildTree(flatPages);
        treeNodes.value = sortTreeByTitle(tree);
    } catch (err) {
        console.error('Error loading flat pages:', err);
        error.value = 'Ошибка загрузки страниц проекта';
        treeNodes.value = [];
    } finally {
        isLoading.value = false;
    }
}

const hasPages = computed(() => treeNodes.value.length > 0);
const hasProject = computed(() => !!project.value);

// Загружаем данные при монтировании и изменении проекта
onMounted(() => {
    loadFlatPages();
});

watch(() => project.value?.id, () => {
    loadFlatPages();
});

watch(() => pagesFromProps.value, () => {
    loadFlatPages();
}, { deep: true });
</script>

<template>
    <div class="project-pages-tree sidebar-card ">
        <div v-if="!hasProject" class="blank-state">
            Проект не выбран
        </div>
        <div v-else-if="isLoading" class="blank-state">
            Загрузка страниц...
        </div>
        <div v-else-if="error" class="blank-state error">
            {{ error }}
        </div>
        <div v-else-if="!hasPages" class="blank-state">
            В проекте пока нет страниц
        </div>
        <ProjectPagesSubtree v-else :nodes="treeNodes" />
    </div>
</template>

<style scoped lang="scss">
.blank-state {
    padding: 16px;
    text-align: center;
    color: #666;
    font-style: italic;

    &.error {
        color: #e74c3c;
    }
}
</style>



