<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useProjectStore } from '@/stores/project';
import { type NavItem } from '@/types';
import { FileText, LayoutGrid, List, MessageSquare, Settings } from 'lucide-vue-next';

const page = usePage();
const projectStore = useProjectStore();

// Единый список элементов меню проекта, как в AppSidebar, но для горизонтального меню
const allNavItems = computed((): NavItem[] => {
    const currentProject = projectStore.selectedProject;

    return [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            projectRequired: false,
        },
        {
            title: 'Страницы',
            href: currentProject ? `/projects/${currentProject.id}/pages` : '/pages',
            icon: FileText,
            projectRequired: true,
        },
        {
            title: 'Задачи',
            href: currentProject ? `/projects/${currentProject.id}/tasks` : '/tasks',
            icon: List,
            projectRequired: true,
        },
        {
            title: 'Промпты',
            href: currentProject ? `/projects/${currentProject.id}/prompts` : '/prompts',
            icon: MessageSquare,
            projectRequired: true,
        },
        {
            title: null,
            href: currentProject ? `/projects/${currentProject.id}/edit` : '/projects',
            icon: Settings,
            projectRequired: true,
        },
    ];
});

// Фильтрация: показываем пункты, доступные для текущего состояния проекта
const visibleNavItems = computed(() => allNavItems.value.filter(item => !item.projectRequired || projectStore.hasSelectedProject));

const isActive = (href: string) => page.url === href;
</script>

<template>
    <nav class="project-menu">
        <ul class="menu-list">
            <li v-for="item in visibleNavItems" :key="item.title">
                <Link :href="item.href" class="menu-link" :class="{ active: isActive(item.href) }">
                    <component :is="item.icon" class="icon" />
                    <span class="label" v-if="item.title">{{ item.title }}</span>
                </Link>
            </li>
        </ul>
    </nav>
    <slot />

</template>

<style scoped lang="scss">
.project-menu {
    width: 100%;
}

.menu-list {
    display: flex;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 6px;
    color: var(--sidebar-foreground, inherit);
    text-decoration: none;
    min-height: 34px;
}

.menu-link:hover {
    background: var(--sidebar-accent, rgba(0,0,0,0.05));
}

.menu-link.active {
    background: var(--sidebar-accent, rgba(0,0,0,0.08));
}

.icon {
    width: 16px;
    height: 16px;
}

.label {
    font-size: 0.9rem;
}
</style>
