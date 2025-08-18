import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
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
    role: 'user' | 'assistant' | 'system';
    content: string | null;
    timestamp: string;
    tool_call_id?: string;
    tool_calls?: {
        id: string;
        function: {
            name: string;
        };
    }[];
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

export interface PageDiffDescription {
    id: number;
    page_id: number;
    content: string | null;
    created_by: number;
    generation_status: string;
    llm_chat_id?: number | null;
    llm_chat?: LLMChat | null;
    created_at: string;
    updated_at: string;
    edited_at?: string;
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
    currentVersion?: PageVersion;
    hasActiveDraft?: boolean;
    // Для обратной совместимости
    title?: string;
    content?: string;
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

export interface DashboardData {
    token_statistics: TokenStatistics;
}
