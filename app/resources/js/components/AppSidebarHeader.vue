<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useProjectStore } from '@/stores/project';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Breadcrumb } from '@/types';
import { X } from 'lucide-vue-next';

const projectStore = useProjectStore();
const page = usePage();

// Генерация breadcrumbs на основе текущего пути
const breadcrumbs = computed((): Breadcrumb[] => {
    const path = page.url;
    const segments = path.split('/').filter(Boolean);
    
    if (segments[0] === 'projects' && segments.length > 1) {
        const projectId = segments[1];
        const project = projectStore.selectedProject;
        
        if (project) {
            const crumbs: Breadcrumb[] = [
                { title: 'Проекты', href: '/projects' },
                { title: project.title, href: `/projects/${project.id}` },
            ];
            
            // Добавляем дополнительные сегменты пути
            for (let i = 2; i < segments.length; i++) {
                const segment = segments[i];
                const segmentTitle = segment.charAt(0).toUpperCase() + segment.slice(1);
                const href = `/projects/${projectId}/${segments.slice(2, i + 1).join('/')}`;
                crumbs.push({ title: segmentTitle, href });
            }
            
            return crumbs;
        }
    }
    
    return [
        { title: 'Главная', href: '/' }
    ];
});

const clearProject = () => {
    // Перенаправляем на список проектов
    window.location.href = '/projects';
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center justify-between w-full">
            <!-- Левая часть: Trigger -->
            <div class="flex items-center gap-3">
                <SidebarTrigger class="-ml-1" />
                
                <!-- Кнопка очистки проекта (только при выбранном проекте) -->
                <div v-if="projectStore.hasSelectedProject" class="flex items-center gap-1">
                    <Button 
                        variant="ghost" 
                        size="sm" 
                        @click="clearProject"
                        title="Очистить выбор проекта"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            
            <!-- Breadcrumbs справа -->
            <div v-if="breadcrumbs && breadcrumbs.length > 1">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </header>
</template>
