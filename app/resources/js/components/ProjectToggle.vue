<script setup lang="ts">
import { useProjectStore } from '@/stores/project';
import { Folder, LayoutGrid } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Link } from '@inertiajs/vue3';

const projectStore = useProjectStore();

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

onMounted(() => {
    // Загружаем список проектов при инициализации
    projectStore.loadProjects();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <div class="project-toggle">
                <div class="button">
                    <div class="button-icon">
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
            </div>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-[200px] p-0">
            <div class="bg-card shadow-sm">
                <div v-if="projectStore.projectsLoading" class="p-2 text-sm text-muted-foreground">
                    Загрузка...
                </div>
                <div v-else>
                    <Link
                        class="item cursor-pointer p-2 hover:bg-accent block"
                        v-for="project in projectStore.projects"
                        :key="project.id"
                        :href="`/projects/${project.id}`"
                    >
                        {{ project.title }}
                    </Link>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped lang="scss">
.project-toggle {
    position: relative;
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
