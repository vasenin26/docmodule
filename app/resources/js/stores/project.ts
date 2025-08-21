import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Project } from '@/types';

export const useProjectStore = defineStore('project', () => {
    // Кеш проектов для оптимизации
    const projectsCache = ref<Map<number, Project>>(new Map());
    
    // Глобально сохраненный проект (не сбрасывается при навигации)
    const currentProject = ref<Project | null>(null);
    
    // Получаем текущую страницу от Inertia
    const page = usePage();
    
    // Проект определяется из URL, но сохраняется глобально
    const selectedProject = computed(() => {
        const path = page.url;
        console.log('🔍 Store: checking Inertia path:', path);
        const match = path.match(/^\/projects\/(\d+)/);
        
        if (match) {
            const projectId = parseInt(match[1]);
            console.log('🔍 Store: found project ID in URL:', projectId);
            
            const cached = projectsCache.value.get(projectId);
            if (cached) {
                console.log('🔍 Store: updating current project from cache:', cached);
                currentProject.value = cached;
                return cached;
            } else {
                console.log('🔍 Store: project not in cache, will load...');
                return currentProject.value; // Возвращаем текущий проект пока загружается новый
            }
        } else {
            // URL не содержит проект, но возвращаем сохраненный проект
            console.log('🔍 Store: no project in URL, returning current project:', currentProject.value);
            return currentProject.value;
        }
    });
    
    // Ключевое computed свойство для условного отображения
    const hasSelectedProject = computed(() => selectedProject.value !== null);
    
    // Получение проекта по ID с кешированием
    const getProjectById = async (projectId: number): Promise<Project | null> => {
        console.log('🔍 Store: getProjectById called with ID:', projectId);
        
        // Сначала проверяем кеш
        if (projectsCache.value.has(projectId)) {
            const cached = projectsCache.value.get(projectId)!;
            console.log('🔍 Store: returning cached project');
            currentProject.value = cached; // Обновляем текущий проект
            return cached;
        }
        
        console.log('🔍 Store: loading project from API...');
        
        // Загружаем из API
        try {
            const response = await fetch(`/api/projects/${projectId}`);
            console.log('🔍 Store: API response status:', response.status);
            
            if (response.ok) {
                const project = await response.json();
                console.log('🔍 Store: loaded project:', project);
                projectsCache.value.set(projectId, project);
                currentProject.value = project; // Обновляем текущий проект
                return project;
            } else {
                console.error('🔍 Store: API error:', response.status, response.statusText);
            }
        } catch (e) {
            console.error('🔍 Store: fetch error:', e);
        }
        return null;
    };
    
    // Очистка кеша
    const clearCache = () => {
        projectsCache.value.clear();
    };

    // Сброс текущего проекта
    const clearCurrentProject = () => {
        currentProject.value = null;
    };
    
    // Предзагрузка проекта для текущего URL
    const preloadCurrentProject = async () => {
        const path = window.location.pathname;
        const match = path.match(/^\/projects\/(\d+)/);
        if (match) {
            const projectId = parseInt(match[1]);
            if (!projectsCache.value.has(projectId)) {
                await getProjectById(projectId);
            }
        }
    };
    
    // Отслеживаем изменения URL через Inertia и загружаем проект
    watch(() => page.url, async (newPath) => {
        console.log('🔍 Store: Inertia path changed to:', newPath);
        const match = newPath.match(/^\/projects\/(\d+)/);
        if (match) {
            const projectId = parseInt(match[1]);
            console.log('🔍 Store: path change detected project ID:', projectId);
            if (!projectsCache.value.has(projectId)) {
                console.log('🔍 Store: loading project from path change...');
                await getProjectById(projectId);
            }
        }
    }, { immediate: true });
    
    return {
        selectedProject,
        hasSelectedProject,
        getProjectById,
        clearCache,
        clearCurrentProject,
        preloadCurrentProject
    };
});
