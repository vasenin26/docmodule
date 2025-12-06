<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { useProjectStore } from '@/stores/project';
import type { FlatPage, TreeNode } from '@/types';
import ProjectPagesSubtree from '@/components/pages/ProjectPagesSubtree.vue';
import { buildTree, sortTreeByTitle } from '@/utils/normalizeToFlat';
import { FlatPagesService } from '@/services/FlatPagesService';

defineOptions({ name: 'ProjectPagesTree' });

const projectStore = useProjectStore();

const project = computed(() => projectStore.selectedProject);
const treeNodes = ref<TreeNode[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

/**
 * Загружает плоские страницы и строит дерево.
 * Источник данных: FlatPagesService.loadFlatPagesForProject(projectId).
 */
async function loadFlatPages() {
    if (!project.value?.id) {
        treeNodes.value = [];
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        // Всегда загружаем данные через сервис (он сам использует кэш при необходимости)
        const flatPages: FlatPage[] = await FlatPagesService.loadFlatPagesForProject(project.value.id);

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

// Загружаем данные при монтировании и при смене проекта
onMounted(() => {
    loadFlatPages();
});

watch(() => project.value?.id, () => {
    loadFlatPages();
});
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
