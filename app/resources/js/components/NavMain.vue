<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { useProjectStore } from '@/stores/project';
import { computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const projectStore = useProjectStore();

// Фильтрация элементов меню: показываем только доступные
const visibleNavItems = computed(() => {
    const filtered = props.items.filter(item => {
        if (!item.projectRequired) {
            return true;
        }

        return projectStore.hasSelectedProject;
    });

    return filtered;
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in visibleNavItems"
                :key="item.title"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="item.href === page.url"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
