import type { FlatPage } from '@/types';

/**
 * Сервис для предзагрузки плоских страниц проекта
 */
export class FlatPagesService {
    private static cache = new Map<number, FlatPage[]>();
    private static loadingPromises = new Map<number, Promise<FlatPage[]>>();

    /**
     * Загружает плоские страницы для проекта
     * @param projectId - ID проекта
     * @param forceRefresh - принудительное обновление кэша
     * @returns Promise с массивом плоских страниц
     */
    static async loadFlatPagesForProject(projectId: number, forceRefresh = false): Promise<FlatPage[]> {
        if (!forceRefresh && this.cache.has(projectId)) {
            return this.cache.get(projectId)!;
        }

        // Если уже идет загрузка для этого проекта, возвращаем существующий Promise
        if (this.loadingPromises.has(projectId)) {
            return this.loadingPromises.get(projectId)!;
        }

        const loadingPromise = this.fetchFlatPagesFromServer(projectId);
        this.loadingPromises.set(projectId, loadingPromise);

        try {
            const flatPages = await loadingPromise;
            this.cache.set(projectId, flatPages);
            return flatPages;
        } finally {
            this.loadingPromises.delete(projectId);
        }
    }

    /**
     * Очищает кэш для конкретного проекта или весь кэш
     * @param projectId - ID проекта (опционально)
     */
    static clearCache(projectId?: number): void {
        if (projectId) {
            this.cache.delete(projectId);
            this.loadingPromises.delete(projectId);
        } else {
            this.cache.clear();
            this.loadingPromises.clear();
        }
    }

    /**
     * Получает плоские страницы с сервера
     * @param projectId - ID проекта
     * @returns Promise с массивом плоских страниц
     */
    private static async fetchFlatPagesFromServer(projectId: number): Promise<FlatPage[]> {
        try {
            const response = await fetch(`/api/projects/${projectId}/flat-pages`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.pages || [];
        } catch (error) {
            console.error('Error fetching flat pages:', error);
            throw error;
        }
    }

    /**
     * Проверяет, есть ли данные в кэше для проекта
     * @param projectId - ID проекта
     * @returns true если данные есть в кэше
     */
    static hasCachedData(projectId: number): boolean {
        return this.cache.has(projectId);
    }

    /**
     * Получает кэшированные данные для проекта
     * @param projectId - ID проекта
     * @returns кэшированные данные или null
     */
    static getCachedData(projectId: number): FlatPage[] | null {
        return this.cache.get(projectId) || null;
    }
}

/**
 * Упрощенная функция для загрузки плоских страниц
 * @param projectId - ID проекта
 * @returns Promise с массивом плоских страниц
 */
export async function fetchFlatPagesFromServer(projectId: number): Promise<FlatPage[]> {
    return FlatPagesService.loadFlatPagesForProject(projectId);
}
