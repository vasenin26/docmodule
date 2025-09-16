import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface Breadcrumb {
    title: string;
    href?: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    projectRequired?: boolean; // НОВОЕ ПОЛЕ: требуется ли выбранный проект
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface LLMMessage {
    type: 'user' | 'assistant' | 'system' | 'tool' | 'git-file' | 'page-version';
    message: {
        content?: string | null;
        timestamp: string;
        tool_call_id?: string;
        tool_calls?: {
            id: string;
            function: {
                name: string;
                arguments?: string;    // Аргументы функции (JSON строка)
            };
        }[];
        // Новые поля для tool-сообщений:
        tool_name?: string;        // Название инструмента
        tool_args?: string;        // Аргументы инструмента (JSON строка)
        tool_result?: string;      // Результат выполнения
        tool_success?: boolean;    // Успешность выполнения
        // Альтернативные поля для tool-сообщений (новый формат):
        name?: string;             // Название инструмента (новый формат)
        args?: string;             // Аргументы инструмента (новый формат)
        result?: string;           // Результат выполнения (новый формат)
        success?: boolean;         // Успешность выполнения (новый формат)
        id?: string;               // ID вызова инструмента (новый формат)
        // Поля для git-file сообщений:
        url?: string;              // URL файла в Git репозитории
        // Поля для page-version сообщений:
        versionId?: string;        // ID версии страницы
    };
}

export interface LLMChat {
    id: number;
    messages: LLMMessage[];
    tokens?: number | null;
    prompt_tokens?: number | null;
    completion_tokens?: number | null;
    total_tokens?: number | null;
    created_at: string;
    updated_at: string;
}

export interface VersionDiffTask {
    id: number;
    project_id: number;
    page_version_id: number | null;
    content: string | null;
    created_by: number;
    generation_status: string;
    llm_chat_id?: number | null;
    llm_chat?: LLMChat | null;
    pageVersion?: PageVersion | null;
    creator: User;
    techplane?: Techplane | null;
    created_at: string;
    updated_at: string;
    edited_at?: string;
}

// Для обратной совместимости
export interface PageDiffDescription extends VersionDiffTask {
    page_id: number;
}

export interface Techplane {
    id: number;
    task_id: number;
    content: string | null;
    created_by: number;
    generation_status: string;
    chat_id?: number | null;
    task: VersionDiffTask;
    creator: User;
    created_at: string;
    updated_at: string;
}

export interface Implementation {
    id: number;
    content: string | null;
    techplane_id: number;
    chat_id?: number | null;
    status: string;
    created_by: number;
    created_at: string;
    updated_at: string;
    techplane: Techplane;
    creator: User;
    llm_chat?: LLMChat | null;
}

export interface TaskUpdateFormData {
    content: string;
}

export interface Project {
    id: number;
    title: string;
    owner_id: number;
    owner: User;
    created_at: string;
    updated_at: string;
    pages?: Page[];
    repositories?: Repository[];
}

export interface Repository {
    id: number;
    url: string;
    options: any;
    created_at: string;
    updated_at: string;
}

export interface PageVersion {
    id: number;
    page_id: number;
    title: string;
    content: string;
    previous_version_id?: number | null;
    files: string[];
    created_at: string;
    updated_at: string;
}

export interface Page {
    id: number;
    parent_id?: number | null;
    version_id?: number | null;
    created_at: string;
    created_by: number;
    creator: User;
    project?: Project;
    children: Page[];
    current_version?: PageVersion;
    hasActiveDraft?: boolean;
}

export interface PagesData {
    data: Page[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface TokenStatistics {
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
}

export interface Actualization {
    id: number;
    page_id: number;
    page_version_id: number; // Новое поле
    status: 'pending' | 'processing' | 'completed' | 'failed';
    llm_chat_id?: number;
    created_by: number;
    created_at: string;
    updated_at: string;
    created_by_user?: User;
    page?: Page;
    page_version?: PageVersion; // Новое поле
    llm_chat?: LLMChat;
}
