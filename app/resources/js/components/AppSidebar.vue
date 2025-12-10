<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Bot, FileText, FolderOpen, LayoutGrid, MessageSquare, Settings, List, Terminal } from 'lucide-vue-next';
import { computed } from 'vue';
import { useProjectStore } from '@/stores/project';
import AppLogo from './AppLogo.vue';

const projectStore = useProjectStore();

// Единый список всех элементов меню с маркерами projectRequired
const allNavItems = computed((): NavItem[] => {
    const currentProject = projectStore.selectedProject;

    return [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
            projectRequired: false // Dashboard всегда доступен и ведет на общий дашборд
        },
        {
            title: 'Проекты',
            href: '/projects',
            icon: FolderOpen,
            projectRequired: false
        },
        {
            title: 'Страницы',
            href: currentProject ? `/projects/${currentProject.id}/pages` : '/pages',
            icon: FileText,
            projectRequired: true
        },
        {
            title: 'Задачи',
            href: `/projects/${currentProject?.id}/tasks`,
            icon: List,
            projectRequired: true
        },
        {
            title: 'Промпты',
            href: `/projects/${currentProject?.id}/prompts`,
            icon: MessageSquare,
            projectRequired: true
        },
        {
            title: 'Агенты',
            href: `/projects/${currentProject?.id}/agents`,
            icon: Bot,
            projectRequired: true
        },
        {
            title: 'Терминалы',
            href: '/terminals',
            icon: Terminal,
            projectRequired: false
        },
        {
            title: 'Настройки',
            href: `/projects/${currentProject?.id}/edit`,
            icon: Settings,
            projectRequired: true
        },
    ];
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="allNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
