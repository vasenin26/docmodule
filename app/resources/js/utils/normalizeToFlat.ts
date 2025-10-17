import type { Page, FlatPage, TreeNode } from '@/types';

/**
 * Нормализует входящие данные (nested или flat) в единый flat формат
 * @param pages - массив страниц в любом формате (nested или flat)
 * @returns массив страниц в flat формате
 */
export function normalizeToFlat(pages: Page[] | FlatPage[]): FlatPage[] {
    if (!Array.isArray(pages)) {
        return [];
    }

    return pages.map(page => {
        // Если это уже flat формат
        if ('id_current_version' in page && 'title_current_version' in page) {
            return page as FlatPage;
        }

        // Конвертируем из nested формата
        const nestedPage = page as Page;
        return {
            id: nestedPage.id,
            id_current_version: nestedPage.version_id || 0,
            title_current_version: nestedPage.current_version?.title || `Страница #${nestedPage.id}`,
            parent_id: nestedPage.parent_id,
            children: nestedPage.children?.map(child => child.id) || [],
            current_version: nestedPage.current_version
        };
    });
}

/**
 * Строит дерево TreeNode[] из плоского списка страниц
 * @param flatPages - массив страниц в flat формате
 * @returns массив узлов дерева
 */
export function buildTree(flatPages: FlatPage[]): TreeNode[] {
    if (!Array.isArray(flatPages) || flatPages.length === 0) {
        return [];
    }

    // Создаем карту всех страниц для быстрого доступа
    const pageMap = new Map<number, FlatPage>();
    flatPages.forEach(page => {
        pageMap.set(page.id, page);
    });

    // Создаем узлы дерева
    const nodeMap = new Map<number, TreeNode>();
    const rootNodes: TreeNode[] = [];

    // Сначала создаем все узлы
    flatPages.forEach(flatPage => {
        const node: TreeNode = {
            id: flatPage.id,
            id_current_version: flatPage.id_current_version,
            title_current_version: flatPage.title_current_version,
            parent_id: flatPage.parent_id,
            children: []
        };
        nodeMap.set(flatPage.id, node);
    });

    // Затем строим иерархию
    flatPages.forEach(flatPage => {
        const node = nodeMap.get(flatPage.id);
        if (!node) return;

        if (flatPage.parent_id && nodeMap.has(flatPage.parent_id)) {
            // Добавляем к родителю
            const parent = nodeMap.get(flatPage.parent_id);
            if (parent && parent.children) {
                parent.children.push(node);
            }
        } else {
            // Это корневой узел
            rootNodes.push(node);
        }
    });

    return rootNodes;
}

/**
 * Рекурсивно сортирует дерево по title_current_version
 * @param nodes - массив узлов дерева
 * @returns отсортированный массив узлов
 */
export function sortTreeByTitle(nodes: TreeNode[]): TreeNode[] {
    return nodes
        .map(node => ({
            ...node,
            children: node.children ? sortTreeByTitle(node.children) : []
        }))
        .sort((a, b) => a.title_current_version.localeCompare(b.title_current_version));
}
