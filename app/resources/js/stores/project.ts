import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Project } from '@/types';
import { createApi } from '@/service/api/Api';
import { ProjectGetByIdRequest } from '@/service/api/request/Project/ProjectGetByIdRequest';
import { ProjectListRequest } from '@/service/api/request/Project/ProjectListRequest';

export const useProjectStore = defineStore('project', () => {
    // Кеш проектов для оптимизации
    const projectsCache = ref<Map<number, Project>>(new Map());

    // Глобально сохраненный проект (не сбрасывается при навигации)
    const currentProject = ref<Project | null>(null);

    // Полный список проектов
    const projects = ref<Project[]>([]);
    const projectsLoading = ref<boolean>(false);

    // Получаем текущую страницу от Inertia
    const page = usePage();

    // Проект определяется из URL или из пропсов страницы
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
            // Проверяем, есть ли project_id в пропсах страницы
            const pageProps = page.props as any;
            if (pageProps?.project_id) {
                const projectId = pageProps.project_id;
                const cached = projectsCache.value.get(projectId);
                if (cached) {
                    currentProject.value = cached;
                    return cached;
                } else {
                    // Загружаем проект асинхронно
                    getProjectById(projectId);
                    return currentProject.value; // Возвращаем текущий проект пока загружается новый
                }
            }
            // URL не содержит проект и нет project_id в пропсах, возвращаем сохраненный проект
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

    // Загрузка списка проектов
    const loadProjects = async (): Promise<Project[]> => {
        if (projectsLoading.value) return projects.value;
        projectsLoading.value = true;
        try {
            const api = createApi();
            const req = new ProjectListRequest();
            const list = await req.call(api);
            projects.value = list;
            // Заполняем кеш для быстрого доступа по id
            list.forEach((p) => {
                projectsCache.value.set(p.id, p);
            });
            return list;
        } catch (e) {
            console.error('🔍 Store: load projects error:', e);
            return [];
        } finally {
            projectsLoading.value = false;
        }
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

    // Отслеживаем изменения URL и пропсов через Inertia и загружаем проект
    watch(() => [page.url, page.props], async ([newPath, newProps]) => {
        const path = newPath as string;
        const match = path.match(/^\/projects\/(\d+)/);
        if (match) {
            const projectId = parseInt(match[1]);
            if (!projectsCache.value.has(projectId)) {
                await getProjectById(projectId);
            }
        } else {
            // Проверяем project_id в пропсах
            const props = newProps as any;
            if (props?.project_id) {
                const projectId = props.project_id;
                if (!projectsCache.value.has(projectId)) {
                    await getProjectById(projectId);
                }
            }
        }
    }, { immediate: true });

    return {
        selectedProject,
        hasSelectedProject,
        getProjectById,
        projects,
        projectsLoading,
        loadProjects,
        clearCache,
        clearCurrentProject,
        preloadCurrentProject
    };
});
