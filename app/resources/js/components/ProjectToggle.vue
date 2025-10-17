<script setup lang="ts">
import { useProjectStore } from '@/stores/project';
import { Folder, LayoutGrid } from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';

const projectStore = useProjectStore();

const showList = ref<boolean>(true);

// Динамическое название и иконка
const displayInfo = computed(() => {
    if (projectStore.hasSelectedProject && projectStore.selectedProject) {
        return {
            title: projectStore.selectedProject.title,
            subtitle: `${projectStore.selectedProject.id}`,
            icon: Folder,
            isProject: true,
        };
    } else {
        return {
            title: 'DocModule',
            subtitle: 'Система управления документацией',
            icon: LayoutGrid,
            isProject: false,
        };
    }
});

function showProjectList() {
    showList.value = true;
}

onMounted(() => {
    // Загружаем список проектов при инициализации
    projectStore.loadProjects();
});
</script>

<template>
    <div class="project-toggle">
        <div class="button">
            <div class="button-icon" @click="showProjectList">
                <component :is="displayInfo.icon" class="size-5 fill-current text-white dark:text-black" />
            </div>
            <div class="button-title">
                {{ displayInfo.title }}
            </div>
            <div class="button-tip">
                <span>
                    <span v-if="displayInfo.isProject"> ID:{{ displayInfo.subtitle }} </span>
                    <span v-else> Выберите проект -> </span>
                </span>
            </div>
        </div>
        <div class="list bg-card shadow-sm" v-if="showList">
            <div v-if="projectStore.projectsLoading" class="p-2 text-sm text-muted-foreground">
                Загрузка...
            </div>
            <div v-else>
                <div
                    class="item cursor-pointer p-2 hover:bg-accent"
                    v-for="project in projectStore.projects"
                    :key="project.id"
                >
                    {{ project.title }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.project-toggle {
    position: relative;
}

.list {
    position: absolute;
    padding: 10px;
    left: 0;
    top: 100%;
    right: 0;
}

.button {
    width: 200px;
    display: grid;
    padding: 0;
    grid-template-columns: auto 1fr;
    grid-template-rows: auto auto;
    grid-template-areas:
        'icon title'
        'icon tip';
    column-gap: 0;
    row-gap: 0;
    align-items: center;
}

.button-icon {
    grid-area: icon;
    display: flex;
    align-items: center;
    justify-content: center;
}

.button-title {
    grid-area: title;
    font-weight: 600;
}

.button-tip {
    grid-area: tip;
    font-size: 0.85rem;

    span {
        cursor: pointer;
    }
}
</style>
