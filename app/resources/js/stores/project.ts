import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Project } from '@/types';
import { createApi } from '@/service/api/Api';
import { ProjectGetByIdRequest } from '@/service/api/request/Project/ProjectGetByIdRequest';

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
        const match = path.match(/^\/projects\/(\d+)/);

        if (match) {
            const projectId = parseInt(match[1]);

            const cached = projectsCache.value.get(projectId);
            if (cached) {
                currentProject.value = cached;
                return cached;
            } else {
                return currentProject.value; // Возвращаем текущий проект пока загружается новый
            }
        } else {
            // URL не содержит проект, но возвращаем сохраненный проект
            return currentProject.value;
        }
    });

    // Ключевое computed свойство для условного отображения
    const hasSelectedProject = computed(() => selectedProject.value !== null);

    // Получение проекта по ID с кешированием
    const getProjectById = async (projectId: number): Promise<Project | null> => {

        // Сначала проверяем кеш
        if (projectsCache.value.has(projectId)) {
            const cached = projectsCache.value.get(projectId)!;
            currentProject.value = cached; // Обновляем текущий проект
            return cached;
        }

        // Загружаем из API
        try {
            const api = createApi();
            const req = new ProjectGetByIdRequest(projectId);
            const project = await req.call(api);
            projectsCache.value.set(projectId, project);
            currentProject.value = project; // Обновляем текущий проект
            return project;
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
        const match = newPath.match(/^\/projects\/(\d+)/);
        if (match) {
            const projectId = parseInt(match[1]);
            if (!projectsCache.value.has(projectId)) {
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
